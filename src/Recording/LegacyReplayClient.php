<?php
declare(strict_types=1);

namespace Manhart\Fmp\Recording;

use Manhart\Fmp\Client\LegacyApiClient;

final class LegacyReplayClient extends LegacyApiClient
{
    use ReplayTransport;
}
