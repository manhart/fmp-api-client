<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

/** Symbols marked as actively trading in the Legacy directory. */
class AvailableTradedList extends StockList
{
    protected static string $url = 'v3/available-traded/list';
}
