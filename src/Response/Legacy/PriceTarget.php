<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Individual analyst price targets from the Legacy dataset. */
class PriceTarget extends AbstractResponse
{
    protected static string $url = 'v4/price-target';

    public function getPublishedDate(): ?\DateTimeInterface
    {
        return $this->getResponseValueAsDate('publishedDate');
    }

    public function getNewsURL(): ?string
    {
        return $this->getResponseValueAsString('newsURL');
    }

    public function getNewsTitle(): ?string
    {
        return $this->getResponseValueAsString('newsTitle');
    }

    public function getAnalystName(): ?string
    {
        return $this->getResponseValueAsString('analystName');
    }

    public function getPriceTarget(): float
    {
        return $this->getResponseValueAsFloat('priceTarget');
    }

    public function getAdjPriceTarget(): float
    {
        return $this->getResponseValueAsFloat('adjPriceTarget');
    }

    public function getPriceWhenPosted(): float
    {
        return $this->getResponseValueAsFloat('priceWhenPosted');
    }

    public function getNewsPublisher(): ?string
    {
        return $this->getResponseValueAsString('newsPublisher');
    }

    public function getNewsBaseURL(): ?string
    {
        return $this->getResponseValueAsString('newsBaseURL');
    }

    public function getAnalystCompany(): ?string
    {
        return $this->getResponseValueAsString('analystCompany');
    }
}
