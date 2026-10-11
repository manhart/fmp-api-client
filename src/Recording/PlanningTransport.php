<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Recording;

/**
 * Replace transfers with synthetic payloads while retaining the endpoint and encoded URL.
 * Hosts derive from an API client and share AbstractApiClient's constructor and URL formatter.
 *
 * @mixin \Manhart\Fmp\Client\AbstractApiClient
 */
trait PlanningTransport
{
    public string $endpoint = '';
    public array $parameters = [];

    public function __construct()
    {
        // Stable and Legacy hosts have different parents but share this constructor implementation.
        //noinspection PhpMultipleClassDeclarationsInspection
        parent::__construct('planner-placeholder');
    }

    /**
     * Exercise the real factory/URL path without network calls or a real API key.
     *
     * @throws \JsonException If a query parameter cannot be encoded.
     */
    public function executeCurl(string $endpoint, array $params = []): string
    {
        $this->endpoint = $endpoint;
        $this->parameters = $params;
        $this->buildEndpointURL($endpoint, $params);
        if (str_ends_with($endpoint, 'xlsx')) {
            return "PK\x03\x04PLAN";
        }
        return str_contains($endpoint, 'historical-price-full') ? '{"symbol":"PLAN","historical":[]}' : '[]';
    }
}
