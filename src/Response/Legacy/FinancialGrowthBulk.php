<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Company financial-growth rows across the Legacy bulk dataset; no local growth calculation. */
class FinancialGrowthBulk extends AbstractResponse
{
    protected static string $url = 'v4/financial-growth-bulk';
}
