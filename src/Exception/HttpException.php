<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Exception;

/** Completed non-2xx response; the client redacts and bounds the diagnostic body excerpt. */
class HttpException extends ResponseException
{
    /**
     * @param string $endpointURL Diagnostic URL without authentication.
     * @param string $bodyExcerpt Already-redacted prefix, limited to 2,048 bytes by the client.
     * @param int $receivedBytes Actual received body bytes, before redaction and excerpt truncation.
     */
    public function __construct(
        public readonly int $status,
        string $endpointURL,
        public readonly string $bodyExcerpt = '',
        public readonly int $receivedBytes = 0,
    ) {
        $message = "FMP HTTP $status for $endpointURL";
        parent::__construct($bodyExcerpt === '' ? $message : "$message: $bodyExcerpt", $status);
    }
}
