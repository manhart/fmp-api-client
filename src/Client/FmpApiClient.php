<?php
declare(strict_types=1);

namespace Manhart\Fmp\Client;

use Manhart\Fmp\Response\FileResponse;
use Manhart\Fmp\Response\Response;
use Manhart\Fmp\Response\Stable\StableEndpointRegistry;

class FmpApiClient extends AbstractApiClient
{
    use StableClientMethods;

    public function fetch(string $endpoint, ...$params): Response|FileResponse
    {
        $class = StableEndpointRegistry::resolve($endpoint);
        return $class::fetch($this, ...$params);
    }
}
