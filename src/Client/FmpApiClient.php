<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Client;

use Manhart\Fmp\Response\FileResponse;
use Manhart\Fmp\Response\Response;
use Manhart\Fmp\Response\Stable\EndpointRegistry;

/** Main Stable client: named shortcuts or generic dispatch to typed endpoint responses. */
class FmpApiClient extends AbstractApiClient
{
    use ClientMethods;

    /**
     * Resolve an endpoint and perform one request through its typed factory.
     *
     * @param string $endpoint Stable route with or without a leading / or stable/ prefix.
     * @param mixed ...$params Arguments of the endpoint's fetch() method, in order or by name.
     * @throws \JsonException If transport cannot encode parameters or read capture metadata.
     */
    public function fetch(string $endpoint, ...$params): Response|FileResponse
    {
        $class = EndpointRegistry::resolve($endpoint);
        return $class::fetch($this, ...$params);
    }
}
