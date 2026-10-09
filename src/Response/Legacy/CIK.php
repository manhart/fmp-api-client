<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023-2026. Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * CIK.php created on 10.03.26, 23:22.
 */

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\Response;

class CIK extends Response
{
    protected static string $url = 'v3/cik';

    public function getName(): string
    {
        return $this->getResponseValueAsString('name');
    }

    /**
     * @return int|null Central Index Key
     */
    public function getCIKAsInt(): ?int
    {
        return $this->getResponseValueAsInt('cik') ?: null;
    }

    public function getCIK(): string
    {
        return $this->getResponseValueAsString('cik');
    }
}
