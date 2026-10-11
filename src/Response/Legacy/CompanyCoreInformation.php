<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Legacy company-identity summary, separate from a Stable profile response. */
class CompanyCoreInformation extends AbstractResponse
{
    protected static string $url = 'v4/company-core-information';

    public function getSymbol(): ?string
    {
        return $this->getResponseValueAsString('symbol');
    }

    public function getCIKAsInt(): ?int
    {
        return $this->getResponseValueAsInt('cik') ?: null;
    }

    public function getCIK(): ?string
    {
        return $this->getResponseValueAsString('cik') ?: null;
    }

    public function getExchange(): ?string
    {
        return $this->getResponseValueAsString('exchange');
    }

    public function getSICCode(): ?int
    {
        return $this->getResponseValueAsInt('sicCode') ?: null;
    }

    public function getSICGroup(): ?string
    {
        return $this->getResponseValueAsString('sicGroup');
    }

    public function getSICDescription(): ?string
    {
        return $this->getResponseValueAsString('sicDescription');
    }

    public function getStateLocation(): ?string
    {
        return $this->getResponseValueAsString('stateLocation');
    }

    public function getStateOfIncorporation(): ?string
    {
        return $this->getResponseValueAsString('stateOfIncorporation');
    }

    public function getFiscalYearEnd(): ?string
    {
        return $this->getResponseValueAsString('fiscalYearEnd');
    }

    public function getBusinessAddress(): ?string
    {
        return $this->getResponseValueAsString('businessAddress');
    }

    public function getMailingAddress(): ?string
    {
        return $this->getResponseValueAsString('mailingAddress');
    }

    public function getTaxIdentificationNumber(): ?string
    {
        return $this->getResponseValueAsString('taxIdentificationNumber');
    }

    public function getRegistrantName(): ?string
    {
        return $this->getResponseValueAsString('registrantName');
    }
}
