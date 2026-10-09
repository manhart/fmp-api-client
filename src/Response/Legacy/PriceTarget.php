<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023-2026. Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * PriceTarget.php created on 10.03.26, 23:22.
 */

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\Response;

class PriceTarget extends Response
{
    protected static string $url = 'v4/price-target';

    public function getPublishedDate(): ?\DateTimeInterface
    {
        return $this->getResponseValueAsDate('publishedDate');
    }

    public function getNewsURL(): ?string
    {
        return $this->getResponseValue('newsURL');
    }

    public function getNewsTitle(): string
    {
        return $this->getResponseValueAsString('newsTitle');
    }

    public function getAnalystName(): ?string
    {
        return $this->getResponseValue('analystName');
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
        return $this->getResponseValue('newsPublisher');
    }

    public function getNewsBaseURL(): ?string
    {
        return $this->getResponseValue('newsBaseURL');
    }

    public function getAnalystCompany(): ?string
    {
        return $this->getResponseValue('analystCompany');
    }
}
