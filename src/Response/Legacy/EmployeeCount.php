<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Reported company employee counts from the Legacy dataset. */
class EmployeeCount extends AbstractResponse
{
    protected static string $url = 'v4/employee_count';

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

    public function getAcceptanceTime(): ?\DateTimeInterface
    {
        return $this->getResponseValueAsDate('acceptanceTime');
    }

    public function getPeriodOfReport(): ?\DateTimeInterface
    {
        return $this->getResponseValueAsDate('periodOfReport');
    }

    public function getCompanyName(): ?string
    {
        return $this->getResponseValueAsString('companyName');
    }

    public function getFormType(): ?string
    {
        return $this->getResponseValueAsString('formType');
    }

    public function getFilingDate(): ?\DateTimeInterface
    {
        return $this->getResponseValueAsDate('filingDate');
    }

    public function getEmployeeCount(): int
    {
        return $this->getResponseValueAsInt('employeeCount');
    }

    public function getSource(): ?string
    {
        return $this->getResponseValueAsString('source');
    }
}
