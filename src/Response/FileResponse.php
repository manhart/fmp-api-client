<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response;

use Manhart\Fmp\Client\AbstractApiClient;
use Manhart\Fmp\Exception\ResponseException;

/**
 * Binary XLSX response; expose file bytes instead of iterable records or typed getters.
 * Generated endpoint subclasses declare fetch() with their own parameter signatures.
 *
 * @method static static fetch(\Manhart\Fmp\Client\FmpApiClient $client, mixed ...$params)
 */
abstract class FileResponse
{
    protected static string $url;
    public const array FIELDS = [];
    public const array PARAMS = [];

    final public function __construct(private readonly string $content)
    {
    }

    public static function getUrl(): string
    {
        return static::$url;
    }

    /**
     * Request the file and check its ZIP signature; reject JSON API errors instead of saving them.
     *
     * @throws \Manhart\Fmp\Exception\CurlException If the transfer fails; its details are preserved.
     * @throws \Manhart\Fmp\Exception\HttpException For a completed non-2xx response.
     * @throws ResponseException For provider errors, decoding failures or unexpected file content.
     * @throws \JsonException If parameter encoding or cached transport metadata fails.
     */
    public static function create(AbstractApiClient $client, ...$params): static
    {
        $payload = $client->executeCurl(static::getUrl(), $params);
        if (str_starts_with(ltrim($payload), '{') || str_starts_with(ltrim($payload), '[')) {
            try {
                PayloadDecoder::decode($payload);
            } catch (\JsonException | ResponseException $exception) {
                throw ResponseException::fromDecoderError(
                    $client->redact($exception->getMessage()),
                    $exception->getCode(),
                    $client->getLastEndpointURL(),
                );
            }
        }
        if (!str_starts_with($payload, "PK\x03\x04")) {
            throw new ResponseException('Expected an XLSX file from '.$client->getLastEndpointURL());
        }
        return new static($payload);
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public static function fields(): array
    {
        return static::FIELDS;
    }

    public static function params(): array
    {
        return static::PARAMS;
    }
}
