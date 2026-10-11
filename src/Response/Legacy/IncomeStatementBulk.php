<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Income statement rows across companies; JSON values and CSV field names remain unchanged. */
class IncomeStatementBulk extends AbstractResponse
{
    protected static string $url = 'v4/income-statement-bulk';
}
