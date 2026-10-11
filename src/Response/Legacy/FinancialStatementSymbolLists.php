<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Symbols with financial statements available from the Legacy dataset. */
class FinancialStatementSymbolLists extends AbstractResponse
{
    protected static string $url = 'v3/financial-statement-symbol-lists';

    private array $symbols;

    public function __construct(array $response)
    {
        $this->symbols = $response;

        // For the implemented interfaces, an associative array with the key 'symbol' within an indexed array is needed.
        $symbolAssocArray = array_map(static fn($item) => ['symbol' => $item], $this->symbols);
        parent::__construct($symbolAssocArray);
    }

    public function getSymbol(): ?string
    {
        return $this->getResponseValueAsString('symbol');
    }

    public function getSymbols(): array
    {
        return $this->symbols;
    }
}
