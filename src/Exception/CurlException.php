<?php
declare(strict_types=1);

namespace Manhart\Fmp\Exception;

class CurlException extends \RuntimeException
{
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
