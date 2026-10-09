<?php
declare(strict_types=1);

namespace Manhart\Fmp\Tests;

use Manhart\Fmp\Client\FmpApiClient;
use Manhart\Fmp\Response\Stable\StableEndpointRegistry;
use Manhart\Fmp\Tests\Support\FixtureClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ClientShortcutsTest extends TestCase
{
    public static function shortcuts(): iterable
    {
        $root = dirname(__DIR__);
        $shortcuts = json_decode(file_get_contents($root.'/resources/client-shortcuts.json'), true, 512, JSON_THROW_ON_ERROR);
        $requests = json_decode(file_get_contents(__DIR__.'/fixtures/documented-requests.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach ($shortcuts as $method => $endpoint) {
            yield $method => [$method, $endpoint, $requests[$endpoint]['parameters'], $requests[$endpoint]['query']];
        }
    }

    #[DataProvider('shortcuts')]
    public function testShortcutForwardsEveryDocumentedParameterInOneRequest(string $method, string $endpoint, array $parameters, array $expectedQuery): void
    {
        $client = new FixtureClient();
        $response = $client->$method(...$parameters);
        $this->assertInstanceOf(StableEndpointRegistry::resolve($endpoint), $response);
        $this->assertCount(1, $client->calls);
        $this->assertSame('/stable/'.$endpoint, parse_url($client->urls[0], PHP_URL_PATH));
        parse_str(parse_url($client->urls[0], PHP_URL_QUERY) ?? '', $actualQuery);
        $this->assertEquals($expectedQuery, $actualQuery);
        $this->assertStringNotContainsString('synthetic-api-key', $client->urls[0]);
    }

    #[DataProvider('shortcuts')]
    public function testShortcutKeepsEndpointSignatureAndOmitsOptionalNulls(string $method, string $endpoint, array $parameters, array $expectedQuery): void
    {
        $shortcut = new \ReflectionMethod(FmpApiClient::class, $method);
        $factory = new \ReflectionMethod(StableEndpointRegistry::resolve($endpoint), 'fetch');
        $factoryParameters = array_slice($factory->getParameters(), 1);
        $this->assertCount(count($factoryParameters), $shortcut->getParameters());
        foreach ($shortcut->getParameters() as $index => $parameter) {
            $expected = $factoryParameters[$index];
            $this->assertSame($expected->getName(), $parameter->getName());
            $this->assertSame((string)$expected->getType(), (string)$parameter->getType());
            $this->assertSame($expected->isOptional(), $parameter->isOptional());
            if ($parameter->isOptional()) {
                $this->assertSame($expected->getDefaultValue(), $parameter->getDefaultValue());
                $parameters[$parameter->getName()] = null;
                unset($expectedQuery[$parameter->getName()]);
            }
        }
        $this->assertSame(StableEndpointRegistry::resolve($endpoint), (string)$shortcut->getReturnType());
        $client = new FixtureClient();
        $client->$method(...$parameters);
        parse_str(parse_url($client->urls[0], PHP_URL_QUERY) ?? '', $actualQuery);
        $this->assertEquals($expectedQuery, $actualQuery);
        $this->assertCount(1, $client->calls);
    }

    public function testPublicSurfaceAndCuratedNames(): void
    {
        $shortcuts = json_decode(file_get_contents(__DIR__.'/../resources/client-shortcuts.json'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(84, $shortcuts);
        $methods = [];
        foreach ((new \ReflectionClass(FmpApiClient::class))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() === FmpApiClient::class) {
                $methods[] = $method->getName();
            }
        }
        $this->assertEqualsCanonicalizing(['fetch', ...array_keys($shortcuts)], $methods);
        $expected = [
            'searchSECCompaniesByName' => 'sec-filings-company-search/name',
            'searchIndustryClassification' => 'industry-classification-search',
            'getExecutiveCompensation' => 'governance-executive-compensation',
            'getBatchMarketCapitalization' => 'market-capitalization-batch',
            'getHistoricalPrices' => 'historical-price-eod/full',
            'getHistoricalPricesLight' => 'historical-price-eod/light',
            'getDividendAdjustedPrices' => 'historical-price-eod/dividend-adjusted',
            'getNonSplitAdjustedPrices' => 'historical-price-eod/non-split-adjusted',
            'getSymbolChanges' => 'symbol-change',
            'getExchangeHolidays' => 'holidays-by-exchange',
        ];
        $this->assertEquals($expected, array_intersect_key($shortcuts, $expected));
    }

    public function testSingleQuoteRejectsSymbolArraysBeforeAnyRequest(): void
    {
        $client = new FixtureClient();
        try {
            $client->getQuote(['AAPL', 'MSFT']);
            $this->fail('Single-symbol quote must reject arrays.');
        } catch (\TypeError) {
            $this->assertSame([], $client->calls);
        }
    }

    public function testBatchQuoteAndValueParametersReachTheWire(): void
    {
        $client = new FixtureClient();
        $client->getBatchQuote(['AAPL', 'MSFT']);
        $this->assertSame('https://financialmodelingprep.com/stable/batch-quote?symbols=AAPL%2CMSFT', $client->urls[0]);
        $client->getCompanyScreener(isEtf: false, avgVolumeMoreThan: 12.5);
        $this->assertStringContainsString('isEtf=false', $client->urls[1]);
        $this->assertStringContainsString('avgVolumeMoreThan=12.5', $client->urls[1]);
        $client->getIncomeStatement('AAPL', limit: 0, period: \Manhart\Fmp\Enum\Period::Annual);
        $this->assertStringContainsString('limit=0', $client->urls[2]);
        $this->assertStringContainsString('period=annual', $client->urls[2]);
        $client->getHistoricalPrices('AAPL', from: new \DateTimeImmutable('2026-01-02'), to: '2026-01-03');
        $this->assertStringContainsString('from=2026-01-02&to=2026-01-03', $client->urls[3]);
        $this->assertCount(4, $client->calls);
    }
}
