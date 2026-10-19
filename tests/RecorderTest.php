<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Tests;

use Manhart\Fmp\Recording\CaptureCache;
use Manhart\Fmp\Recording\Recorder;
use Manhart\Fmp\HttpResponse;
use Manhart\Fmp\Tests\Support\FixtureClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RecorderTest extends TestCase
{
    private string $directory;
    private CaptureCache $cache;
    private array $case = ['capture' => ['symbol' => 'AAPL'], 'fields' => ['symbol', 'price']];

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/fmp-recorder-test-'.bin2hex(random_bytes(8));
        $this->cache = new CaptureCache($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory.'/*') as $file) {
            unlink($file);
        }
        rmdir($this->directory);
    }

    public function testSecondRunReplaysWithoutConstructingLiveClient(): void
    {
        $client = new FixtureClient();
        $recorder = new Recorder($this->cache, fn(int $remaining): FixtureClient => $client);
        $this->assertSame('verified', $recorder->capture('quote', $this->case)['outcome']);
        $replay = new Recorder($this->cache, static fn(int $remaining) => throw new \LogicException('Network client must not be constructed.'));
        $result = $replay->capture('quote', $this->case);
        $this->assertSame('verified', $result['outcome']);
        $this->assertTrue($result['cached']);
        $this->assertSame(0, $replay->statistics()['calls']);
        $this->assertSame(0, $replay->statistics()['receivedBytes']);
        $this->assertCount(1, $client->calls);
    }

    public function testLegacyReplayUsesAnExistingCacheWithoutConstructingALiveClient(): void
    {
        $url = 'https://financialmodelingprep.com/api/v3/profile/AAPL';
        $body = '[{"symbol":"AAPL","price":12.5}]';
        $key = CaptureCache::key($url);
        $this->cache->begin($key, 'legacy:getProfile', $url);
        $this->cache->save($key, new HttpResponse($body, 200, 'application/json', strlen($body)), 'recorded');
        $recorder = new Recorder($this->cache, static fn(int $remaining) => throw new \LogicException('Must use the existing legacy capture.'));
        $case = ['parameters' => ['symbol' => 'AAPL'], 'types' => ['symbol' => 'string']];
        $result = $recorder->capture('legacy:getProfile', $case, offline: true);
        $this->assertSame('verified', $result['outcome']);
        $this->assertTrue($result['cached']);
        $this->assertSame(0, $recorder->statistics()['calls']);
        $this->assertSame(0, $recorder->statistics()['receivedBytes']);
    }

    public static function legacyBulkCases(): iterable
    {
        yield 'income' => ['getIncomeStatementBulk', 'v4/income-statement-bulk?year=2023&period=annual'];
        yield 'balance sheet' => ['getBalanceSheetStatementBulk', 'v4/balance-sheet-statement-bulk?year=2023&period=annual'];
        yield 'cash flow' => ['getCashflowStatementBulk', 'v4/cash-flow-statement-bulk?year=2023&period=annual'];
        yield 'metrics' => ['getKeyMetricsBulk', 'v4/key-metrics-bulk?year=2023&period=annual'];
        yield 'ratios' => ['getRatiosBulk', 'v4/ratios-bulk?year=2023&period=annual'];
        yield 'growth' => ['getFinancialGrowthBulk', 'v4/financial-growth-bulk?year=2023&period=annual'];
        yield 'metrics TTM' => ['getKeyMetricsTTMBulk', 'v4/key-metrics-ttm-bulk'];
        yield 'ratios TTM' => ['getRatiosTTMBulk', 'v4/ratios-ttm-bulk'];
    }

    /** @throws \JsonException If the capture manifest or cache metadata cannot be decoded. */
    #[DataProvider('legacyBulkCases')]
    public function testLegacyBulkCsvReplaysOfflineAndIsMarkedLarge(string $method, string $route): void
    {
        $cases = json_decode(file_get_contents(__DIR__.'/../resources/legacy-cases.json'), true, 512, JSON_THROW_ON_ERROR);
        $case = $cases[$method];
        $this->assertTrue($case['large']);

        $url = 'https://financialmodelingprep.com/api/'.$route;
        $body = "symbol,date,value\nAAPL,2023-09-30,0\nMSFT,2023-06-30,1.2\n";
        $key = CaptureCache::key($url);
        $this->cache->begin($key, 'legacy:'.$method, $url);
        $this->cache->save($key, new HttpResponse($body, 200, 'text/csv', strlen($body)), 'recorded');

        $recorder = new Recorder($this->cache, static fn(int $remaining) => throw new \LogicException('Bulk replay must not download again.'));
        $result = $recorder->capture('legacy:'.$method, $case, offline: true);
        $this->assertSame('verified', $result['outcome']);
        $this->assertTrue($result['cached']);
        $this->assertSame(0, $recorder->statistics()['calls']);
        $this->assertSame(0, $recorder->statistics()['receivedBytes']);
    }

    /** @throws \JsonException If the capture manifest or cache metadata cannot be decoded. */
    public function testNestedCompanyOutlookReplaysAsOneRecord(): void
    {
        $cases = json_decode(file_get_contents(__DIR__.'/../resources/legacy-cases.json'), true, 512, JSON_THROW_ON_ERROR);
        $url = 'https://financialmodelingprep.com/api/v4/company-outlook?symbol=AAPL';
        $body = '{"profile":{"symbol":"AAPL"},"metrics":{"peRatio":0},"ratios":[]}';
        $key = CaptureCache::key($url);
        $this->cache->begin($key, 'legacy:getCompanyOutlook', $url);
        $this->cache->save($key, new HttpResponse($body, 200, 'application/json', strlen($body)), 'recorded');

        $recorder = new Recorder($this->cache, static fn(int $remaining) => throw new \LogicException('Outlook replay must stay offline.'));
        $result = $recorder->capture('legacy:getCompanyOutlook', $cases['getCompanyOutlook'], offline: true);
        $this->assertSame('verified', $result['outcome']);
        $this->assertTrue($result['cached']);
        $this->assertSame(0, $recorder->statistics()['calls']);
        $this->assertSame(0, $recorder->statistics()['receivedBytes']);
    }

    public function testBlockedAndEmptyResponsesAreNotSuccessfulAndNotRetried(): void
    {
        $client = new FixtureClient();
        $client->status = 403;
        $recorder = new Recorder($this->cache, fn(int $remaining): FixtureClient => $client);
        $this->assertSame('blocked', $recorder->capture('quote', $this->case)['outcome']);
        $this->assertSame('blocked', $recorder->capture('quote', $this->case)['outcome']);
        $this->assertCount(1, $client->calls);
        $client->status = 200;
        $client->payload = '[]';
        $this->assertSame('empty', $recorder->capture('quote-short', $this->case)['outcome']);
    }

    public function testMalformedCachedPayloadCanBeReplayedWithoutAnotherCapture(): void
    {
        $client = new FixtureClient();
        $client->payload = '<html>bad</html>';
        $recorder = new Recorder($this->cache, fn(int $remaining): FixtureClient => $client);
        $this->assertSame('parse-error', $recorder->capture('quote', $this->case)['outcome']);
        $this->assertSame('parse-error', $recorder->capture('quote', $this->case)['outcome']);
        $this->assertCount(1, $client->calls);
    }

    public function testDifferentParametersHaveSeparateCacheKeysAndRefreshIsExplicit(): void
    {
        $client = new FixtureClient();
        $recorder = new Recorder($this->cache, fn(int $remaining): FixtureClient => $client, requestsPerMinute: 3000);
        $recorder->capture('quote', $this->case);
        $case = ['capture' => ['symbol' => 'MSFT']];
        $recorder->capture('quote', $case);
        $recorder->capture('quote', $case, refresh: true);
        $this->assertCount(3, $client->calls);
        $this->assertNotSame(CaptureCache::key($client->urls[0]), CaptureCache::key($client->urls[1]));
        $this->assertSame(CaptureCache::key('https://financialmodelingprep.com/stable/quote?b=2&a=1'), CaptureCache::key('https://financialmodelingprep.com/stable/quote?a=1&b=2'));
    }

    public function testApiErrorInsideHttpSuccessIsNotVerified(): void
    {
        $client = new FixtureClient();
        $client->payload = '{"Error Message":"Premium access required"}';
        $recorder = new Recorder($this->cache, fn(int $remaining): FixtureClient => $client);
        $this->assertSame('api-error', $recorder->capture('quote', $this->case)['outcome']);
        $this->assertSame('api-error', $recorder->capture('quote', $this->case)['outcome']);
        $this->assertCount(1, $client->calls);
    }

    public static function textApiErrors(): iterable
    {
        foreach (['Query Error: invalid period', 'Restricted Endpoint: unavailable, contact support.'] as $message) {
            foreach (['plain' => '', 'whitespace' => " \t\r\n", 'BOM and whitespace' => " \t\xEF\xBB\xBF \r\n"] as $name => $prefix) {
                yield "$message $name" => [$prefix.$message."\nDetails"];
            }
        }
    }

    /** @throws \JsonException If fixture capture metadata cannot be encoded/decoded. */
    #[DataProvider('textApiErrors')]
    public function testTextApiErrorsAreClassifiedAndReplayedWithoutAnotherCapture(string $payload): void
    {
        $client = new FixtureClient();
        $client->payload = $payload;
        $recorder = new Recorder($this->cache, fn(int $remaining): FixtureClient => $client);
        $result = $recorder->capture('quote', $this->case);
        $this->assertSame('api-error', $result['outcome']);
        $this->assertFalse($result['cached']);
        $key = CaptureCache::key($client->urls[0]);
        $this->assertSame('api-error', $this->cache->metadata($key)['outcome']);
        $this->assertSame($payload, $this->cache->response($key)->body);

        $replay = new Recorder($this->cache, static fn(int $remaining) => throw new \LogicException('Must use the cached API error.'));
        $cached = $replay->capture('quote', $this->case, offline: true);
        $this->assertSame('api-error', $cached['outcome']);
        $this->assertTrue($cached['cached']);
        $this->assertSame($result['error'], $cached['error']);
        $this->assertSame('api-error', $this->cache->metadata($key)['outcome']);
        $this->assertCount(1, $client->calls);
        $this->assertSame(0, $replay->statistics()['calls']);
        $this->assertSame(0, $replay->statistics()['receivedBytes']);
    }

    public function testMissingOfflineRecordingNeverConstructsClient(): void
    {
        $recorder = new Recorder($this->cache, static fn(int $remaining) => throw new \LogicException('Must stay offline.'));
        $this->assertSame('missing', $recorder->capture('quote', $this->case, offline: true)['outcome']);
        $this->assertSame(0, $recorder->statistics()['calls']);
    }

    public function testBudgetStopsBeforeTheNextRequest(): void
    {
        $client = new FixtureClient();
        $recorder = new Recorder($this->cache, fn(int $remaining): FixtureClient => $client, maxBytes: strlen($client->payload));
        $this->assertSame('verified', $recorder->capture('quote', $this->case)['outcome']);
        $this->assertSame('budget-exhausted', $recorder->capture('quote-short', $this->case)['outcome']);
        $this->assertCount(1, $client->calls);
    }

    public function testInterruptedAttemptsAreNeverAutomaticallyRepeated(): void
    {
        $url = 'https://financialmodelingprep.com/stable/quote?symbol=AAPL';
        $this->cache->begin(CaptureCache::key($url), 'quote', $url);
        $recorder = new Recorder($this->cache, static fn(int $remaining) => throw new \LogicException('Must not retry interrupted attempt.'));
        $this->assertSame('incomplete', $recorder->capture('quote', $this->case)['outcome']);
        $this->assertSame(0, $recorder->statistics()['calls']);
    }
}
