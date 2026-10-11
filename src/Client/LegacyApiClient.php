<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Client;

use Manhart\Fmp\Response\Legacy\AnalystEstimates;
use Manhart\Fmp\Response\Legacy\AvailableEuronext;
use Manhart\Fmp\Response\Legacy\AvailableTradedList;
use Manhart\Fmp\Response\Legacy\BalanceSheetStatement;
use Manhart\Fmp\Response\Legacy\BalanceSheetStatementBulk;
use Manhart\Fmp\Response\Legacy\CashflowStatement;
use Manhart\Fmp\Response\Legacy\CashflowStatementBulk;
use Manhart\Fmp\Response\Legacy\CIK;
use Manhart\Fmp\Response\Legacy\CIKList;
use Manhart\Fmp\Response\Legacy\CIKSearch;
use Manhart\Fmp\Response\Legacy\CommitmentOfTradersReportList;
use Manhart\Fmp\Response\Legacy\CompanyCoreInformation;
use Manhart\Fmp\Response\Legacy\CompanyNotes;
use Manhart\Fmp\Response\Legacy\CompanyOutlook;
use Manhart\Fmp\Response\Legacy\CUSIP;
use Manhart\Fmp\Response\Legacy\DelistedCompanies;
use Manhart\Fmp\Response\Legacy\EmployeeCount;
use Manhart\Fmp\Response\Legacy\EtfList;
use Manhart\Fmp\Response\Legacy\ExchangeSymbols;
use Manhart\Fmp\Response\Legacy\ExecutiveCompensation;
use Manhart\Fmp\Response\Legacy\ExecutiveCompensationBenchmark;
use Manhart\Fmp\Response\Legacy\FinancialStatementSymbolLists;
use Manhart\Fmp\Response\Legacy\FinancialGrowth;
use Manhart\Fmp\Response\Legacy\FinancialGrowthBulk;
use Manhart\Fmp\Response\Legacy\Grade;
use Manhart\Fmp\Response\Legacy\HistoricalEmployeeCount;
use Manhart\Fmp\Response\Legacy\HistoricalPrice;
use Manhart\Fmp\Response\Legacy\IncomeStatement;
use Manhart\Fmp\Response\Legacy\IncomeStatementBulk;
use Manhart\Fmp\Response\Legacy\KeyMetrics;
use Manhart\Fmp\Response\Legacy\KeyMetricsBulk;
use Manhart\Fmp\Response\Legacy\KeyMetricsTTM;
use Manhart\Fmp\Response\Legacy\KeyMetricsTTMBulk;
use Manhart\Fmp\Response\Legacy\PriceTarget;
use Manhart\Fmp\Response\Legacy\Profile;
use Manhart\Fmp\Response\Legacy\Quote;
use Manhart\Fmp\Response\Legacy\QuoteShort;
use Manhart\Fmp\Response\Legacy\Ratios;
use Manhart\Fmp\Response\Legacy\RatiosBulk;
use Manhart\Fmp\Response\Legacy\RatiosTTM;
use Manhart\Fmp\Response\Legacy\RatiosTTMBulk;
use Manhart\Fmp\Response\Legacy\Search;
use Manhart\Fmp\Response\Legacy\SearchName;
use Manhart\Fmp\Response\Legacy\SearchTicker;
use Manhart\Fmp\Response\Legacy\ShareFloat;
use Manhart\Fmp\Response\Legacy\StockDividend;
use Manhart\Fmp\Response\Legacy\StockDividendCalendar;
use Manhart\Fmp\Response\Legacy\StockList;
use Manhart\Fmp\Response\Legacy\StockScreener;
use Manhart\Fmp\Response\Legacy\StockSplit;
use Manhart\Fmp\Response\Legacy\StockSplitCalendar;
use Manhart\Fmp\Response\Legacy\SymbolChange;
use Manhart\Fmp\Response\Legacy\UpgradesDowngrades;

