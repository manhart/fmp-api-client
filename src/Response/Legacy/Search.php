<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Company/listing matches selected by a ticker or name fragment. */
class Search extends AbstractResponse
{
    protected static string $url = 'v3/search';

    public function getSymbol(): ?string
    {
        return $this->getResponseValueAsString('symbol');
    }

    public function getName(): ?string
    {
        return $this->getResponseValueAsString('name');
    }

    public function getCurrency(): ?string
    {
        return $this->getResponseValueAsString('currency');
    }

    public function getStockExchange(): ?string
    {
        return $this->getResponseValueAsString('stockExchange');
    }

    public function getExchangeShortName(): ?string
    {
        return $this->getResponseValueAsString('exchangeShortName');
    }
}
