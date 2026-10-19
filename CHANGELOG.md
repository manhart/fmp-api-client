# Changelog

All notable changes are recorded here. Entries under Unreleased are intended
for the next release. Dates use YYYY-MM-DD.

## [Unreleased]

## [0.2.1] - 2026-10-19

### Fixed

- Hide the API-key constructor argument in exception traces through
  `SensitiveParameter` and redact the stored key in `var_dump()`, `print_r()`
  and `__debugInfo()`-aware dumpers such as Symfony VarDumper. Preserve mangled
  property names and private properties of subclasses; document that
  `var_export()` and `serialize()` still expose the key.
- Reject null bytes in endpoint URLs before passing an authenticated URL to
  cURL, preventing initialization errors from exposing the key in traces.
- Read negative and fractional Unix timestamp strings consistently with
  numeric JSON values, using whole seconds in UTC.
- Use the date getter's fallback for whitespace-only strings instead of
  interpreting them as the current time.
- Classify known provider text errors in HTTP-success payloads as `api-error`
  during recording and replay, including leading whitespace and UTF-8 BOMs.
- Preserve UTF-8 character boundaries when limiting redacted HTTP error
  excerpts to 2,048 bytes, retaining the original received-byte count.
- Report missing replay metadata and bodies before attempting to read them,
  avoiding additional unsuppressed filesystem warnings.
- Correct diagnostic terminology and clarify that cache/file symlinks are
  rejected while symlinks in ancestor directories are allowed.

## [0.2.0] - 2026-10-11

### Added

- Package author, keywords and repository/support metadata for Composer.
- A concise `AGENTS.md` with the code map and contribution checks.
- A `CLAUDE.md` pointer to the shared instructions in `AGENTS.md`.
- Concise contract documentation for clients, response getters, recording/replay
  and generated endpoints, including parameter formats and special response shapes.
- IDE type information for dynamic endpoint dispatch and checked JSON exceptions.
- Seven Legacy methods for key metrics, ratios, financial growth, analyst
  estimates and Company Outlook, plus eight statement/metric/ratio/growth bulks.
  All return concrete response classes and preserve provider fields and values.
- Recorder cases for the 15 new Legacy methods, with explicit large-download
  selection for all eight bulks and cached JSON/CSV replay coverage.
- `HttpException` with HTTP status, a redacted body excerpt limited to 2,048
  bytes, and actual received body bytes.
- Bulk-memory guidance for buffered downloads, decoded rows and recorder
  replay, distinguishing response-byte budgets from PHP memory limits.

### Changed

- Reserve `Manhart\Fmp\Response\Response` for Stable responses and move shared
  response behavior to `Manhart\Fmp\Response\AbstractResponse`.
- Rename `StableResponse` to `Response`, `StableClientMethods` to `ClientMethods`
  and `Response\Stable\StableEndpointRegistry` to `Response\Stable\EndpointRegistry`.
  Existing class names are replaced without compatibility aliases.
- Use short SPDX copyright/license comments in PHP files and central author
  metadata in `composer.json`.
- Group README examples under Usage: Stable API and Usage: Legacy API, and
  install a tagged release by default.
- Clarify that the supported Legacy wrappers are a subset of FMP's Legacy APIs and
  that route access depends on the account.
- Link the provider website directly in the introduction and show application
  key loading from the environment, php.ini or a local variable.
- Allow affected Legacy text and date getters to return null for unavailable
  values. Consumers must handle their nullable return types.
- Pass transport and HTTP exceptions unchanged through typed response
  factories. Row-factory callers must now also handle `CurlException`.
- Propagate parameter-encoding and replay-metadata `JsonException` failures
  before decoding; wrap only expected payload-decoding failures.
- Align string getters through one converter. Alias-based string reads preserve
  whitespace and normalize supplied defaults when no alias is available;
  callers can request trimming explicitly through the normalizer.
- Reject unsafe existing Unix recording directories, symlinks and special
  recording files. Existing directory permissions are left unchanged; the
  user-specific temporary default remains configurable through `--cache-dir`.

### Removed

- Remove the unused protected `AbstractResponse::$client` and `decodeJson()`
  extension hooks and the public `dump()` debugging method, without aliases.
  Subclasses using these hooks must be updated; inspect rows through `current()`
  or iteration instead of `dump()`.

### Fixed

- Generate only the enum and date imports needed by each endpoint and the
  selected client shortcuts.
- Prevent Legacy getters from throwing TypeError for empty responses, missing
  or empty fields, and exhausted iterators. Convert scalar text fields through
  the existing string helper while retaining original response values.
- Read numeric Legacy profile employee counts as strings and default-image
  flags through the boolean helper, with false as the missing-value fallback.
- Retain provider error reasons for HTTP failures and preserve cURL transfer
  details instead of replacing the original transport exceptions.
- Preserve decoder error codes for file responses and share construction of
  redacted decoder exceptions without retaining unsafe original throwables.
- Apply string normalizers to existing string values in alias-based getters,
  including CSV fields, while preserving zero values and original row data.
- Remove only a complete UTF-8 BOM, preserving Unicode CSV headers. Recognize
  FMP's plain-text `Restricted Endpoint:` errors even when they contain commas;
  valid header-only CSV remains an empty response.
- Write captures and reports through exclusively created temporary files and
  atomic replacement, and validate recorder locks before opening them.
- Detect missing or obsolete generated Stable files through a manifest test
  and generate multiline client signatures/calls in PER 2.0 format.

## [0.1.0] - 2026-10-09

### Added

- A standalone PHP 8.4 client with constructor-supplied credentials.
- Typed responses for 243 Stable endpoint paths, 84 client shortcuts and
  generic endpoint dispatch through `FmpApiClient::fetch()`.
- A separate `LegacyApiClient` with 38 existing V3/V4 methods.
- Shared HTTP transport with timeouts, response byte limits and key redaction.
- JSON, CSV and XLSX response handling.
- Documentation import, reproducible code generation, fixture-based tests
  and explicit live recording with cache replay and budgets.

[Unreleased]: https://github.com/manhart/fmp-api-client/compare/v0.2.1...HEAD
[0.2.1]: https://github.com/manhart/fmp-api-client/compare/v0.2.0...v0.2.1
[0.2.0]: https://github.com/manhart/fmp-api-client/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/manhart/fmp-api-client/tree/v0.1.0
