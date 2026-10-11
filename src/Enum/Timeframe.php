<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Enum;

/** Convenience price-bar intervals; each endpoint may support only a subset or additional strings. */
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
