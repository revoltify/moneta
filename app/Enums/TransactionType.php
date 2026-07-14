<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionType: string
{
    case Income = 'income';
    case Expense = 'expense';
    case Transfer = 'transfer';
    case CapitalWithdrawal = 'capital_withdrawal';
    case CapitalInvestment = 'capital_investment';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Income',
            self::Expense => 'Expense',
            self::Transfer => 'Transfer',
            self::CapitalWithdrawal => 'Capital Withdrawal',
            self::CapitalInvestment => 'Capital Investment',
        };
    }

    public function requiresCategory(): bool
    {
        return in_array($this, [self::Income, self::Expense], true);
    }

    public function categoryKind(): ?CategoryKind
    {
        return match ($this) {
            self::Income => CategoryKind::Income,
            self::Expense => CategoryKind::Expense,
            default => null,
        };
    }
}
