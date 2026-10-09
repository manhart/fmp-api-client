<?php
declare(strict_types=1);

namespace Manhart\Fmp\Tests\Support;

use Manhart\Fmp\Client\FmpApiClient;

class FixtureClient extends FmpApiClient
{
    use FixtureTransport;
}
