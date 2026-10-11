# Working on fmp-api-client

This is a standalone PHP library for Financial Modeling Prep (FMP), under the
`Manhart\Fmp` namespace. It supports PHP 8.4+ and has no framework dependency.
The consuming application supplies the API key to the client constructor.

## Code map

- `src/Client/FmpApiClient.php`: main Stable client; named methods come from the
  generated `ClientMethods` trait. `fetch()` resolves typed endpoint classes.
- `src/Client/LegacyApiClient.php`: maintained V3/V4 methods and contracts.
- `src/Client/AbstractApiClient.php`: shared cURL transport, parameter encoding,
  limits, counters and key redaction. `src/HttpResponse.php` holds HTTP results.
- `src/Response/AbstractResponse.php`: shared iteration, getters and factories.
  `Response.php` adds Stable row normalization; `FileResponse.php` handles XLSX.
- `src/Response/Stable/`: generated endpoint classes and `EndpointRegistry`.
  `src/Response/Legacy/`: maintained Legacy response classes.
- `resources/endpoints.json`: Stable parameters, fields and capture cases.
  `resources/client-shortcuts.json`: selected client methods.
- `resources/legacy-cases.json`: Legacy capture arguments and large-download
  flags; `tests/LegacyFundamentalsTest.php` independently checks new routes.
- `src/Recording/` and `tools/record.php`: explicit live captures and replay.
- `tests/fixtures/documented-requests.json`: independent expected requests.

Start with the relevant client, endpoint class and test. Read the matching
manifest entry when changing a contract; avoid scanning all generated classes.

## Changes and checks

- Follow PER 2.0, four-space indentation, concrete types and KISS/DRY/YAGNI.
- Put `declare(strict_types=1);` immediately after `<?php`. Each own PHP file
  carries `SPDX-FileCopyrightText` and `SPDX-License-Identifier: MIT` comments.
  Preserve existing copyright years. Authors are maintained in `composer.json`.
- Change generators and contracts, then regenerate; do not hand-edit generated
  endpoints, the registry or client methods. Generated headers use an explicit
  publication year so regeneration is reproducible.
- Generate only imports required by the actual parameter types. Preserve
  imports used by PHPDoc. Keep Stable and Legacy response behavior separate.
- Keep Legacy bulk fields, dates, periods, currencies and units unchanged.
  Company Outlook keeps its nested sections within one iterable row. Do not
  add automatic Stable/Legacy fallback or infer dataset equivalence.
- Explain non-obvious class/trait responsibilities, parameter meaning, response
  shapes and side effects in concise English. Avoid tags that only repeat native
  types or names. Endpoint/parameter explanations live in the manifest and are
  preserved by the documentation importer; common parameter notes belong to the
  generator. Document actual behavior rather than an assumed contract.
- Document escaping checked exceptions such as `JsonException`, including
  propagation through helpers. Exceptions caught and wrapped by a factory are
  documented at the decoding helper, not falsely exposed by row endpoints.
  Typed factories pass transport/HTTP exceptions unchanged and wrap only
  expected decoder failures through `ResponseException::fromDecoderError()`.
  Pass already-redacted values to this helper. Transport parameter/replay
  metadata JSON failures propagate through Stable and Legacy methods.
  Use `class-string` for dynamic class names and document generated fetch()
  contracts on response bases so IDEs can resolve the dispatch.
- Preserve independent request fixtures when importing documentation. Review
  changed parameters against FMP's source before updating those fixtures.
- Add focused regression coverage for behavior changes. Run the affected test
  and then the complete Composer-managed suite:

```sh
composer validate --strict
php tools/generate.php
composer test
```

The tests use fixtures and a local HTTP server. Live FMP captures require an
explicit request; never add them to the default tests or CI. Keep credentials
and live response files outside the repository.

Maintain `CHANGELOG.md` for every new version: record user-visible changes in
`Unreleased`, then move them to a dated version section when releasing. Update
README examples when public names or contracts change. Commit and tag only
when requested.
