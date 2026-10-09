<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023-2026. Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * UpgradesDowngrades.php created on 10.03.26, 23:23.
 */

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\Response;

class UpgradesDowngrades extends Response
{
    protected static string $url = 'v4/upgrades-downgrades';

    public function getPublishedDate(): ?\DateTimeInterface
    {
        return $this->getResponseValueAsDate('publishedDate');
    }

    public function getNewGrade(): ?string
    {
        return $this->getResponseValue('newGrade');
    }

    public function getPreviousGrade(): ?string
    {
        return $this->getResponseValue('previousGrade');
    }

    public function getGradingCompany(): ?string
    {
        return $this->getResponseValue('gradingCompany');
    }

    public function getAction(): ?string
    {
        return $this->getResponseValue('action');
    }

    public function getPriceWhenPosted(): float
    {
        return $this->getResponseValueAsFloat('priceWhenPosted');
    }

    public function getNewsBaseURL(): ?string
    {
        return $this->getResponseValue('newsBaseURL');
    }

    public function getNewsPublisher(): ?string
    {
        return $this->getResponseValue('newsPublisher');
    }

    public function getNewsURL(): ?string
    {
        return $this->getResponseValue('newsURL');
    }

    public function getNewsTitle(): string
    {
        return $this->getResponseValueAsString('newsTitle');
    }
}
