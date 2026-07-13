<?php

require __DIR__.'/../../../public/install/inc/env.php';

uses()->beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/moneta_test_'.uniqid();
    mkdir($this->tempDir, 0777, true);
})->afterEach(function () {
    if (isset($this->tempDir) && is_dir($this->tempDir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }
        rmdir($this->tempDir);
    }
});

test('parseEnvFile returns empty array for missing file', function () {
    expect(parseEnvFile('/nonexistent/path/.env'))->toBe([]);
});

test('parseEnvFile parses simple key-value pairs', function () {
    $path = $this->tempDir.'/.env';
    file_put_contents($path, "APP_NAME=Moneta\nAPP_ENV=production\nAPP_DEBUG=false");

    expect(parseEnvFile($path))->toBe([
        'APP_NAME' => 'Moneta',
        'APP_ENV' => 'production',
        'APP_DEBUG' => 'false',
    ]);
});

test('parseEnvFile strips surrounding quotes from values', function () {
    $path = $this->tempDir.'/.env';
    file_put_contents($path, "APP_NAME=\"Moneta\"\nAPP_URL='https://example.com'");

    expect(parseEnvFile($path))->toBe([
        'APP_NAME' => 'Moneta',
        'APP_URL' => 'https://example.com',
    ]);
});

test('parseEnvFile skips comments and empty lines', function () {
    $path = $this->tempDir.'/.env';
    file_put_contents($path, "# This is a comment\n\nAPP_KEY=abc123\n# Another comment\nAPP_DEBUG=true\n");

    expect(parseEnvFile($path))->toBe([
        'APP_KEY' => 'abc123',
        'APP_DEBUG' => 'true',
    ]);
});

test('parseEnvFile strips export prefix', function () {
    $path = $this->tempDir.'/.env';
    file_put_contents($path, "export APP_ENV=production\nexport DB_HOST=localhost");

    expect(parseEnvFile($path))->toBe([
        'APP_ENV' => 'production',
        'DB_HOST' => 'localhost',
    ]);
});

test('parseEnvFile handles values containing equals signs', function () {
    $path = $this->tempDir.'/.env';
    file_put_contents($path, 'APP_KEY=base64:abc123==');

    expect(parseEnvFile($path))->toBe([
        'APP_KEY' => 'base64:abc123==',
    ]);
});

test('parseEnvFile handles multiline env file', function () {
    $path = $this->tempDir.'/.env';
    file_put_contents($path, implode("\n", [
        'DB_CONNECTION=mysql',
        'DB_HOST=127.0.0.1',
        'DB_PORT=3306',
        'DB_DATABASE=moneta',
        'DB_USERNAME=root',
        'DB_PASSWORD=secret',
    ]));

    expect(parseEnvFile($path))->toBe([
        'DB_CONNECTION' => 'mysql',
        'DB_HOST' => '127.0.0.1',
        'DB_PORT' => '3306',
        'DB_DATABASE' => 'moneta',
        'DB_USERNAME' => 'root',
        'DB_PASSWORD' => 'secret',
    ]);
});

test('writeEnvFile updates existing keys in place', function () {
    $path = $this->tempDir.'/.env';
    file_put_contents($path, "APP_NAME=OldName\nAPP_ENV=local\nAPP_DEBUG=true");

    writeEnvFile($path, ['APP_NAME' => 'Moneta', 'APP_ENV' => 'production']);

    $result = parseEnvFile($path);
    expect($result['APP_NAME'])->toBe('Moneta')
        ->and($result['APP_ENV'])->toBe('production')
        ->and($result['APP_DEBUG'])->toBe('true');
});

test('writeEnvFile appends new keys not already in file', function () {
    $path = $this->tempDir.'/.env';
    file_put_contents($path, "APP_NAME=Moneta\n");

    writeEnvFile($path, ['APP_KEY' => 'base64:abc123', 'APP_DEBUG' => 'true']);

    $result = parseEnvFile($path);
    expect($result)->toHaveKey('APP_NAME')
        ->and($result)->toHaveKey('APP_KEY')
        ->and($result)->toHaveKey('APP_DEBUG');
});

