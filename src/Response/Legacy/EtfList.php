<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

/** Exchange-traded fund symbols from the Legacy directory. */
class EtfList extends StockList
{
    protected static string $url = 'v3/etf/list';
}
