<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023-2026. Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * SymbolChange.php created on 10.03.26, 23:23.
 */

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\Response;

class SymbolChange extends Response
{
    protected static string $url = 'v4/symbol_change';

    public function getDate(): \DateTimeInterface
    {
        return $this->getResponseValueAsDate('date');
    }

    public function getName(): string
    {
        return $this->getResponseValueAsString('name');
    }

    public function getOldSymbol(): string
    {
        return $this->getResponseValueAsString('oldSymbol');
    }

    public function getNewSymbol(): string
    {
        return $this->getResponseValueAsString('newSymbol');
    }
}
