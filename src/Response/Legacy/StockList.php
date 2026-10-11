<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Company and security symbols from the Legacy directory. */
class StockList extends AbstractResponse
{
    protected static string $url = 'v3/stock/list';

    public function getSymbol(): ?string
    {
        return $this->getResponseValueAsString('symbol');
    }

    public function getExchange(): ?string
    {
        return $this->getResponseValueAsString('exchange');
    }

    public function getExchangeShortName(): ?string
    {
        return $this->getResponseValueAsString('exchangeShortName');
    }

    public function getPrice(): float
    {
        return $this->getResponseValueAsFloat('price');
    }

    public function getName(): ?string
    {
        return $this->getResponseValueAsString('name');
    }

    public function getType(): ?string
    {
        return $this->getResponseValueAsString('type');
    }
}
