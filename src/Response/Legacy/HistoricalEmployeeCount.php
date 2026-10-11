<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

/** Reported company employee counts over time. */
class HistoricalEmployeeCount extends EmployeeCount
{
    protected static string $url = 'v4/historical/employee_count';
}
