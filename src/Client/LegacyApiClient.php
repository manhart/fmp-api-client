<?php
declare(strict_types=1);

namespace Manhart\Fmp\Client;

use Manhart\Fmp\Response\Legacy\AvailableEuronext;
use Manhart\Fmp\Response\Legacy\AvailableTradedList;
use Manhart\Fmp\Response\Legacy\BalanceSheetStatement;
use Manhart\Fmp\Response\Legacy\CashflowStatement;
use Manhart\Fmp\Response\Legacy\CIK;
use Manhart\Fmp\Response\Legacy\CIKList;
use Manhart\Fmp\Response\Legacy\CIKSearch;
use Manhart\Fmp\Response\Legacy\CommitmentOfTradersReportList;
use Manhart\Fmp\Response\Legacy\CompanyCoreInformation;
use Manhart\Fmp\Response\Legacy\CompanyNotes;
use Manhart\Fmp\Response\Legacy\CUSIP;
use Manhart\Fmp\Response\Legacy\DelistedCompanies;
use Manhart\Fmp\Response\Legacy\EmployeeCount;
use Manhart\Fmp\Response\Legacy\EtfList;
use Manhart\Fmp\Response\Legacy\ExchangeSymbols;
use Manhart\Fmp\Response\Legacy\ExecutiveCompensation;
use Manhart\Fmp\Response\Legacy\ExecutiveCompensationBenchmark;
use Manhart\Fmp\Response\Legacy\FinancialStatementSymbolLists;
use Manhart\Fmp\Response\Legacy\Grade;
use Manhart\Fmp\Response\Legacy\HistoricalEmployeeCount;
use Manhart\Fmp\Response\Legacy\HistoricalPrice;
use Manhart\Fmp\Response\Legacy\IncomeStatement;
use Manhart\Fmp\Response\Legacy\PriceTarget;
use Manhart\Fmp\Response\Legacy\Profile;
use Manhart\Fmp\Response\Legacy\Quote;
use Manhart\Fmp\Response\Legacy\QuoteShort;
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
use Manhart\Fmp\Exception\ResponseException;

class LegacyApiClient extends AbstractApiClient
{
    /**
     * Search over 70,000 symbols by symbol name or company name, including cryptocurrencies, forex, stocks, etf and other financial instruments.
     *
     * @param string $query The query to search for.
     * @param int|null $limit The maximum amount of results to return.
     * @param string|null $exchange The short exchange name to search in.
     *
     * @throws ResponseException
     * @link https://site.financialmodelingprep.com/developer/docs#general-search-company-search
     */
    public function search(string $query, ?int $limit = null, ?string $exchange = null): Search
    {
        return Search::create($this, query: $query, limit: $limit, exchange: $exchange);
    }

    /**
     * Find ticker symbols and exchanges for both equity securities and exchange-traded funds (ETFs) by searching with the company name or ticker symbol.
     * @link https://site.financialmodelingprep.com/developer/docs#ticker-search-company-search
     * @throws ResponseException
     */
    public function searchTicker(string $query, ?int $limit = null, ?string $exchange = null): SearchTicker
    {
        return SearchTicker::create($this, query: $query, limit: $limit, exchange: $exchange);
    }

    /**
     * Find ticker symbols and exchange information for equity securities and exchange-traded funds (ETFs) by searching with the company name.
     * @link https://site.financialmodelingprep.com/developer/docs#name-search-company-search
     * @throws ResponseException
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
     * @return CIKSearch The CIKSearch object for the specified company name.
     * @throws ResponseException
     * @link https://site.financialmodelingprep.com/developer/docs#cik-name-search-company-search
     */
    public function searchCIKByName(string $companyName): CIKSearch
    {
        return CIKSearch::create($this, $companyName);
    }

    /**
     * Quickly find registered company names linked to SEC-registered entities using their CIK Number with our CIK Search
     *
     * @param string|int $CIK The Central Index Number to search for.
     *
     * @return CIK
     * @throws ResponseException
     * @link https://site.financialmodelingprep.com/developer/docs#cik-search-company-search
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
     * Access information about financial instruments and securities by simply entering their unique CUSIP (Committee on Uniform Securities Identification
     * Procedures) numbers with our CUSIP Search.
     *
     * @param string $CUSIP The CUSIP to search for.
     *
     * @return CUSIP
     * @throws ResponseException
     * @link https://site.financialmodelingprep.com/developer/docs#cusip-search-company-search
     */
    public function getCUSIP(string $CUSIP): CUSIP
    {
        return CUSIP::create($this, $CUSIP);
    }

    public function getStockList(): StockList
    {
        return StockList::create($this);
    }

    /**
     * Our Exchange Traded Fund Search makes it easy to find the symbol for any ETF you're looking for. Simply enter the ETF's name and we'll
     * return the symbol, name, and price.
     *
     * @return EtfList
     * @throws ResponseException
     * @link https://site.financialmodelingprep.com/developer/docs#exchange-traded-fund-search-stock-list
     */
    public function getEtfList(): EtfList
    {
        return EtfList::create($this);
    }

    /**
     * Discover all companies with financial statements available on our API. Our comprehensive list covers major exchanges such as the NYSE and NASDAQ,
     * as well as international exchanges. This list is regularly updated, so you can always find the information you need.
     *
     * @return FinancialStatementSymbolLists
     * @throws ResponseException
     * @link https://site.financialmodelingprep.com/developer/docs#statement-symbols-list-stock-list
     */
    public function getFinancialStatementSymbolLists(): FinancialStatementSymbolLists
    {
        return FinancialStatementSymbolLists::create($this);
    }

