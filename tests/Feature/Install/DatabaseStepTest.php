<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $this->flag = storage_path('installed');
    @unlink($this->flag);

    $this->envDir = sys_get_temp_dir().'/moneta-env-'.uniqid();
    File::ensureDirectoryExists($this->envDir);
    copy(base_path('.env.example'), $this->envDir.'/.env');
    $this->app->useEnvironmentPath($this->envDir);

    config(['database.connections.sqlite.database' => ':memory:']);
});

afterEach(function (): void {
    touch($this->flag);
    File::deleteDirectory($this->envDir);
});

test('a working sqlite connection writes the environment file', function (): void {
    $this->post(route('install.database.store'), ['connection' => 'sqlite'])
        ->assertRedirect(route('install.migrations'));

    $contents = file_get_contents($this->envDir.'/.env');

    expect($contents)
        ->toContain("DB_CONNECTION=sqlite\n")
        ->toContain("APP_ENV=production\n")
        ->toContain("APP_DEBUG=false\n");
});

test('an unreachable database returns a connection error', function (): void {
    $this->from(route('install.database'))
        ->post(route('install.database.store'), [
            'connection' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 1,
            'database' => 'moneta',
            'username' => 'moneta',
            'password' => 'secret',
        ])
        ->assertRedirect(route('install.database'))
        ->assertSessionHasErrors('connection');

    expect(file_get_contents($this->envDir.'/.env'))->not->toContain('DB_CONNECTION=mysql');
});

test('server connections require host, port, database and username', function (): void {
    $this->post(route('install.database.store'), ['connection' => 'mysql'])
        ->assertSessionHasErrors(['host', 'port', 'database', 'username']);
});

test('a connection whose extension is missing is rejected', function (): void {
    config(['installer.drivers.mysql' => 'pdo_definitely_missing']);

    $this->post(route('install.database.store'), [
        'connection' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'moneta',
        'username' => 'moneta',
    ])->assertSessionHasErrors('connection');
});

test('the database step lists connection availability', function (): void {
    $this->get(route('install.database'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('install/database')
            ->where('connections.0.value', 'sqlite')
            ->where('connections.0.available', true)
            ->has('connections', 5));
});

test('the connection must be a supported driver', function (): void {
    $this->post(route('install.database.store'), ['connection' => 'mongodb'])
        ->assertSessionHasErrors('connection');
});

test('a working connection writes server credentials to the environment file', function (): void {
    $database = $this->envDir.'/connection-test.sqlite';
    touch($database);

    config(['database.connections.mysql' => array_merge(
        config('database.connections.sqlite'),
        ['database' => $database],
    )]);

    $this->post(route('install.database.store'), [
        'connection' => 'mysql',
        'host' => 'db.example.com',
        'port' => 3306,
        'database' => $database,
        'username' => 'moneta_user',
        'password' => 'secret pass',
    ])->assertRedirect(route('install.migrations'));

    $contents = file_get_contents($this->envDir.'/.env');

    expect($contents)
        ->toContain("DB_CONNECTION=mysql\n")
        ->toContain("DB_HOST=db.example.com\n")
        ->toContain("DB_PORT=3306\n")
        ->toContain(sprintf('DB_DATABASE=%s%s', $database, PHP_EOL))
        ->toContain("DB_USERNAME=moneta_user\n")
        ->toContain("DB_PASSWORD='secret pass'")
        ->not->toContain('# DB_HOST');
});
