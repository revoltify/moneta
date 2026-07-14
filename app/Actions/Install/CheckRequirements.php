<?php

declare(strict_types=1);

namespace App\Actions\Install;

final class CheckRequirements
{
    /**
     * @return array{
     *     php: array{version: string, required: string, passes: bool},
     *     extensions: list<array{name: string, loaded: bool}>,
     *     drivers: list<array{connection: string, extension: string, loaded: bool}>,
     *     paths: list<array{path: string, writable: bool}>,
     *     passes: bool
     * }
     */
    public function handle(): array
    {
        /** @var string $requiredPhp */
        $requiredPhp = config('installer.php_version');

        /** @var list<string> $requiredExtensions */
        $requiredExtensions = config('installer.extensions');

        /** @var array<string, string> $requiredDrivers */
        $requiredDrivers = config('installer.drivers');

        $php = [
            'version' => PHP_VERSION,
            'required' => $requiredPhp,
            'passes' => version_compare(PHP_VERSION, $requiredPhp, '>='),
        ];

        $extensions = array_map(
            fn (string $extension) => ['name' => $extension, 'loaded' => extension_loaded($extension)],
            $requiredExtensions,
        );

        $drivers = [];

        foreach ($requiredDrivers as $connection => $extension) {
            $drivers[] = [
                'connection' => $connection,
                'extension' => $extension,
                'loaded' => extension_loaded($extension),
            ];
        }

        $paths = array_map(
            fn (string $path) => [
                'path' => str_replace(base_path().DIRECTORY_SEPARATOR, '', $path),
                'writable' => is_writable($path),
            ],
            $this->paths(),
        );

        $passes = $php['passes']
            && collect($extensions)->every(fn (array $extension) => $extension['loaded'])
            && collect($drivers)->contains(fn (array $driver) => $driver['loaded'])
            && collect($paths)->every(fn (array $path) => $path['writable']);

        return [
            'php' => $php,
            'extensions' => $extensions,
            'drivers' => $drivers,
            'paths' => $paths,
            'passes' => $passes,
        ];
    }

    /**
     * @return list<string>
     */
    private function paths(): array
    {
        $env = app()->environmentFilePath();

        return [
            storage_path(),
            storage_path('framework'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
            file_exists($env) ? $env : dirname($env),
        ];
    }
}
