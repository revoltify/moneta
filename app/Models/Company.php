<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\GeneratesUniqueCompanySlugs;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $timezone
 * @property string $currency
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['name', 'slug', 'timezone', 'currency'])]
final class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use GeneratesUniqueCompanySlugs, HasFactory, SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'timezone' => 'Asia/Dhaka',
        'currency' => 'BDT',
    ];

    /**
     * @return HasMany<Wallet, $this>
     */
    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @return HasMany<Budget, $this>
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * @return HasMany<RecurringTransaction, $this>
     */
    public function recurringTransactions(): HasMany
    {
        return $this->hasMany(RecurringTransaction::class);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (Company $company) {
            if (empty($company->slug)) {
                $company->slug = static::generateUniqueCompanySlug($company->name);
            }
        });

        self::updating(function (Company $company) {
            if ($company->isDirty('name')) {
                $company->slug = static::generateUniqueCompanySlug($company->name, $company->id);
            }
        });
    }
}
