<?php
declare(strict_types=1);

namespace Manhart\Fmp\Recording;

use Manhart\Fmp\HttpResponse;

trait ReplayTransport
{
    public function __construct(private readonly CaptureCache $cache)
    {
        parent::__construct('replay-placeholder');
    }

    public function request(string $endpoint, array $params = []): HttpResponse
    {
        $url = $this->buildEndpointURL($endpoint, $params);
        return $this->cache->response(CaptureCache::key($url));
    }
}
