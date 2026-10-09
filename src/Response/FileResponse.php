<?php
declare(strict_types=1);

namespace Manhart\Fmp\Response;

use Manhart\Fmp\Client\AbstractApiClient;
use Manhart\Fmp\Exception\ResponseException;

abstract class FileResponse
{
    protected static string $url;
    public const array FIELDS = [];
    public const array PARAMS = [];

    final public function __construct(private readonly string $content)
    {
    }

    public static function getUrl(): string
    {
        return static::$url;
    }

    public static function create(AbstractApiClient $client, ...$params): static
    {
        $payload = $client->executeCurl(static::getUrl(), $params);
        if (str_starts_with(ltrim($payload), '{') || str_starts_with(ltrim($payload), '[')) {
            try {
                PayloadDecoder::decode($payload);
            } catch (\Throwable $e) {
                throw new ResponseException($client->redact($e->getMessage()), previous: new ResponseException($client->redact($e->getMessage())));
            }
        }
        if (!str_starts_with($payload, "PK\x03\x04")) {
            throw new ResponseException('Expected an XLSX file from '.$client->getLastEndpointURL());
        }
        return new static($payload);
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public static function fields(): array
    {
        return static::FIELDS;
    }

    public static function params(): array
    {
        return static::PARAMS;
    }
}
