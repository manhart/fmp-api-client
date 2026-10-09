<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023 Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * Response.php created on 27.09.23, 20:28.
 */

namespace Manhart\Fmp\Response;

interface ResponseInterface
{
    public function __construct(array $response);
    public static function getUrl(): string;
    public function getResponseValue(string $key, mixed $default = null): mixed;
    public function getResponseValueByKeys(array $keys, mixed $default = null): mixed;
    public function getResponseValueAsStringByKeys(array $keys, ?string $default = null, ?callable $normalizer = null): ?string;
    public function getResponseValueAsFloatByKeys(array $keys, ?float $default = null): ?float;
    public function getResponseValueAsIntByKeys(array $keys, ?int $default = null): ?int;
}
