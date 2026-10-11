<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Company identity, listing details and market overview from the Legacy dataset. */
class Profile extends AbstractResponse
{
    protected static string $url = 'v3/profile';

    public function getSymbol(): ?string
    {
        return $this->getResponseValueAsString('symbol');
    }

    public function getPrice(): ?float
    {
        return $this->getResponseValueAsFloat('price');
    }

    public function getBeta(): ?float
    {
        return $this->getResponseValueAsFloat('beta');
    }

    public function getLastDiv(): ?float
    {
        return $this->getResponseValueAsFloat('lastDiv');
    }

    public function getRange(): ?string
    {
        return $this->getResponseValueAsString('range');
    }

    public function getChanges(): ?float
    {
        return $this->getResponseValueAsFloat('changes');
    }

    public function getCompanyName(): ?string
    {
        return $this->getResponseValueAsString('companyName');
    }

    public function getWebsite(): ?string
    {
        return $this->getResponseValueAsString('website');
    }

    public function getDescription(): ?string
    {
        return $this->getResponseValueAsString('description');
    }

    public function getCountry(): ?string
    {
        return $this->getResponseValueAsString('country');
    }

    public function getFullTimeEmployees(): ?string
    {
        return $this->getResponseValueAsString('fullTimeEmployees');
    }

    public function getPhone(): ?string
    {
        return $this->getResponseValueAsString('phone');
    }

    public function getAddress(): ?string
    {
        return $this->getResponseValueAsString('address');
    }

    public function getCity(): ?string
    {
        return $this->getResponseValueAsString('city');
    }

    public function getZip(): ?string
    {
        return $this->getResponseValueAsString('zip');
    }

    public function getState(): ?string
    {
        return $this->getResponseValueAsString('state');
    }

    public function getVolAvg(): ?int
    {
        return $this->getResponseValueAsInt('volAvg') ?: null;
    }

    public function getExchange(): ?string
    {
        return $this->getResponseValueAsString('exchange');
    }

    public function getExchangeShortName(): ?string
    {
        return $this->getResponseValueAsString('exchangeShortName');
    }

    public function getIndustry(): ?string
    {
        return $this->getResponseValueAsString('industry');
    }

    /** SEC CIK as a number; leading zeroes are lost and missing/zero values yield null. */
    public function getCIKAsInt(): ?int
    {
        return $this->getResponseValueAsInt('cik') ?: null;
    }

    public function getCIK(): ?string
    {
        return $this->getResponseValueAsString('cik');
    }

    public function getISIN(): ?string
    {
        return $this->getResponseValueAsString('isin');
    }

    /** Security identifier assigned under the CUSIP system. */
    public function getCUSIP(): ?string
    {
        return $this->getResponseValueAsString('cusip');
    }

    public function getSector(): ?string
    {
        return $this->getResponseValueAsString('sector');
    }

    public function getMktCap(): ?int
    {
        return $this->getResponseValueAsInt('mktCap') ?: null;
    }

    public function getCurrency(): ?string
    {
        return $this->getResponseValueAsString('currency');
    }

    public function getCEO(): ?string
    {
        return $this->getResponseValueAsString('ceo');
    }

    public function getDcfDiff(): ?float
    {
        return $this->getResponseValueAsFloat('dcfDiff');
    }

    public function getDcf(): ?float
    {
        return $this->getResponseValueAsFloat('dcf') ?: null;
    }

    public function getImage(): ?string
    {
        return $this->getResponseValueAsString('image');
    }

    public function getDefaultImage(): bool
    {
        return $this->getResponseValueAsBool('defaultImage');
    }

    public function getIpoDate(): ?\DateTimeInterface
    {
        return $this->getResponseValueAsDate('ipoDate');
    }

    public function isEtf(): bool
    {
        return $this->getResponseValueAsBool('isEtf');
    }

    public function isActivelyTrading(): bool
    {
        return $this->getResponseValueAsBool('isActivelyTrading');
    }

    public function isAdr(): bool
    {
        return $this->getResponseValueAsBool('isAdr');
    }

    public function isFund(): bool
    {
        return $this->getResponseValueAsBool('isFund');
    }

    public function ipoDate(): ?string
    {
        return $this->getResponseValueAsString('ipoDate');
    }

    public function getType(): string
    {
        if($this->isEtf()) {
            return 'etf';
        }

        if($this->isFund()) {
            return 'trust';
        }

        return 'stock';
    }
}
