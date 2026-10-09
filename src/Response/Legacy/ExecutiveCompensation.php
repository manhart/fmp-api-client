<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023-2026. Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * ExecutiveCompensation.php created on 10.03.26, 23:22.
 */

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\Response;

class ExecutiveCompensation extends Response
{
    protected static string $url = 'v4/governance/executive_compensation';

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

    public function getCompanyName(): string
    {
        return $this->getResponseValueAsString('companyName');
    }

    public function getIndustryTitle(): string
    {
        return $this->getResponseValueAsString('industryTitle');
    }

    public function getFillingDate(): \DateTimeInterface
    {
        return $this->getResponseValueAsDate('fillingDate');
    }

    public function getAcceptedDate(): ?\DateTimeInterface
    {
        return $this->getResponseValueAsDate('acceptedDate');
    }

    public function getNameAndPosition(): string
    {
        return $this->getResponseValueAsString('nameAndPosition');
    }

    public function getYear(): ?int
    {
        return $this->getResponseValueAsInt('year') ?: null;
    }

    public function getSalary(): ?float
    {
        return $this->getResponseValueAsFloat('salary') ?: null;
    }

    public function getBonus(): ?float
    {
        return $this->getResponseValueAsFloat('bonus') ?: null;
    }

    public function getStockAward(): ?float
    {
        return $this->getResponseValueAsFloat('stock_award') ?: null;
    }

    public function getIncentivePlanCompensation(): ?float
    {
        return $this->getResponseValueAsFloat('incentive_plan_compensation') ?: null;
    }

    public function getAllOtherCompensation(): ?float
    {
        return $this->getResponseValueAsFloat('all_other_compensation') ?: null;
    }

    public function getTotal(): ?float
    {
        return $this->getResponseValueAsFloat('total') ?: null;
    }

    public function getSourceUrl(): ?string
    {
        return $this->getResponseValue('url') ?: null;
    }
}
