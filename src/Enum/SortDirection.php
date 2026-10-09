<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023 Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * SortDirection.php created on 28.02.26, 19:05.
 */

namespace Manhart\Fmp\Enum;

enum SortDirection: string
{
    case Asc = 'asc';
    case Desc = 'desc';
}
