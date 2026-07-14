<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Transactions\CreateTransfer;
use App\Mcp\Concerns\InteractsWithCompany;
use App\Support\AuditLogger;
use App\Support\Money;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

final class RecordTransfer extends Tool
{
    use InteractsWithCompany;

    protected string $description = 'Transfer money between two wallets of the same company (same currency only). Amount is a decimal string in the company currency.';

    public function __construct(private CreateTransfer $createTransfer) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->description('Company slug. Defaults to your current company.'),
            'from_wallet' => $schema->string()->description('Source wallet id or name.')->required(),
            'to_wallet' => $schema->string()->description('Destination wallet id or name.')->required(),
            'amount' => $schema->string()->description('Decimal amount in the company currency, e.g. "1500.50".')->required(),
            'date' => $schema->string()->description('YYYY-MM-DD. Defaults to today in the company timezone.'),
            'description' => $schema->string()->description('What this transfer is for.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate([
            'from_wallet' => 'required',
            'to_wallet' => 'required',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'nullable|date',
            'description' => 'nullable|string|max:255',
        ]);

        $company = $this->company($request);

        $from = $this->wallet($company, $request->get('from_wallet'));
        $to = $this->wallet($company, $request->get('to_wallet'));

        $date = $request->get('date') !== null
            ? Carbon::parse((string) $request->get('date'))
            : Carbon::parse(now($company->timezone)->toDateString());

        try {
            $transaction = $this->createTransfer->handle(
                $company,
                $from,
                $to,
                Money::toMinorUnits((string) $request->get('amount')),
                $date,
                $request->get('description'),
                creator: $this->authenticatedUser($request),
            );
        } catch (InvalidArgumentException $exception) {
            return Response::error($exception->getMessage());
        }

        AuditLogger::log($company, $this->authenticatedUser($request), 'created', $transaction, [
            'type' => 'transfer',
            'amount' => $transaction->amount,
            'from' => $from->name,
            'to' => $to->name,
            'via' => 'mcp',
        ]);

        return Response::text(sprintf(
            'Transferred %s from "%s" to "%s" on %s (transaction #%d). Balances: %s = %s, %s = %s.',
            Money::format($transaction->amount, $from->currency),
            $from->name,
            $to->name,
            $transaction->date->toDateString(),
            $transaction->id,
            $from->name,
            Money::format($from->refresh()->cached_balance, $from->currency),
            $to->name,
            Money::format($to->refresh()->cached_balance, $to->currency),
        ));
    }
}
