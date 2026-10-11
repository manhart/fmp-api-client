<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Tests;

use DateTimeImmutable;
use Manhart\Fmp\Response\AbstractResponse;
use Manhart\Fmp\Response\Legacy\FinancialStatementSymbolLists;
use Manhart\Fmp\Response\Legacy\HistoricalPrice;
use Manhart\Fmp\Response\Legacy\Profile;
use Manhart\Fmp\Response\Legacy\StockDividend;
use Manhart\Fmp\Response\Legacy\StockSplit;
use Manhart\Fmp\Tests\Support\LegacyFixtureClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LegacyGettersTest extends TestCase
{
    /** Fixed field/getter cases remain independent of the current method implementations. */
    public static function stringGetters(): iterable
    {
        $classes = [
            'BalanceSheetStatement' => [
                ['getSymbol', 'symbol', false],
                ['getReportedCurrency', 'reportedCurrency', false],
                ['getCIK', 'cik', true],
                ['getPeriod', 'period', false],
                ['getLink', 'link', false],
                ['getFinalLink', 'finalLink', false],
            ],
            'CIK' => [
                ['getName', 'name', false],
                ['getCIK', 'cik', false],
            ],
            'CUSIP' => [
                ['getCompany', 'company', false],
                ['getTicker', 'ticker', false],
                ['getCUSIP', 'cusip', false],
            ],
            'CashflowStatement' => [
                ['getSymbol', 'symbol', false],
                ['getReportedCurrency', 'reportedCurrency', false],
                ['getCIK', 'cik', true],
                ['getPeriod', 'period', false],
                ['getLink', 'link', false],
                ['getFinalLink', 'finalLink', false],
            ],
            'CommitmentOfTradersReportList' => [
                ['getTradingSymbol', 'trading_symbol', false],
                ['getShortName', 'short_name', false],
            ],
            'CompanyCoreInformation' => [
                ['getSymbol', 'symbol', false],
                ['getCIK', 'cik', true],
                ['getExchange', 'exchange', false],
                ['getSICGroup', 'sicGroup', false],
                ['getSICDescription', 'sicDescription', false],
                ['getStateLocation', 'stateLocation', false],
                ['getStateOfIncorporation', 'stateOfIncorporation', false],
                ['getFiscalYearEnd', 'fiscalYearEnd', false],
                ['getBusinessAddress', 'businessAddress', false],
                ['getMailingAddress', 'mailingAddress', false],
                ['getTaxIdentificationNumber', 'taxIdentificationNumber', false],
                ['getRegistrantName', 'registrantName', false],
            ],
            'CompanyNotes' => [
                ['getCIK', 'cik', false],
                ['getSymbol', 'symbol', false],
                ['getTitle', 'title', false],
                ['getExchange', 'exchange', false],
            ],
            'DelistedCompanies' => [
                ['getSymbol', 'symbol', false],
                ['getCompanyName', 'companyName', false],
                ['getExchange', 'exchange', false],
            ],
            'EmployeeCount' => [
                ['getCIK', 'cik', false],
                ['getSymbol', 'symbol', false],
                ['getCompanyName', 'companyName', false],
                ['getFormType', 'formType', false],
                ['getSource', 'source', false],
            ],
            'ExecutiveCompensation' => [
                ['getCIK', 'cik', false],
                ['getSymbol', 'symbol', false],
                ['getCompanyName', 'companyName', false],
                ['getIndustryTitle', 'industryTitle', false],
                ['getNameAndPosition', 'nameAndPosition', false],
                ['getSourceUrl', 'url', true],
            ],
            'ExecutiveCompensationBenchmark' => [
                ['getIndustryTitle', 'industryTitle', false],
            ],
            'FinancialStatementSymbolLists' => [
                ['getSymbol', 'symbol', false],
            ],
            'Grade' => [
                ['getSymbol', 'symbol', false],
                ['getGradingCompany', 'gradingCompany', false],
                ['getPreviousGrade', 'previousGrade', false],
                ['getNewGrade', 'newGrade', false],
            ],
            'HistoricalPrice' => [
                ['getLabel', 'label', false],
            ],
            'IncomeStatement' => [
                ['getSymbol', 'symbol', false],
                ['getReportedCurrency', 'reportedCurrency', false],
                ['getCIK', 'cik', true],
                ['getPeriod', 'period', false],
                ['getLink', 'link', false],
                ['getFinalLink', 'finalLink', false],
            ],
            'PriceTarget' => [
                ['getNewsURL', 'newsURL', false],
                ['getNewsTitle', 'newsTitle', false],
                ['getAnalystName', 'analystName', false],
                ['getNewsPublisher', 'newsPublisher', false],
                ['getNewsBaseURL', 'newsBaseURL', false],
                ['getAnalystCompany', 'analystCompany', false],
            ],
            'Profile' => [
                ['getSymbol', 'symbol', false],
                ['getRange', 'range', false],
                ['getCompanyName', 'companyName', false],
                ['getWebsite', 'website', false],
                ['getDescription', 'description', false],
                ['getCountry', 'country', false],
                ['getFullTimeEmployees', 'fullTimeEmployees', false],
                ['getPhone', 'phone', false],
                ['getAddress', 'address', false],
                ['getCity', 'city', false],
                ['getZip', 'zip', false],
                ['getState', 'state', false],
                ['getExchange', 'exchange', false],
                ['getExchangeShortName', 'exchangeShortName', false],
                ['getIndustry', 'industry', false],
                ['getCIK', 'cik', false],
                ['getISIN', 'isin', false],
                ['getCUSIP', 'cusip', false],
                ['getSector', 'sector', false],
                ['getCurrency', 'currency', false],
                ['getCEO', 'ceo', false],
                ['getImage', 'image', false],
                ['ipoDate', 'ipoDate', false],
            ],
            'Quote' => [
                ['getSymbol', 'symbol', false],
                ['getName', 'name', false],
                ['getExchange', 'exchange', false],
            ],
            'QuoteShort' => [
                ['getSymbol', 'symbol', false],
            ],
            'Search' => [
                ['getSymbol', 'symbol', false],
                ['getName', 'name', false],
                ['getCurrency', 'currency', false],
                ['getStockExchange', 'stockExchange', false],
                ['getExchangeShortName', 'exchangeShortName', false],
            ],
            'ShareFloat' => [
                ['getSymbol', 'symbol', false],
                ['getSource', 'source', false],
            ],
            'StockDividend' => [
                ['getLabel', 'label', false],
            ],
            'StockDividendCalendar' => [
                ['getSymbol', 'symbol', false],
                ['getLabel', 'label', false],
            ],
            'StockList' => [
                ['getSymbol', 'symbol', false],
                ['getExchange', 'exchange', false],
                ['getExchangeShortName', 'exchangeShortName', false],
                ['getName', 'name', false],
                ['getType', 'type', false],
            ],
            'StockScreener' => [
                ['getSymbol', 'symbol', false],
                ['getCompanyName', 'companyName', false],
                ['getSector', 'sector', false],
                ['getIndustry', 'industry', false],
                ['getExchange', 'exchange', false],
                ['getExchangeShortName', 'exchangeShortName', false],
                ['getCountry', 'country', false],
            ],
            'StockSplit' => [
                ['getLabel', 'label', false],
            ],
            'StockSplitCalendar' => [
                ['getSymbol', 'symbol', false],
                ['getLabel', 'label', false],
            ],
            'SymbolChange' => [
                ['getName', 'name', false],
                ['getOldSymbol', 'oldSymbol', false],
                ['getNewSymbol', 'newSymbol', false],
            ],
            'UpgradesDowngrades' => [
                ['getNewGrade', 'newGrade', false],
                ['getPreviousGrade', 'previousGrade', false],
                ['getGradingCompany', 'gradingCompany', false],
                ['getAction', 'action', false],
                ['getNewsBaseURL', 'newsBaseURL', false],
                ['getNewsPublisher', 'newsPublisher', false],
                ['getNewsURL', 'newsURL', false],
                ['getNewsTitle', 'newsTitle', false],
            ],
        ];

        foreach ($classes as $name => $methods) {
            $class = 'Manhart\\Fmp\\Response\\Legacy\\'.$name;
            foreach ($methods as $arguments) {
                yield $name.'::'.$arguments[0] => [$class, ...$arguments];
            }
        }
    }

    /** @param class-string<AbstractResponse> $class */
    #[DataProvider('stringGetters')]
    public function testStringGettersHandleMissingDataAndScalarValues(string $class, string $method, string $field, bool $zeroToNull): void
    {
        $this->assertNull((new $class([]))->$method(), 'Empty response');
        $this->assertNull(self::rowResponse($class, [])->$method(), 'Missing field');

        $values = [
            [null, null],
            ['', null],
            ['000123', '000123'],
            ['  Example  ', '  Example  '],
            [164000, '164000'],
            [0, $zeroToNull ? null : '0'],
            [false, null],
            [true, '1'],
            [[], null],
        ];
        foreach ($values as [$value, $expected]) {
            $response = self::rowResponse($class, [$field => $value]);
            $this->assertSame($expected, $response->$method());
            $this->assertSame($value, $response->getResponseValue($field), 'Raw values stay unchanged');
        }

        $response = self::rowResponse($class, [$field => 'Example']);
        $response->next();
        $this->assertFalse($response->valid());
        $this->assertNull($response->$method(), 'Exhausted iterator');
        $response->rewind();
        $this->assertSame('Example', $response->$method());
    }

    public static function dateGetters(): iterable
    {
        $classes = [
            'BalanceSheetStatement' => [
                ['getDate', 'date'],
            ],
            'CashflowStatement' => [
                ['getDate', 'date'],
            ],
            'DelistedCompanies' => [
                ['getDelistedDate', 'delistedDate'],
            ],
            'EmployeeCount' => [
                ['getAcceptanceTime', 'acceptanceTime'],
                ['getPeriodOfReport', 'periodOfReport'],
                ['getFilingDate', 'filingDate'],
            ],
            'ExecutiveCompensation' => [
                ['getFillingDate', 'fillingDate'],
            ],
            'IncomeStatement' => [
                ['getDate', 'date'],
            ],
            'SymbolChange' => [
                ['getDate', 'date'],
            ],
        ];

        foreach ($classes as $name => $methods) {
            $class = 'Manhart\\Fmp\\Response\\Legacy\\'.$name;
            foreach ($methods as $arguments) {
                yield $name.'::'.$arguments[0] => [$class, ...$arguments];
            }
        }
    }

    /** @param class-string<AbstractResponse> $class */
    #[DataProvider('dateGetters')]
    public function testDateGettersHandleMissingInvalidAndValidValues(string $class, string $method, string $field): void
    {
        $this->assertNull((new $class([]))->$method());
        $this->assertNull(self::rowResponse($class, [])->$method());
        foreach ([null, '', 'not-a-date', []] as $value) {
            $this->assertNull(self::rowResponse($class, [$field => $value])->$method());
        }

        $date = new DateTimeImmutable('2024-02-29');
        $this->assertSame($date, self::rowResponse($class, [$field => $date])->$method());
        $this->assertSame('2024-02-29', self::rowResponse($class, [$field => '2024-02-29'])->$method()->format('Y-m-d'));
        foreach ([0, '0'] as $timestamp) {
            $this->assertSame(0, self::rowResponse($class, [$field => $timestamp])->$method()->getTimestamp());
        }

        $response = self::rowResponse($class, [$field => '2024-02-29']);
        $response->next();
        $this->assertFalse($response->valid());
        $this->assertNull($response->$method());
    }

    /**
     * Build the established Legacy payload shape, including historical and symbol-list wrappers.
     *
     * @param class-string<AbstractResponse> $class
     */
    private static function rowResponse(string $class, array $row): AbstractResponse
    {
        if (in_array($class, [HistoricalPrice::class, StockDividend::class, StockSplit::class], true)) {
            return new $class(['symbol' => 'DEMO', 'historical' => [$row]]);
        }
        if ($class === FinancialStatementSymbolLists::class) {
            return new $class([$row['symbol'] ?? null]);
        }
        return new $class([$row]);
    }

    public static function imageFlags(): iterable
    {
        yield 'null' => [null, false];
        yield 'empty' => ['', false];
        yield 'true' => [true, true];
        yield 'false' => [false, false];
        yield 'numeric true' => [1, true];
        yield 'numeric false' => [0, false];
        yield 'CSV true' => ['true', true];
        yield 'CSV false' => ['false', false];
        yield 'CSV one' => ['1', true];
        yield 'CSV zero' => ['0', false];
        yield 'yes' => ['yes', true];
        yield 'no' => ['no', false];
        yield 'unknown' => ['unknown', false];
        yield 'invalid' => [[], false];
    }

    #[DataProvider('imageFlags')]
    public function testDefaultImageUsesBooleanConversion(mixed $value, bool $expected): void
    {
        $response = new Profile([['defaultImage' => $value]]);
        $this->assertSame($expected, $response->getDefaultImage());
        $this->assertSame($value, $response->getResponseValue('defaultImage'));
    }

    public function testDefaultImageFallbackAlsoAppliesWithoutACurrentRow(): void
    {
        $this->assertFalse((new Profile([]))->getDefaultImage());
        $response = new Profile([[]]);
        $this->assertFalse($response->getDefaultImage());
        $response->next();
        $this->assertFalse($response->getDefaultImage());
    }

    public static function profilePayloads(): iterable
    {
        yield 'JSON' => [
            '[{"symbol":"DEMO","companyName":"Example","fullTimeEmployees":164000,"defaultImage":true}]',
            164000,
            true,
        ];
        yield 'CSV' => [
            "symbol,companyName,fullTimeEmployees,defaultImage\nDEMO,Example,164000,true\n",
            '164000',
            'true',
        ];
    }

    #[DataProvider('profilePayloads')]
    public function testProfileFactoriesExposeTypedValuesWithoutChangingRawPayload(string $payload, int|string $employees, bool|string $image): void
    {
        $client = new LegacyFixtureClient();
        $client->payload = $payload;
        $response = $client->getProfile('DEMO');

        $this->assertSame('Example', $response->getCompanyName());
        $this->assertSame('164000', $response->getFullTimeEmployees());
        $this->assertTrue($response->getDefaultImage());
        $this->assertSame($employees, $response->getResponseValue('fullTimeEmployees'));
        $this->assertSame($image, $response->getResponseValue('defaultImage'));
        $this->assertCount(1, $client->calls);
    }

    public function testEmptyProfileFromClientDoesNotThrowOnGetterAccess(): void
    {
        $client = new LegacyFixtureClient();
        $client->payload = '[]';
        $response = $client->getProfile('XYZ');

        $this->assertNull($response->getCompanyName());
        $this->assertNull($response->getSymbol());
        $this->assertNull($response->getFullTimeEmployees());
        $this->assertFalse($response->getDefaultImage());
        $this->assertCount(1, $client->calls);
    }

    public function testInheritedDirectoryGettersAcceptEmptyData(): void
    {
        foreach ([
            'AvailableTradedList' => 'getExchange',
            'AvailableEuronext' => 'getStockExchange',
            'EtfList' => 'getExchange',
            'ExchangeSymbols' => 'getExchange',
        ] as $name => $exchangeMethod) {
            $class = 'Manhart\\Fmp\\Response\\Legacy\\'.$name;
            $response = new $class([]);
            $this->assertNull($response->getSymbol());
            $this->assertNull($response->getName());
            $this->assertNull($response->$exchangeMethod());
        }
    }
}
