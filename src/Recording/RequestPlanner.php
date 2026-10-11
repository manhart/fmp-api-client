<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Recording;

use Manhart\Fmp\Client\FmpApiClient;

/** Resolve Stable/Legacy recording cases to the same URLs their live factories would request. */
final class RequestPlanner extends FmpApiClient
{
    use PlanningTransport;

    /**
     * Build a diagnostic URL without a transfer, retaining wire arguments for the recorder.
     *
     * @param string $id Stable endpoint path or legacy:methodName.
     * @param array $case Capture arguments (or parameters) and optional types for date conversion.
     * @throws \JsonException If query parameters cannot be encoded into the planned URL.
     */
    public function plan(string $id, array $case): string
    {
        $parameters = $case['capture'] ?? $case['parameters'];
        foreach ($case['types'] ?? [] as $name => $type) {
            if (str_contains($type, 'DateTimeInterface') && isset($parameters[$name])) {
                $parameters[$name] = new \DateTimeImmutable($parameters[$name]);
            }
        }
        if (str_starts_with($id, 'legacy:')) {
            $method = substr($id, 7);
            $legacy = new LegacyRequestPlanner();
            $legacy->$method(...$parameters);
            $this->endpoint = $legacy->endpoint;
            $this->parameters = $legacy->parameters;
            return $this->buildEndpointURL($this->endpoint, $this->parameters);
        } else {
            $this->fetch($id, ...$parameters);
        }
        return $this->getLastEndpointURL();
    }
}
