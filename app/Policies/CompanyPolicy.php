<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

final class CompanyPolicy
{
    public function delete(User $user, Company $company): bool
    {
        return Company::query()->count() > 1;
    }
}
