<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Balance sheet rows across companies for the requested provider year and frequency. */
class BalanceSheetStatementBulk extends AbstractResponse
{
    protected static string $url = 'v4/balance-sheet-statement-bulk';
}
