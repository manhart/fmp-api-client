<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023-2026. Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * CompanyNotes.php created on 10.03.26, 23:22.
 */

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\Response;

class CompanyNotes extends Response
{
    protected static string $url = 'v4/company-notes';

    public function getCIK(): string
    {
        return $this->getResponseValueAsString('cik');
    }

    public function getCIKAsInt(): ?int
    {
        return $this->getResponseValueAsInt('cik') ?: null;
    }

    public function getSymbol(): string
    {
        return $this->getResponseValueAsString('symbol');
    }

    public function getTitle(): string
    {
        return $this->getResponseValueAsString('title');
    }

    public function getExchange(): string
    {
        return $this->getResponseValueAsString('exchange');
    }
}
