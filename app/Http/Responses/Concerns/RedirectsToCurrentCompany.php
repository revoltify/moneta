<?php

declare(strict_types=1);

namespace App\Http\Responses\Concerns;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

trait RedirectsToCurrentCompany
{
    protected function redirectPathForCurrentCompany(Request $request, string $redirect): string
    {
        $company = $this->currentCompany($request);

        URL::defaults(['current_company' => $company->slug]);

        return "/{$company->slug}{$redirect}";
    }

    protected function currentCompany(Request $request): Company
    {
        $user = $request->user();

        abort_if(! $user, 403);

        $company = $user->currentCompany ?? $user->fallbackCompany();

        abort_if(! $company, 403);

        return $company;
    }
}
