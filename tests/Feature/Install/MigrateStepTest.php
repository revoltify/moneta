<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Passport;

beforeEach(function () {
    $this->flag = sys_get_temp_dir().'/moneta-installed-'.uniqid();
    config(['installer.installed' => null, 'installer.flag_path' => $this->flag]);
});

afterEach(function () {
    @unlink($this->flag);
});

test('the migrations step renders when the database connects', function () {
    $this->get(route('install.migrations'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('install/migrations'));
});

test('running migrations creates the schema and redirects to the admin step', function () {
    $this->post(route('install.migrations.run'))
        ->assertRedirect(route('install.admin'));

    expect(Schema::hasTable('users'))->toBeTrue()
        ->and(Schema::hasTable('companies'))->toBeTrue();
});

test('running migrations generates missing passport keys', function () {
    $keyDir = sys_get_temp_dir().'/moneta-passport-'.uniqid();
    File::ensureDirectoryExists($keyDir);
    Passport::loadKeysFrom($keyDir);

    try {
        $this->post(route('install.migrations.run'))
            ->assertRedirect(route('install.admin'))
            ->assertSessionHasNoErrors();

        expect(file_exists($keyDir.'/oauth-private.key'))->toBeTrue()
            ->and(file_exists($keyDir.'/oauth-public.key'))->toBeTrue();
    } finally {
        Passport::loadKeysFrom(storage_path());
        File::deleteDirectory($keyDir);
    }
});

test('the admin step renders once the schema exists', function () {
    $this->get(route('install.admin'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('install/admin'));
});
