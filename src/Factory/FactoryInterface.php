<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023 Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * Factory.php created on 25.09.23, 22:53.
 */

namespace Manhart\Fmp\Factory;

use Manhart\Fmp\Client\AbstractApiClient;
use Manhart\Fmp\Response\ResponseInterface;

interface FactoryInterface
{
    public static function create(AbstractApiClient $client, ...$params): ResponseInterface;
}
