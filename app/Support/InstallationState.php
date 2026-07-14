<?php

namespace App\Support;

class InstallationState
{
    public function installed(): bool
    {
        $override = config('installer.installed');

        if ($override !== null) {
            return filter_var($override, FILTER_VALIDATE_BOOLEAN);
        }

        return file_exists($this->path());
    }

    public function markInstalled(): void
    {
        if (config('installer.installed') !== null) {
            return;
        }

        file_put_contents($this->path(), json_encode([
            'installed_at' => now()->toIso8601String(),
        ]));
    }

    public function path(): string
    {
        return config('installer.flag_path') ?? storage_path('installed');
    }
}
