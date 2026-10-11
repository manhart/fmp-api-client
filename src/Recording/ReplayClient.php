<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Recording;

use Manhart\Fmp\Client\FmpApiClient;

/** Validate saved Stable payloads through the regular endpoint factories, without network calls. */
final class ReplayClient extends FmpApiClient
{
    use ReplayTransport;
}
