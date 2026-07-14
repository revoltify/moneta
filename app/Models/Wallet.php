<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToCompany;
use App\Enums\WalletType;
use Database\Factories\WalletFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property WalletType $type
 * @property string|null $account_number
 * @property string|null $icon
 * @property string|null $color
 * @property string $currency
 * @property int $opening_balance
 * @property int $cached_balance
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 */
#[Fillable(['company_id', 'name', 'type', 'account_number', 'icon', 'color', 'currency', 'opening_balance', 'cached_balance', 'archived_at'])]
final class Wallet extends Model
{
    /** @use HasFactory<WalletFactory> */
    use BelongsToCompany, HasFactory;

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull($this->qualifyColumn('archived_at'));
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function derivedBalance(): int
    {
        $incoming = (int) $this->company->transactions()
            ->posted()
            ->where(fn ($query) => $query
                ->where(fn ($inner) => $inner
                    ->where('wallet_id', $this->id)
                    ->whereIn('type', ['income', 'capital_investment']))
                ->orWhere(fn ($inner) => $inner
                    ->where('counter_wallet_id', $this->id)
                    ->where('type', 'transfer')))
            ->sum('amount');

        $outgoing = (int) $this->company->transactions()
            ->posted()
            ->where('wallet_id', $this->id)
            ->whereIn('type', ['expense', 'capital_withdrawal', 'transfer'])
            ->sum('amount');

        return $this->opening_balance + $incoming - $outgoing;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => WalletType::class,
            'opening_balance' => 'integer',
            'cached_balance' => 'integer',
            'archived_at' => 'datetime',
        ];
    }
}
