<?php

use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->flag = sys_get_temp_dir().'/moneta-installed-'.uniqid();
    config(['installer.installed' => null, 'installer.flag_path' => $this->flag]);
});

afterEach(function () {
    @unlink($this->flag);
});

test('the requirements step reports server status', function () {
    $this->get(route('install.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('install/requirements')
            ->where('requirements.php.passes', true)
            ->where('requirements.passes', true)
            ->has('requirements.extensions')
            ->has('requirements.drivers')
            ->has('requirements.paths'));
});

test('the database step redirects back when requirements fail', function () {
    config(['installer.php_version' => '99.0']);

    $this->get(route('install.database'))->assertRedirect(route('install.index'));
});

test('the database step renders when requirements pass', function () {
    $this->get(route('install.database'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('install/database'));
});
