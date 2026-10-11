<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Latest trailing twelve-month company metrics across the Legacy bulk dataset. */
class KeyMetricsTTMBulk extends AbstractResponse
{
    protected static string $url = 'v4/key-metrics-ttm-bulk';
}
