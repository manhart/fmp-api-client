<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Tests;

use Manhart\Fmp\Client\FmpApiClient;
use Manhart\Fmp\Client\LegacyApiClient;
use Manhart\Fmp\Response\AbstractResponse;
use Manhart\Fmp\Response\Response;
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

    public function testSharedResponseBaseKeepsStableNormalizationSeparate(): void
    {
        $stableClient = new class extends FmpApiClient {
            public function __construct()
            {
                parent::__construct('fixture-key');
            }

            public function executeCurl(string $endpoint, array $params = []): string
            {
                return '{"symbol":"SYNTHETIC","price":123.45}';
            }
        };
        $legacyClient = new class extends LegacyApiClient {
            public function __construct()
            {
                parent::__construct('fixture-key');
            }

            public function executeCurl(string $endpoint, array $params = []): string
            {
                return '{"symbol":"SYNTHETIC","historical":[{"date":"2026-10-09","close":123.45}]}';
            }
        };
        $stable = $stableClient->getProfile('AAPL');
        $legacy = $legacyClient->getHistoricalPrice('AAPL');
        $this->assertInstanceOf(Response::class, $stable);
        $this->assertInstanceOf(AbstractResponse::class, $stable);
        $this->assertInstanceOf(AbstractResponse::class, $legacy);
        $this->assertNotInstanceOf(Response::class, $legacy);
        $this->assertSame([['symbol' => 'SYNTHETIC', 'price' => 123.45]], iterator_to_array($stable));
        $stable->rewind();
        $this->assertSame('SYNTHETIC', $stable->getResponseValueAsString('symbol'));
        $this->assertSame('SYNTHETIC', $legacy->getSymbol());
        $this->assertCount(1, $legacy);
        $this->assertSame(123.45, $legacy->getClose());
        $this->assertSame('2026-10-09', $legacy->getDate()->format('Y-m-d'));
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
        $this->assertCount(53, $methods);
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
