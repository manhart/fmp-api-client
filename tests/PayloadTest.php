<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Tests;

use Manhart\Fmp\Exception\ResponseException;
use Manhart\Fmp\Response\PayloadDecoder;
use Manhart\Fmp\Tests\Support\FixtureClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PayloadTest extends TestCase
{
    public static function whitespaceDates(): iterable
    {
        yield 'space' => [' '];
        yield 'tab' => ["\t"];
    }

    /** @throws \JsonException If the synthetic JSON response cannot be encoded. */
    #[DataProvider('whitespaceDates')]
    public function testWhitespaceDatesUseTheFallbackForJsonAndCsv(string $value): void
    {
        $client = new FixtureClient();
        $fallback = new \DateTimeImmutable('2000-01-01');
        foreach ([
            json_encode([['symbol' => 'DEMO', 'ipoDate' => $value]], JSON_THROW_ON_ERROR),
            "symbol,ipoDate\nDEMO,$value\n",
        ] as $payload) {
            $client->payload = $payload;
            $response = $client->getProfile('DEMO');
            $this->assertNull($response->getResponseValueAsDate('ipoDate'));
            $this->assertSame($fallback, $response->getResponseValueAsDate('ipoDate', $fallback));
            $this->assertSame($value, $response->getResponseValue('ipoDate'));
        }
    }

    public static function numericTimestamps(): iterable
    {
        yield 'negative int' => [-1, -1];
        yield 'negative integer string' => ['-1', -1];
        yield 'negative float' => [-1.5, -1];
        yield 'negative decimal string' => ['-1.5', -1];
        yield 'zero' => [0, 0];
        yield 'zero string' => ['0', 0];
        yield 'subsecond float' => [0.75, 0];
        yield 'subsecond string' => ['0.75', 0];
        yield 'positive float' => [1.5, 1];
        yield 'positive decimal string' => ['1.5', 1];
        yield 'positive int' => [1767225600, 1767225600];
        yield 'positive integer string' => ['1767225600', 1767225600];
    }

    /** @throws \JsonException If the synthetic JSON response cannot be encoded. */
    #[DataProvider('numericTimestamps')]
    public function testJsonAndCsvUseTheSameUnixSeconds(int|float|string $value, int $expected): void
    {
        $client = new FixtureClient();
        foreach ([
            json_encode([['symbol' => 'DEMO', 'timestamp' => $value]], JSON_THROW_ON_ERROR),
            "symbol,timestamp\nDEMO,$value\n",
        ] as $payload) {
            $client->payload = $payload;
            $response = $client->fetch('quote', 'DEMO');
            $date = $response->getResponseValueAsDate('timestamp');
            $this->assertSame($expected, $date?->getTimestamp());
            $this->assertSame('+00:00', $date?->format('P'));
        }
    }

    public function testJsonObjectsAndArraysRemainReadable(): void
    {
        $client = new FixtureClient();
        $client->payload = '{"symbol":"DEMO","price":"12.50","active":"true","timestamp":1767225600}';
        $response = $client->fetch('quote', 'DEMO');
        $this->assertCount(1, $response);
        $this->assertSame('DEMO', $response->getResponseValueAsString('symbol'));
        $this->assertSame(12.5, $response->getResponseValueAsFloat('price'));
        $this->assertTrue($response->getResponseValueAsBool('active'));
        $this->assertSame('2026-01-01', $response->getResponseValueAsDate('timestamp')->format('Y-m-d'));
        $client->payload = '[{"symbol":"FIRST"},{"symbol":"SECOND"}]';
        $response = $client->fetch('quote', 'DEMO');
        $symbols = [];
        foreach ($response as $ignore) {
            $symbols[] = $response->getResponseValueAsString('symbol');
        }
        $this->assertSame(['FIRST', 'SECOND'], $symbols);
    }

    public function testCsvBomQuotesNewlinesAndEmptyFields(): void
    {
        $payload = "\xEF\xBB\xBFsymbol,name,price\r\nDEMO,\"Example, Ltd.\",12.5\r\nOTHER,\"Two\nLines\",\r\n";
        $this->assertSame([
            ['symbol' => 'DEMO', 'name' => 'Example, Ltd.', 'price' => '12.5'],
            ['symbol' => 'OTHER', 'name' => "Two\nLines", 'price' => ''],
        ], PayloadDecoder::decode($payload));
    }

    public function testEmptyResponses(): void
    {
        foreach (['', " \r\n", "\xEF\xBB\xBF", " \t\xEF\xBB\xBF \r\n", '[]', "symbol,price\n"] as $payload) {
            $this->assertSame([], PayloadDecoder::decode($payload));
        }
    }

    public function testOnlyTheCompleteUtf8BomIsRemoved(): void
    {
        // Fullwidth/Arabic headers start with EF or EF/BB, which the former mask stripped.
        foreach (['Ａ', 'ﻡ', '»', '¿'] as $header) {
            foreach (['', "\xEF\xBB\xBF", " \t\xEF\xBB\xBF \r\n"] as $prefix) {
                $this->assertSame([
                    [$header => 'DEMO', 'price' => '12.5'],
                ], PayloadDecoder::decode($prefix.$header.",price\nDEMO,12.5\n"));
            }
        }
        $this->assertSame(['symbol' => 'Ａ'], PayloadDecoder::decode("\xEF\xBB\xBF {\"symbol\":\"Ａ\"}"));
    }

    public function testRestrictedEndpointProseWithACommaIsAnApiError(): void
    {
        $message = 'Restricted Endpoint: unavailable under your subscription, please contact support.';
        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage($message);
        PayloadDecoder::decode($message."\nDetails");
    }

    public static function invalidPayloads(): iterable
    {
        yield 'invalid JSON' => ['[{'];
        yield 'API message' => ['{"Error Message":"Invalid API key"}'];
        yield 'API status' => ['{"status":404,"error":"Not Found","message":"Missing"}'];
        yield 'query error' => ["Query Error: unavailable\nDetails"];
        yield 'restricted endpoint with comma' => ['Restricted Endpoint: unavailable, please contact support.'];
        yield 'HTML error' => ['<html><body>error, unavailable</body></html>'];
        yield 'plain text' => ['Service unavailable'];
        yield 'short CSV row' => ["symbol,price\nDEMO\n"];
        yield 'long CSV row' => ["symbol,price\nDEMO,1,2\n"];
        yield 'duplicate CSV header' => ["symbol,symbol\nA,B\n"];
    }

    #[DataProvider('invalidPayloads')]
    public function testInvalidResponsesAreRejected(string $payload): void
    {
        $client = new FixtureClient();
        $client->payload = $payload;
        $this->expectException(ResponseException::class);
        $client->fetch('quote', 'DEMO');
    }

    public function testXlsxRejectsApiErrorWithoutLeakingKey(): void
    {
        $client = new FixtureClient();
        $client->payload = '{"Error Message":"Failed for synthetic-api-key"}';
        try {
            $client->fetch('financial-reports-xlsx', symbol: 'AAPL', year: 2022, period: 'FY');
            $this->fail('Expected file response exception.');
        } catch (ResponseException $exception) {
            do {
                $this->assertStringNotContainsString('synthetic-api-key', $exception->getMessage());
            } while ($exception = $exception->getPrevious());
        }
    }

    public function testExceptionChainRedactsApiKey(): void
    {
        $client = new FixtureClient();
        $client->payload = '{"Error Message":"Failed for synthetic-api-key"}';
        try {
            $client->fetch('quote', 'DEMO');
            $this->fail('Expected response exception.');
        } catch (ResponseException $exception) {
            do {
                $this->assertStringNotContainsString('synthetic-api-key', $exception->getMessage());
            } while ($exception = $exception->getPrevious());
        }
    }
}
