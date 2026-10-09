<?php
declare(strict_types=1);

namespace Manhart\Fmp\Tests;

use Manhart\Fmp\Exception\ResponseException;
use Manhart\Fmp\Response\PayloadDecoder;
use Manhart\Fmp\Tests\Support\FixtureClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PayloadTest extends TestCase
{
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
        foreach (['', " \r\n", '[]', "symbol,price\n"] as $payload) {
            $this->assertSame([], PayloadDecoder::decode($payload));
        }
    }

    public static function invalidPayloads(): iterable
    {
        yield 'invalid JSON' => ['[{'];
        yield 'API message' => ['{"Error Message":"Invalid API key"}'];
        yield 'API status' => ['{"status":404,"error":"Not Found","message":"Missing"}'];
        yield 'query error' => ["Query Error: unavailable\nDetails"];
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
