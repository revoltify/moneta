<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Mcp\Concerns\InteractsWithCompany;
use App\Models\RecurringTransaction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
final class ListRecurring extends Tool
{
    use InteractsWithCompany;

    protected string $description = 'List recurring transaction schedules of a company. Amounts are integers in minor units (1/100 of the currency).';

    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->description('Company slug. Defaults to your current company.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $company = $this->company($request);

        $schedules = RecurringTransaction::query()
            ->forCompany($company)
            ->with(['wallet', 'counterWallet', 'category'])
            ->orderBy('name')
            ->get()
            ->map(fn (RecurringTransaction $recurring) => [
                'id' => $recurring->id,
                'name' => $recurring->name,
                'type' => $recurring->type->value,
                'wallet' => $recurring->wallet->name,
                'counterWallet' => $recurring->counterWallet?->name,
                'category' => $recurring->category?->name,
                'amount' => $recurring->amount,
                'frequency' => $recurring->frequency->value,
                'interval' => $recurring->interval,
                'nextRunOn' => $recurring->next_run_on->toDateString(),
                'endsOn' => $recurring->ends_on?->toDateString(),
                'active' => $recurring->is_active,
            ]);

        return Response::json(['company' => $company->slug, 'recurring' => $schedules]);
    }
}
