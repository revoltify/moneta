<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\ArchiveCategory;
use App\Mcp\Tools\ArchiveWallet;
use App\Mcp\Tools\CreateCategory;
use App\Mcp\Tools\CreateRecurring;
use App\Mcp\Tools\CreateWallet;
use App\Mcp\Tools\DeleteRecurring;
use App\Mcp\Tools\GetBalanceSheet;
use App\Mcp\Tools\GetCashFlow;
use App\Mcp\Tools\GetDashboardSummary;
use App\Mcp\Tools\GetIncomeStatement;
use App\Mcp\Tools\GetMonthlySummary;
use App\Mcp\Tools\ListBudgets;
use App\Mcp\Tools\ListCategories;
use App\Mcp\Tools\ListRecurring;
use App\Mcp\Tools\ListTransactions;
use App\Mcp\Tools\ListWallets;
use App\Mcp\Tools\ReconcileWallet;
use App\Mcp\Tools\RecordTransaction;
use App\Mcp\Tools\RecordTransfer;
use App\Mcp\Tools\RemoveBudget;
use App\Mcp\Tools\SetBudget;
use App\Mcp\Tools\ToggleRecurring;
use App\Mcp\Tools\UpdateCategory;
use App\Mcp\Tools\UpdateTransaction;
use App\Mcp\Tools\UpdateWallet;
use App\Mcp\Tools\VoidTransaction;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Moneta')]
#[Version('1.0.0')]
#[Instructions(<<<'TEXT'
Moneta is a company-scoped money management system. Each company has a base currency; reports and dashboards only count wallets in that currency.

- Every tool accepts an optional "company" parameter (the company slug). Omit it to use the authenticated user's current company.
- Money INPUT parameters (amount, opening_balance, actual_balance) are decimal strings in the company's currency, e.g. "1500.50".
- Money values in OUTPUT are integers in minor units (1/100 of the currency) unless the field name ends with "Formatted".
- Dates are YYYY-MM-DD strings interpreted in the company's timezone.
- Wallet and category parameters accept either a numeric id or an exact name.
- Transaction types: income, expense, transfer, capital_investment, capital_withdrawal. Income/expense require a category of the matching kind; capital moves take no category.
TEXT)]
final class MonetaServer extends Server
{
    protected array $tools = [
        ListWallets::class,
        ListCategories::class,
        ListTransactions::class,
        ListBudgets::class,
        ListRecurring::class,
        GetDashboardSummary::class,
        GetIncomeStatement::class,
        GetBalanceSheet::class,
        GetCashFlow::class,
        GetMonthlySummary::class,
        RecordTransaction::class,
        RecordTransfer::class,
        UpdateTransaction::class,
        VoidTransaction::class,
        CreateWallet::class,
        UpdateWallet::class,
        ArchiveWallet::class,
        ReconcileWallet::class,
        CreateCategory::class,
        UpdateCategory::class,
        ArchiveCategory::class,
        SetBudget::class,
        RemoveBudget::class,
        CreateRecurring::class,
        ToggleRecurring::class,
        DeleteRecurring::class,
    ];
}
