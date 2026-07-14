<?php

namespace App\Http\Middleware;

use App\Support\EnvWriter;
use App\Support\InstallationState;
use Closure;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrepareForInstallation
{
    public function __construct(
        private InstallationState $state,
        private EnvWriter $envWriter,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->state->installed()) {
            return $next($request);
        }

        if (! file_exists(app()->environmentFilePath())) {
            copy(base_path('.env.example'), app()->environmentFilePath());
        }

        config([
            'session.driver' => 'file',
            'cache.default' => 'file',
            'queue.default' => 'sync',
        ]);

        if (blank(config('app.key'))) {
            $key = 'base64:'.base64_encode(Encrypter::generateKey(config('app.cipher')));

            $this->envWriter->set(['APP_KEY' => $key]);
            config(['app.key' => $key]);
        }

        return $next($request);
    }
}
