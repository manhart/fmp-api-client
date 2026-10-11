<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Exception;

/** Transfer failure with byte accounting and the retained body prefix for diagnostics. */
class CurlException extends \RuntimeException
{
    /**
     * @param int $code cURL error code, rather than an HTTP status code.
     * @param int $receivedBytes Body bytes received, including the chunk that exceeded a limit.
     * @param string $partialBody Body retained before failure; the transport redacts its key.
     */
    public function __construct(
        string $message,
        int $code = 0,
        ?\Throwable $previous = null,
        public readonly int $receivedBytes = 0,
        public readonly string $partialBody = '',
    ) {
        parent::__construct($message, $code, $previous);
    }
}
