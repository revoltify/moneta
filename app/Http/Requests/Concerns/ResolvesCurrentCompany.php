<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Models\Company;

trait ResolvesCurrentCompany
{
    protected function company(): Company
    {
        $company = $this->route('current_company');

        abort_unless($company instanceof Company, 404);

        return $company;
    }
}
