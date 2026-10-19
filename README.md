# FMP API client for PHP

Standalone, typed PHP client for [Financial Modeling Prep (FMP)](https://site.financialmodelingprep.com/), for stock, ETF and financial data. The package supports 243 Stable endpoint paths and 53 Legacy methods.

Use `Manhart\Fmp\Client\FmpApiClient` for Stable requests and `Manhart\Fmp\Client\LegacyApiClient` for Legacy requests. Both take the API key explicitly in their constructor.

License: [MIT](LICENSE). Copyright 2023–2026 Alexander Manhart IT. Author: Alexander Manhart.

[Installation](#install) · [Stable usage](#usage-stable-api) · [Legacy usage](#usage-legacy-api) · [Changelog](CHANGELOG.md)

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
composer require manhart/fmp-api-client
```

Composer selects a compatible tagged release, adds the dependency to your application's `composer.json`, installs the package in `vendor/manhart/fmp-api-client`, and creates `vendor/autoload.php` to load its classes. This installation uses [Composer's VCS repository support](https://getcomposer.org/doc/05-repositories.md#vcs) and works before the package is listed on Packagist. Once it is available on Packagist, only the `composer require` command is needed. To explicitly try the development branch, use `composer require manhart/fmp-api-client:dev-main`.

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

## Usage: Stable API

### First Stable request

The consuming application loads its API key and passes it to `new FmpApiClient($apiKey)` or `new LegacyApiClient($apiKey)`. It chooses whether to use environment variables, PHP configuration or a private credential store. The clients do not load configuration files or resolve credentials automatically.

Save the following as `example.php` in your application's directory, next to `composer.json`. It loads [Composer's autoloader](https://getcomposer.org/doc/01-basic-usage.md#autoloading), reads `FMP_API_KEY` from the process environment or falls back to a setting of the same name in `php.ini`, and fetches quotes for two symbols in one request:

```php
<?php
declare(strict_types=1);

use Manhart\Fmp\Client\FmpApiClient;
use Manhart\Fmp\Response\Stable\BatchQuote;

require __DIR__ . '/vendor/autoload.php';

$key = getenv('FMP_API_KEY') ?: get_cfg_var('FMP_API_KEY');
// If you cannot configure environment variables or php.ini, assign it in your application:
// $key = 'My FMP API Key'; // Keep the real value outside committed files.
if ($key === false || $key === '') {
    throw new RuntimeException('Set FMP_API_KEY in the environment, php.ini or your application.');
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

Alternatively, add the following to the `php.ini` used by your PHP process and reload PHP if needed:

```ini
FMP_API_KEY = "My FMP API Key"
```

The environment takes precedence. [`get_cfg_var()`](https://www.php.net/manual/en/function.get-cfg-var.php) reads this custom configuration entry; use the commented `$key = 'My FMP API Key';` alternative in application code when neither configuration option is available. Replace the placeholder with your own key locally.

The example makes one live FMP request and prints the returned symbols and prices. Your FMP subscription must allow access to the endpoint. Keep the real key outside Git and example files. In an existing application, replace the example's key-loading line with your own code if needed; the constructor stays the same. The separate recording CLI reads `FMP_API_KEY` from its environment for live captures.

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

`getResponseValueAsDate()` treats numeric JSON values and numeric CSV strings as Unix seconds in UTC, including negative timestamps. Fractions are truncated toward zero; other date text follows PHP's `DateTime` rules. Missing or invalid values use the supplied fallback.

Both string getters use the same conversion and apply an optional normalizer to every scalar value, including JSON/CSV strings. Whitespace is preserved unless you explicitly pass a normalizer such as `trim(...)`:

```php
$currency = $profile->getResponseValueAsStringByKeys(
    ['currency', 'reportedCurrency'],
    default: 'usd',
    normalizer: strtoupper(...),
);
```

Aliases are checked in order, skipping only missing/null values and empty strings. Zero, false and whitespace-only strings count as present. Conversion and normalization happen after selection; a rejected value uses the default without trying another alias. When no alias exists, the supplied default is converted and normalized just as in `getResponseValueAsString()`. Stored row values remain unchanged.

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

`request()` returns an undecoded `HttpResponse` with the full body, status, content type and received body bytes, including HTTP error responses. `executeCurl()` additionally rejects non-successful HTTP status codes. Stable, Legacy and file factories use the following exception contract:

| Failure | Exception | Details |
| --- | --- | --- |
| Timeout, connection failure or configured download limit | `CurlException` | cURL error code, `receivedBytes` and redacted `partialBody` |
| Completed response outside HTTP 2xx | `HttpException`, a subclass of `ResponseException` | HTTP `status`, redacted `bodyExcerpt` and `receivedBytes`; `getCode()` also returns the HTTP status |
| Invalid JSON/CSV, an API error inside HTTP success, or unexpected file content | `ResponseException` | Redacted diagnostic message; decoder failures preserve their numeric code and have a redacted copy as the previous exception |

Transport and HTTP exceptions are passed through unchanged. The HTTP body excerpt is redacted before being limited to 2,048 bytes, without interpreting its format. Its byte count can be smaller than `receivedBytes`, which records the actual received body bytes before redaction and truncation. Decoder errors retain a safe copy instead of the original throwable, whose message or trace may contain provider data.

Catch HTTP errors before the broader response exception, and also handle transport failures from typed factories:

```php
use Manhart\Fmp\Exception\CurlException;
use Manhart\Fmp\Exception\HttpException;
use Manhart\Fmp\Exception\ResponseException;

try {
    $profile = $client->getProfile('AAPL');
} catch (HttpException $exception) {
    printf("FMP HTTP %d: %s\n", $exception->status, $exception->bodyExcerpt);
} catch (CurlException $exception) {
    printf("cURL %d, %d body bytes: %s\n", $exception->getCode(), $exception->receivedBytes, $exception->getMessage());
} catch (ResponseException $exception) {
    echo $exception->getMessage(), PHP_EOL;
}
```

An application that previously caught only `ResponseException` for row factories must also catch `CurlException`. Parameter encoding and replay-metadata failures can propagate `JsonException` before payload decoding begins. Unexpected programming errors are passed through unchanged.

The client performs no automatic retries, pagination or caching. Diagnostic URLs and exception messages redact the key. The API-key constructor parameter is marked `SensitiveParameter`. `var_dump()`, `print_r()` and dumpers that honour `__debugInfo()`, such as Symfony VarDumper, show the stored key as `[REDACTED]`. `var_export()` and `serialize()` bypass `__debugInfo()` and include the key; do not export, serialize or store client instances in sessions or caches.

Endpoint URLs containing null bytes are rejected with `InvalidArgumentException` before authentication is appended. Authentication is added only to the outgoing FMP request. A byte-limited transfer can receive one cURL chunk beyond the threshold before cancellation; the body is truncated at the configured limit and the actual received body bytes are counted. HTTP/TLS overhead and provider-side quota accounting are outside this counter.

### Bulk responses and memory

The current client buffers the complete response body. JSON and CSV decoding then builds all rows as PHP arrays before returning the response. Iterating with `foreach` reads those already-loaded rows; it does not stream the download or decoder. The CSV parser's temporary stream can spill to disk, but the decoded rows remain in memory.

Large Bulk datasets can require several times their body size in PHP memory. PHP documents a default [`memory_limit` of `128M`](https://www.php.net/manual/en/ini.core.php#ini.memory-limit); hosting and CLI settings may differ. An exhausted memory limit terminates the process with a fatal error rather than a catchable client exception.

`maxResponseBytes` limits received response-body bytes, not the decoder's memory use. It does not guarantee that a response fitting the download limit will fit PHP's memory limit. The recorder's `--max-bytes` budget likewise does not bound RAM usage, including during offline replay.

Run large Bulk requests in a controlled environment with enough memory for both the body and decoded data. The client does not raise `memory_limit` automatically or currently provide a streaming Bulk response. See the [recording guide](docs/recordings.md#budgets-and-cache) for capture and replay limits.

## Usage: Legacy API

```php
use Manhart\Fmp\Client\LegacyApiClient;

$legacyClient = new LegacyApiClient($key);
$legacyQuote = $legacyClient->getQuote(['AAPL', 'MSFT']);
$legacyStatement = $legacyClient->getIncomeStatement('AAPL', dataType: 'csv');
```

`LegacyApiClient` provides 53 Legacy methods, preserving the existing methods' names, argument contracts, response getters and V3/V4 routes. These wrappers cover a subset of FMP's Legacy APIs. A method such as `getProfile()` returns a Stable response on `FmpApiClient` and a Legacy response on `LegacyApiClient`. Stable and Legacy parameters and response fields can differ.

Nullable Legacy text and date getters without an explicit fallback return `null` for missing or empty fields, empty responses and exhausted iterators. Text getters convert scalar values to strings, so `getFullTimeEmployees()` also accepts numeric JSON values; raw response fields remain unchanged. `getDefaultImage()` returns a boolean and defaults to `false` when the flag is unavailable. Existing explicit getter fallbacks are retained.

### Fundamentals and bulk data

The Legacy client also provides these typed methods:

| Data | Methods | Arguments |
| --- | --- | --- |
| Metrics, ratios, growth and forecasts | `getKeyMetrics()`, `getRatios()`, `getFinancialGrowth()`, `getAnalystEstimates()` | Required `symbol`; optional `period` (default `annual`) and `limit` |
| Trailing twelve-month values | `getKeyMetricsTTM()`, `getRatiosTTM()` | Required `symbol` |
| Bundled company overview | `getCompanyOutlook()` | Required `symbol` |
| Bulk statements | `getIncomeStatementBulk()`, `getBalanceSheetStatementBulk()`, `getCashflowStatementBulk()` | Required `year`; optional `period` (default `annual`) |
| Bulk metrics, ratios and growth | `getKeyMetricsBulk()`, `getRatiosBulk()`, `getFinancialGrowthBulk()` | Required `year`; optional `period` (default `annual`) |
| Bulk trailing twelve-month values | `getKeyMetricsTTMBulk()`, `getRatiosTTMBulk()` | No arguments |

```php
$metrics = $legacyClient->getKeyMetrics('AAPL', period: 'quarter', limit: 4);
$peRatio = $metrics->getResponseValueAsFloat('peRatio', null);

$ratios = $legacyClient->getRatiosBulk(2023, period: 'annual');
foreach ($ratios as $row) {
    echo $row['symbol'], PHP_EOL;
}

$outlook = $legacyClient->getCompanyOutlook('AAPL');
$profile = $outlook->getResponseValue('profile'); // Original nested profile section.
```

Each call returns its concrete class under `Manhart\Fmp\Response\Legacy`, such as `KeyMetrics`, `RatiosBulk` or `CompanyOutlook`. Use the shared getters or iterate over the rows. Passing `null` as a numeric getter's fallback distinguishes missing data from zero. Company Outlook is a single row whose nested sections remain unchanged.

Legacy frequency filters use `annual` or `quarter`; they are passed to FMP as supplied. Bulk `year` selects the provider's dataset for that year. Returned dates, fiscal periods, currencies, units and additional fields remain unchanged. JSON values retain their types, and CSV values remain strings until converted by a typed getter. The client does not translate these datasets into Stable field names or switch between APIs automatically.

Bulk methods return one response across companies and can download substantial data. Set `maxResponseBytes` on the Legacy client to bound a transfer if needed, and review the [memory limits](#bulk-responses-and-memory) before loading large datasets. The recorder's eight Legacy bulk cases require `--include-large`; see the [recording guide](docs/recordings.md).

Legacy-only operations such as `getCompanyCoreInformation()` remain on `LegacyApiClient`. For Stable company information, select the appropriate endpoint explicitly, such as `getProfile()`, `getSECProfile()` or `searchIndustryClassification()`. The client does not combine requests to imitate Legacy responses.

FMP states that Legacy documentation is available after sign-in to eligible accounts and encourages migration to the Stable API. A supported wrapper does not guarantee that FMP will serve its route for an account. The recorder reports failed access explicitly. See [FMP's Legacy documentation](https://site.financialmodelingprep.com/documentation) and [changelog](https://site.financialmodelingprep.com/developer/docs/changelog).

Create each client explicitly with its API key. Client instances have independent credentials and request counters.

## Contract maintenance

`src/Client` contains the two clients and their shared transport. Stable row responses derive from `Manhart\Fmp\Response\Response`; both APIs share iteration and getters through `AbstractResponse`. Endpoint classes remain separated under `Response\Stable` and `Response\Legacy`. See [AGENTS.md](AGENTS.md) for the compact code map and development conventions.

The reviewed source is [FMP's official Markdown endpoint reference](https://site.financialmodelingprep.com/api-docs.md), imported on 2026-10-08. `resources/endpoints.json` records all parameter tables, required markers, field names and capture cases. Repeated paths used by different asset classes are merged, retaining every documented parameter.

To import a freshly downloaded reference and regenerate:

```sh
php tools/import-docs.php /path/to/api-docs.md
# Review the contracts and the independent expected requests in tests/fixtures.
php tools/generate.php
composer test
```

The importer does not overwrite independent expected request fixtures. Parameter tests must be updated from the documentation when contracts change. Newly removed endpoint paths must be reviewed before obsolete generated class files are deleted. The test suite checks that the generated Stable files match the manifest exactly, including missing or obsolete classes; the generator does not delete files automatically.

`resources/client-shortcuts.json` selects the named Stable methods and their endpoint paths. The generator produces their typed signatures from the same endpoint contracts; it rejects unknown endpoint paths and conflicting method names. To add a shortcut, update that mapping, regenerate and verify the new method against the documented request fixtures.

Curated endpoint and parameter explanations are stored as `description` in the manifest. The importer preserves these explanations while the endpoint or parameter exists. The generator includes them in endpoint classes and shortcuts, alongside shared notes for date formats, reporting periods and other non-obvious arguments.

## Live recordings

See [the recording guide](docs/recordings.md) for one-time captures, cache replay, budgets and verification statuses. Endpoint access depends on your FMP subscription. The recorder reports access errors and empty responses explicitly; a verified capture covers the selected request parameters.

Choose a private cache directory outside every served web root with `--cache-dir`. The default is a user-specific directory below `sys_get_temp_dir()`; your PHP configuration determines that location. On Unix, existing directories must have no group/other permissions and belong to the current user when POSIX user information is available. The cache directory itself and files inside it must not be symlinks; symlinks in ancestor directories are allowed. On Windows, configure private directory ACLs. Filesystem permissions and a `.cache` name do not by themselves prevent HTTP access.
