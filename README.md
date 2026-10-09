# FMP API client

Standalone PHP client for Financial Modeling Prep. The package supports 243 Stable endpoint paths and the 38 existing Legacy methods.

Use `Manhart\Fmp\Client\FmpApiClient` for Stable requests and `Manhart\Fmp\Client\LegacyApiClient` for Legacy requests. Both take the API key explicitly in their constructor.

License: MIT.

## Install

You need PHP 8.4 or later with the cURL, JSON and ctype extensions, and Composer 2. Install Composer using the [official installation guide](https://getcomposer.org/download/). The commands below assume that `php` and `composer` are available in your terminal and use a compatible PHP installation.

### Use the client in your application

Open a terminal in your application's directory, where its `composer.json` is located. If the application does not have that file yet, create it with the interactive command below and follow the prompts:

```sh
composer init
```

Register the GitHub repository as a package source, then install the client:

```sh
composer config repositories.fmp-api-client vcs https://github.com/manhart/fmp-api-client.git
composer require manhart/fmp-api-client:dev-main
```

Composer adds the dependency to your application's `composer.json`, installs the package in `vendor/manhart/fmp-api-client`, and creates `vendor/autoload.php` to load its classes. `dev-main` selects the current development branch rather than a tagged release. This installation uses [Composer's VCS repository support](https://getcomposer.org/doc/05-repositories.md#vcs) and works without a Packagist release, once the package code is available on GitHub.

Continue with the [first Stable request](#first-stable-request) below.

### Develop the client

To change the client itself, install Git and clone the repository into a directory of your choice:

```sh
git clone https://github.com/manhart/fmp-api-client.git
cd fmp-api-client
composer install
composer test
```

`composer install` installs the development dependencies, including the project's PHPUnit. `composer test` runs the test suite using that installation. Development requires PHP 8.4.1 or later; PHPUnit also needs the dom, filter, libxml, mbstring, tokenizer and xmlwriter extensions in addition to the client requirements.

## Stable requests

### First Stable request

The consuming application loads its API key and passes it to `new FmpApiClient($apiKey)` or `new LegacyApiClient($apiKey)`. It chooses whether to use environment variables, PHP configuration or a private credential store. The clients do not load configuration files or resolve credentials automatically.

Save the following as `example.php` in your application's directory, next to `composer.json`. It loads [Composer's autoloader](https://getcomposer.org/doc/01-basic-usage.md#autoloading), reads `FMP_API_KEY` from the process environment and fetches quotes for two symbols in one request:

```php
<?php
declare(strict_types=1);

use Manhart\Fmp\Client\FmpApiClient;
use Manhart\Fmp\Response\Stable\BatchQuote;

require __DIR__ . '/vendor/autoload.php';

$key = getenv('FMP_API_KEY');
if ($key === false || $key === '') {
    throw new RuntimeException('Set FMP_API_KEY locally.');
}
$client = new FmpApiClient($key);
$quotes = BatchQuote::fetch($client, ['AAPL', 'MSFT']);

foreach ($quotes as $row) {
    echo $row['symbol'], ': ', $row['price'], PHP_EOL;
}
```

Set `FMP_API_KEY` in the environment of the PHP process before running the example. In Bash, you can enter the key at a hidden prompt and then run it:

```sh
read -rsp 'FMP API key: ' FMP_API_KEY
export FMP_API_KEY
printf '\n'
php example.php
```

The example makes one live FMP request and prints the returned symbols and prices. Your FMP subscription must allow access to the endpoint. Keep the real key outside Git and example files. In an existing application, replace `getenv()` with your own key-loading code if needed; the constructor stays the same. The separate recording CLI also reads `FMP_API_KEY` for live captures.

### Typed endpoint classes

Each class under `Manhart\Fmp\Response\Stable` represents one endpoint. Its `fetch()` method declares the required and optional parameters and returns that concrete response type. This gives your IDE parameter hints and a known return type:

```php
use Manhart\Fmp\Response\Stable\Profile;

$profile = Profile::fetch($client, symbol: 'AAPL');
```

The complete example above uses the same approach with `BatchQuote::fetch()`. All 243 Stable endpoints have a typed class, including endpoints that do not have a client shortcut.

### Client shortcuts

For common portfolio and import operations, the client also offers 84 named methods:

```php
$profile = $client->getProfile('AAPL');
$quotes = $client->getBatchQuote(['AAPL', 'MSFT']);
$dividends = $client->getDividends('AAPL', limit: 100);
```

Each shortcut delegates to its typed endpoint class and has the same parameters and return type. For example, `getProfile()` returns `Manhart\Fmp\Response\Stable\Profile`. `getQuote()` accepts one symbol; use `getBatchQuote()` for multiple symbols.

| Area | Methods |
| --- | --- |
| Search and directories | `searchSymbol()`, `searchName()`, `searchCIK()`, `searchCUSIP()`, `searchIsin()`, `searchExchangeVariants()`, `searchSECCompaniesByName()`, `getCompanyScreener()`, `getStockList()`, `getEtfList()`, `getActivelyTradingList()`, `getFinancialStatementSymbolList()`, `getCIKList()`, `getAvailableExchanges()`, `getAvailableCountries()`, `getAvailableSectors()`, `getAvailableIndustries()`, `getIndexList()`, `getSymbolChanges()`, `getDelistedCompanies()` |
| Company information | `getProfile()`, `getProfileCIK()`, `getSECProfile()`, `searchIndustryClassification()`, `getCompanyNotes()`, `getEmployeeCount()`, `getHistoricalEmployeeCount()`, `getExecutiveCompensation()`, `getExecutiveCompensationBenchmark()`, `getCommitmentOfTradersList()`, `getStockPeers()` |
| Prices and shares | `getQuote()`, `getQuoteShort()`, `getBatchQuote()`, `getBatchQuoteShort()`, `getSharesFloat()`, `getSharesFloatAll()`, `getMarketCapitalization()`, `getBatchMarketCapitalization()`, `getHistoricalMarketCapitalization()`, `getStockPriceChange()`, `getHistoricalPrices()`, `getHistoricalPricesLight()`, `getDividendAdjustedPrices()`, `getNonSplitAdjustedPrices()` |
| Events | `getDividends()`, `getDividendsCalendar()`, `getSplits()`, `getSplitsCalendar()`, `getEarnings()`, `getEarningsCalendar()` |
| Statements and metrics | `getIncomeStatement()`, `getBalanceSheetStatement()`, `getCashFlowStatement()`, `getIncomeStatementTTM()`, `getBalanceSheetStatementTTM()`, `getCashFlowStatementTTM()`, `getKeyMetrics()`, `getKeyMetricsTTM()`, `getRatios()`, `getRatiosTTM()`, `getFinancialGrowth()`, `getFinancialScores()` |
| Analyst data | `getGrades()`, `getGradesHistorical()`, `getGradesConsensus()`, `getPriceTargetSummary()`, `getPriceTargetConsensus()` |
| ETF, forex and market hours | `getEtfInfo()`, `getEtfHoldings()`, `getEtfAssetExposure()`, `getEtfCountryWeightings()`, `getEtfSectorWeightings()`, `getForexList()`, `getBatchForexQuotes()`, `getExchangeMarketHours()`, `getExchangeHolidays()` |
| Bulk imports | `getProfileBulk()`, `getEodBulk()`, `getIncomeStatementBulk()`, `getBalanceSheetStatementBulk()`, `getCashFlowStatementBulk()`, `getKeyMetricsTTMBulk()`, `getRatiosTTMBulk()` |

### General endpoint access

With the same `$client`, request an endpoint by its path and named parameters:

```php
use Manhart\Fmp\Enum\Period;

$statements = $client->fetch(
    'income-statement',
    symbol: 'AAPL',
    period: Period::Annual,
    limit: 5,
);
```

The client's `fetch()` method supports all 243 Stable endpoints and dispatches to the corresponding typed class. Its return type is `Response|FileResponse`; use a shortcut or the endpoint class directly when you want a concrete return type in your IDE.

Required arguments are required in PHP; optional arguments default to `null` and are omitted from the query. Date parameters accept ISO date strings or `DateTimeInterface`. Enum parameters also accept strings, so documented values beyond the convenience enum remain available. `symbols` accepts a comma-separated string or an array; a singular `symbol` follows the documented string contract.

`FIELDS` describes the fields in the documentation examples. It does not remove additional fields or require all fields to be present in every record. JSON and CSV values are available through iteration and the existing typed response getters. CSV values remain strings until accessed through a typed getter.

### XLSX

```php
$file = $client->fetch(
    'financial-reports-xlsx',
    symbol: 'AAPL',
    year: 2022,
    period: 'FY',
);
file_put_contents('report.xlsx', $file->getContent());
```

File responses expose their original bytes through `getContent()`. They do not represent table rows.

### Transport and errors

```php
$client = new FmpApiClient(
    $key,
    timeoutSeconds: 60,
    connectTimeoutSeconds: 10,
    maxResponseBytes: 16 * 1024 * 1024,
);
```

`request()` returns an `HttpResponse` with body, status, content type and received body bytes. `executeCurl()` additionally rejects non-successful HTTP status codes. Typed response factories decode payloads and throw `ResponseException` for HTTP/API errors or malformed data. Transport failures and configured download limits throw `CurlException`.

The client performs no automatic retries, pagination or caching. Diagnose URLs and exception messages redact the key. Authentication is added only to the outgoing FMP request. A byte-limited transfer can receive one cURL chunk beyond the threshold before cancellation; the body is truncated at the configured limit and the actual received body bytes are counted. HTTP/TLS overhead and provider-side quota accounting are outside this counter.

## Legacy

```php
use Manhart\Fmp\Client\LegacyApiClient;

$legacyClient = new LegacyApiClient($key);
$legacyQuote = $legacyClient->getQuote(['AAPL', 'MSFT']);
$legacyStatement = $legacyClient->getIncomeStatement('AAPL', dataType: 'csv');
```

`LegacyApiClient` provides the 38 Legacy methods with their existing names, argument contracts, response getters and V3/V4 routes. A method such as `getProfile()` returns a Stable response on `FmpApiClient` and a Legacy response on `LegacyApiClient`. Stable and Legacy parameters and response fields can differ.

Legacy-only operations such as `getCompanyCoreInformation()` remain on `LegacyApiClient`. For Stable company information, select the appropriate endpoint explicitly, such as `getProfile()`, `getSECProfile()` or `searchIndustryClassification()`. The client does not combine requests to imitate Legacy responses.

The official Markdown reference currently describes Legacy routes as discontinued. Local method support does not guarantee that FMP will serve those routes for an account. The recorder reports failed access explicitly.

Create each client explicitly with its API key. Client instances have independent credentials and request counters.

## Contract maintenance

The reviewed source is [FMP's official Markdown endpoint reference](https://site.financialmodelingprep.com/api-docs.md), imported on 2026-10-08. `resources/endpoints.json` records all parameter tables, required markers, field names and capture cases. Repeated paths used by different asset classes are merged, retaining every documented parameter.

To import a freshly downloaded reference and regenerate:

```sh
php tools/import-docs.php /path/to/api-docs.md
# Review the contracts and the independent expected requests in tests/fixtures.
php tools/generate.php
composer test
```

The importer does not overwrite independent expected request fixtures. Parameter tests must be updated from the documentation when contracts change. Newly removed endpoint paths must be reviewed before obsolete generated class files are deleted.

`resources/client-shortcuts.json` selects the named Stable methods and their endpoint paths. The generator produces their typed signatures from the same endpoint contracts; it rejects unknown endpoint paths and conflicting method names. To add a shortcut, update that mapping, regenerate and verify the new method against the documented request fixtures.

## Live recordings

See [the recording guide](docs/recordings.md) for one-time captures, cache replay, budgets and verification statuses. Endpoint access depends on your FMP subscription. The recorder reports access errors and empty responses explicitly; a verified capture covers the selected request parameters.