/** V3/V4 compatibility surface; argument contracts and response shapes differ from Stable. */
class LegacyApiClient extends AbstractApiClient
{
    /**
     * Search over 70,000 symbols by symbol name or company name, including cryptocurrencies, forex, stocks, etf, and other financial instruments.
     *
     * @param string $query The query to search for.
     * @param int|null $limit The maximum amount of results to return.
     * @param string|null $exchange The short exchange name to search in.
     *
     * @link https://site.financialmodelingprep.com/developer/docs#general-search-company-search
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function search(string $query, ?int $limit = null, ?string $exchange = null): Search
    {
        return Search::create($this, query: $query, limit: $limit, exchange: $exchange);
    }

    /**
     * Find ticker symbols and exchanges for both equity securities and exchange-traded funds (ETFs) by searching with the company name or ticker symbol.
     * @link https://site.financialmodelingprep.com/developer/docs#ticker-search-company-search
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function searchTicker(string $query, ?int $limit = null, ?string $exchange = null): SearchTicker
    {
        return SearchTicker::create($this, query: $query, limit: $limit, exchange: $exchange);
    }

    /**
     * Find ticker symbols and exchange information for equity securities and exchange-traded funds (ETFs) by searching with the company name.
     * @link https://site.financialmodelingprep.com/developer/docs#name-search-company-search
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function searchName(string $query, ?int $limit = null, ?string $exchange = null): SearchName
    {
        return SearchName::create($this, query: $query, limit: $limit, exchange: $exchange);
    }

    /**
     * Discover CIK numbers for SEC-registered entities with our CIK Name Search.
     *
     * @param string $companyName The name of the company to search for.
     *
     * @link https://site.financialmodelingprep.com/developer/docs#cik-name-search-company-search
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function searchCIKByName(string $companyName): CIKSearch
    {
        return CIKSearch::create($this, $companyName);
    }

    /**
     * Look up the SEC registrant; integer CIK values are padded to ten digits.
     *
     * @param string|int $CIK Pass a string to retain an already formatted SEC identifier.
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getCIK(string|int $CIK): CIK
    {
        if(is_int($CIK)) {
            // format CIK as string
            $CIK = \str_pad((string)$CIK, 10, '0', \STR_PAD_LEFT);
        }
        return CIK::create($this, $CIK);
    }

    /**
     * Resolve a security from its CUSIP identifier, preserving leading zeroes.
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getCUSIP(string $CUSIP): CUSIP
    {
        return CUSIP::create($this, $CUSIP);
    }

    /** @throws \JsonException If parameter encoding or cached transport metadata fails. */
    public function getStockList(): StockList
    {
        return StockList::create($this);
    }

    /**
     * Our Exchange Traded Fund Search makes it easy to find the symbol for any ETF you're looking for. Enter the ETF's name, and we'll
     * return the symbol, name, and price.
     *
     * @link https://site.financialmodelingprep.com/developer/docs#exchange-traded-fund-search-stock-list
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getEtfList(): EtfList
    {
        return EtfList::create($this);
    }

    /**
     * Discover all companies with financial statements available on our API. Our comprehensive list covers major exchanges such as the NYSE and NASDAQ,
     * as well as international exchanges. This list is regularly updated, so you can always find the information you need.
     *
     * @link https://site.financialmodelingprep.com/developer/docs#statement-symbols-list-stock-list
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getFinancialStatementSymbolLists(): FinancialStatementSymbolLists
    {
        return FinancialStatementSymbolLists::create($this);
    }

    /**
     * Discover all actively traded stocks with our Tradable Search feature. This comprehensive list includes over 70,000 stocks, with symbol, name, price, and exchange information for each company.
     *
     * @link https://site.financialmodelingprep.com/developer/docs#tradable-search-stock-list
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getAvailableTradedList(): AvailableTradedList
    {
        return AvailableTradedList::create($this);
    }

    /**
     * The Commitment of Traders Report is a weekly report from the Commodity Futures Trading Commission (CFTC) that provides insights into the
     * positions of market participants in various markets. Our Commitment of Traders Report tool makes it easy to access and analyze this valuable
     * data, helping you to make more informed trading decisions.
     *
     * @link https://site.financialmodelingprep.com/developer/docs#commitment-of-traders-report-stock-list
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getCommitmentOfTradersReportList(): CommitmentOfTradersReportList
    {
        return CommitmentOfTradersReportList::create($this);
    }

    /** @throws \JsonException If parameter encoding or cached transport metadata fails. */
    public function getCIKList(): CIKList
    {
        return CIKList::create($this);
    }

    /**
     * Find all symbols for stocks traded on Euronext exchanges with our comprehensive list. Euronext is one of the largest exchanges in Europe, and our
     * list includes stocks from a wide range of industries.
     *
     * @link https://site.financialmodelingprep.com/developer/docs#euronext-symbols-stock-list
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getAvailableEuronextSymbols(): AvailableEuronext
    {
        return AvailableEuronext::create($this);
    }

    /**
     * Stay up-to-date on the latest symbol changes with our easy-to-use tool. Track symbol changes due to mergers, acquisitions, stock splits, and name changes.
     *
     * @link https://site.financialmodelingprep.com/developer/docs#symbol-changes-stock-list
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getSymbolChanges(): SymbolChange
    {
        return SymbolChange::create($this);
    }

    /** @throws \JsonException If parameter encoding or cached transport metadata fails. */
    public function getExchangeSymbols(string $exchange): ExchangeSymbols
    {
        return ExchangeSymbols::create($this, $exchange);
    }

