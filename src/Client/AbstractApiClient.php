<?php
declare(strict_types=1);

namespace Manhart\Fmp\Client;

use Manhart\Fmp\HttpResponse;
use Manhart\Fmp\Exception\CurlException;
use Manhart\Fmp\Exception\ResponseException;

abstract class AbstractApiClient
{
    protected string $baseUrl = 'https://financialmodelingprep.com';
    private int $requests = 0;
    private string $lastEndpointURL = '';

    public function __construct(
        private readonly string $apiKey,
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

    /** Performs one HTTP request. Does not retry or paginate. */
    public function request(string $endpoint, array $params = []): HttpResponse
    {
        $url = $this->buildEndpointURL($endpoint, $params);
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

    public function executeCurl(string $endpoint, array $params = []): string
    {
        $response = $this->request($endpoint, $params);
        if ($response->status < 200 || $response->status >= 300) {
            throw new ResponseException('FMP HTTP '.$response->status.' for '.$this->lastEndpointURL, $response->status);
        }
        return $response->body;
    }

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

    public function redact(string $text): string
    {
        return str_replace([$this->apiKey, rawurlencode($this->apiKey)], '[REDACTED]', $text);
    }

    /**
     * @return int Amount of Requests
     */
    public function getRequests(): int
    {
        return $this->requests;
    }

    public function getLastEndpointURL(): string
    {
        return $this->lastEndpointURL;
    }
}
