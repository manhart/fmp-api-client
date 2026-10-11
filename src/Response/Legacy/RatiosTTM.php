<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Latest trailing twelve-month company ratios from the Legacy dataset. */
class RatiosTTM extends AbstractResponse
{
    protected static string $url = 'v3/ratios-ttm';
}
