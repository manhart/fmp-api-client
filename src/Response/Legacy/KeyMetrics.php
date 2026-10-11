<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Financial metrics for a company's reporting periods, using Legacy field names. */
class KeyMetrics extends AbstractResponse
{
    protected static string $url = 'v3/key-metrics';
}
