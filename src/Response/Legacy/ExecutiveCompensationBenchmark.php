<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Executive compensation benchmarks for a reporting year. */
class ExecutiveCompensationBenchmark extends AbstractResponse
{
    protected static string $url = 'v4/executive-compensation-benchmark';

    public function getIndustryTitle(): ?string
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
