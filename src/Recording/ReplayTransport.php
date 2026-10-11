<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Recording;

use Manhart\Fmp\HttpResponse;

/**
 * Replace network transfers with checksum-verified captures, including saved HTTP status codes.
 *
 * @mixin \Manhart\Fmp\Client\AbstractApiClient
 */
trait ReplayTransport
{
    public function __construct(private readonly CaptureCache $cache)
    {
        // Stable and Legacy hosts share AbstractApiClient's constructor.
        //noinspection PhpMultipleClassDeclarationsInspection
        parent::__construct('replay-placeholder');
    }

    /**
     * A missing, interrupted or corrupt capture fails locally; there is no live fallback.
     *
     * @throws \JsonException If query encoding or stored JSON metadata fails.
     */
    public function request(string $endpoint, array $params = []): HttpResponse
    {
        $url = $this->buildEndpointURL($endpoint, $params);
        return $this->cache->response(CaptureCache::key($url));
    }
}
