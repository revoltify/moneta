<?php

function getAdminEmail(): ?string
{
    try {
        $env = parseEnvFile(base_path('.env'));
        if (empty($env)) {
            return null;
        }
        $pdo = connectToDatabase($env);
        $stmt = $pdo->query('SELECT email FROM users ORDER BY id LIMIT 1');
        $email = $stmt->fetchColumn();

        return $email ?: null;
    } catch (Exception) {
        return null;
    }
}

function runArtisan(string $command, array $args = []): array
{
    $projectPath = base_path();
    $argStr = '';

    foreach ($args as $key => $value) {
        if (is_bool($value)) {
            $argStr .= ' '.($value ? $key : '');
        } else {
            $argStr .= " {$key}=".escapeshellarg($value);
        }
    }

    $cmd = escapeshellcmd(PHP_BINARY).' '.escapeshellarg("{$projectPath}/artisan")." {$command}{$argStr} 2>&1";

    $output = [];
    $exitCode = 0;
    exec($cmd, $output, $exitCode);

    return [
        'success' => $exitCode === 0,
        'output' => implode("\n", $output),
    ];
}

function runInstaller(array $adminData): array
{
    $log = [];

    $log[] = 'Running database migrations...';
    $result = runArtisan('migrate', ['--force' => true]);
    $log[] = trim($result['output']);

    if (! $result['success']) {
        $log[] = 'ERROR: Migration failed';

        return ['success' => false, 'output' => implode("\n", $log)];
    }

    $log[] = 'Generating Passport OAuth keys...';
    $result = runArtisan('passport:keys', ['--force' => true]);
    $log[] = trim($result['output']);

    $log[] = 'Creating admin account and company...';
    $result = runArtisan('moneta:install', [
        '--name' => $adminData['name'],
        '--email' => $adminData['email'],
        '--password' => $adminData['password'],
        '--company' => $adminData['company'],
    ]);
    $log[] = trim($result['output']);

    if (! $result['success']) {
        return ['success' => false, 'output' => implode("\n", $log)];
    }

    $log[] = 'Creating storage symbolic link...';
    runArtisan('storage:link', ['--force' => true]);

    return [
        'success' => true,
        'output' => implode("\n", $log),
    ];
}
