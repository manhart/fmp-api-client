<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Recording;

use Manhart\Fmp\HttpResponse;

/** Disk cache with atomic file replacement and SHA-256 verification of captured bodies. */
final class CaptureCache
{
    /** Create a private directory; reject symlinks and unsafe existing Unix ownership/permissions. */
    public function __construct(private readonly string $directory)
    {
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new \RuntimeException('Cannot create recording directory.');
        }
        $path = rtrim($directory, DIRECTORY_SEPARATOR) ?: DIRECTORY_SEPARATOR;
        clearstatcache(true, $path);
        $stat = lstat($path);
        if ($stat === false || ($stat['mode'] & 0170000) !== 0040000) {
            throw new \RuntimeException('Recording directory must be a real directory, not a symlink.');
        }
        if (PHP_OS_FAMILY !== 'Windows' && (
            ($stat['mode'] & 0077) !== 0
            || (function_exists('posix_geteuid') && $stat['uid'] !== posix_geteuid())
        )) {
            throw new \RuntimeException('Recording directory must be private (0700) and owned by the current user.');
        }
    }

    /** Hash route and sorted query parameters, ignoring the host and the apikey argument. */
    public static function key(string $url): string
    {
        $parts = parse_url($url);
        parse_str($parts['query'] ?? '', $query);
        unset($query['apikey']);
        ksort($query);
        return hash('sha256', ($parts['path'] ?? '').'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986));
    }

    public function exists(string $key): bool
    {
        return is_file($this->filePath($key.'.json'));
    }

    /** @return resource Exclusive recorder lock; the caller releases/closes it after the run. */
    public function acquireLock(): mixed
    {
        $lock = fopen($this->filePath('.lock'), 'c');
        if ($lock === false) {
            throw new \RuntimeException('Cannot open recorder lock.');
        }
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            throw new \RuntimeException('Another recorder is already running.');
        }
        return $lock;
    }

    /** @throws \JsonException If the invocation report cannot be encoded. */
    public function writeReport(array $report): void
    {
        $this->writeJson('report.json', $report);
    }

    /**
     * Mark the attempt incomplete before any transfer, so interrupted attempts cannot look verified.
     *
     * @throws \JsonException If attempt metadata cannot be encoded.
     */
    public function begin(string $key, string $id, string $url): void
    {
        $this->writeMetadata($key, ['id' => $id, 'url' => $url, 'capturedAt' => gmdate('c'), 'outcome' => 'incomplete', 'receivedBytes' => 0]);
    }

    /**
     * Save the body before its checksum metadata; begin() must have recorded this attempt first.
     *
     * @throws \JsonException If existing metadata is malformed or updated metadata cannot be encoded.
     */
    public function save(string $key, HttpResponse $response, string $outcome, ?string $error = null): void
    {
        $metadata = $this->metadata($key);
        $this->writeFile($key.'.body', $response->body);
        $metadata += ['status' => $response->status, 'contentType' => $response->contentType];
        $metadata['outcome'] = $outcome;
        $metadata['receivedBytes'] = $response->receivedBytes;
        $metadata['sha256'] = hash('sha256', $response->body);
        $metadata['error'] = $error;
        $this->writeMetadata($key, $metadata);
    }

    /** @throws \JsonException If the stored metadata is not valid JSON. */
    public function metadata(string $key): array
    {
        $path = $this->filePath($key.'.json');
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new \RuntimeException('Recording metadata is missing.');
        }
        return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Rebuild a captured response after checksum/completeness checks; reject partial captures.
     *
     * @throws \JsonException If the stored metadata is not valid JSON.
     */
    public function response(string $key): HttpResponse
    {
        $metadata = $this->metadata($key);
        if (!isset($metadata['sha256'])) {
            throw new \RuntimeException('Previous recording attempt was interrupted. Use an explicit refresh.');
        }
        $body = file_get_contents($this->filePath($key.'.body'));
        if ($body === false || hash('sha256', $body) !== $metadata['sha256']) {
            throw new \RuntimeException('Recording is missing or corrupt. Use an explicit refresh.');
        }
        if ($metadata['outcome'] === 'incomplete') {
            throw new \RuntimeException('Recording is incomplete. Use an explicit refresh.');
        }
        return new HttpResponse($body, $metadata['status'], $metadata['contentType'], $metadata['receivedBytes']);
    }

    /** @throws \JsonException If metadata contains values that JSON cannot encode. */
    private function writeMetadata(string $key, array $metadata): void
    {
        $this->writeJson($key.'.json', $metadata);
    }

    /** @throws \JsonException If the metadata or report cannot be encoded. */
    private function writeJson(string $filename, array $data): void
    {
        $this->writeFile($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
    }

    /** Reject symlinks, including dangling ones, and special files before reading or writing. */
    private function filePath(string $filename): string
    {
        $path = $this->directory.'/'.$filename;
        clearstatcache(true, $path);
        $stat = @lstat($path);
        if ($stat !== false && ($stat['mode'] & 0170000) !== 0100000) {
            throw new \RuntimeException('Recording files must be regular files, not symlinks or special files.');
        }
        return $path;
    }

    /** Exclusive temporary creation avoids following predictable .tmp links; rename stays atomic. */
    private function writeFile(string $filename, string $content): void
    {
        $path = $this->filePath($filename);
        $temporary = $this->directory.'/.capture-'.bin2hex(random_bytes(16));
        $stream = fopen($temporary, 'xb');
        if ($stream === false) {
            throw new \RuntimeException('Cannot create temporary recording file.');
        }
        try {
            if (!chmod($temporary, 0600) || fwrite($stream, $content) !== strlen($content)) {
                throw new \RuntimeException('Cannot write recording file.');
            }
            fclose($stream);
            $stream = null;
            $this->filePath($filename);
            if (!rename($temporary, $path)) {
                throw new \RuntimeException('Cannot replace recording file.');
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }
}
