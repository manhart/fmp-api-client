<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Dividend events across symbols for a requested date window. */
class StockDividendCalendar extends AbstractResponse
{
    protected static string $url = 'v3/stock_dividend_calendar';

    public function getSymbol(): ?string
    {
        return $this->getResponseValueAsString('symbol');
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->getResponseValueAsDate('date');
    }

    public function getLabel(): ?string
    {
        return $this->getResponseValueAsString('label');
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
