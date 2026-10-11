<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Tests\Support;

use Manhart\Fmp\Client\LegacyApiClient;

class LegacyFixtureClient extends LegacyApiClient
{
    use FixtureTransport;
}
