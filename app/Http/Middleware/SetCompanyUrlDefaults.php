<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

final class SetCompanyUrlDefaults
{
    /**
     * Set the default URL parameters for company-based routes.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($currentCompany = $request->user()?->currentCompany) {
            URL::defaults([
                'current_company' => $currentCompany->slug,
                'company' => $currentCompany->slug,
            ]);
        }

        return $next($request);
    }
}
