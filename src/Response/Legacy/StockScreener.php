<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023-2026. Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * StockScreener.php created on 10.03.26, 23:23.
 */

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\Response;

class StockScreener extends Response
{
    protected static string $url = 'v3/stock-screener';

    public function getSymbol(): string
    {
        return $this->getResponseValue('symbol');
    }

    public function getCompanyName(): string
    {
        return $this->getResponseValueAsString('companyName');
    }

    public function getMarketCap(): int
    {
        return $this->getResponseValueAsInt('marketCap');
    }

    public function getSector(): string
    {
        return $this->getResponseValueAsString('sector');
    }

    public function getIndustry(): string
    {
        return $this->getResponseValueAsString('industry');
    }

    public function getBeta(): float
    {
        return $this->getResponseValueAsFloat('beta');
    }

    public function getPrice(): float
    {
        return $this->getResponseValueAsFloat('price');
    }

    public function getLastAnnualDividend(): float
    {
        return $this->getResponseValueAsFloat('lastAnnualDividend');
    }

    public function getVolume(): int
    {
        return $this->getResponseValueAsInt('volume');
    }

    public function getExchange(): string
    {
        return $this->getResponseValueAsString('exchange');
    }

    public function getExchangeShortName(): string
    {
        return $this->getResponseValueAsString('exchangeShortName');
    }

    public function getCountry(): string
    {
        return $this->getResponseValueAsString('country');
    }

    public function getIsEtf(): bool
    {
        return $this->getResponseValueAsBool('isEtf');
    }

    public function getIsActivelyTrading(): bool
    {
        return $this->getResponseValueAsBool('isActivelyTrading');
    }
}