    /** @throws \JsonException If parameter encoding or cached transport metadata fails. */
    public function getProfile(string $symbol): Profile
    {
        return Profile::create($this, $symbol);
    }

    /** @throws \JsonException If parameter encoding or cached transport metadata fails. */
    public function getExecutiveCompensation(string $symbol): ExecutiveCompensation
    {
        return ExecutiveCompensation::create($this, symbol: $symbol);
    }

    /** @throws \JsonException If parameter encoding or cached transport metadata fails. */
    public function getExecutiveCompensationBenchmark(int $year): ExecutiveCompensationBenchmark
    {
        return ExecutiveCompensationBenchmark::create($this, year: $year);
    }

    /** @throws \JsonException If parameter encoding or cached transport metadata fails. */
    public function getCompanyNotes(string $symbol): CompanyNotes
    {
        return CompanyNotes::create($this, symbol: $symbol);
    }

    /** @throws \JsonException If parameter encoding or cached transport metadata fails. */
    public function getHistoricalEmployeeCount(string $symbol): HistoricalEmployeeCount
    {
        return HistoricalEmployeeCount::create($this, symbol: $symbol);
    }

    /** @throws \JsonException If parameter encoding or cached transport metadata fails. */
    public function getEmployeeCount(string $symbol): EmployeeCount
    {
        return EmployeeCount::create($this, symbol: $symbol);
    }

    /**
     * Request companies matching named provider filters.
     *
     * @param mixed ...$params Named query filters, e.g. marketCapMoreThan or exchange.
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getStockScreener(...$params): StockScreener
    {
        return StockScreener::create($this, ...$params);
    }

    /** @throws \JsonException If parameter encoding or cached transport metadata fails. */
    public function getGrade(string $symbol, ?int $limit = null): Grade
    {
        return Grade::create($this, $symbol, limit: $limit);
    }

    /**
     * Request share float for a symbol, or the all-symbol route when the symbol is null.
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getShareFloat(?string $symbol = null): ShareFloat
    {
        return ($symbol) ? ShareFloat::create($this, symbol: $symbol) : ShareFloat::create($this, 'all');
    }

    /**
     * Request dividend events and unwrap the Legacy symbol/historical response.
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getStockDividend(string $symbol): StockDividend
    {
        return StockDividend::create($this, $symbol);
    }

    /**
     * Request dividend events; omitted dates use provider defaults.
     *
     * @param \DateTimeInterface|null $from Start calendar date; only YYYY-MM-DD is sent.
     * @param \DateTimeInterface|null $to End calendar date; only YYYY-MM-DD is sent.
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getStockDividendCalendar(?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): StockDividendCalendar
    {
        $from_string = $from?->format('Y-m-d');
        $to_string = $to?->format('Y-m-d');
        return StockDividendCalendar::create($this, from: $from_string, to: $to_string);
    }

    /**
     * Request split events and unwrap the Legacy symbol/historical response.
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getStockSplit(string $symbol): StockSplit
    {
        return StockSplit::create($this, $symbol);
    }

    /**
     * Request split events; omitted dates use provider defaults.
     *
     * @param \DateTimeInterface|null $from Start calendar date; only YYYY-MM-DD is sent.
     * @param \DateTimeInterface|null $to End calendar date; only YYYY-MM-DD is sent.
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getStockSplitCalendar(?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): StockSplitCalendar
    {
        $from_string = $from?->format('Y-m-d');
        $to_string = $to?->format('Y-m-d');
        return StockSplitCalendar::create($this, from: $from_string, to: $to_string);
    }

    /** @throws \JsonException If parameter encoding or cached transport metadata fails. */
    public function getPriceTarget(string $symbol): PriceTarget
    {
        return PriceTarget::create($this, symbol: $symbol);
    }

    /** @throws \JsonException If parameter encoding or cached transport metadata fails. */
    public function getUpgradesDowngrades(string $symbol): UpgradesDowngrades
    {
        return UpgradesDowngrades::create($this, symbol: $symbol);
    }

