<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023-2026. Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * DelistedCompanies.php created on 10.03.26, 23:22.
 */

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\Response;

class DelistedCompanies extends Response
{
    protected static string $url = 'v3/delisted-companies';

    public function getSymbol(): string
    {
        return $this->getResponseValueAsString('symbol');
    }

    public function getCompanyName(): string
    {
        return $this->getResponseValueAsString('companyName');
    }

    public function getExchange(): string
    {
        return $this->getResponseValueAsString('exchange');
    }

    public function getIpoDate(): ?\DateTimeInterface
    {
        return $this->getResponseValueAsDate('ipoDate');
    }

    public function getDelistedDate(): \DateTimeInterface
    {
        return $this->getResponseValueAsDate('delistedDate');
    }
}
