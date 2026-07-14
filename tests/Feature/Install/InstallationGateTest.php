<?php

declare(strict_types=1);

beforeEach(function () {
    $this->flag = sys_get_temp_dir().'/moneta-installed-'.uniqid();
    config(['installer.installed' => null, 'installer.flag_path' => $this->flag]);
});

afterEach(function () {
    @unlink($this->flag);
});

test('web routes redirect to the installer when not installed', function () {
    $this->get('/login')->assertRedirect(route('install.index'));
    $this->get('/')->assertRedirect(route('install.index'));
});

test('the installer is reachable when not installed', function () {
    $this->get('/install')->assertOk();
});

test('the health endpoint is reachable when not installed', function () {
    $this->get('/up')->assertOk();
});

test('the installer redirects home when installed', function () {
    file_put_contents($this->flag, '{}');

    $this->get('/install')->assertRedirect('/');
    $this->get('/install/database')->assertRedirect('/');
});

test('web routes work normally when installed', function () {
    file_put_contents($this->flag, '{}');

    $this->get('/login')->assertOk();
});
