<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Enum;

/** Convenience reporting frequencies; this differs from the FY/Q1-Q4 labels in ReportPeriod. */
enum Period: string
{
    case Annual = 'annual';
    case Quarter = 'quarter';
}
