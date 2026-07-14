<?php

namespace App\Http\Middleware;

use App\Support\InstallationState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstalled
{
    public function __construct(private InstallationState $state) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->state->installed()) {
            return $next($request);
        }

        if ($request->is('install', 'install/*', 'up')) {
            return $next($request);
        }

        return redirect()->route('install.index');
    }
}
