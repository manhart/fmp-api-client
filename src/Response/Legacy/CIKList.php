<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

/** SEC registrants and their Central Index Key identifiers. */
class CIKList extends CIK
{
    protected static string $url = 'v3/cik_list';
}
