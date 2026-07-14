<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForCompany(Builder $query, Company $company): Builder
    {
        return $query->where($this->qualifyColumn('company_id'), $company->id);
    }
}
