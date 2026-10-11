<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Debt/security notes associated with a company. */
class CompanyNotes extends AbstractResponse
{
    protected static string $url = 'v4/company-notes';

    public function getCIK(): ?string
    {
        return $this->getResponseValueAsString('cik');
    }

    public function getCIKAsInt(): ?int
    {
        return $this->getResponseValueAsInt('cik') ?: null;
    }

    public function getSymbol(): ?string
    {
        return $this->getResponseValueAsString('symbol');
    }

    public function getTitle(): ?string
    {
        return $this->getResponseValueAsString('title');
    }

    public function getExchange(): ?string
    {
        return $this->getResponseValueAsString('exchange');
    }
}
