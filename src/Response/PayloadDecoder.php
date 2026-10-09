<?php
declare(strict_types=1);

namespace Manhart\Fmp\Response;

use Manhart\Fmp\Exception\ResponseException;

final class PayloadDecoder
{
    public static function decode(string $payload): array
    {
        $trimmed = ltrim($payload, "\xEF\xBB\xBF \t\r\n");
        if ($trimmed === '') {
            return [];
        }
        if ($trimmed[0] === '[' || $trimmed[0] === '{') {
            $data = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
            self::throwApiErrors($data);
            return $data;
        }
        if (str_starts_with($trimmed, 'Query Error:')) {
            throw new ResponseException(substr($trimmed, 0, strcspn($trimmed, "\r\n")));
        }
        if ($trimmed[0] === '<') {
            throw new ResponseException('Unexpected HTML/XML response.');
        }

        $stream = fopen('php://temp/maxmemory:8388608', 'w+b');
        if ($stream === false) {
            throw new ResponseException('Unable to allocate CSV parser stream.');
        }
        try {
            fwrite($stream, $trimmed);
            rewind($stream);
            $headers = fgetcsv($stream, separator: ',', enclosure: '"', escape: '');
            if ($headers === false || $headers === [null]) {
                return [];
            }
            if (count($headers) === 1 && !str_contains($trimmed, ',')) {
                throw new ResponseException('Unexpected non-JSON/non-CSV response.');
            }
            if (count(array_unique($headers)) !== count($headers) || in_array('', $headers, true)) {
                throw new ResponseException('Invalid CSV header.');
            }
            $rows = [];
            while (($row = fgetcsv($stream, separator: ',', enclosure: '"', escape: '')) !== false) {
                if ($row === [null]) {
                    continue;
                }
                if (count($row) !== count($headers)) {
                    throw new ResponseException('CSV row does not match the header.');
                }
                $rows[] = array_combine($headers, $row);
            }
            return $rows;
        } finally {
            fclose($stream);
        }
    }

    public static function throwApiErrors(array $data): void
    {
        if (isset($data['Error Message']) && is_string($data['Error Message'])) {
            throw new ResponseException($data['Error Message']);
        }
        if (isset($data['error'])) {
            $message = $data['message'] ?? $data['error'];
            throw new ResponseException(is_string($message) ? $message : 'FMP returned an API error.');
        }
    }
}
