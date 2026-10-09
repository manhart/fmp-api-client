<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023-2026. Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * SearchTicker.php created on 10.03.26, 23:23.
 */

namespace Manhart\Fmp\Response\Legacy;

class SearchTicker extends Search
{
    protected static string $url = 'v3/search-ticker';
}
