<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Old and new ticker symbols associated with listing changes. */
class SymbolChange extends AbstractResponse
{
    protected static string $url = 'v4/symbol_change';

    public function getDate(): ?\DateTimeInterface
    {
        return $this->getResponseValueAsDate('date');
    }

    public function getName(): ?string
    {
        return $this->getResponseValueAsString('name');
    }

    public function getOldSymbol(): ?string
    {
        return $this->getResponseValueAsString('oldSymbol');
    }

    public function getNewSymbol(): ?string
    {
        return $this->getResponseValueAsString('newSymbol');
    }
}
