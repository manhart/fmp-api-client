<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023-2026. Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * CommitmentOfTradersReportList.php created on 10.03.26, 23:22.
 */

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\Response;

class CommitmentOfTradersReportList extends Response
{
    protected static string $url = 'v4/commitment_of_traders_report/list';

    public function getTradingSymbol(): string
    {
        return $this->getResponseValueAsString('trading_symbol');
    }

    public function getShortName(): string
    {
        return $this->getResponseValueAsString('short_name');
    }
}
