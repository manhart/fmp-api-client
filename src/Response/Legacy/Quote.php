<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023-2026. Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * Quote.php created on 10.03.26, 23:22.
 */

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\Response;

class Quote extends Response
{
    protected static string $url = 'v3/quote';

    public function getSymbol(): string
    {
        return $this->getResponseValueAsString('symbol');
    }

    public function getName(): string
    {
        return $this->getResponseValueAsString('name');
    }

    public function getPrice(): float
    {
        return $this->getResponseValueAsFloat('price');
    }

    public function getChangesPercentage(): float
    {
        return $this->getResponseValueAsFloat('changesPercentage');
    }

    public function getChange(): float
    {
        return $this->getResponseValueAsFloat('change');
    }

    public function getDayLow(): float
    {
        return $this->getResponseValueAsFloat('dayLow');
    }

    public function getDayHigh(): float
    {
        return $this->getResponseValueAsFloat('dayHigh');
    }

    public function getYearHigh(): float
    {
        return $this->getResponseValueAsFloat('yearHigh');
    }

    public function getYearLow(): float
    {
        return $this->getResponseValueAsFloat('yearLow');
    }

    public function getMarketCap(): float
    {
        return $this->getResponseValueAsFloat('marketCap');
    }

    public function getPriceAvg50(): float
    {
        return $this->getResponseValueAsFloat('priceAvg50');
    }

    public function getPriceAvg200(): float
    {
        return $this->getResponseValueAsFloat('priceAvg200');
    }

    public function getExchange() : string
    {
        return $this->getResponseValueAsString('exchange');
    }

    public function getVolume(): int
    {
        return $this->getResponseValueAsInt('volume');
    }

    public function getAvgVolume(): int
    {
        return $this->getResponseValueAsInt('avgVolume');
    }

    public function getOpen(): float
    {
        return $this->getResponseValueAsFloat('open');
    }

    public function getPreviousClose(): float
    {
        return $this->getResponseValueAsFloat('previousClose');
    }

    public function getEps(): float
    {
        return $this->getResponseValueAsFloat('eps');
    }

    public function getPe(): float
    {
        return $this->getResponseValueAsFloat('pe');
    }

    public function getEarningsAnnouncement(): ?\DateTimeInterface
    {
        return $this->getResponseValueAsDate('earningsAnnouncement');
    }

    public function getSharesOutstanding(): int
    {
        return $this->getResponseValueAsInt('sharesOutstanding');
    }

    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->getResponseValueAsDate('timestamp');
    }
}
