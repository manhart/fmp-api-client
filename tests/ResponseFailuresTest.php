<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Tests;

use Manhart\Fmp\Client\AbstractApiClient;
use Manhart\Fmp\Client\FmpApiClient;
use Manhart\Fmp\Exception\CurlException;
use Manhart\Fmp\Exception\HttpException;
use Manhart\Fmp\Exception\ResponseException;
use Manhart\Fmp\HttpResponse;
use Manhart\Fmp\Response\AbstractResponse;
use Manhart\Fmp\Response\FileResponse;
use Manhart\Fmp\Response\Legacy\Profile as LegacyProfile;
use Manhart\Fmp\Response\Stable\FinancialReportsXlsx;
use Manhart\Fmp\Response\Stable\Profile;
use Manhart\Fmp\Tests\Support\FixtureClient;
use Manhart\Fmp\Tests\Support\LegacyFixtureClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Throwable;

final class ResponseFailuresTest extends TestCase
{
    public static function httpErrors(): iterable
    {
        foreach (['stable', 'legacy', 'file'] as $kind) {
            foreach ([400, 402, 429, 500, 503] as $status) {
                yield "$kind HTTP $status" => [$kind, $status];
            }
        }
    }

    #[DataProvider('httpErrors')]
    public function testHttpErrorsRetainProviderReasonAndByteAccounting(string $kind, int $status): void
    {
        $client = $kind === 'legacy' ? new LegacyFixtureClient() : new FixtureClient();
        $client->status = $status;
        $client->payload = '{"Error Message":"Restricted route for synthetic-api-key"}';

        try {
            self::createResponse($client, $kind);
            $this->fail('An HTTP error must not return a response.');
        } catch (HttpException $exception) {
            $this->assertInstanceOf(ResponseException::class, $exception);
            $this->assertSame($status, $exception->status);
            $this->assertSame($status, $exception->getCode());
            $this->assertSame('{"Error Message":"Restricted route for [REDACTED]"}', $exception->bodyExcerpt);
            $this->assertSame(strlen($client->payload), $exception->receivedBytes);
            $this->assertStringContainsString($exception->bodyExcerpt, $exception->getMessage());
            $this->assertStringContainsString($client->getLastEndpointURL(), $exception->getMessage());
            $this->assertStringNotContainsString('synthetic-api-key', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
            $this->assertCount(1, $client->calls);
        }
    }

    public static function errorBodies(): iterable
    {
        yield 'empty' => ['', ''];
        yield 'unstructured' => ['Please enter a search value.', 'Please enter a search value.'];
        yield 'large' => [str_repeat('x', 8192), str_repeat('x', 2048)];
        yield 'key crossing excerpt boundary' => [str_repeat('x', 2038).'synthetic-api-key'.str_repeat('x', 100), str_repeat('x', 2038).'[REDACTED]'];
    }

    #[DataProvider('errorBodies')]
    public function testHttpExcerptsAreRedactedBeforeTruncation(string $body, string $expected): void
    {
        $client = new FixtureClient();
        $client->status = 400;
        $client->payload = $body;

        try {
            $client->executeCurl('stable/profile', ['symbol' => 'DEMO']);
            $this->fail('Expected an HTTP error.');
        } catch (HttpException $exception) {
            $this->assertSame($expected, $exception->bodyExcerpt);
            $this->assertLessThanOrEqual(2048, strlen($exception->bodyExcerpt));
            $this->assertSame(strlen($body), $exception->receivedBytes);
            $this->assertStringNotContainsString('synthetic-api-key', $exception->getMessage());
            $this->assertStringContainsString('FMP HTTP 400 for ', $exception->getMessage());
            if ($body === '') {
                $this->assertSame('FMP HTTP 400 for '.$client->getLastEndpointURL(), $exception->getMessage());
            }
        }
    }

    public function testLiteralAndEncodedKeysAreRedactedInHttpDiagnostics(): void
    {
        $key = 'synthetic key+/';
        $body = 'Access denied for '.$key.' or '.rawurlencode($key);
        $client = new class($key, $body) extends FmpApiClient {
            public function __construct(string $key, private readonly string $body)
            {
                parent::__construct($key);
            }

            public function request(string $endpoint, array $params = []): HttpResponse
            {
                $this->buildEndpointURL($endpoint, $params);
                return new HttpResponse($this->body, 402, 'text/plain', strlen($this->body));
            }
        };

        try {
            $client->executeCurl('stable/profile', ['symbol' => $key]);
            $this->fail('Expected an HTTP error.');
        } catch (HttpException $exception) {
            $this->assertSame('Access denied for [REDACTED] or [REDACTED]', $exception->bodyExcerpt);
            $this->assertStringNotContainsString($key, $exception->getMessage());
            $this->assertStringNotContainsString(rawurlencode($key), $exception->getMessage());
            $this->assertSame(strlen($body), $exception->receivedBytes);
        }
    }

    public function testParameterEncodingErrorsEscapeBeforeTransfer(): void
    {
        $client = new FixtureClient();
        try {
            $client->fetch('custom-discounted-cash-flow', symbol: 'DEMO', revenueGrowthPct: NAN);
            $this->fail('A non-finite query value cannot be encoded.');
        } catch (\JsonException $exception) {
            $this->assertSame(JSON_ERROR_INF_OR_NAN, $exception->getCode());
            $this->assertSame([], $client->calls);
        }
    }

    public static function transportFailures(): iterable
    {
        foreach (['stable', 'legacy', 'file'] as $kind) {
            yield "$kind timeout" => [$kind, new CurlException('Transfer timed out.', CURLE_OPERATION_TIMEDOUT, receivedBytes: 123, partialBody: '{"partial":true}')];
            yield "$kind byte limit" => [$kind, new CurlException('Response exceeded the configured byte limit.', CURLE_WRITE_ERROR, receivedBytes: 65536, partialBody: str_repeat('x', 128))];
            yield "$kind HTTP" => [$kind, new HttpException(429, 'https://example.invalid/stable/profile', 'Rate limit', 10)];
            yield "$kind metadata" => [$kind, new \JsonException('Invalid cached transport metadata.', JSON_ERROR_SYNTAX)];
        }
    }

    #[DataProvider('transportFailures')]
    public function testFactoriesPreserveOriginalTransportExceptions(string $kind, Throwable $failure): void
    {
        $client = new class($failure) extends FixtureClient {
            public int $attempts = 0;

            public function __construct(private readonly Throwable $failure)
            {
                parent::__construct();
            }

            public function executeCurl(string $endpoint, array $params = []): string
            {
                $this->attempts++;
                throw $this->failure;
            }
        };

        try {
            self::createResponse($client, $kind);
            $this->fail('Expected the injected transfer failure.');
        } catch (Throwable $exception) {
            $this->assertSame($failure, $exception);
            $this->assertSame(1, $client->attempts);
        }
    }

    public static function decoderFailures(): iterable
    {
        foreach (['stable', 'legacy', 'file'] as $kind) {
            yield "$kind malformed JSON" => [$kind, '{"broken":', JSON_ERROR_SYNTAX];
            yield "$kind provider error" => [$kind, '{"Error Message":"Invalid key synthetic-api-key"}', 0];
        }
        foreach (['stable', 'legacy'] as $kind) {
            yield "$kind CSV header" => [$kind, "symbol,symbol\nDEMO,OTHER\n", 0];
            yield "$kind CSV row" => [$kind, "symbol,price\nDEMO\n", 0];
        }
    }

    #[DataProvider('decoderFailures')]
    public function testDecoderExceptionsHaveRedactedMessagesAndPreservedCodes(string $kind, string $payload, int $code): void
    {
        $client = $kind === 'legacy' ? new LegacyFixtureClient() : new FixtureClient();
        $client->payload = $payload;

        try {
            self::createResponse($client, $kind);
            $this->fail('Invalid data must not return a response.');
        } catch (ResponseException $exception) {
            $this->assertNotInstanceOf(HttpException::class, $exception);
            $this->assertSame($code, $exception->getCode());
            $this->assertInstanceOf(ResponseException::class, $exception->getPrevious());
            $this->assertSame($code, $exception->getPrevious()->getCode());
            $this->assertNull($exception->getPrevious()->getPrevious());
            $this->assertStringNotContainsString('synthetic-api-key', $exception->getMessage());
            $this->assertStringNotContainsString('synthetic-api-key', $exception->getPrevious()->getMessage());
            $this->assertStringContainsString($client->getLastEndpointURL(), $exception->getMessage());
            $this->assertCount(1, $client->calls);
        }
    }

    public static function injectedDecoderFailures(): iterable
    {
        yield 'JSON' => [new \JsonException('Invalid key synthetic-api-key', JSON_ERROR_SYNTAX)];
        yield 'provider' => [new ResponseException('Invalid key synthetic-api-key', 7)];
    }

    #[DataProvider('injectedDecoderFailures')]
    public function testOriginalDecoderExceptionIsNotRetained(Throwable $failure): void
    {
        $response = self::failingDecoder($failure);
        $client = new FixtureClient();

        try {
            $response::create($client);
            $this->fail('Expected the injected decoder failure.');
        } catch (ResponseException $exception) {
            $this->assertNotSame($failure, $exception);
            $this->assertNotSame($failure, $exception->getPrevious());
            $this->assertSame($failure->getCode(), $exception->getCode());
            $this->assertSame('Invalid key [REDACTED]', $exception->getPrevious()->getMessage());
        }
    }

    public static function programmingErrors(): iterable
    {
        yield 'logic' => [new \LogicException('Unexpected decoder bug.')];
        yield 'type' => [new \TypeError('Unexpected decoder type.')];
    }

    #[DataProvider('programmingErrors')]
    public function testUnexpectedDecoderProgrammingErrorsRemainUnchanged(Throwable $failure): void
    {
        $response = self::failingDecoder($failure);

        try {
            $response::create(new FixtureClient());
            $this->fail('Expected the injected programming error.');
        } catch (Throwable $exception) {
            $this->assertSame($failure, $exception);
        }
    }

    /** @throws \JsonException If transport parameter encoding or cached metadata fails. */
    private static function createResponse(AbstractApiClient $client, string $kind): AbstractResponse|FileResponse
    {
        return match ($kind) {
            'legacy' => LegacyProfile::create($client, 'DEMO'),
            'file' => FinancialReportsXlsx::create($client, symbol: 'DEMO', year: 2022, period: 'FY'),
            default => Profile::create($client, symbol: 'DEMO'),
        };
    }

    /** Supply an independent decoder failure without changing production code for testability. */
    private static function failingDecoder(Throwable $failure): AbstractResponse
    {
        $response = new class([]) extends AbstractResponse {
            protected static string $url = 'stable/profile';
            public static Throwable $failure;

            protected static function decodePayload(string $payload): array
            {
                throw self::$failure;
            }
        };
        $response::$failure = $failure;
        return $response;
    }
}