    /**
     * Discover all actively traded stocks with our Tradable Search feature. This comprehensive list includes over 70,000 stocks, with symbol, name, price, and exchange information for each company.
     *
     * @return AvailableTradedList
     * @throws ResponseException
     * @link https://site.financialmodelingprep.com/developer/docs#tradable-search-stock-list
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
     * @return CommitmentOfTradersReportList
     * @throws ResponseException
     * @link https://site.financialmodelingprep.com/developer/docs#commitment-of-traders-report-stock-list
     */
    public function getCommitmentOfTradersReportList(): CommitmentOfTradersReportList
    {
        return CommitmentOfTradersReportList::create($this);
    }

    public function getCIKList(): CIKList
    {
        return CIKList::create($this);
    }

    /**
     * Find all symbols for stocks traded on Euronext exchanges with our comprehensive list. Euronext is one of the largest exchanges in Europe, and our
     * list includes stocks from a wide range of industries.
     *
     * @return AvailableEuronext
     * @throws ResponseException
     * @link https://site.financialmodelingprep.com/developer/docs#euronext-symbols-stock-list
     */
    public function getAvailableEuronextSymbols(): AvailableEuronext
    {
        return AvailableEuronext::create($this);
    }

    /**
     * Stay up-to-date on the latest symbol changes with our easy-to-use tool. Track symbol changes due to mergers, acquisitions, stock splits, and name changes.
     *
     * @return SymbolChange
     * @throws ResponseException
     * @link https://site.financialmodelingprep.com/developer/docs#symbol-changes-stock-list
     */
    public function getSymbolChanges(): SymbolChange
    {
        return SymbolChange::create($this);
    }

    public function getExchangeSymbols(string $exchange): ExchangeSymbols
    {
        return ExchangeSymbols::create($this, $exchange);
    }

    public function getProfile(string $symbol): Profile
    {
        return Profile::create($this, $symbol);
    }

    public function getExecutiveCompensation(string $symbol): ExecutiveCompensation
    {
        return ExecutiveCompensation::create($this, symbol: $symbol);
    }

    public function getExecutiveCompensationBenchmark(int $year): ExecutiveCompensationBenchmark
    {
        return ExecutiveCompensationBenchmark::create($this, year: $year);
    }

    public function getCompanyNotes(string $symbol): CompanyNotes
    {
        return CompanyNotes::create($this, symbol: $symbol);
    }

    public function getHistoricalEmployeeCount(string $symbol): HistoricalEmployeeCount
    {
        return HistoricalEmployeeCount::create($this, symbol: $symbol);
    }

    public function getEmployeeCount(string $symbol): EmployeeCount
    {
        return EmployeeCount::create($this, symbol: $symbol);
    }

    /**
     * Find stocks that meet your investment criteria with our Screener (Stock) endpoint. This endpoint allows you to search for stocks based on various
     * criteria, such as market cap, price, volume, beta, sector, and country.
     *
     * @param ...$params
     *
     * @return StockScreener
     * @throws ResponseException
     * @link https://site.financialmodelingprep.com/developer/docs#screener-stock-company-information
     */
    public function getStockScreener(...$params): StockScreener
    {
        return StockScreener::create($this, ...$params);
    }

    public function getGrade(string $symbol, ?int $limit = null): Grade
    {
        return Grade::create($this, $symbol, limit: $limit);
    }

    public function getShareFloat(?string $symbol = null): ShareFloat
    {
        return ($symbol) ? ShareFloat::create($this, symbol: $symbol) : ShareFloat::create($this, 'all');
    }

    public function getStockDividend(string $symbol): StockDividend
    {
        return StockDividend::create($this, $symbol);
    }

    public function getStockDividendCalendar(?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): StockDividendCalendar
    {
        $from_string = $from?->format('Y-m-d');
        $to_string = $to?->format('Y-m-d');
        return StockDividendCalendar::create($this, from: $from_string, to: $to_string);
    }

    public function getStockSplit(string $symbol): StockSplit
    {
        return StockSplit::create($this, $symbol);
    }

    public function getStockSplitCalendar(?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): StockSplitCalendar
    {
        $from_string = $from?->format('Y-m-d');
        $to_string = $to?->format('Y-m-d');
        return StockSplitCalendar::create($this, from: $from_string, to: $to_string);
    }

    public function getPriceTarget(string $symbol): PriceTarget
    {
        return PriceTarget::create($this, symbol: $symbol);
    }

    public function getUpgradesDowngrades(string $symbol): UpgradesDowngrades
    {
        return UpgradesDowngrades::create($this, symbol: $symbol);
    }

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

    public function getCompanyCoreInformation(string $symbol): CompanyCoreInformation
    {
        return CompanyCoreInformation::create($this, symbol: $symbol);
    }

    public function getDelistedCompanies(): DelistedCompanies
    {
        return DelistedCompanies::create($this);
    }

    public function getIncomeStatement(
        string $symbol,
        ?string $period = IncomeStatement::PERIOD_ANNUAL,
        ?string $dataType = 'json',
        ?int $limit = null
    ): IncomeStatement {
        return IncomeStatement::create($this, $symbol, limit: $limit, period: $period, datatype: $dataType);
    }

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

    public function getQuote(string|array $symbol): Quote
    {
        return Quote::create($this, $symbol);
    }

    public function getQuoteShort(string $symbol): QuoteShort
    {
        return QuoteShort::create($this, $symbol);
    }

}
