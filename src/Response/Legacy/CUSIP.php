<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023-2026. Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * CUSIP.php created on 10.03.26, 23:22.
 */

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\Response;

class CUSIP extends Response
{
    protected static string $url = 'v3/cusip';

    public function getCompany(): string
    {
        return $this->getResponseValueAsString('company');
    }

    public function getTicker(): string
    {
        return $this->getResponseValueAsString('ticker');
    }

    public function getCUSIP(): string
    {
        return $this->getResponseValueAsString('cusip');
    }

}
