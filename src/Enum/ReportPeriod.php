<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Enum;

/** Fiscal report labels used by report downloads and similar endpoints, rather than annual/quarter. */
enum ReportPeriod: string
{
    case FY = 'FY';
    case Q1 = 'Q1';
    case Q2 = 'Q2';
    case Q3 = 'Q3';
    case Q4 = 'Q4';
}
