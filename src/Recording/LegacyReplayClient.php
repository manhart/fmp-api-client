<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Recording;

use Manhart\Fmp\Client\LegacyApiClient;

/** Validate saved V3/V4 payloads through the existing Legacy factories, without network calls. */
final class LegacyReplayClient extends LegacyApiClient
{
    use ReplayTransport;
}
