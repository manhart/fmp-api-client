<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Tests;

use Manhart\Fmp\Client\FmpApiClient;
use Manhart\Fmp\Tests\Support\LegacyFixtureClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LegacyEndpointsTest extends TestCase
{
    public static function legacyMethods(): iterable
    {
        $cases = json_decode(file_get_contents(__DIR__.'/../resources/legacy-cases.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach ($cases as $method => $case) {
            yield $method => [$method, $case];
        }
    }

    #[DataProvider('legacyMethods')]
    public function testLegacyMethodsLoadAndForwardAllArguments(string $method, array $case): void
    {
        $client = new LegacyFixtureClient();
        if (str_contains($case['endpoint'], 'historical-price-full')) {
            $client->payload = '{"symbol":"AAPL","historical":[{"date":"2024-01-02","close":12.5}]}';
        } elseif ($method === 'getFinancialStatementSymbolLists') {
            $client->payload = '["AAPL","MSFT"]';
        }
        $params = $case['parameters'];
        foreach ($case['types'] as $name => $type) {
            if (str_contains($type, 'DateTimeInterface')) {
                $params[$name] = new \DateTimeImmutable($params[$name]);
            }
        }
        $response = $client->$method(...$params);
        $this->assertInstanceOf($case['class'], $response);
        $this->assertCount(1, $client->calls);
        $this->assertStringStartsWith('https://financialmodelingprep.com/api/', $client->urls[0]);
        if (array_key_exists('dataType', $params)) {
            $this->assertStringContainsString('datatype=json', $client->urls[0]);
        }
    }

    public function testLegacyQuoteArrayAndGetters(): void
    {
        $client = new LegacyFixtureClient();
        $client->payload = '[{"symbol":"AAPL","price":12.5,"volume":123,"sharesOutstanding":456,"previousClose":12}]';
        $response = $client->getQuote(['AAPL', 'MSFT']);
        $this->assertStringEndsWith('/api/v3/quote/AAPL,MSFT', $client->urls[0]);
        $this->assertSame('AAPL', $response->getSymbol());
        $this->assertSame(12.5, $response->getPrice());
        $this->assertSame(123, $response->getVolume());
        $this->assertSame(456, $response->getSharesOutstanding());
        $this->assertSame(12.0, $response->getPreviousClose());
    }

    public function testLegacyCsvDataTypeAndCikPadding(): void
    {
        $client = new LegacyFixtureClient();
        $client->payload = "symbol,revenue\nAAPL,123.5\n";
        $response = $client->getIncomeStatement('AAPL', dataType: 'csv');
        $this->assertSame(123.5, $response->getRevenue());
        $this->assertStringContainsString('datatype=csv', $client->urls[0]);
        $client->getBalanceSheetStatement(320193);
        $this->assertStringContainsString('/0000320193?', $client->urls[1]);
    }

    public function testEmptyHistoricalResponsesRemainIterable(): void
    {
        $client = new LegacyFixtureClient();
        $client->payload = '[]';
        foreach (['getStockDividend', 'getStockSplit', 'getHistoricalPrice'] as $method) {
            $response = $client->$method('AAPL');
            $this->assertCount(0, $response);
            $this->assertSame('', $response->getSymbol());
        }
    }

    public function testShareFloatAllRoute(): void
    {
        $client = new LegacyFixtureClient();
        $client->getShareFloat();
        $this->assertStringEndsWith('/api/v4/shares_float/all', $client->urls[0]);
    }

    public function testConstructorRequiresAnExplicitKey(): void
    {
        $this->expectException(\ArgumentCountError::class);
        new FmpApiClient();
    }

    public function testApiKeyIsRequiredWithoutInspectingEnvironment(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new FmpApiClient('');
    }
}
