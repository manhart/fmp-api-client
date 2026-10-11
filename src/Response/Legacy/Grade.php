<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Analyst rating changes and the publishing firms. */
class Grade extends AbstractResponse
{
    protected static string $url = 'v3/grade';

    public function getSymbol(): ?string
    {
        return $this->getResponseValueAsString('symbol');
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->getResponseValueAsDate('date');
    }

    public function getGradingCompany(): ?string
    {
        return $this->getResponseValueAsString('gradingCompany');
    }

    public function getPreviousGrade(): ?string
    {
        return $this->getResponseValueAsString('previousGrade');
    }

    public function getNewGrade(): ?string
    {
        return $this->getResponseValueAsString('newGrade');
    }
}
