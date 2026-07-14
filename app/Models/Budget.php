<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToCompany;
use Database\Factories\BudgetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $category_id
 * @property string $period
 * @property int $amount
 * @property int $alert_threshold
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Category $category
 */
#[Fillable(['company_id', 'category_id', 'period', 'amount', 'alert_threshold', 'is_active'])]
final class Budget extends Model
{
    /** @use HasFactory<BudgetFactory> */
    use BelongsToCompany, HasFactory;

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
            'amount' => 'integer',
            'alert_threshold' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
