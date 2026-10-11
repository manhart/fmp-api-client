<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Analyst rating changes from the Legacy dataset. */
class UpgradesDowngrades extends AbstractResponse
{
    protected static string $url = 'v4/upgrades-downgrades';

    public function getPublishedDate(): ?\DateTimeInterface
    {
        return $this->getResponseValueAsDate('publishedDate');
    }

    public function getNewGrade(): ?string
    {
        return $this->getResponseValueAsString('newGrade');
    }

    public function getPreviousGrade(): ?string
    {
        return $this->getResponseValueAsString('previousGrade');
    }

    public function getGradingCompany(): ?string
    {
        return $this->getResponseValueAsString('gradingCompany');
    }

    public function getAction(): ?string
    {
        return $this->getResponseValueAsString('action');
    }

    public function getPriceWhenPosted(): float
    {
        return $this->getResponseValueAsFloat('priceWhenPosted');
    }

    public function getNewsBaseURL(): ?string
    {
        return $this->getResponseValueAsString('newsBaseURL');
    }

    public function getNewsPublisher(): ?string
    {
        return $this->getResponseValueAsString('newsPublisher');
    }

    public function getNewsURL(): ?string
    {
        return $this->getResponseValueAsString('newsURL');
    }

    public function getNewsTitle(): ?string
    {
        return $this->getResponseValueAsString('newsTitle');
    }
}
