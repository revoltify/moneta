<?php

declare(strict_types=1);

use App\Actions\Companies\CreateCompany;
use App\Models\AuditLog;
use App\Models\User;

function auditSetup(): array
{
    $user = User::factory()->create();
    $company = app(CreateCompany::class)->handle($user, 'Acme Studio');

    return [$user, $company];
}

test('an audit trail is written for the transaction lifecycle', function () {
    [$user, $company] = auditSetup();
    $bank = $company->wallets()->where('name', 'Bank')->firstOrFail();
    $commission = $company->categories()->where('name', 'Sales')->firstOrFail();

    $this->actingAs($user)->post(route('transactions.store', ['current_company' => $company->slug]), [
        'type' => 'income',
        'wallet_id' => $bank->id,
        'category_id' => $commission->id,
        'amount' => '5000',
        'date' => now()->toDateString(),
    ]);

    $transaction = $company->transactions()->firstOrFail();

    $this->actingAs($user)->put(route('transactions.update', ['current_company' => $company->slug, 'transaction' => $transaction->id]), [
        'type' => 'income',
        'wallet_id' => $bank->id,
        'category_id' => $commission->id,
        'amount' => '6000',
        'date' => now()->toDateString(),
    ]);

    $this->actingAs($user)->delete(route('transactions.destroy', ['current_company' => $company->slug, 'transaction' => $transaction->id]));

    $actions = AuditLog::query()->forCompany($company)->pluck('action');

    expect($actions)->toContain('created', 'updated', 'voided');

    $updated = AuditLog::query()->forCompany($company)->where('action', 'updated')->firstOrFail();

    expect($updated->user_id)->toBe($user->id)
        ->and($updated->changes['amount'])->toBe(['from' => 500_000, 'to' => 600_000]);
});
