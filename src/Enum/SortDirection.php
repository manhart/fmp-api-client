<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Enum;

/** Provider sorting values; string arguments remain available where an endpoint accepts them. */
enum SortDirection: string
{
    case Asc = 'asc';
    case Desc = 'desc';
}
