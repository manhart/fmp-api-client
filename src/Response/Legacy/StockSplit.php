<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023-2026. Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * StockSplit.php created on 10.03.26, 23:23.
 */

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\Response;

class StockSplit extends Response
{
    protected static string $url = 'v3/historical-price-full/stock_split';

    private string $symbol;

    public function __construct(array $response)
    {
        $this->symbol = $response['symbol'] ?? '';
        parent::__construct($response['historical'] ?? []);
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->getResponseValueAsDate('date');
    }

    public function getLabel(): string
    {
        return $this->getResponseValue('label');
    }

    public function getNumerator(): float
    {
        return $this->getResponseValueAsFloat('numerator');
    }

    public function getDenominator(): int
    {
        return $this->getResponseValueAsInt('denominator');
    }
}
