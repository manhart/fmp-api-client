<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023-2026. Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * HistoricalEmployeeCount.php created on 10.03.26, 23:22.
 */

namespace Manhart\Fmp\Response\Legacy;

class HistoricalEmployeeCount extends EmployeeCount
{
    protected static string $url = 'v4/historical/employee_count';
}
