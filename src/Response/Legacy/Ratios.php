<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Company ratios by reporting period; fields and units follow the Legacy dataset. */
class Ratios extends AbstractResponse
{
    protected static string $url = 'v3/ratios';
}
