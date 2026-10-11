<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** SEC company identity selected by a Central Index Key. */
class CIK extends AbstractResponse
{
    protected static string $url = 'v3/cik';

    public function getName(): ?string
    {
        return $this->getResponseValueAsString('name');
    }

    /** SEC CIK as a number; leading zeroes are lost and missing/zero values yield null. */
    public function getCIKAsInt(): ?int
    {
        return $this->getResponseValueAsInt('cik') ?: null;
    }

    public function getCIK(): ?string
    {
        return $this->getResponseValueAsString('cik');
    }
}
