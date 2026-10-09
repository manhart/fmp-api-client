<?php
declare(strict_types=1);

namespace Manhart\Fmp\Recording;

use Manhart\Fmp\HttpResponse;

final class CaptureCache
{
    public function __construct(private readonly string $directory)
    {
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new \RuntimeException('Cannot create recording directory.');
        }
    }

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
        return is_file($this->directory.'/'.$key.'.json');
    }

    public function begin(string $key, string $id, string $url): void
    {
        $this->writeMetadata($key, ['id' => $id, 'url' => $url, 'capturedAt' => gmdate('c'), 'outcome' => 'incomplete', 'receivedBytes' => 0]);
    }

    public function save(string $key, HttpResponse $response, string $outcome, ?string $error = null): void
    {
        $metadata = $this->metadata($key);
        $bodyPath = $this->directory.'/'.$key.'.body';
        if (file_put_contents($bodyPath.'.tmp', $response->body) === false || !rename($bodyPath.'.tmp', $bodyPath)) {
            throw new \RuntimeException('Cannot write response recording.');
        }
        $metadata += ['status' => $response->status, 'contentType' => $response->contentType];
        $metadata['outcome'] = $outcome;
        $metadata['receivedBytes'] = $response->receivedBytes;
        $metadata['sha256'] = hash('sha256', $response->body);
        $metadata['error'] = $error;
        $this->writeMetadata($key, $metadata);
    }

    public function metadata(string $key): array
    {
        $path = $this->directory.'/'.$key.'.json';
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new \RuntimeException('Recording metadata is missing.');
        }
        return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    }

    public function response(string $key): HttpResponse
    {
        $metadata = $this->metadata($key);
        if (!isset($metadata['sha256'])) {
            throw new \RuntimeException('Previous recording attempt was interrupted. Use an explicit refresh.');
        }
        $body = file_get_contents($this->directory.'/'.$key.'.body');
        if ($body === false || hash('sha256', $body) !== $metadata['sha256']) {
            throw new \RuntimeException('Recording is missing or corrupt. Use an explicit refresh.');
        }
        if ($metadata['outcome'] === 'incomplete') {
            throw new \RuntimeException('Recording is incomplete. Use an explicit refresh.');
        }
        return new HttpResponse($body, $metadata['status'], $metadata['contentType'], $metadata['receivedBytes']);
    }

    private function writeMetadata(string $key, array $metadata): void
    {
        $path = $this->directory.'/'.$key.'.json';
        if (file_put_contents($path.'.tmp', json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n") === false || !rename($path.'.tmp', $path)) {
            throw new \RuntimeException('Cannot write recording metadata.');
        }
    }
}
