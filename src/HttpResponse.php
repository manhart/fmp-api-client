<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp;

/** Undecoded body and HTTP metadata; interpreting status and payload is the caller's job. */
final readonly class HttpResponse
{
    /**
     * @param int $receivedBytes Received body bytes before truncation or redaction;
     *     excludes HTTP headers and TLS overhead and may differ from strlen($body).
     */
    public function __construct(
        public string $body,
        public int $status,
        public string $contentType,
        public int $receivedBytes,
    ) {
    }
}
