<?php
declare(strict_types=1);

namespace Manhart\Fmp\Tests;

use Manhart\Fmp\Enum\Period;
use Manhart\Fmp\Enum\ReportPeriod;
use Manhart\Fmp\Enum\Timeframe;
use Manhart\Fmp\Response\FileResponse;
use Manhart\Fmp\Response\Stable\StableEndpointRegistry;
use Manhart\Fmp\Tests\Support\FixtureClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StableEndpointsTest extends TestCase
{
    public static function documentedRequests(): iterable
    {
        $requests = json_decode(file_get_contents(__DIR__.'/fixtures/documented-requests.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach ($requests as $endpoint => $request) {
            yield $endpoint => [$endpoint, $request['parameters'], $request['query']];
        }
    }

    #[DataProvider('documentedRequests')]
    public function testEveryDocumentedParameterReachesTheWire(string $endpoint, array $parameters, array $expectedQuery): void
    {
        $client = new FixtureClient();
        $class = StableEndpointRegistry::resolve($endpoint);
        $this->assertSame('stable/'.$endpoint, $class::getUrl());
        if (is_subclass_of($class, FileResponse::class)) {
            $client->payload = file_get_contents(__DIR__.'/fixtures/workbook.xlsx');
        }
        $response = $client->fetch($endpoint, ...$parameters);
        $this->assertInstanceOf($class, $response);
        $this->assertCount(1, $client->calls);
        $url = $client->urls[0];
        $this->assertSame('/stable/'.$endpoint, parse_url($url, PHP_URL_PATH));
        parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $actualQuery);
        $this->assertEquals($expectedQuery, $actualQuery);
        $this->assertStringNotContainsString('synthetic-api-key', $url);
        if ($response instanceof FileResponse) {
            $this->assertSame($client->payload, $response->getContent());
        } else {
            $this->assertCount(1, $response);
            $this->assertSame('SYNTHETIC', $response->getResponseValueAsString('symbol'));
        }
        $this->assertSame($class, StableEndpointRegistry::resolve('/stable/'.$endpoint));
    }

    #[DataProvider('documentedRequests')]
    public function testOptionalNullsAreOmitted(string $endpoint, array $parameters, array $expectedQuery): void
    {
        $class = StableEndpointRegistry::resolve($endpoint);
        $method = new \ReflectionMethod($class, 'fetch');
        $optional = [];
        foreach ($method->getParameters() as $parameter) {
            if ($parameter->getName() !== 'client' && $parameter->isOptional()) {
                $parameters[$parameter->getName()] = null;
                $optional[] = $parameter->getName();
            }
        }
        $client = new FixtureClient();
        if (is_subclass_of($class, FileResponse::class)) {
            $client->payload = file_get_contents(__DIR__.'/fixtures/workbook.xlsx');
        }
        $client->fetch($endpoint, ...$parameters);
        parse_str(parse_url($client->urls[0], PHP_URL_QUERY) ?? '', $query);
        foreach ($optional as $name) {
            $this->assertArrayNotHasKey($name, $query);
            unset($expectedQuery[$name]);
        }
        $this->assertEquals($expectedQuery, $query);
        $this->assertCount(1, $client->urls);
    }

    public function testEnumsDatesArraysAndFalseValues(): void
    {
        $client = new FixtureClient();
        $client->fetch('income-statement', symbol: 'AAPL', period: Period::Annual, limit: 0);
        $this->assertStringContainsString('period=annual', $client->urls[0]);
        $this->assertStringContainsString('limit=0', $client->urls[0]);
        $client->fetch('income-statement', symbol: 'AAPL', period: ReportPeriod::Q1);
        $this->assertStringContainsString('period=Q1', $client->urls[1]);
        $client->fetch('batch-quote', symbols: ['AAPL', 'MSFT']);
        $this->assertStringContainsString('symbols=AAPL%2CMSFT', $client->urls[2]);
        $client->fetch('technical-indicators/sma', symbol: 'AAPL', periodLength: 10, timeframe: Timeframe::OneDay, from: new \DateTimeImmutable('2026-01-02'), to: '2026-01-03');
        $this->assertStringContainsString('from=2026-01-02', $client->urls[3]);
        $this->assertStringContainsString('timeframe=1day', $client->urls[3]);
        $client->fetch('company-screener', isEtf: false, avgVolumeMoreThan: 12.5, includeAllShareClasses: true);
        $this->assertStringContainsString('isEtf=false', $client->urls[4]);
        $this->assertStringContainsString('avgVolumeMoreThan=12.5', $client->urls[4]);
        $this->assertStringContainsString('includeAllShareClasses=true', $client->urls[4]);
    }

    public function testMissingRequiredParameterDoesNotSendRequest(): void
    {
        $client = new FixtureClient();
        try {
            $client->fetch('quote');
            $this->fail('Missing symbol must be rejected.');
        } catch (\ArgumentCountError) {
            $this->assertSame([], $client->calls);
        }
    }

    public function testUnknownEndpointDoesNotSendRequest(): void
    {
        $client = new FixtureClient();
        $this->expectException(\InvalidArgumentException::class);
        $client->fetch('unsupported-endpoint');
    }
}
