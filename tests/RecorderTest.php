<?php
declare(strict_types=1);

namespace Manhart\Fmp\Tests;

use Manhart\Fmp\Recording\CaptureCache;
use Manhart\Fmp\Recording\Recorder;
use Manhart\Fmp\HttpResponse;
use Manhart\Fmp\Tests\Support\FixtureClient;
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
