<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023-2026. Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * CompanyCoreInformation.php created on 10.03.26, 23:22.
 */

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\Response;

class CompanyCoreInformation extends Response
{
    protected static string $url = 'v4/company-core-information';

    public function getSymbol(): string
    {
        return $this->getResponseValue('symbol');
    }

    public function getCIKAsInt(): ?int
    {
        return $this->getResponseValueAsInt('cik') ?: null;
    }

    public function getCIK(): ?string
    {
        return $this->getResponseValue('cik') ?: null;
    }

    public function getExchange(): string
    {
        return $this->getResponseValueAsString('exchange');
    }

    public function getSICCode(): ?int
    {
        return $this->getResponseValueAsInt('sicCode') ?: null;
    }

    public function getSICGroup(): ?string
    {
        return $this->getResponseValue('sicGroup');
    }

    public function getSICDescription(): ?string
    {
        return $this->getResponseValue('sicDescription');
    }

    public function getStateLocation(): ?string
    {
        return $this->getResponseValue('stateLocation');
    }

    public function getStateOfIncorporation(): ?string
    {
        return $this->getResponseValue('stateOfIncorporation');
    }

    public function getFiscalYearEnd(): ?string
    {
        return $this->getResponseValue('fiscalYearEnd');
    }

    public function getBusinessAddress(): ?string
    {
        return $this->getResponseValue('businessAddress');
    }

    public function getMailingAddress(): ?string
    {
        return $this->getResponseValue('mailingAddress');
    }

    public function getTaxIdentificationNumber(): ?string
    {
        return $this->getResponseValue('taxIdentificationNumber');
    }

    public function getRegistrantName(): ?string
    {
        return $this->getResponseValue('registrantName');
    }
}
