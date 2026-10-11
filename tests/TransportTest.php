<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Tests;

use Manhart\Fmp\Client\FmpApiClient;
use Manhart\Fmp\Client\AbstractApiClient;
use Manhart\Fmp\Client\LegacyApiClient;
use Manhart\Fmp\Exception\CurlException;
use Manhart\Fmp\Exception\HttpException;
use Manhart\Fmp\Exception\ResponseException;
use Manhart\Fmp\Response\AbstractResponse;
use Manhart\Fmp\Response\FileResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TransportTest extends TestCase
{
    private static $server;
    private static string $baseUrl;
    private static array $pipes = [];

    public static function setUpBeforeClass(): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $code, $error);
        if ($socket === false) {
            throw new \RuntimeException($error, $code);
        }
        $address = stream_socket_get_name($socket, false);
        fclose($socket);
        self::$baseUrl = 'http://'.$address;
        self::$server = proc_open([PHP_BINARY, '-d', 'display_errors=0', '-S', $address, __DIR__.'/Support/router.php'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], self::$pipes);
        for ($attempt = 0; $attempt < 100; $attempt++) {
            $connection = @stream_socket_client('tcp://'.$address, $code, $error, 0.05);
            if ($connection !== false) {
                fclose($connection);
                return;
            }
            usleep(10000);
        }
        throw new \RuntimeException('Local HTTP fixture server failed to start.');
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            foreach (self::$pipes as $pipe) {
                fclose($pipe);
            }
            proc_close(self::$server);
        }
    }

    private function client(int $timeout = 5, ?int $maxBytes = null, string $apiKey = 'local-test-secret', bool $legacy = false, string $prefix = ''): FmpApiClient|LegacyApiClient
    {
        $client = $legacy
            ? new LegacyApiClient($apiKey, timeoutSeconds: $timeout, maxResponseBytes: $maxBytes)
            : new FmpApiClient($apiKey, timeoutSeconds: $timeout, maxResponseBytes: $maxBytes);
        (new \ReflectionProperty(AbstractApiClient::class, 'baseUrl'))->setValue($client, self::$baseUrl.$prefix);
        return $client;
    }

    public function testNativeCurlSendsKeyAndFormatsParameters(): void
    {
        $client = $this->client();
        $response = $client->request('stable/quote', ['symbol' => 'AAPL', 'flag' => false]);
        $data = json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(200, $response->status);
        $this->assertSame('local-test-secret', $data['query']['apikey']);
        $this->assertSame('false', $data['query']['flag']);
        $this->assertSame(strlen($response->body), $response->receivedBytes);
        $this->assertSame(1, $client->getRequests());
        $this->assertStringNotContainsString('local-test-secret', $client->getLastEndpointURL());
    }

    public function testDiagnosticRedactionDoesNotModifyRequestParameters(): void
    {
        $client = $this->client();
        $response = $client->request('stable/search-symbol', ['query' => 'local-test-secret']);
        $data = json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('local-test-secret', $data['query']['query']);
        $this->assertStringNotContainsString('local-test-secret', $client->getLastEndpointURL());
    }

    public function testExplicitClientsHaveIndependentKeysAndRequestState(): void
    {
        $first = $this->client(apiKey: 'first-synthetic-key');
        $second = $this->client(apiKey: 'second-synthetic-key', legacy: true);

        foreach ([$first, $second, $first] as $index => $client) {
            $response = $client->request('stable/quote', ['symbol' => 'AAPL']);
            $data = json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);
            $expectedKey = $index === 1 ? 'second-synthetic-key' : 'first-synthetic-key';
            $this->assertSame($expectedKey, $data['query']['apikey']);
            $this->assertStringNotContainsString($expectedKey, $client->getLastEndpointURL());
        }
        $this->assertSame(2, $first->getRequests());
        $this->assertSame(1, $second->getRequests());
    }

    public function testHttpErrorIsNotParsedAsCsv(): void
    {
        $client = $this->client();
        $this->expectException(ResponseException::class);
        $this->expectExceptionCode(429);
        $client->executeCurl('error');
    }

    public function testRequestReturnsUndecodedHttpErrors(): void
    {
        $client = $this->client();
        $response = $client->request('error');
        $this->assertSame(429, $response->status);
        $this->assertSame('{"Error Message":"Rate limit for local-test-secret"}', $response->body);
        $this->assertSame(strlen($response->body), $response->receivedBytes);
        $this->assertSame(1, $client->getRequests());
    }

    public function testNativeHttpErrorRetainsRedactedBodyAndReceivedBytes(): void
    {
        $client = $this->client(prefix: '/error');
        try {
            $client->getProfile('DEMO');
            $this->fail('Expected an HTTP error.');
        } catch (HttpException $exception) {
            $this->assertSame(429, $exception->status);
            $this->assertSame(429, $exception->getCode());
            $this->assertStringContainsString('Rate limit for [REDACTED]', $exception->bodyExcerpt);
            $this->assertStringNotContainsString('local-test-secret', $exception->getMessage());
            $this->assertSame(strlen('{"Error Message":"Rate limit for local-test-secret"}'), $exception->receivedBytes);
            $this->assertSame(1, $client->getRequests());
        }
    }

    public function testConfiguredDownloadLimitStopsLargeResponse(): void
    {
        $client = $this->client(maxBytes: 128);
        try {
            $client->request('large');
            $this->fail('Expected response byte limit.');
        } catch (CurlException $error) {
            $this->assertSame(128, strlen($error->partialBody));
            $this->assertGreaterThanOrEqual(128, $error->receivedBytes);
            $this->assertSame(1, $client->getRequests());
        }
    }

    public static function factoryKinds(): iterable
    {
        yield 'Stable' => ['stable'];
        yield 'Legacy' => ['legacy'];
        yield 'XLSX' => ['file'];
    }

    #[DataProvider('factoryKinds')]
    public function testTypedDownloadLimitsPreserveCurlFailureDetails(string $kind): void
    {
        $client = $this->client(maxBytes: 128, legacy: $kind === 'legacy', prefix: '/large');
        try {
            $this->requestTypedResponse($client, $kind);
            $this->fail('Expected a response byte limit.');
        } catch (CurlException $exception) {
            $this->assertSame(CURLE_WRITE_ERROR, $exception->getCode());
            $this->assertSame(str_repeat('x', 128), $exception->partialBody);
            $this->assertGreaterThanOrEqual(128, $exception->receivedBytes);
            $this->assertSame(1, $client->getRequests());
        }
    }

    public function testTimeoutIsFiniteAndDoesNotRetry(): void
    {
        $client = $this->client(timeout: 1);
        try {
            $client->request('slow');
            $this->fail('Expected timeout.');
        } catch (CurlException $error) {
            $this->assertSame(CURLE_OPERATION_TIMEDOUT, $error->getCode());
            $this->assertSame(1, $client->getRequests());
        }
    }

    #[DataProvider('factoryKinds')]
    public function testTypedTimeoutsRemainCurlExceptionsWithoutRetry(string $kind): void
    {
        $client = $this->client(timeout: 1, legacy: $kind === 'legacy', prefix: '/slow');
        try {
            $this->requestTypedResponse($client, $kind);
            $this->fail('Expected a timeout.');
        } catch (CurlException $exception) {
            $this->assertSame(CURLE_OPERATION_TIMEDOUT, $exception->getCode());
            $this->assertSame(1, $client->getRequests());
        }
    }

    /** @throws \JsonException If transport parameter encoding or cached metadata fails. */
    private function requestTypedResponse(FmpApiClient|LegacyApiClient $client, string $kind): AbstractResponse|FileResponse
    {
        if ($client instanceof LegacyApiClient) {
            return $client->getProfile('DEMO');
        }
        return $kind === 'file'
            ? $client->fetch('financial-reports-xlsx', symbol: 'DEMO', year: 2022, period: 'FY')
            : $client->getProfile('DEMO');
    }
}
