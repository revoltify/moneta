<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\RecurrenceFrequency;
use App\Enums\TransactionType;
use App\Mcp\Concerns\InteractsWithCompany;
use App\Models\RecurringTransaction;
use App\Support\Money;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

final class CreateRecurring extends Tool
{
    use InteractsWithCompany;

    protected string $description = 'Create a recurring schedule that automatically posts an income, expense or transfer. Amount is a decimal string in the company currency.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->description('Company slug. Defaults to your current company.'),
            'name' => $schema->string()->description('Schedule name, e.g. "Office rent".')->required(),
            'type' => $schema->string()->enum(['income', 'expense', 'transfer'])->required(),
            'wallet' => $schema->string()->description('Wallet id or name.')->required(),
            'counter_wallet' => $schema->string()->description('Destination wallet for transfers.'),
            'category' => $schema->string()->description('Category id or name. Required for income and expense.'),
            'amount' => $schema->string()->description('Decimal amount in the company currency.')->required(),
            'frequency' => $schema->string()->enum(['daily', 'weekly', 'monthly', 'yearly'])->required(),
            'interval' => $schema->integer()->description('Repeat every N periods (1-12).')->default(1),
            'starts_on' => $schema->string()->description('First run date YYYY-MM-DD. Defaults to today in the company timezone.'),
            'ends_on' => $schema->string()->description('Optional end date YYYY-MM-DD.'),
            'description' => $schema->string()->description('Description used on posted transactions.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:income,expense,transfer',
            'wallet' => 'required',
            'amount' => 'required|numeric|min:0.01',
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'interval' => 'nullable|integer|min:1|max:12',
            'starts_on' => 'nullable|date',
            'ends_on' => 'nullable|date',
            'description' => 'nullable|string|max:255',
        ]);

        $company = $this->company($request);

        $type = TransactionType::from((string) $request->get('type'));
        $wallet = $this->wallet($company, $request->get('wallet'));

        $counterWallet = null;

        if ($type === TransactionType::Transfer) {
            if ($request->get('counter_wallet') === null) {
                return Response::error('counter_wallet is required for transfers.');
            }

            $counterWallet = $this->wallet($company, $request->get('counter_wallet'));

            if ($counterWallet->id === $wallet->id) {
                return Response::error('counter_wallet must differ from wallet.');
            }
        }

        $category = $type->requiresCategory()
            ? $this->category($company, (string) $request->get('category', ''), $type->categoryKind())
            : null;

        $startsOn = $request->get('starts_on') !== null
            ? Carbon::parse((string) $request->get('starts_on'))
            : Carbon::parse(now($company->timezone)->toDateString());

        if ($request->get('ends_on') !== null && Carbon::parse((string) $request->get('ends_on'))->lessThanOrEqualTo($startsOn)) {
            return Response::error('ends_on must be after starts_on.');
        }

        $frequency = RecurrenceFrequency::from((string) $request->get('frequency'));

        $recurring = RecurringTransaction::create([
            'company_id' => $company->id,
            'name' => (string) $request->get('name'),
            'type' => $type,
            'wallet_id' => $wallet->id,
            'counter_wallet_id' => $counterWallet?->id,
            'category_id' => $category?->id,
            'amount' => Money::toMinorUnits((string) $request->get('amount')),
            'description' => $request->get('description'),
            'frequency' => $frequency,
            'interval' => (int) $request->get('interval', 1),
            'day_of_month' => $frequency === RecurrenceFrequency::Monthly ? $startsOn->day : null,
            'starts_on' => $startsOn->toDateString(),
            'ends_on' => $request->get('ends_on'),
            'next_run_on' => $startsOn->toDateString(),
            'is_active' => true,
        ]);

        return Response::text(sprintf(
            'Recurring schedule "%s" (#%d) created: %s of %s, %s starting %s.',
            $recurring->name,
            $recurring->id,
            $type->label(),
            Money::format($recurring->amount, $company->currency),
            $frequency->value,
            $startsOn->toDateString(),
        ));
    }
}
