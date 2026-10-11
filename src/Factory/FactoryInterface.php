<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Factory;

use Manhart\Fmp\Client\AbstractApiClient;
use Manhart\Fmp\Response\ResponseInterface;

/** Endpoint factories request and decode their own route using the supplied client. */
interface FactoryInterface
{
    public static function create(AbstractApiClient $client, ...$params): ResponseInterface;
}
