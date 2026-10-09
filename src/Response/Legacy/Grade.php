<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023-2026. Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * Grade.php created on 10.03.26, 23:22.
 */

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\Response;

class Grade extends Response
{
    protected static string $url = 'v3/grade';

    public function getSymbol(): string
    {
        return $this->getResponseValue('symbol');
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->getResponseValueAsDate('date');
    }

    public function getGradingCompany(): string
    {
        return $this->getResponseValueAsString('gradingCompany');
    }

    public function getPreviousGrade(): string
    {
        return $this->getResponseValueAsString('previousGrade');
    }

    public function getNewGrade(): string
    {
        return $this->getResponseValueAsString('newGrade');
    }
}
