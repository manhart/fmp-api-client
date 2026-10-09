<?php
declare(strict_types=1);

namespace Manhart\Fmp\Recording;

use Manhart\Fmp\Client\FmpApiClient;

final class ReplayClient extends FmpApiClient
{
    use ReplayTransport;
}
