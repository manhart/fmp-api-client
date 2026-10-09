<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023-2026. Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * EmployeeCount.php created on 10.03.26, 23:22.
 */

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\Response;

class EmployeeCount extends Response
{
    protected static string $url = 'v4/employee_count';

    public function getCIK(): string
    {
        return $this->getResponseValueAsString('cik');
    }

    public function getCIKAsInt(): ?int
    {
        return $this->getResponseValueAsInt('cik') ?: null;
    }

    public function getSymbol(): string
    {
        return $this->getResponseValueAsString('symbol');
    }

    public function getAcceptanceTime(): \DateTimeInterface
    {
        return $this->getResponseValueAsDate('acceptanceTime');
    }

    public function getPeriodOfReport(): \DateTimeInterface
    {
        return $this->getResponseValueAsDate('periodOfReport');
    }

    public function getCompanyName(): string
    {
        return $this->getResponseValueAsString('companyName');
    }

    public function getFormType(): string
    {
        return $this->getResponseValueAsString('formType');
    }

    public function getFilingDate(): \DateTimeInterface
    {
        return $this->getResponseValueAsDate('filingDate');
    }

    public function getEmployeeCount(): int
    {
        return $this->getResponseValueAsInt('employeeCount');
    }

    public function getSource(): string
    {
        return $this->getResponseValueAsString('source');
    }
}
