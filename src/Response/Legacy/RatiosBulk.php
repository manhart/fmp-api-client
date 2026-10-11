<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Company ratios across the Legacy bulk dataset; CSV values stay strings until converted by getters. */
class RatiosBulk extends AbstractResponse
{
    protected static string $url = 'v4/ratios-bulk';
}
