<?php
declare(strict_types=1);

namespace Manhart\Fmp\Tests\Support;

use Manhart\Fmp\HttpResponse;

trait FixtureTransport
{
    public string $payload = '[{"symbol":"SYNTHETIC","price":12.5,"volume":10,"nullable":null}]';
    public int $status = 200;
    public string $contentType = 'application/json';
    public array $urls = [];
    public array $calls = [];

    public function __construct()
    {
        parent::__construct('synthetic-api-key');
    }

    public function request(string $endpoint, array $params = []): HttpResponse
    {
        $this->urls[] = $this->buildEndpointURL($endpoint, $params);
        $this->calls[] = [$endpoint, $params];
        return new HttpResponse($this->payload, $this->status, $this->contentType, strlen($this->payload));
    }
}
