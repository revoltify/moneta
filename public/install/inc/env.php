<?php

function parseEnvFile(string $path): array
{
    if (! file_exists($path)) {
        return [];
    }

    $vars = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (str_starts_with($line, 'export ')) {
            $line = substr($line, 7);
        }

        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }

        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));

        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[-1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        $vars[$key] = $value;
    }

    return $vars;
}

function writeEnvFile(string $envPath, array $replacements): void
{
    if (! file_exists($envPath)) {
        $example = dirname($envPath).'/.env.example';
        if (file_exists($example)) {
            copy($example, $envPath);
        } else {
            throw new RuntimeException('.env file not found and no .env.example available');
        }
    }

    $content = file_get_contents($envPath);
    $lines = explode("\n", $content);
    $updatedKeys = [];

    foreach ($lines as &$line) {
        $trimmed = trim($line);
        if ($trimmed === '') {
            continue;
        }

        $export = false;
        $search = $trimmed;
        if (str_starts_with($search, 'export ')) {
            $export = true;
            $search = substr($search, 7);
        }

        $wasCommented = false;
        if (str_starts_with($search, '#')) {
            $wasCommented = true;
            $search = trim(substr($search, 1));
        }

        $pos = strpos($search, '=');
        if ($pos === false) {
            continue;
        }

        $key = trim(substr($search, 0, $pos));

        if (array_key_exists($key, $replacements)) {
            $value = $replacements[$key];
            $prefix = $export ? 'export ' : '';
            $line = $prefix.$key.'='.$value;
            $updatedKeys[] = $key;
        }
    }

    foreach ($replacements as $key => $value) {
        if (! in_array($key, $updatedKeys)) {
            $lines[] = $key.'='.$value;
        }
    }

    file_put_contents($envPath, implode("\n", $lines), LOCK_EX);
}

function getDatabaseEnvConfig(array $input): array
{
    $connection = $input['db_connection'] ?? 'sqlite';
    $config = ['DB_CONNECTION' => $connection];

    if ($connection === 'sqlite') {
        $config['DB_DATABASE'] = $input['db_database'] ?? 'database.sqlite';
    } else {
        $config['DB_HOST'] = $input['db_host'] ?? '127.0.0.1';
        $config['DB_PORT'] = $input['db_port'] ?? '';
        $config['DB_DATABASE'] = $input['db_database'] ?? '';
        $config['DB_USERNAME'] = $input['db_username'] ?? '';
        $config['DB_PASSWORD'] = $input['db_password'] ?? '';
    }

    return $config;
}
