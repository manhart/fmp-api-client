<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Analyst forecasts by reporting period, including provider ranges and consensus values. */
class AnalystEstimates extends AbstractResponse
{
    protected static string $url = 'v3/analyst-estimates';
}
