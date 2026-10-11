<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Tests;

use Manhart\Fmp\Exception\ResponseException;
use Manhart\Fmp\Exception\HttpException;
use Manhart\Fmp\Response\Legacy\AnalystEstimates;
use Manhart\Fmp\Response\Legacy\BalanceSheetStatementBulk;
use Manhart\Fmp\Response\Legacy\CashflowStatementBulk;
use Manhart\Fmp\Response\Legacy\CompanyOutlook;
use Manhart\Fmp\Response\Legacy\FinancialGrowth;
use Manhart\Fmp\Response\Legacy\FinancialGrowthBulk;
use Manhart\Fmp\Response\Legacy\IncomeStatementBulk;
use Manhart\Fmp\Response\Legacy\KeyMetrics;
use Manhart\Fmp\Response\Legacy\KeyMetricsBulk;
use Manhart\Fmp\Response\Legacy\KeyMetricsTTM;
use Manhart\Fmp\Response\Legacy\KeyMetricsTTMBulk;
use Manhart\Fmp\Response\Legacy\Ratios;
use Manhart\Fmp\Response\Legacy\RatiosBulk;
use Manhart\Fmp\Response\Legacy\RatiosTTM;
use Manhart\Fmp\Response\Legacy\RatiosTTMBulk;
use Manhart\Fmp\Tests\Support\LegacyFixtureClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LegacyFundamentalsTest extends TestCase
{
    /** Expected requests are independent of the recorder manifest and response route properties. */
    public static function documentedRequests(): iterable
    {
        yield 'metrics' => ['getKeyMetrics', ['AAPL', 'quarter', 4], KeyMetrics::class, 'v3/key-metrics/AAPL?period=quarter&limit=4'];
        yield 'metrics TTM' => ['getKeyMetricsTTM', ['AAPL'], KeyMetricsTTM::class, 'v3/key-metrics-ttm/AAPL'];
        yield 'ratios' => ['getRatios', ['AAPL', 'quarter', 4], Ratios::class, 'v3/ratios/AAPL?period=quarter&limit=4'];
        yield 'ratios TTM' => ['getRatiosTTM', ['AAPL'], RatiosTTM::class, 'v3/ratios-ttm/AAPL'];
        yield 'growth' => ['getFinancialGrowth', ['AAPL', 'quarter', 4], FinancialGrowth::class, 'v3/financial-growth/AAPL?period=quarter&limit=4'];
        yield 'estimates' => ['getAnalystEstimates', ['AAPL', 'quarter', 4], AnalystEstimates::class, 'v3/analyst-estimates/AAPL?period=quarter&limit=4'];
        yield 'outlook' => ['getCompanyOutlook', ['AAPL'], CompanyOutlook::class, 'v4/company-outlook?symbol=AAPL'];
        yield 'income bulk' => ['getIncomeStatementBulk', [2023, 'quarter'], IncomeStatementBulk::class, 'v4/income-statement-bulk?year=2023&period=quarter'];
        yield 'balance sheet bulk' => ['getBalanceSheetStatementBulk', [2023, 'quarter'], BalanceSheetStatementBulk::class, 'v4/balance-sheet-statement-bulk?year=2023&period=quarter'];
        yield 'cash flow bulk' => ['getCashflowStatementBulk', [2023, 'quarter'], CashflowStatementBulk::class, 'v4/cash-flow-statement-bulk?year=2023&period=quarter'];
        yield 'metrics bulk' => ['getKeyMetricsBulk', [2023, 'quarter'], KeyMetricsBulk::class, 'v4/key-metrics-bulk?year=2023&period=quarter'];
        yield 'ratios bulk' => ['getRatiosBulk', [2023, 'quarter'], RatiosBulk::class, 'v4/ratios-bulk?year=2023&period=quarter'];
        yield 'growth bulk' => ['getFinancialGrowthBulk', [2023, 'quarter'], FinancialGrowthBulk::class, 'v4/financial-growth-bulk?year=2023&period=quarter'];
        yield 'metrics TTM bulk' => ['getKeyMetricsTTMBulk', [], KeyMetricsTTMBulk::class, 'v4/key-metrics-ttm-bulk'];
        yield 'ratios TTM bulk' => ['getRatiosTTMBulk', [], RatiosTTMBulk::class, 'v4/ratios-ttm-bulk'];
    }

    #[DataProvider('documentedRequests')]
    public function testTypedMethodsUseDocumentedRoutesInOneCall(string $method, array $arguments, string $responseClass, string $route): void
    {
        $client = new LegacyFixtureClient();
        $response = $client->$method(...$arguments);

        $this->assertInstanceOf($responseClass, $response);
        $this->assertSame(['https://financialmodelingprep.com/api/'.$route], $client->urls);
        $this->assertCount(1, $client->calls);
    }

    public static function periodicMethods(): iterable
    {
        yield 'metrics' => ['getKeyMetrics'];
        yield 'ratios' => ['getRatios'];
        yield 'growth' => ['getFinancialGrowth'];
        yield 'estimates' => ['getAnalystEstimates'];
    }

    #[DataProvider('periodicMethods')]
    public function testAnnualDefaultAndNullFiltersAreForwardedWithoutInventedParameters(string $method): void
    {
        $client = new LegacyFixtureClient();
        $client->$method('AAPL');
        $client->$method('AAPL', period: null, limit: null);

        $this->assertStringEndsWith('/AAPL?period=annual', $client->urls[0]);
        $this->assertSame(strtok($client->urls[0], '?'), $client->urls[1]);
        $this->assertCount(2, $client->calls);
    }

    public static function periodicBulks(): iterable
    {
        yield 'income' => ['getIncomeStatementBulk'];
        yield 'balance sheet' => ['getBalanceSheetStatementBulk'];
        yield 'cash flow' => ['getCashflowStatementBulk'];
        yield 'metrics' => ['getKeyMetricsBulk'];
        yield 'ratios' => ['getRatiosBulk'];
        yield 'growth' => ['getFinancialGrowthBulk'];
    }

    #[DataProvider('periodicBulks')]
    public function testBulkYearIsRequired(string $method): void
    {
        $client = new LegacyFixtureClient();
        $this->expectException(\ArgumentCountError::class);
        $client->$method();
    }

    #[DataProvider('periodicBulks')]
    public function testBulkDefaultsToAnnualWithNoExtraFilters(string $method): void
    {
        $client = new LegacyFixtureClient();
        $client->$method(2023);

        $this->assertStringEndsWith('?year=2023&period=annual', $client->urls[0]);
        $this->assertCount(1, $client->calls);
    }

    public function testJsonMetricsKeepOriginalFieldsNullsAndZero(): void
    {
        $client = new LegacyFixtureClient();
        $client->payload = '[{"symbol":"AAPL","date":"2023-09-30","period":"FY","reportedCurrency":"USD","peRatio":0,"revenuePerShare":null,"providerExtra":{"unit":"ratio"}}]';
        $response = $client->getKeyMetrics('AAPL');

        $this->assertSame('FY', $response->getResponseValue('period'));
        $this->assertSame('USD', $response->getResponseValue('reportedCurrency'));
        $this->assertSame(0, $response->getResponseValue('peRatio'));
        $this->assertSame(0.0, $response->getResponseValueAsFloat('peRatio', null));
        $this->assertNull($response->getResponseValueAsFloat('revenuePerShare', null));
        $this->assertNull($response->getResponseValueAsFloat('missing', null));
        $this->assertSame(['unit' => 'ratio'], $response->getResponseValue('providerExtra'));
        $this->assertArrayNotHasKey('priceToEarningsRatio', $response->current());
    }

    public function testCsvBulkKeepsRawStringsAndEveryRow(): void
    {
        $client = new LegacyFixtureClient();
        $client->contentType = 'text/csv';
        $client->payload = "symbol,date,period,reportedCurrency,peRatio,providerExtra\nAAPL,2023-09-30,FY,USD,0,00123\nMSFT,2023-06-30,FY,USD,,00456\n";
        $response = $client->getRatiosBulk(2023);

        $this->assertCount(2, $response);
        $this->assertSame('0', $response->getResponseValue('peRatio'));
        $this->assertSame(0.0, $response->getResponseValueAsFloat('peRatio', null));
        $this->assertSame('00123', $response->getResponseValue('providerExtra'));
        $rows = iterator_to_array($response);
        $this->assertSame(['AAPL', 'MSFT'], array_column($rows, 'symbol'));
        $response->next();
        $this->assertFalse($response->valid());
        $response->rewind();
        $response->next();
        $this->assertNull($response->getResponseValueAsFloat('peRatio', null));
        $this->assertSame('FY', $response->getResponseValue('period'));
        $this->assertSame('00456', $response->getResponseValue('providerExtra'));
    }

    public function testCompanyOutlookPreservesAllNestedSectionsInOneRow(): void
    {
        $client = new LegacyFixtureClient();
        $client->payload = '{"profile":{"symbol":"AAPL","currency":"USD"},"metrics":{"peRatio":0},"ratios":[{"currentRatioTTM":1.2}],"insideTrades":[],"financialsAnnual":{"income":[{"revenue":123}]},"providerExtra":null}';
        $response = $client->getCompanyOutlook('AAPL');

        $this->assertCount(1, $response);
        $this->assertSame(['symbol' => 'AAPL', 'currency' => 'USD'], $response->getResponseValue('profile'));
        $this->assertSame(['peRatio' => 0], $response->getResponseValue('metrics'));
        $this->assertSame([['currentRatioTTM' => 1.2]], $response->getResponseValue('ratios'));
        $this->assertSame([], $response->getResponseValue('insideTrades'));
        $this->assertSame(['income' => [['revenue' => 123]]], $response->getResponseValue('financialsAnnual'));
        $this->assertArrayHasKey('providerExtra', $response->current());
        $this->assertNull($response->getResponseValue('providerExtra'));
        $this->assertSame([$response->current()], iterator_to_array($response));
        $this->assertCount(1, $client->calls);
    }

    public function testCompanyOutlookListRetainsSeparateRows(): void
    {
        $client = new LegacyFixtureClient();
        $client->payload = '[{"profile":{"symbol":"AAPL"}},{"profile":{"symbol":"MSFT"}}]';
        $response = $client->getCompanyOutlook('AAPL');

        $this->assertCount(2, $response);
        $this->assertSame([['profile' => ['symbol' => 'AAPL']], ['profile' => ['symbol' => 'MSFT']]], iterator_to_array($response));
    }

    public function testEmptyCompanyOutlookStaysEmpty(): void
    {
        $client = new LegacyFixtureClient();
        $client->payload = '[]';
        $response = $client->getCompanyOutlook('AAPL');

        $this->assertCount(0, $response);
        $this->assertSame([], iterator_to_array($response));
    }

    public static function invalidResponses(): iterable
    {
        yield 'malformed JSON' => [200, '{"symbol":'];
        yield 'provider error' => [200, '{"Error Message":"Invalid key synthetic-api-key"}'];
        yield 'HTTP forbidden' => [403, 'synthetic-api-key'];
    }

    #[DataProvider('invalidResponses')]
    public function testNewMethodsUseSharedErrorWrappingAndKeyRedaction(int $status, string $payload): void
    {
        $client = new LegacyFixtureClient();
        $client->status = $status;
        $client->payload = $payload;

        try {
            $client->getKeyMetrics('AAPL');
            $this->fail('An invalid response must not return financial records.');
        } catch (ResponseException $exception) {
            $this->assertStringNotContainsString('synthetic-api-key', $exception->getMessage());
            if ($status === 403) {
                $this->assertInstanceOf(HttpException::class, $exception);
                $this->assertSame(403, $exception->status);
                $this->assertSame('[REDACTED]', $exception->bodyExcerpt);
                $this->assertNull($exception->getPrevious());
            } else {
                $this->assertNotNull($exception->getPrevious());
                $this->assertStringNotContainsString('synthetic-api-key', $exception->getPrevious()->getMessage());
            }
            $this->assertCount(1, $client->calls);
        }
    }
}
