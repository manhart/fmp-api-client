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

# Inspect and record one Legacy fundamentals case:
php tools/record.php --plan --endpoint=legacy:getKeyMetrics
php tools/record.php --endpoint=legacy:getKeyMetrics

# Legacy bulks require explicit large-download selection, even for one case:
php tools/record.php --plan --endpoint=legacy:getKeyMetricsBulk --include-large
php tools/record.php --endpoint=legacy:getKeyMetricsBulk --include-large
php tools/record.php --offline --endpoint=legacy:getKeyMetricsBulk --include-large

# Replay all recorded cases without a key or network access:
php tools/record.php --offline --include-legacy --include-large

# Explicitly replace one recording:
php tools/record.php --endpoint=quote --refresh
```

Do not place a real key in command arguments, Git, examples or chat. `FMP_API_KEY` is read only when a missing/selected refreshed case needs a live request. Consequently a cache-only run does not need it.

## Budgets and cache

Defaults are 60 requests/minute and 1 GiB of received response-body bytes per invocation. The tool accepts rates up to 3,000/minute; choose a rate within your FMP subscription's limit. Other processes' consumption is not tracked by this tool. Limits count redirects' received bodies as delivered by cURL, not provider-side accounting or HTTP/TLS overhead; cancellation can exceed the threshold by one received cURL chunk.

`--max-bytes=N` and `--rate=N` override the defaults. Bulk and full financial-report captures require `--include-large`. These endpoints may return substantial files despite minimal parameters. Only one parameter case is selected per endpoint; it is not a crawl of every page or Bulk part.

The byte budget limits received response bodies, not PHP memory. Capture buffers a complete body, and JSON/CSV verification builds every row in memory. Offline cache replay also loads and decodes the complete recording. Saving a capture to disk does not make verification stream its rows. Large datasets can require several times their body size in RAM and can exhaust `memory_limit` with an uncatchable fatal error. Use enough memory for the selected cases; see [Bulk responses and memory](../README.md#bulk-responses-and-memory). Neither `--include-large` nor `--max-bytes` changes this behavior, and the tool does not raise PHP's memory limit automatically.

By default, recordings live below `sys_get_temp_dir()` in the user-specific directory `manhart-fmp-recordings-<uid>`. PHP configuration determines the temporary location; it is not guaranteed to be outside a served web root. For durable recordings, select a private persistent directory with `--cache-dir` and reuse it on later runs. Keep that directory outside every served web root. A `.cache` name, filesystem permissions or Git/archive exclusions do not prevent a web server running as the same user from serving its contents. Temporary-directory cleanup can remove recordings, so a later live run would then see missing cases.

New cache directories are created with Unix mode `0700`. Existing directories are rejected if they allow any group/other access, or if their owner differs from the effective user when the POSIX extension is available. Existing permissions are not changed automatically. The directory itself must not be a symlink; symlinks in ancestor paths are allowed. On Windows, use private directory ACLs; Unix mode/owner checks do not apply there.

Metadata, bodies, locks and reports reject symlinks (including dangling links) and special files. New body/JSON files use mode `0600`, exclusively created random temporary files and atomic replacement; predictable `.tmp` paths are never used. The CLI holds an exclusive lock to prevent concurrent recorder runs in the same cache directory. A cache key consists of the endpoint path and canonical query parameters, without authentication.

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

`resources/legacy-cases.json` contains one representative case for each of the 53 Legacy methods. Fundamentals cases use `AAPL`; the six annual bulk cases use `year=2023&period=annual`, while the two TTM bulks have no filters. All eight Legacy bulks are marked large. Adding a case does not fetch it: only a live invocation with that case selected can make a request. Existing captures are reused; use the same private `--cache-dir` for planning, capture and replay.

