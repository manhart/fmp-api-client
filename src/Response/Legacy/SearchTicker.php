<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

/** Company/listing matches selected by a ticker fragment. */
class SearchTicker extends Search
{
    protected static string $url = 'v3/search-ticker';
}
