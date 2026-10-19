<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Client;

use Manhart\Fmp\HttpResponse;
use Manhart\Fmp\Exception\CurlException;
use Manhart\Fmp\Exception\HttpException;

/** Shared HTTP transport; credentials and request counters belong to each instance. */
abstract class AbstractApiClient
{
    private const int ERROR_BODY_EXCERPT_BYTES = 2048;

    protected string $baseUrl = 'https://financialmodelingprep.com';
    private int $requests = 0;
    private string $lastEndpointURL = '';

    /**
     * @param string $apiKey Supplied by the application.
     * @param int $timeoutSeconds Total transfer timeout in seconds.
     * @param int $connectTimeoutSeconds Connection timeout in seconds.
     * @param int|null $maxResponseBytes Per-request body limit; null leaves it unlimited.
     */
    public function __construct(
        #[\SensitiveParameter] private readonly string $apiKey,
        private readonly int $timeoutSeconds = 60,
        private readonly int $connectTimeoutSeconds = 10,
        private readonly ?int $maxResponseBytes = null,
    ) {
        if ($apiKey === '' || strpbrk($apiKey, "\r\n") !== false) {
            throw new \InvalidArgumentException('A non-empty API key without line breaks is required.');
        }
        if ($timeoutSeconds <= 0 || $connectTimeoutSeconds <= 0 || ($maxResponseBytes !== null && $maxResponseBytes <= 0)) {
            throw new \InvalidArgumentException('Timeouts and response byte limit must be positive.');
        }
    }

    /** Hide the stored key in var_dump(), print_r() and __debugInfo()-aware dumpers such as Symfony VarDumper. */
    public function __debugInfo(): array
    {
        // Mangled names let dumpers that start from (array) $this replace the real private entry.
        $info = get_mangled_object_vars($this);
        $info["\0".self::class."\0apiKey"] = '[REDACTED]';
        return $info;
    }

    /**
     * Send one request and return its undecoded body, including HTTP error responses.
     * No retries or pagination; transfer failures and byte limits raise CurlException.
     *
     * @param string $endpoint Relative route, e.g. stable/profile or v3/quote.
     * @param array $params Leading positional values become path segments; named values
     *     become query parameters. Null values are omitted; dates use YYYY-MM-DD.
     * @throws \InvalidArgumentException If the endpoint URL contains a null byte.
     * @throws \JsonException
     */
    public function request(string $endpoint, array $params = []): HttpResponse
    {
        $url = $this->buildEndpointURL($endpoint, $params);
        if (str_contains($url, "\0")) {
            throw new \InvalidArgumentException('Endpoint URL must not contain null bytes.');
        }
        $separator = str_contains($url, '?') ? '&' : '?';
        $channel = curl_init($url.$separator.'apikey='.rawurlencode($this->apiKey));
        if ($channel === false) {
            throw new CurlException('Unable to initialize cURL.');
        }
        $body = '';
        $receivedBytes = 0;
        $exceeded = false;
        $protocols = defined('CURLOPT_PROTOCOLS_STR')
            ? [constant('CURLOPT_PROTOCOLS_STR') => 'http,https', constant('CURLOPT_REDIR_PROTOCOLS_STR') => 'https']
            : [CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS, CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS];
        curl_setopt_array($channel, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeoutSeconds,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_WRITEFUNCTION => function (\CurlHandle $handle, string $chunk) use (&$body, &$receivedBytes, &$exceeded): int {
                $length = strlen($chunk);
                if ($this->maxResponseBytes !== null && $receivedBytes + $length > $this->maxResponseBytes) {
                    $remaining = $this->maxResponseBytes - $receivedBytes;
                    $body .= substr($chunk, 0, $remaining);
                    $receivedBytes += $length;
                    $exceeded = true;
                    return 0;
                }
                $body .= $chunk;
                $receivedBytes += $length;
                return $length;
            },
        ] + $protocols);
        $this->requests++;
        $result = curl_exec($channel);
        $status = curl_getinfo($channel, CURLINFO_RESPONSE_CODE);
        $contentType = curl_getinfo($channel, CURLINFO_CONTENT_TYPE) ?? '';
        $error = curl_error($channel);
        $errorCode = curl_errno($channel);
        unset($channel);
        if ($result === false) {
            throw new CurlException(
                $exceeded ? 'Response exceeded the configured byte limit.' : $this->redact($error),
                $errorCode,
                receivedBytes: $receivedBytes,
                partialBody: $this->redact($body),
            );
        }
        return new HttpResponse($body, $status, $contentType, $receivedBytes);
    }

    /**
     * Return the body of a successful request; reject non-2xx HTTP status codes.
     * @throws CurlException If the transfer fails or exceeds its byte limit.
     * @throws HttpException For non-2xx responses, retaining redacted diagnostic details.
     * @throws \JsonException
     */
    public function executeCurl(string $endpoint, array $params = []): string
    {
        $response = $this->request($endpoint, $params);
        if ($response->status < 200 || $response->status >= 300) {
            $body = $this->redact($response->body);
            $length = min(strlen($body), self::ERROR_BODY_EXCERPT_BYTES);
            // A continuation byte just beyond the cut belongs to the last included character.
            while ($length > 0 && isset($body[$length]) && (ord($body[$length]) & 0xC0) === 0x80) {
                --$length;
            }
            throw new HttpException(
                status: $response->status,
                endpointURL: $this->lastEndpointURL,
                bodyExcerpt: substr($body, 0, $length),
                receivedBytes: $response->receivedBytes,
            );
        }
        return $response->body;
    }

    /**
     * Build and remember the diagnostic URL; authentication is added only when sending.
     * @throws \JsonException
     */
    protected function buildEndpointURL(string $endpoint, array $params): string
    {
        $endpoint = ltrim($endpoint, '/');
        while ($params && !is_string(array_key_first($params))) {
            $param = array_shift($params);
            $endpoint .= '/'.(is_array($param)
                ? implode(',', array_map(rawurlencode(...), $param))
                : rawurlencode((string)$param));
        }
        if (str_starts_with($endpoint, 'v3/') || str_starts_with($endpoint, 'v4/')) {
            $endpoint = "api/$endpoint";
        }
        unset($params['apikey']);
        foreach ($params as $key => $value) {
            $params[$key] = self::formatParameter($value);
        }
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $url = "$this->baseUrl/$endpoint".($query === '' ? '' : '?'.$query);
        $this->lastEndpointURL = $this->redact($url);
        return $url;
    }

    /**
     * Serialize enum values, local calendar dates, comma-separated lists, and textual booleans.
     * @throws \JsonException
     */
    private static function formatParameter(string|int|float|bool|array|\BackedEnum|\DateTimeInterface|null $value): string|int|float|null
    {
        return match (true) {
            $value instanceof \BackedEnum => $value->value,
            $value instanceof \DateTimeInterface => $value->format('Y-m-d'),
            is_array($value) => implode(',', $value),
            is_bool($value) => $value ? 'true' : 'false',
            is_float($value) => json_encode($value, JSON_THROW_ON_ERROR),
            default => $value,
        };
    }

    /** Replace the literal and URL-encoded key in diagnostic text or captured payloads. */
    public function redact(string $text): string
    {
        return str_replace([$this->apiKey, rawurlencode($this->apiKey)], '[REDACTED]', $text);
    }

    /** Count attempted transfers, including failures; planning and replay do not increment it. */
    public function getRequests(): int
    {
        return $this->requests;
    }

    /** Last diagnostic URL without authentication, or an empty string before any request. */
    public function getLastEndpointURL(): string
    {
        return $this->lastEndpointURL;
    }
}
