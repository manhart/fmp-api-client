<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response;

/**
 * Stable row response: object payloads become a one-row list; JSON values remain intact.
 * Generated endpoint subclasses declare fetch() with their own parameter signatures.
 *
 * @method static static fetch(\Manhart\Fmp\Client\FmpApiClient $client, mixed ...$params)
 */
abstract class Response extends AbstractResponse
{
    /** Example fields from the reference, not a projection or a required schema. */
    public const array FIELDS = [];
    /** Query parameter names and PHP types used to describe the generated contract. */
    public const array PARAMS = [];

    public static function fields(): array
    {
        return static::FIELDS;
    }

    public static function params(): array
    {
        return static::PARAMS;
    }

    /** @throws \JsonException If the JSON body is malformed; create() wraps it in ResponseException. */
    protected static function decodePayload(string $payload): array
    {
        $data = parent::decodePayload($payload);
        return array_is_list($data) ? $data : [$data];
    }
}
