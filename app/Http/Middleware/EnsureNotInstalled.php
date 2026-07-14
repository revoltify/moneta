<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\InstallationState;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnsureNotInstalled
{
    public function __construct(private InstallationState $state) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->state->installed() || $this->detectExistingInstallation()) {
            return redirect('/');
        }

        return $next($request);
    }

    private function detectExistingInstallation(): bool
    {
        if (! $this->databaseReachable()) {
            return false;
        }

        try {
            if (Schema::hasTable('users') && User::exists()) {
                $this->state->markInstalled();

                return true;
            }
        } catch (Throwable) {
        }

        return false;
    }

    private function databaseReachable(): bool
    {
        $config = config('database.connections.'.config('database.default'));

        $host = $config['host'] ?? null;
        $port = $config['port'] ?? null;

        if (! $host || ! $port || ! function_exists('fsockopen')) {
            return true;
        }

        $socket = @fsockopen($host, (int) $port, $errorCode, $errorMessage, 2);

        if ($socket === false) {
            return false;
        }

        fclose($socket);

        return true;
    }
}
