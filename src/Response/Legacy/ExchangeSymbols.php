<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

/** Listings for the requested Legacy exchange code. */
class ExchangeSymbols extends Quote
{
    protected static string $url = 'v3/symbol';
}
