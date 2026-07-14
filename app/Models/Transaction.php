<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToCompany;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property TransactionType $type
 * @property int $wallet_id
 * @property int|null $counter_wallet_id
 * @property int|null $category_id
 * @property int $amount
 * @property string $currency
 * @property Carbon $date
 * @property string|null $description
 * @property string|null $reference
 * @property TransactionStatus $status
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Wallet $wallet
 * @property-read Wallet|null $counterWallet
 * @property-read Category|null $category
 * @property-read User|null $creator
 */
#[Fillable(['company_id', 'type', 'wallet_id', 'counter_wallet_id', 'category_id', 'amount', 'currency', 'date', 'description', 'reference', 'status', 'created_by'])]
final class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use BelongsToCompany, HasFactory;

    /**
     * @return BelongsTo<Wallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * @return BelongsTo<Wallet, $this>
     */
    public function counterWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'counter_wallet_id');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePosted(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('status'), TransactionStatus::Posted);
    }

    public function isPosted(): bool
    {
        return $this->status === TransactionStatus::Posted;
    }

    public function signedAmount(): int
    {
        return match ($this->type) {
            TransactionType::Income, TransactionType::CapitalInvestment => $this->amount,
            TransactionType::Expense, TransactionType::CapitalWithdrawal => -$this->amount,
            TransactionType::Transfer => 0,
        };
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'status' => TransactionStatus::class,
            'date' => 'date',
            'amount' => 'integer',
        ];
    }
}
