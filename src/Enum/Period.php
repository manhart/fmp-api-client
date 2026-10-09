<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023 Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * Period.php created on 28.02.26, 18:06.
 */

namespace Manhart\Fmp\Enum;

enum Period: string
{
    case Annual = 'annual';
    case Quarter = 'quarter';
}
