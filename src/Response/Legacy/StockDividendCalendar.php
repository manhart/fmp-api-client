<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023-2026. Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * StockDividendCalendar.php created on 10.03.26, 23:23.
 */

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\Response;

class StockDividendCalendar extends Response
{
    protected static string $url = 'v3/stock_dividend_calendar';

    public function getSymbol(): string
    {
        return $this->getResponseValueAsString('symbol');
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->getResponseValueAsDate('date');
    }

    public function getLabel(): string
    {
        return $this->getResponseValue('label');
    }

    public function getAdjDividend(): float
    {
        return $this->getResponseValueAsFloat('adjDividend');
    }

    public function getDividend(): float
    {
        return $this->getResponseValueAsFloat('dividend');
    }

    public function getRecordDate(): ?\DateTimeInterface
    {
        return $this->getResponseValueAsDate('recordDate');
    }

    public function getPaymentDate(): ?\DateTimeInterface
    {
        return $this->getResponseValueAsDate('paymentDate');
    }

    public function getDeclarationDate(): ?\DateTimeInterface
    {
        return $this->getResponseValueAsDate('declarationDate');
    }
}
