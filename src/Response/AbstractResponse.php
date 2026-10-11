<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response;

use Iterator;
use Manhart\Fmp\Client\AbstractApiClient;
use Manhart\Fmp\Exception\ResponseException;
use Manhart\Fmp\Factory\FactoryInterface;

/**
 * Shared row container and typed access to the current iterator row.
 * Getters use their fallback for missing/invalid values; JSON values are stored unchanged.
 * CSV values stay strings until read through a typed getter.
 * Pass null as a numeric getter's fallback to distinguish missing data from zero.
 */
abstract class AbstractResponse implements ResponseInterface, FactoryInterface, Iterator, \Countable
{
    protected static string $url;

    protected array $response;

    private int $position = 0;

    public function __construct(array $response)
    {
        $this->response = $response;
    }

    /**
     * Request and decode the subclass route, preserving the decoder's payload shape.
     *
     * @param mixed ...$params Positional path segments followed by named query arguments.
     * @throws \Manhart\Fmp\Exception\CurlException If the transfer fails; its details are preserved.
     * @throws \Manhart\Fmp\Exception\HttpException For a completed non-2xx response.
     * @throws ResponseException If payload decoding fails; messages redact the key.
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public static function create(AbstractApiClient $client, ...$params): static
    {
        $payload = $client->executeCurl(static::getUrl(), $params);
        try {
            $data = static::decodePayload($payload);
        } catch (\JsonException | ResponseException $exception) {
            throw ResponseException::fromDecoderError(
                $client->redact($exception->getMessage()),
                $exception->getCode(),
                $client->getLastEndpointURL(),
            );
        }

        return new static($data);
    }

    /** @throws \JsonException If the JSON body is malformed; create() wraps decoding failures. */
    protected static function decodePayload(string $payload): array
    {
        return PayloadDecoder::decode($payload);
    }

    public static function getUrl(): string
    {
        return static::$url;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    /** Cast numeric values/strings to int; missing, empty or nonnumeric values use the fallback. */
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
     * Read a scalar as text; empty values or a normalizer returning null use the fallback.
     * A missing/null field supplies the default to conversion; whitespace is preserved.
     *
     * @param callable|null $normalizer Receives the string; may return a scalar or null.
     */
    public function getResponseValueAsString(string $key, ?string $default = null, ?callable $normalizer = null): ?string
    {
        return $this->asString($this->getResponseValue($key, $default), $default, $normalizer);
    }

    /** Convert numeric values/strings to float; missing, empty or invalid values use the fallback. */
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

    /**
     * Select the first candidate that is neither null nor an empty string; zero/false are valid.
     *
     * @param list<string> $keys Field aliases in priority order; conversion happens after selection.
     */
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
     * Convert the first non-null/non-empty alias; whitespace, zero and false count as present.
     * Normalization follows selection, so a rejected value uses the default, not the next alias.
     * If no alias exists, convert the default; neither string getter trims implicitly.
     *
     * @param callable|null $normalizer Receives the selected scalar as a string; may return a scalar or null.
     */
    public function getResponseValueAsStringByKeys(array $keys, ?string $default = null, ?callable $normalizer = null): ?string
    {
        return $this->asString($this->getResponseValueByKeys($keys, $default), $default, $normalizer);
    }

    /** Convert supported scalar values and callback results without changing the stored row. */
    private function asString(mixed $value, ?string $default, ?callable $normalizer): ?string
    {
        if (!\is_scalar($value))
            return $default;

        $value = (string)$value;
        if ($normalizer)
            $value = $normalizer($value);

        $value = \is_scalar($value) ? (string)$value : '';
        return $value === '' ? $default : $value;
    }

    /** Convert the selected candidate; a nonnumeric value uses the fallback, not the next key. */
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

    /**
     * Read DateTime values, Unix seconds (including digit strings), or PHP-parsable date text.
     * Invalid dates use the fallback; timestamp dates use UTC, other text follows DateTime rules.
     */
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

    /** Recognize numeric and yes/no-style booleans; unknown strings use the fallback. */
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

    /** Read the current row; null/missing fields and an exhausted iterator use the fallback. */
    public function getResponseValue(string $key, mixed $default = null): mixed
    {
        return $this->valid() ? ($this->current()[$key] ?? $default) : $default;
    }

    public function valid(): bool
    {
        return isset($this->response[$this->position]);
    }

    public function current(): array
    {
        return $this->response[$this->position];
    }

    public function count(): int
    {
        return \count($this->response);
    }

    /** Whether any data is stored, independent of the current iterator position. */
    public function hasResponse(): bool
    {
        return (bool)$this->response;
    }
}
