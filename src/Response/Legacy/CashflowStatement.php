<?php
declare(strict_types=1);
/*
 * This file is part of manhart/fmp-api-client.
 *
 * @copyright Copyright (c) 2023-2026. Alexander Manhart IT
 * @authors Alexander Manhart
 *
 * CashflowStatement.php created on 10.03.26, 23:22.
 */

namespace Manhart\Fmp\Response\Legacy;

use Manhart\Fmp\Response\Response;

class CashflowStatement extends Response
{
    protected static string $url = 'v3/cash-flow-statement';

    public const PERIOD_QUARTER = 'quarter',
        PERIOD_ANNUAL = 'annual';

    public function getDate(): \DateTimeInterface
    {
        return $this->getResponseValueAsDate('date');
    }

    public function getSymbol(): string
    {
        return $this->getResponseValueAsString('symbol');
    }

    public function getReportedCurrency(): string
    {
        return $this->getResponseValueAsString('reportedCurrency');
    }

    public function getCIKAsInt(): ?int
    {
        return $this->getResponseValueAsInt('cik') ?: null;
    }

    public function getCIK(): ?string
    {
        return $this->getResponseValue('cik') ?: null;
    }

    public function getFillingDate(): ?\DateTimeInterface
    {
        return $this->getResponseValueAsDate('fillingDate');
    }

    public function getAcceptedDate(): ?\DateTimeInterface
    {
        return $this->getResponseValueAsDate('acceptedDate');
    }

    public function getCalendarYear(): ?int
    {
        return $this->getResponseValueAsInt('calendarYear') ?: null;
    }

    public function getPeriod(): string
    {
        return $this->getResponseValueAsString('period');
    }

    public function getNetIncome(): int
    {
        return $this->getResponseValueAsInt('netIncome');
    }

    public function getDepreciationAndAmortization(): int
    {
        return $this->getResponseValueAsInt('depreciationAndAmortization');
    }

    public function getDeferredIncomeTax(): int
    {
        return $this->getResponseValueAsInt('deferredIncomeTax');
    }

    public function getStockBasedCompensation(): int
    {
        return $this->getResponseValueAsInt('stockBasedCompensation');
    }

    public function getChangeInWorkingCapital(): int
    {
        return $this->getResponseValueAsInt('changeInWorkingCapital');
    }

    public function getAccountsReceivables(): int
    {
        return $this->getResponseValueAsInt('accountsReceivables');
    }

    public function getInventory(): int
    {
        return $this->getResponseValueAsInt('inventory');
    }

    public function getAccountsPayables(): int
    {
        return $this->getResponseValueAsInt('accountsPayables');
    }

    public function getOtherWorkingCapital(): int
    {
        return $this->getResponseValueAsInt('otherWorkingCapital');
    }

    public function getOtherNonCashItems(): int
    {
        return $this->getResponseValueAsInt('otherNonCashItems');
    }

    public function getNetCashProvidedByOperatingActivities(): int
    {
        return $this->getResponseValueAsInt('netCashProvidedByOperatingActivities');
    }

    public function getInvestmentsInPropertyPlantAndEquipment(): int
    {
        return $this->getResponseValueAsInt('investmentsInPropertyPlantAndEquipment');
    }

    public function getAcquisitionsNet(): int
    {
        return $this->getResponseValueAsInt('acquisitionsNet');
    }

    public function getPurchasesOfInvestments(): int
    {
        return $this->getResponseValueAsInt('purchasesOfInvestments');
    }

    public function getSalesMaturitiesOfInvestments(): int
    {
        return $this->getResponseValueAsInt('salesMaturitiesOfInvestments');
    }

    public function getOtherInvestingActivites(): int
    {
        return $this->getResponseValueAsInt('otherInvestingActivites');
    }

    public function getNetCashUsedForInvestingActivites(): int
    {
        return $this->getResponseValueAsInt('netCashUsedForInvestingActivites');
    }

    public function getDebtRepayment(): int
    {
        return $this->getResponseValueAsInt('debtRepayment');
    }

    public function getCommonStockIssued(): int
    {
        return $this->getResponseValueAsInt('commonStockIssued');
    }

    public function getCommonStockRepurchased(): int
    {
        return $this->getResponseValueAsInt('commonStockRepurchased');
    }

    public function getDividendsPaid(): int
    {
        return $this->getResponseValueAsInt('dividendsPaid');
    }

    public function getOtherFinancingActivites(): int
    {
        return $this->getResponseValueAsInt('otherFinancingActivites');
    }

    public function getNetCashUsedProvidedByFinancingActivities(): int
    {
        return $this->getResponseValueAsInt('netCashUsedProvidedByFinancingActivities');
    }

    public function getEffectOfForexChangesOnCash(): int
    {
        return $this->getResponseValueAsInt('effectOfForexChangesOnCash');
    }

    public function getNetChangeInCash(): int
    {
        return $this->getResponseValueAsInt('netChangeInCash');
    }

    public function getCashAtEndOfPeriod(): int
    {
        return $this->getResponseValueAsInt('cashAtEndOfPeriod');
    }

    public function getCashAtBeginningOfPeriod(): int
    {
        return $this->getResponseValueAsInt('cashAtBeginningOfPeriod');
    }

    public function getOperatingCashFlow(): int
    {
        return $this->getResponseValueAsInt('operatingCashFlow');
    }

    public function getCapitalExpenditure(): int
    {
        return $this->getResponseValueAsInt('capitalExpenditure');
    }

    public function getFreeCashFlow(): int
    {
        return $this->getResponseValueAsInt('freeCashFlow');
    }

    public function getLink(): string
    {
        return $this->getResponseValueAsString('link');
    }

    public function getFinalLink(): string
    {
        return $this->getResponseValueAsString('finalLink');
    }
}
