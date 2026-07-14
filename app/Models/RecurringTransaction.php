<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToCompany;
use App\Enums\RecurrenceFrequency;
use App\Enums\TransactionType;
use Database\Factories\RecurringTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property TransactionType $type
 * @property int $wallet_id
 * @property int|null $counter_wallet_id
 * @property int|null $category_id
 * @property int $amount
 * @property string $currency
 * @property string|null $description
 * @property RecurrenceFrequency $frequency
 * @property int $interval
 * @property int|null $day_of_month
 * @property Carbon $starts_on
 * @property Carbon|null $ends_on
 * @property Carbon $next_run_on
 * @property Carbon|null $last_run_on
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Wallet $wallet
 * @property-read Wallet|null $counterWallet
 * @property-read Category|null $category
 */
#[Fillable(['company_id', 'name', 'type', 'wallet_id', 'counter_wallet_id', 'category_id', 'amount', 'currency', 'description', 'frequency', 'interval', 'day_of_month', 'starts_on', 'ends_on', 'next_run_on', 'last_run_on', 'is_active'])]
final class RecurringTransaction extends Model
{
    /** @use HasFactory<RecurringTransactionFactory> */
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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'frequency' => RecurrenceFrequency::class,
            'amount' => 'integer',
            'interval' => 'integer',
            'day_of_month' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'next_run_on' => 'date',
            'last_run_on' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
