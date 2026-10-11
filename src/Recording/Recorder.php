<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Recording;

use Manhart\Fmp\Client\AbstractApiClient;
use Manhart\Fmp\Exception\CurlException;
use Manhart\Fmp\HttpResponse;
use Manhart\Fmp\Response\FileResponse;

/** Capture each request once and validate by replay, with per-instance transfer/rate budgets. */
final class Recorder
{
    private int $calls = 0;
    private int $receivedBytes = 0;
    private float $lastRequestAt = 0.0;

    /**
     * @param \Closure(int):AbstractApiClient $clientFactory Receives remaining body bytes;
     *     construct a client with that transfer limit.
     * @param int $maxBytes Aggregate received body budget for this recorder instance.
     * @param int $requestsPerMinute Spaces requests evenly; cache reads consume no calls.
     */
    public function __construct(
        private readonly CaptureCache $cache,
        private readonly \Closure $clientFactory,
        private readonly int $maxBytes = 1073741824,
        private readonly int $requestsPerMinute = 60,
    ) {
        if ($maxBytes <= 0 || $requestsPerMinute <= 0) {
            throw new \InvalidArgumentException('Recorder limits must be positive.');
        }
    }

    /**
     * Reuse a capture or make one transfer, then classify it through the normal response factory.
     * Incomplete attempts require an explicit refresh; successful verification covers this case only.
     *
     * @param string $id Stable endpoint path or legacy:methodName.
     * @param array $case Capture arguments, optional parameter types and expected example fields.
     * @param bool $refresh Replace an existing capture; may spend another API call.
     * @param bool $offline Read captures only; a missing capture is reported without a transfer.
     * @return array{id: string, outcome: string, cached: bool, receivedBytes?: int,
     *     httpStatus?: int, error?: string} Status report; provider/parse failures are classified.
     * @throws \JsonException If planning or capture metadata processing fails outside validation.
     */
    public function capture(string $id, array $case, bool $refresh = false, bool $offline = false): array
    {
        $legacy = str_starts_with($id, 'legacy:');
        $planner = new RequestPlanner();
        $url = $planner->plan($id, $case);
        $key = CaptureCache::key($url);
        $cached = $this->cache->exists($key);
        if (!$cached || $refresh) {
            if ($offline) {
                return ['id' => $id, 'outcome' => 'missing', 'cached' => false];
            }
            $remaining = $this->maxBytes - $this->receivedBytes;
            if ($remaining <= 0) {
                return ['id' => $id, 'outcome' => 'budget-exhausted', 'cached' => false];
            }
            $wait = 60 / $this->requestsPerMinute - (microtime(true) - $this->lastRequestAt);
            if ($wait > 0) {
                usleep((int)ceil($wait * 1000000));
            }
            $client = ($this->clientFactory)($remaining);
            if (!$client instanceof AbstractApiClient) {
                throw new \LogicException('Client factory must return an API client.');
            }
            $this->cache->begin($key, $id, $url);
            $this->lastRequestAt = microtime(true);
            $this->calls++;
            try {
                $http = $client->request($planner->endpoint, $planner->parameters);
                $this->receivedBytes += $http->receivedBytes;
                $http = new HttpResponse($client->redact($http->body), $http->status, $http->contentType, $http->receivedBytes);
                $this->cache->save($key, $http, 'recorded');
            } catch (CurlException $error) {
                $this->receivedBytes += $error->receivedBytes;
                $this->cache->save($key, new HttpResponse($client->redact($error->partialBody), 0, '', $error->receivedBytes), 'incomplete', $client->redact($error->getMessage()));
                return ['id' => $id, 'outcome' => 'incomplete', 'cached' => false, 'error' => $client->redact($error->getMessage())];
            }
        }
        try {
            if (($this->cache->metadata($key)['outcome'] ?? '') === 'incomplete') {
                return ['id' => $id, 'outcome' => 'incomplete', 'cached' => $cached && !$refresh];
            }
            $http = $this->cache->response($key);
            $metadata = $this->cache->metadata($key);
            if ($http->status < 200 || $http->status >= 300) {
                $outcome = in_array($http->status, [401, 402, 403, 429], true) ? 'blocked' : 'http-error';
                $this->cache->save($key, $http, $outcome, 'HTTP '.$http->status);
                return ['id' => $id, 'outcome' => $outcome, 'cached' => $cached && !$refresh, 'httpStatus' => $http->status];
            }
            $replay = $legacy ? new LegacyReplayClient($this->cache) : new ReplayClient($this->cache);
            $parameters = $case['capture'] ?? $case['parameters'];
            foreach ($case['types'] ?? [] as $name => $type) {
                if (str_contains($type, 'DateTimeInterface') && isset($parameters[$name])) {
                    $parameters[$name] = new \DateTimeImmutable($parameters[$name]);
                }
            }
            if ($legacy) {
                $method = substr($id, 7);
                $response = $replay->$method(...$parameters);
            } else {
                $response = $replay->fetch($id, ...$parameters);
            }
            $outcome = 'verified';
            if (!$response instanceof FileResponse) {
                if (count($response) === 0) {
                    $outcome = 'empty';
                } elseif (isset($case['fields']) && $case['fields'] && !array_intersect($case['fields'], array_keys($response->current()))) {
                    $outcome = 'schema-mismatch';
                }
            }
            $this->cache->save($key, $http, $outcome);
            return ['id' => $id, 'outcome' => $outcome, 'cached' => $cached && !$refresh, 'receivedBytes' => $metadata['receivedBytes']];
        } catch (\Throwable $error) {
            $outcome = 'parse-error';
            if (isset($http)) {
                $errorPayload = json_decode($http->body, true);
                if (is_array($errorPayload) && (isset($errorPayload['Error Message']) || isset($errorPayload['error']))) {
                    $outcome = 'api-error';
                }
                $this->cache->save($key, $http, $outcome, $error->getMessage());
            }
            return ['id' => $id, 'outcome' => $outcome, 'cached' => $cached && !$refresh, 'error' => $error->getMessage()];
        }
    }

    /** @return array{calls: int, receivedBytes: int, maxBytes: int} Live-transfer totals; replay adds none. */
    public function statistics(): array
    {
        return ['calls' => $this->calls, 'receivedBytes' => $this->receivedBytes, 'maxBytes' => $this->maxBytes];
    }
}
