<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetCurrentCompany
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        [$user, $company] = [$request->user(), $this->company($request)];

        abort_if(! $user || ! $company, 404);

        if ($request->route('current_company') && ! $user->isCurrentCompany($company)) {
            $user->switchCompany($company);
        }

        return $next($request);
    }

    private function company(Request $request): ?Company
    {
        $company = $request->route('current_company') ?? $request->route('company');

        if (is_string($company)) {
            $company = Company::where('slug', $company)->first();
        }

        return $company;
    }
}
