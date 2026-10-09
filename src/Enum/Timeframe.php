<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023 Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * Timeframe.php created on 28.02.26, 19:05.
 */

namespace Manhart\Fmp\Enum;

enum Timeframe: string
{
    case OneMinute = '1min';
    case FiveMinutes = '5min';
    case FifteenMinutes = '15min';
    case ThirtyMinutes = '30min';
    case OneHour = '1hour';
    case FourHour = '4hour';
    case OneDay = '1day';
    case OneWeek = '1week';
    case OneMonth = '1month';
}
