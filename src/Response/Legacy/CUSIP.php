<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2023-2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\AbstractResponse;

/** Security identity selected by its CUSIP identifier. */
class CUSIP extends AbstractResponse
{
    protected static string $url = 'v3/cusip';

    public function getCompany(): ?string
    {
        return $this->getResponseValueAsString('company');
    }

    public function getTicker(): ?string
    {
        return $this->getResponseValueAsString('ticker');
    }

    public function getCUSIP(): ?string
    {
        return $this->getResponseValueAsString('cusip');
    }

}
