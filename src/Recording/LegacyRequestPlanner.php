<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Recording;

use Manhart\Fmp\Client\LegacyApiClient;

/** Run existing Legacy factories through the synthetic planning transport. */
final class LegacyRequestPlanner extends LegacyApiClient
{
    use PlanningTransport;
}
