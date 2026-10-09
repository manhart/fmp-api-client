<?php
declare(strict_types=1);

namespace Manhart\Fmp\Tests\Support;

use Manhart\Fmp\Client\LegacyApiClient;

class LegacyFixtureClient extends LegacyApiClient
{
    use FixtureTransport;
}
