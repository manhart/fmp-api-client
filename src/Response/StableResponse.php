<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023-2026. Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * StableResponse.php created on 10.03.26, 23:31.
 */

namespace Manhart\Fmp\Response;


abstract class StableResponse extends Response
{
    public const array FIELDS = [];
    public const array PARAMS = [];

    public static function fields(): array
    {
        return static::FIELDS;
    }

    public static function params(): array
    {
        return static::PARAMS;
    }

    protected static function decodePayload(string $payload): array
    {
        $data = parent::decodePayload($payload);
        return array_is_list($data) ? $data : [$data];
    }

}
