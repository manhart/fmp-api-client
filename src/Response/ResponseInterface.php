<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response;

/** Row access uses the current iterator position; key alternatives are checked in order. */
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
