<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023-2026. Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * Search.php created on 10.03.26, 23:23.
 */

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\Response;

class Search extends Response
{
    protected static string $url = 'v3/search';

    public function getSymbol(): string
    {
        return $this->getResponseValue('symbol');
    }

    public function getName(): string
    {
        return $this->getResponseValueAsString('name');
    }

    public function getCurrency(): string
    {
        return $this->getResponseValueAsString('currency');
    }

    public function getStockExchange(): string
    {
        return $this->getResponseValueAsString('stockExchange');
    }

    public function getExchangeShortName(): string
    {
        return $this->getResponseValueAsString('exchangeShortName');
    }
}