test('writeEnvFile preserves comments and formatting', function () {
    $path = $this->tempDir.'/.env';
    file_put_contents($path, "# Database Configuration\nDB_HOST=localhost\nDB_PORT=3306\n# App Settings\nAPP_ENV=local\n");

    writeEnvFile($path, ['DB_HOST' => '127.0.0.1', 'APP_ENV' => 'production']);

    $content = file_get_contents($path);
    expect($content)->toContain('# Database Configuration')
        ->and($content)->toContain('# App Settings')
        ->and($content)->toContain('DB_HOST=127.0.0.1')
        ->and($content)->toContain('APP_ENV=production')
        ->and($content)->toContain('DB_PORT=3306');
});

test('writeEnvFile preserves export prefix on existing keys', function () {
    $path = $this->tempDir.'/.env';
    file_put_contents($path, "export APP_ENV=local\nexport DB_HOST=localhost");

    writeEnvFile($path, ['APP_ENV' => 'production']);

    $content = file_get_contents($path);
    expect($content)->toContain('export APP_ENV=production');
});

test('writeEnvFile throws when no file exists and no .env.example', function () {
    $path = $this->tempDir.'/.env';

    writeEnvFile($path, ['APP_NAME' => 'Moneta']);
})->throws(RuntimeException::class, '.env file not found');

test('writeEnvFile copies .env.example when .env missing', function () {
    $dir = $this->tempDir;
    file_put_contents($dir.'/.env.example', "APP_NAME=Example\nAPP_ENV=local\n");

    writeEnvFile($dir.'/.env', ['APP_NAME' => 'Moneta']);

    expect(file_exists($dir.'/.env'))->toBeTrue();
    $result = parseEnvFile($dir.'/.env');
    expect($result['APP_NAME'])->toBe('Moneta')
        ->and($result['APP_ENV'])->toBe('local');
});

test('getDatabaseEnvConfig returns sqlite defaults', function () {
    $config = getDatabaseEnvConfig(['db_connection' => 'sqlite']);

    expect($config)->toBe([
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => 'database.sqlite',
    ]);
});

test('getDatabaseEnvConfig returns sqlite with custom database', function () {
    $config = getDatabaseEnvConfig([
        'db_connection' => 'sqlite',
        'db_database' => 'custom.sqlite',
    ]);

    expect($config)->toBe([
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => 'custom.sqlite',
    ]);
});

test('getDatabaseEnvConfig returns mysql config with defaults', function () {
    $config = getDatabaseEnvConfig(['db_connection' => 'mysql']);

    expect($config)->toBe([
        'DB_CONNECTION' => 'mysql',
        'DB_HOST' => '127.0.0.1',
        'DB_PORT' => '',
        'DB_DATABASE' => '',
        'DB_USERNAME' => '',
        'DB_PASSWORD' => '',
    ]);
});

test('getDatabaseEnvConfig returns full mysql config', function () {
    $config = getDatabaseEnvConfig([
        'db_connection' => 'mysql',
        'db_host' => 'db.example.com',
        'db_port' => '3307',
        'db_database' => 'moneta_db',
        'db_username' => 'admin',
        'db_password' => 'secret123',
    ]);

    expect($config)->toBe([
        'DB_CONNECTION' => 'mysql',
        'DB_HOST' => 'db.example.com',
        'DB_PORT' => '3307',
        'DB_DATABASE' => 'moneta_db',
        'DB_USERNAME' => 'admin',
        'DB_PASSWORD' => 'secret123',
    ]);
});

test('getDatabaseEnvConfig defaults to sqlite when empty', function () {
    $config = getDatabaseEnvConfig([]);

    expect($config['DB_CONNECTION'])->toBe('sqlite');
});