    /**
     * Request daily prices and unwrap the Legacy symbol/historical response.
     *
     * @param int|null $timeseries Provider limit on returned history rows.
     * @param \DateTimeInterface|null $from Start calendar date; only YYYY-MM-DD is sent.
     * @param \DateTimeInterface|null $to End calendar date; only YYYY-MM-DD is sent.
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getHistoricalPrice(
        string $symbol,
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null,
        ?int $timeseries = null
    ): HistoricalPrice {
        $from_string = $from?->format('Y-m-d');
        $to_string = $to?->format('Y-m-d');
        return HistoricalPrice::create($this, $symbol, from: $from_string, to: $to_string, timeseries: $timeseries);
    }

    /** @throws \JsonException If parameter encoding or cached transport metadata fails. */
    public function getCompanyCoreInformation(string $symbol): CompanyCoreInformation
    {
        return CompanyCoreInformation::create($this, symbol: $symbol);
    }

    /** @throws \JsonException If parameter encoding or cached transport metadata fails. */
    public function getDelistedCompanies(): DelistedCompanies
    {
        return DelistedCompanies::create($this);
    }

    /**
     * Request income statements; period and data format are Legacy query arguments.
     *
     * @param string|null $period Reporting frequency, usually annual or quarter.
     * @param string|null $dataType Provider payload format: json or csv.
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getIncomeStatement(
        string $symbol,
        ?string $period = IncomeStatement::PERIOD_ANNUAL,
        ?string $dataType = 'json',
        ?int $limit = null
    ): IncomeStatement {
        return IncomeStatement::create($this, $symbol, limit: $limit, period: $period, datatype: $dataType);
    }

    /**
     * Request balance sheets by ticker or SEC identifier.
     *
     * @param string|int $symbolOrCIK Integer CIK values are padded to ten digits; strings are passed unchanged.
     * @param string|null $period Reporting frequency, usually annual or quarter.
     * @param string|null $dataType Provider payload format: json or csv.
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getBalanceSheetStatement(
        string|int $symbolOrCIK,
        ?string $period = IncomeStatement::PERIOD_ANNUAL,
        ?string $dataType = 'json',
        ?int $limit = null
    ): BalanceSheetStatement {
        if(is_int($symbolOrCIK)) {
            // format CIK as string
            $symbolOrCIK = \str_pad((string)$symbolOrCIK, 10, '0', \STR_PAD_LEFT);
        }
        return BalanceSheetStatement::create($this, $symbolOrCIK, limit: $limit, period: $period, datatype: $dataType);
    }

    /**
     * Request cash-flow statements by ticker or SEC identifier.
     *
     * @param string|int $symbolOrCIK Integer CIK values are padded to ten digits; strings are passed unchanged.
     * @param string|null $period Reporting frequency, usually annual or quarter.
     * @param string|null $dataType Provider payload format: json or csv.
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getCashflowStatement(
        string|int $symbolOrCIK,
        ?string $period = IncomeStatement::PERIOD_ANNUAL,
        ?string $dataType = 'json',
        ?int $limit = null
    ): CashflowStatement {
        if(is_int($symbolOrCIK)) {
            // format CIK as string
            $symbolOrCIK = \str_pad((string)$symbolOrCIK, 10, '0', \STR_PAD_LEFT);
        }
        return CashflowStatement::create($this, $symbolOrCIK, limit: $limit, period: $period, datatype: $dataType);
    }

    /**
     * Request Legacy quotes for several symbols in one transfer when needed.
     *
     * @param string|list<string> $symbol One symbol, comma-separated symbols, or an array of symbols.
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getQuote(string|array $symbol): Quote
    {
        return Quote::create($this, $symbol);
    }

    /** @throws \JsonException If parameter encoding or cached transport metadata fails. */
    public function getQuoteShort(string $symbol): QuoteShort
    {
        return QuoteShort::create($this, $symbol);
    }

