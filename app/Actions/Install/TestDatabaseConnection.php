<?php

declare(strict_types=1);

namespace App\Actions\Install;

use Illuminate\Support\Facades\DB;

final class TestDatabaseConnection
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    public function handle(string $connection, array $overrides): void
    {
        $config = array_merge(config("database.connections.{$connection}"), $overrides);

        if ($connection === 'sqlite' && $config['database'] !== ':memory:' && ! file_exists($config['database'])) {
            touch($config['database']);
        }

        config(['database.connections.__install_test' => $config]);

        try {
            DB::purge('__install_test');
            DB::connection('__install_test')->getPdo();
            DB::connection('__install_test')->select('select 1');
        } finally {
            DB::purge('__install_test');
        }
    }
}
