<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023-2026. Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * QuoteShort.php created on 10.03.26, 23:22.
 */

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\Response;

class QuoteShort extends Response
{
    protected static string $url = 'v3/quote-short';

    public function getSymbol(): string
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
