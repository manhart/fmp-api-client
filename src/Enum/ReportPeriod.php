<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023 Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * ReportPeriod.php created on 28.02.26, 20:40.
 */

namespace Manhart\Fmp\Enum;

enum ReportPeriod: string
{
    case FY = 'FY';
    case Q1 = 'Q1';
    case Q2 = 'Q2';
    case Q3 = 'Q3';
    case Q4 = 'Q4';
}
