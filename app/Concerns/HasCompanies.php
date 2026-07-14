<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Data\UserCompany;
use App\Models\Company;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

trait HasCompanies
{
    /**
     * Get the user's current company.
     *
     * @return BelongsTo<Company, $this>
     */
    public function currentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'current_company_id');
    }

    /**
     * Switch to the given company.
     */
    public function switchCompany(Company $company): void
    {
        $this->update(['current_company_id' => $company->id]);
        $this->setRelation('currentCompany', $company);

        URL::defaults(['current_company' => $company->slug]);
    }

    /**
     * Determine if the given company is the user's current company.
     */
    public function isCurrentCompany(Company $company): bool
    {
        return $this->current_company_id === $company->id;
    }

    /**
     * Get the companies as a collection of UserCompany objects.
     *
     * @return Collection<int, UserCompany>
     */
    public function toUserCompanies(bool $includeCurrent = false): Collection
    {
        return Company::query()
            ->get()
            ->map(fn (Company $company) => ! $includeCurrent && $this->isCurrentCompany($company) ? null : $this->toUserCompany($company))
            ->filter()
            ->values();
    }

    /**
     * Get the company as a UserCompany object.
     */
    public function toUserCompany(Company $company): UserCompany
    {
        return new UserCompany(
            id: $company->id,
            name: $company->name,
            slug: $company->slug,
            isCurrent: $this->isCurrentCompany($company),
            timezone: $company->timezone,
            currency: $company->currency,
        );
    }

    public function fallbackCompany(?Company $excluding = null): ?Company
    {
        return Company::query()
            ->when($excluding, fn ($query) => $query->where('id', '!=', $excluding->id))
            ->orderByRaw('LOWER(name)')
            ->first();
    }
}
