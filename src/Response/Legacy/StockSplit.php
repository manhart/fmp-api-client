<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Split rows extracted from the Legacy historical wrapper; the symbol is kept separately. */
class StockSplit extends AbstractResponse
{
    protected static string $url = 'v3/historical-price-full/stock_split';

    private string $symbol;

    /**
     * @param array $response Original object with symbol and historical keys, not a normalized row list.
     */
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

    public function getLabel(): ?string
    {
        return $this->getResponseValueAsString('label');
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
