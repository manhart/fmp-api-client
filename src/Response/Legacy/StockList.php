<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023-2026. Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * StockList.php created on 10.03.26, 23:23.
 */

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\Response;

class StockList extends Response
{
    protected static string $url = 'v3/stock/list';

    public function getSymbol(): string
    {
        return $this->getResponseValueAsString('symbol');
    }

    public function getExchange(): string
    {
        return $this->getResponseValueAsString('exchange');
    }

    public function getExchangeShortName(): string
    {
        return $this->getResponseValueAsString('exchangeShortName');
    }

    public function getPrice(): float
    {
        return $this->getResponseValueAsFloat('price');
    }

    public function getName(): string
    {
        return $this->getResponseValueAsString('name');
    }

    public function getType(): string
    {
        return $this->getResponseValueAsString('type');
    }
}
