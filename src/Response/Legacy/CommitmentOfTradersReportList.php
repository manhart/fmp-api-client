<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Futures contracts with Commitment of Traders reports available. */
class CommitmentOfTradersReportList extends AbstractResponse
{
    protected static string $url = 'v4/commitment_of_traders_report/list';

    public function getTradingSymbol(): ?string
    {
        return $this->getResponseValueAsString('trading_symbol');
    }

    public function getShortName(): ?string
    {
        return $this->getResponseValueAsString('short_name');
    }
}
