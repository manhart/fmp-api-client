<?php
declare(strict_types=1);

namespace Manhart\Fmp\Tests;

use Manhart\Fmp\Recording\RequestPlanner;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RequestPlannerTest extends TestCase
{
    public static function correctedCases(): iterable
    {
        yield 'classification search' => [
            'industry-classification-search',
            'https://financialmodelingprep.com/stable/industry-classification-search?symbol=AAPL',
        ];
        yield 'ticker search' => [
            'legacy:searchTicker',
            'https://financialmodelingprep.com/api/v3/search-ticker?query=AAPL&limit=1&exchange=NASDAQ',
        ];
        yield 'known legacy CIK' => [
            'legacy:getCIK',
            'https://financialmodelingprep.com/api/v3/cik/0001067983',
        ];
    }

    #[DataProvider('correctedCases')]
    public function testCuratedCasesProduceExpectedUrlsWithoutRequests(string $id, string $expectedUrl): void
    {
        $root = dirname(__DIR__);
        if (str_starts_with($id, 'legacy:')) {
            $cases = json_decode(file_get_contents($root.'/resources/legacy-cases.json'), true, 512, JSON_THROW_ON_ERROR);
            $case = $cases[substr($id, 7)];
        } else {
            $manifest = json_decode(file_get_contents($root.'/resources/endpoints.json'), true, 512, JSON_THROW_ON_ERROR);
            $case = $manifest['endpoints'][$id];
        }
        $planner = new RequestPlanner();
        $this->assertSame($expectedUrl, $planner->plan($id, $case));
        $this->assertSame(0, $planner->getRequests());
    }
}
