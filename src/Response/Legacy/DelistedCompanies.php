<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Companies reported as delisted by the Legacy dataset. */
class DelistedCompanies extends AbstractResponse
{
    protected static string $url = 'v3/delisted-companies';

    public function getSymbol(): ?string
    {
        return $this->getResponseValueAsString('symbol');
    }

    public function getCompanyName(): ?string
    {
        return $this->getResponseValueAsString('companyName');
    }

    public function getExchange(): ?string
    {
        return $this->getResponseValueAsString('exchange');
    }

    public function getIpoDate(): ?\DateTimeInterface
    {
        return $this->getResponseValueAsDate('ipoDate');
    }

    public function getDelistedDate(): ?\DateTimeInterface
    {
        return $this->getResponseValueAsDate('delistedDate');
    }
}
