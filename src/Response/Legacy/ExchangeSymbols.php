<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023-2026. Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * ExchangeSymbols.php created on 10.03.26, 23:22.
 */

namespace Manhart\Fmp\Response\Legacy;

class ExchangeSymbols extends Quote
{
    protected static string $url = 'v3/symbol';
}
