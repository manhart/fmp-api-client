<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023 Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * Response.php created on 27.09.23, 21:09.
 */

namespace Manhart\Fmp\Response;

use Iterator;
use JsonException;
use Manhart\Fmp\Client\AbstractApiClient;
use Manhart\Fmp\Exception\ResponseException;
use Manhart\Fmp\Factory\FactoryInterface;
use Throwable;
use function json_decode;

class Response implements ResponseInterface, FactoryInterface, Iterator, \Countable
{
    private const int JSON_DECODE_OPTIONS = 512;

    protected static string $url;

    protected static AbstractApiClient $client;

    protected array $response;

    private int $position = 0;

    public function __construct(array $response)
    {
        $this->response = $response;
    }

    /**
     * @throws ResponseException
     */
    public static function create(AbstractApiClient $client, ...$params): static
    {
        try {
            $data = static::decodePayload($client->executeCurl(static::getUrl(), $params));
        } catch (Throwable $e) {
            throw new ResponseException(
                $client->redact('Failed to create response for '.$client->getLastEndpointURL().': '.$e->getMessage()),
                $e->getCode(),
                new ResponseException($client->redact($e->getMessage()), $e->getCode()),
            );
        }

        return new static($data);
    }

    protected static function decodePayload(string $payload): array
    {
        return PayloadDecoder::decode($payload);
    }

    public static function getUrl(): string
    {
        return static::$url;
    }

    /**
     * @throws JsonException
     */
    protected static function decodeJson(string $json): array
    {
        return json_decode($json, true, self::JSON_DECODE_OPTIONS, \JSON_THROW_ON_ERROR);
    }

    /**
     * Move forward to next element
     *
     * @link https://php.net/manual/en/iterator.next.php
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * Return the key of the current element
     *
     * @link https://php.net/manual/en/iterator.key.php
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @link https://php.net/manual/en/iterator.rewind.php
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    public function getResponseValueAsInt(string $key, ?int $default = 0): ?int
    {
        $value = $this->getResponseValue($key, $default);
        if ($value === null || $value === '') {
            return $default;
        }
        if (\is_numeric($value)) {
            return (int)$value;
        }
        return $default;
    }

    /**
     * Returns a response value as string and optionally applies a normalizer callback.
     * Example normalizers: trim(...), strtoupper(...), custom string canonicalization.
     */
    public function getResponseValueAsString(string $key, ?string $default = null, ?callable $normalizer = null): ?string
    {
        $value = $this->getResponseValue($key, $default);
        if ($value === null) {
            return $default;
        }
        if (\is_string($value)) {
            $stringValue = $value;
        }
        elseif (\is_scalar($value)) {
            $stringValue = (string)$value;
        }
        else {
            return $default;
        }

        if ($normalizer !== null) {
            $normalized = $normalizer($stringValue);
            if ($normalized === null) {
                return $default;
            }
            if (\is_string($normalized)) {
                $stringValue = $normalized;
            }
            elseif (\is_scalar($normalized)) {
                $stringValue = (string)$normalized;
            }
            else {
                return $default;
            }
        }

        return $stringValue === '' ? $default : $stringValue;
    }

    public function getResponseValueAsFloat(string $key, ?float $default = 0.0): ?float
    {
        $value = $this->getResponseValue($key, $default);
        if ($value === null || $value === '') {
            return $default;
        }
        if (\is_numeric($value)) {
            return (float)$value;
        }
        return $default;
    }

    public function getResponseValueByKeys(array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            $value = $this->getResponseValue($key, null);
            if ($value === null || $value === '') {
                continue;
            }
            return $value;
        }
        return $default;
    }

    /**
     * Returns the first non-empty value from a key list as string and optionally normalizes it.
     */
    public function getResponseValueAsStringByKeys(array $keys, ?string $default = null, ?callable $normalizer = null): ?string
    {
        $value = $this->getResponseValueByKeys($keys, null);
        if ($value === null) {
            return $default;
        }
        if (\is_string($value)) {
            $value = \trim($value);
            return $value === '' ? $default : $value;
        }
        if (\is_scalar($value)) {
            $stringValue = \trim((string)$value);
        }
        else {
            return $default;
        }

        if ($normalizer !== null) {
            $normalized = $normalizer($stringValue);
            if ($normalized === null) {
                return $default;
            }
            if (\is_string($normalized)) {
                $stringValue = $normalized;
            }
            elseif (\is_scalar($normalized)) {
                $stringValue = (string)$normalized;
            }
            else {
                return $default;
            }
        }

        return $stringValue === '' ? $default : $stringValue;
    }

    public function getResponseValueAsFloatByKeys(array $keys, ?float $default = null): ?float
    {
        $value = $this->getResponseValueByKeys($keys, null);
        if ($value === null) {
            return $default;
        }
        if (\is_numeric($value)) {
            return (float)$value;
        }
        return $default;
    }

    public function getResponseValueAsIntByKeys(array $keys, ?int $default = null): ?int
    {
        $value = $this->getResponseValueByKeys($keys, null);
        if ($value === null) {
            return $default;
        }
        if (\is_numeric($value)) {
            return (int)$value;
        }
        return $default;
    }

    public function getResponseValueAsDate(string $key, ?\DateTimeInterface $default = null): ?\DateTimeInterface
    {
        try {
            $value = $this->getResponseValue($key, null);
            if ($value === null || $value === '') {
                return $default;
            }
            if ($value instanceof \DateTimeInterface) {
                return $value;
            }
            if (\is_int($value) || \is_float($value)) {
                return new \DateTime('@' . (string)(int)$value);
            }
            if (\is_string($value)) {
                // remove line breaks because they are not allowed in DateTime strings
                $value = str_replace(["\r", "\n"], '', $value);
                if ($value === '') {
                    return $default;
                }
                if (\ctype_digit($value)) {
                    $value = '@' . $value;
                }
                return new \DateTime($value);
            }
            return $default;
        }
        catch(\Exception) {
            return $default;
        }
    }

    public function getResponseValueAsBool(string $key, bool $default = false): bool
    {
        $value = $this->getResponseValue($key, null);
        if ($value === null || $value === '') {
            return $default;
        }
        if (\is_bool($value)) {
            return $value;
        }
        if (\is_int($value) || \is_float($value)) {
            return $value != 0;
        }
        if (\is_string($value)) {
            $normalized = \strtolower(\trim($value));
            if ($normalized === '') {
                return $default;
            }
            if (\in_array($normalized, ['1', 'true', 'yes', 'y', 'on'], true)) {
                return true;
            }
            if (\in_array($normalized, ['0', 'false', 'no', 'n', 'off'], true)) {
                return false;
            }
            return $default;
        }
        return $default;
    }

    public function getResponseValue(string $key, mixed $default = null): mixed
    {
        return $this->valid() ? ($this->current()[$key] ?? $default) : $default;
    }

    /**
     * Checks if the current position is valid
     *
     * @link https://php.net/manual/en/iterator.valid.php
     */
    public function valid(): bool
    {
        return isset($this->response[$this->position]);
    }

    /**
     * Return the current element
     *
     * @link https://php.net/manual/en/iterator.current.php
     */
    public function current(): array
    {
        return $this->response[$this->position];
    }

    public function dump(): void
    {
        /** @noinspection ForgottenDebugOutputInspection */
        \var_dump($this->response);
    }

    public function count(): int
    {
        return \count($this->response);
    }

    /**
     * @return bool response data available
     */
    public function hasResponse(): bool
    {
        return (bool)$this->response;
    }
}
