<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023-2026. Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * ExecutiveCompensationBenchmark.php created on 10.03.26, 23:22.
 */

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\Response;

class ExecutiveCompensationBenchmark extends Response
{
    protected static string $url = 'v4/executive-compensation-benchmark';

    public function getIndustryTitle(): string
    {
        return $this->getResponseValueAsString('industryTitle');
    }

    public function getYear(): int
    {
        return $this->getResponseValueAsInt('year');
    }


    public function getAverageCompensation(): float
    {
        return $this->getResponseValueAsFloat('averageCompensation');
    }
}
