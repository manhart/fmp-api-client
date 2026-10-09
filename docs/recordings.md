# Cached contract recordings

The normal test suite never calls FMP. Its request fixtures are independently based on the official parameter examples and its response fixtures are synthetic. Live capture is a separate operation.

## Commands

```sh
# Print planned requests; does not read an API key or call FMP.
php tools/record.php --plan
php tools/record.php --plan --include-legacy --include-large

# After supplying FMP_API_KEY through the local environment:
php tools/record.php

# Inspect one large case before its explicit capture:
php tools/record.php --plan --endpoint=profile-bulk --include-large
php tools/record.php --endpoint=profile-bulk --include-large

# Existing Legacy calls are opt-in:
php tools/record.php --include-legacy

# Replay all recorded cases without a key or network access:
php tools/record.php --offline --include-legacy --include-large

# Explicitly replace one recording:
php tools/record.php --endpoint=quote --refresh
```

Do not place a real key in command arguments, Git, examples or chat. `FMP_API_KEY` is read only when a missing/selected refreshed case needs a live request. Consequently a cache-only run does not need it.

## Budgets and cache

Defaults are 60 requests/minute and 1 GiB of received response-body bytes per invocation. The tool accepts rates up to 3,000/minute; choose a rate within your FMP subscription's limit. Other processes' consumption is not tracked by this tool. Limits count redirects' received bodies as delivered by cURL, not provider-side accounting or HTTP/TLS overhead; cancellation can exceed the threshold by one received cURL chunk.

`--max-bytes=N` and `--rate=N` override the defaults. Bulk and full financial-report captures require `--include-large`. These endpoints may return substantial files despite minimal parameters. Only one parameter case is selected per endpoint; it is not a crawl of every page or Bulk part.

By default, private recordings live in the user-specific temporary directory `manhart-fmp-recordings-<uid>`, outside the web document root. For durable recordings, select a private persistent directory with `--cache-dir`. Keep that directory outside any served web root and reuse it on later runs. Temporary-directory cleanup can remove recordings, so a later live run would then see missing cases. If `.recordings/` is explicitly selected inside the package, it is excluded from Git and Composer archives. The CLI holds an exclusive lock to prevent concurrent recorder runs in the same cache directory. A key consists of the endpoint path and canonical query parameters, without authentication.

A request attempt is marked before the HTTP call. Completed responses, provider errors and interrupted transfers are saved. Every later run consults the cache first. Interrupted or corrupt recordings require an explicit single-case refresh, avoiding accidental repeated requests. Parser changes replay the existing raw response instead of downloading it again.

`report.json` lists outcomes and the invocation's real network call/body-byte counters. A run that cannot validate every selected case exits with status 1; HTTP errors, missing recordings and empty data are not counted as verified.

## Status meanings

| Status | Meaning |
| --- | --- |
| `verified` | HTTP success, successful parser/file handling and non-empty representative data; known documented field names are checked where available |
| `empty` | Request succeeded but provided no representative records |
| `blocked` | HTTP 401, 402, 403 or 429; account access, authentication or quota needs checking |
| `http-error` | Another non-successful HTTP response |
| `api-error` | FMP reports an API error in an HTTP-success payload |
| `schema-mismatch` | Records exist but none of the documented example's field names are present |
| `parse-error` | Cached payload cannot be decoded, or its recording is corrupt |
| `incomplete` | Interrupted or byte-limited request; retained data does not count as a full response |
| `missing` | Offline replay has no recording |
| `budget-exhausted` | No further request was made because the invocation reached its byte budget |

A verified case checks one documented use of an endpoint, not every server-side filter combination or the accuracy of financial values. Every supported parameter is additionally tested locally. New API behavior requires deliberately selected refreshes; the client does not silently update the cache during tests.

## Capture case maintenance

Curated Stable capture values are preserved when documentation is reimported, provided their parameter names are still documented. Newly required parameters receive generated defaults. Review capture values and independent request fixtures after contract changes.

