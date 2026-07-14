<?php

namespace App\Actions\Install;

use App\Actions\Setup\EnsurePersonalAccessClient;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use RuntimeException;

class RunMigrations
{
    public function __construct(private EnsurePersonalAccessClient $ensurePersonalAccessClient) {}

    public function handle(): void
    {
        Artisan::call('migrate', ['--force' => true]);

        $this->ensurePassportKeys();

        $this->ensurePersonalAccessClient->handle();
    }

    private function ensurePassportKeys(): void
    {
        $privateKey = Passport::keyPath('oauth-private.key');
        $publicKey = Passport::keyPath('oauth-public.key');

        if (file_exists($privateKey) && file_exists($publicKey)) {
            return;
        }

        Artisan::call('passport:keys', ['--force' => true]);

        if (! file_exists($privateKey) || ! file_exists($publicKey)) {
            throw new RuntimeException("Unable to generate Passport keys at {$privateKey}. Check that the directory is writable and the openssl extension works.");
        }
    }
}
