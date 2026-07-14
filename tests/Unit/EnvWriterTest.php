<?php

use App\Support\EnvWriter;

beforeEach(function () {
    $this->path = sys_get_temp_dir().'/env-writer-test-'.uniqid().'.env';
});

afterEach(function () {
    @unlink($this->path);
});

test('replaces an existing key', function () {
    file_put_contents($this->path, "APP_ENV=local\nAPP_DEBUG=true\n");

    (new EnvWriter)->set(['APP_ENV' => 'production'], $this->path);

    expect(file_get_contents($this->path))
        ->toContain("APP_ENV=production\n")
        ->toContain("APP_DEBUG=true\n")
        ->not->toContain('APP_ENV=local');
});

test('uncomments a commented key', function () {
    file_put_contents($this->path, "DB_CONNECTION=sqlite\n# DB_HOST=127.0.0.1\n# DB_PORT=3306\n");

    (new EnvWriter)->set(['DB_HOST' => 'db.example.com'], $this->path);

    $contents = file_get_contents($this->path);

    expect($contents)
        ->toContain("DB_HOST=db.example.com\n")
        ->toContain("# DB_PORT=3306\n")
        ->not->toContain('# DB_HOST');
});

test('appends a missing key', function () {
    file_put_contents($this->path, "APP_ENV=local\n");

    (new EnvWriter)->set(['NEW_KEY' => 'value'], $this->path);

    expect(file_get_contents($this->path))->toContain("NEW_KEY=value\n");
});

test('quotes values with spaces and special characters', function () {
    file_put_contents($this->path, "DB_PASSWORD=\n");

    (new EnvWriter)->set(['DB_PASSWORD' => 'p@ss word"x'], $this->path);

    expect(file_get_contents($this->path))->toContain('DB_PASSWORD="p@ss word\"x"');
});

test('writes an empty value for null', function () {
    file_put_contents($this->path, "DB_PASSWORD=secret\n");

    (new EnvWriter)->set(['DB_PASSWORD' => null], $this->path);

    expect(file_get_contents($this->path))->toContain("DB_PASSWORD=\n");
});