    /**
     * Request financial metrics for one company's reporting periods.
     *
     * @param string|null $period Legacy reporting frequency: annual or quarter; null omits the filter.
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getKeyMetrics(
        string $symbol,
        ?string $period = IncomeStatement::PERIOD_ANNUAL,
        ?int $limit = null,
    ): KeyMetrics {
        return KeyMetrics::create($this, $symbol, period: $period, limit: $limit);
    }

    /**
     * Request the company's latest trailing twelve-month metrics in one call.
     *
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getKeyMetricsTTM(string $symbol): KeyMetricsTTM
    {
        return KeyMetricsTTM::create($this, $symbol);
    }

    /**
     * Request financial ratios for one company's reporting periods.
     *
     * @param string|null $period Legacy reporting frequency: annual or quarter; null omits the filter.
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getRatios(
        string $symbol,
        ?string $period = IncomeStatement::PERIOD_ANNUAL,
        ?int $limit = null,
    ): Ratios {
        return Ratios::create($this, $symbol, period: $period, limit: $limit);
    }

    /**
     * Request the company's latest trailing twelve-month ratios in one call.
     *
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getRatiosTTM(string $symbol): RatiosTTM
    {
        return RatiosTTM::create($this, $symbol);
    }

    /**
     * Request provider-calculated growth between company reporting periods.
     *
     * @param string|null $period Legacy reporting frequency: annual or quarter; null omits the filter.
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getFinancialGrowth(
        string $symbol,
        ?string $period = IncomeStatement::PERIOD_ANNUAL,
        ?int $limit = null,
    ): FinancialGrowth {
        return FinancialGrowth::create($this, $symbol, period: $period, limit: $limit);
    }

    /**
     * Request analyst forecasts for company reporting periods.
     *
     * @param string|null $period Legacy reporting frequency: annual or quarter; null omits the filter.
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getAnalystEstimates(
        string $symbol,
        ?string $period = IncomeStatement::PERIOD_ANNUAL,
        ?int $limit = null,
    ): AnalystEstimates {
        return AnalystEstimates::create($this, $symbol, period: $period, limit: $limit);
    }

    /**
     * Request one bundled company overview; nested sections remain accessible as provider arrays.
     *
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getCompanyOutlook(string $symbol): CompanyOutlook
    {
        return CompanyOutlook::create($this, symbol: $symbol);
    }

    /**
     * Request income statements across companies for one provider-selected year.
     *
     * @param string $period Legacy annual/quarter filter; no conversion to Stable FY/Q1-Q4 labels.
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getIncomeStatementBulk(int $year, string $period = IncomeStatement::PERIOD_ANNUAL): IncomeStatementBulk
    {
        return IncomeStatementBulk::create($this, year: $year, period: $period);
    }

    /**
     * Request balance sheets across companies for one provider-selected year.
     *
     * @param string $period Legacy annual/quarter filter; returned reporting dates remain unchanged.
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getBalanceSheetStatementBulk(int $year, string $period = IncomeStatement::PERIOD_ANNUAL): BalanceSheetStatementBulk
    {
        return BalanceSheetStatementBulk::create($this, year: $year, period: $period);
    }

    /**
     * Request cash-flow statements across companies for one provider-selected year.
     *
     * @param string $period Legacy annual/quarter filter; returned reporting dates remain unchanged.
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getCashflowStatementBulk(int $year, string $period = IncomeStatement::PERIOD_ANNUAL): CashflowStatementBulk
    {
        return CashflowStatementBulk::create($this, year: $year, period: $period);
    }

    /**
     * Request metrics across companies in one bulk response; a download can be substantial.
     *
     * @param string $period Legacy annual/quarter filter for the requested provider year.
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getKeyMetricsBulk(int $year, string $period = IncomeStatement::PERIOD_ANNUAL): KeyMetricsBulk
    {
        return KeyMetricsBulk::create($this, year: $year, period: $period);
    }

    /**
     * Request ratios across companies in one bulk response; a download can be substantial.
     *
     * @param string $period Legacy annual/quarter filter for the requested provider year.
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getRatiosBulk(int $year, string $period = IncomeStatement::PERIOD_ANNUAL): RatiosBulk
    {
        return RatiosBulk::create($this, year: $year, period: $period);
    }

    /**
     * Request provider-calculated growth across companies in one bulk response.
     *
     * @param string $period Legacy annual/quarter filter for the requested provider year.
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getFinancialGrowthBulk(int $year, string $period = IncomeStatement::PERIOD_ANNUAL): FinancialGrowthBulk
    {
        return FinancialGrowthBulk::create($this, year: $year, period: $period);
    }

    /**
     * Request the latest trailing twelve-month metrics across companies in one bulk response.
     *
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getKeyMetricsTTMBulk(): KeyMetricsTTMBulk
    {
        return KeyMetricsTTMBulk::create($this);
    }

    /**
     * Request the latest trailing twelve-month ratios across companies in one bulk response.
     *
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public function getRatiosTTMBulk(): RatiosTTMBulk
    {
        return RatiosTTMBulk::create($this);
    }
}
