<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** One bundled company overview; profile, metrics and other nested sections are preserved. */
class CompanyOutlook extends AbstractResponse
{
    protected static string $url = 'v4/company-outlook';

    /** Keep object and list responses iterable without flattening their nested sections. */
    public function __construct(array $response)
    {
        parent::__construct(array_is_list($response) ? $response : [$response]);
    }
}
