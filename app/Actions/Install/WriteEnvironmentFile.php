<?php

declare(strict_types=1);

namespace App\Actions\Install;

use App\Support\EnvWriter;

final class WriteEnvironmentFile
{
    public function __construct(private EnvWriter $envWriter) {}

    /**
     * @param  array{host?: string|null, port?: string|null, database?: string|null, username?: string|null, password?: string|null}  $database
     */
    public function handle(string $connection, array $database, string $appUrl): void
    {
        $values = [
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => $appUrl,
            'DB_CONNECTION' => $connection,
        ];

        if ($connection === 'sqlite') {
            $path = storage_path('database.sqlite');

            if (! file_exists($path)) {
                touch($path);
            }
        } else {
            $values += [
                'DB_HOST' => $database['host'] ?? null,
                'DB_PORT' => $database['port'] ?? null,
                'DB_DATABASE' => $database['database'] ?? null,
                'DB_USERNAME' => $database['username'] ?? null,
                'DB_PASSWORD' => $database['password'] ?? null,
            ];
        }

        $this->envWriter->set($values);
    }
}
