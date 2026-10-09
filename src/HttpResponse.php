<?php
declare(strict_types=1);

namespace Manhart\Fmp;

final readonly class HttpResponse
{
    public function __construct(
        public string $body,
        public int $status,
        public string $contentType,
        public int $receivedBytes,
    ) {
    }
}
