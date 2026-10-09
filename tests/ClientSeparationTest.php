<?php
declare(strict_types=1);

namespace Manhart\Fmp\Tests;

use Manhart\Fmp\Client\FmpApiClient;
use Manhart\Fmp\Client\LegacyApiClient;
use Manhart\Fmp\Response\Stable\Profile;
use Manhart\Fmp\Tests\Support\FixtureClient;
use Manhart\Fmp\Tests\Support\LegacyFixtureClient;
use PHPUnit\Framework\TestCase;

final class ClientSeparationTest extends TestCase
{
    public function testSameMethodNameSelectsTheClientsOwnApi(): void
    {
        $stable = new FixtureClient();
        $legacy = new LegacyFixtureClient();
        $this->assertInstanceOf(Profile::class, $stable->getProfile('AAPL'));
        $this->assertInstanceOf(\Manhart\Fmp\Response\Legacy\Profile::class, $legacy->getProfile('AAPL'));
        $this->assertSame('https://financialmodelingprep.com/stable/profile?symbol=AAPL', $stable->urls[0]);
        $this->assertSame('https://financialmodelingprep.com/api/v3/profile/AAPL', $legacy->urls[0]);
        $this->assertCount(1, $stable->calls);
        $this->assertCount(1, $legacy->calls);
    }

    public function testAllStableEntryPointsReturnTheSameTypedResponse(): void
    {
        $client = new FixtureClient();
        foreach ([
            $client->getProfile('AAPL'),
            $client->fetch('profile', symbol: 'AAPL'),
            Profile::fetch($client, symbol: 'AAPL'),
        ] as $response) {
            $this->assertInstanceOf(Profile::class, $response);
            $this->assertSame('SYNTHETIC', $response->getResponseValueAsString('symbol'));
        }
        $this->assertSame([
            ['stable/profile', ['symbol' => 'AAPL']],
            ['stable/profile', ['symbol' => 'AAPL']],
            ['stable/profile', ['symbol' => 'AAPL']],
        ], $client->calls);
    }

    public function testLegacyMethodSurfaceIsRetainedWithoutStableAliases(): void
    {
        $cases = json_decode(file_get_contents(__DIR__.'/../resources/legacy-cases.json'), true, 512, JSON_THROW_ON_ERROR);
        $methods = [];
        foreach ((new \ReflectionClass(LegacyApiClient::class))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() === LegacyApiClient::class) {
                $methods[] = $method->getName();
            }
        }
        $this->assertCount(38, $methods);
        $this->assertEqualsCanonicalizing(array_keys($cases), $methods);
        $this->assertFalse(method_exists(LegacyApiClient::class, 'fetch'));
        $this->assertFalse(method_exists(FmpApiClient::class, 'getStable'));
        $this->assertFalse(method_exists(FmpApiClient::class, 'getStockDividend'));
        $this->assertFalse(method_exists(FmpApiClient::class, 'getCompanyCoreInformation'));
    }

    public function testLegacyClientAlsoRequiresAnExplicitKey(): void
    {
        $this->expectException(\ArgumentCountError::class);
        new LegacyApiClient();
    }

    public function testLegacyClientRejectsAnEmptyKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new LegacyApiClient('');
    }
}
