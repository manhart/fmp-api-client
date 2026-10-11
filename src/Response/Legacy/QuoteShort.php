<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Latest Legacy price and volume with a reduced field set. */
class QuoteShort extends AbstractResponse
{
    protected static string $url = 'v3/quote-short';

    public function getSymbol(): ?string
    {
        return $this->getResponseValueAsString('symbol');
    }

    public function getPrice(): float
    {
        return $this->getResponseValueAsFloat('price');
    }

    public function getVolume(): int
    {
        return $this->getResponseValueAsInt('volume');
    }
}
