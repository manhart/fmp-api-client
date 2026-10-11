<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Exception;

/** Failure to obtain or decode a usable endpoint response, including FMP error payloads. */
class ResponseException extends \RuntimeException
{
    /** Wrap an already-redacted decoder error without retaining its original throwable or payload. */
    public static function fromDecoderError(string $message, int $code, string $endpointURL): self
    {
        return new self(
            "Failed to decode response for $endpointURL: $message",
            $code,
            new self($message, $code),
        );
    }
}
