<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Reported share counts and publicly tradable float for one or all symbols. */
class ShareFloat extends AbstractResponse
{
    protected static string $url = 'v4/shares_float';

    public function getSymbol(): ?string
    {
        return $this->getResponseValueAsString('symbol');
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->getResponseValueAsDate('date') ?? (new \DateTime())->setTime(0, 0);
    }

    public function getFreeFloat(): ?float
    {
        return $this->getResponseValueAsFloat('freeFloat');
    }

    public function getFloatShares(): ?int
    {
        return $this->getResponseValueAsInt('floatShares') ?: null;
    }

    public function getOutstandingShares(): ?int
    {
        return $this->getResponseValueAsInt('outstandingShares') ?: null;
    }

    public function getSource(): ?string
    {
        return $this->getResponseValueAsString('source');
    }
}
