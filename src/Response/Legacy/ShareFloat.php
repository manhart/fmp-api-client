<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023-2026. Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * ShareFloat.php created on 10.03.26, 23:23.
 */

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\Response;

class ShareFloat extends Response
{
    protected static string $url = 'v4/shares_float';

    public function getSymbol(): string
    {
        return $this->getResponseValue('symbol');
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
        return $this->getResponseValue('source');
    }
}
