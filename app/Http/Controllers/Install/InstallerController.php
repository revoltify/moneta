<?php

namespace App\Http\Controllers\Install;

use App\Actions\Install\CheckRequirements;
use App\Actions\Install\CreateAdminAccount;
use App\Actions\Install\RunMigrations;
use App\Actions\Install\TestDatabaseConnection;
use App\Actions\Install\WriteEnvironmentFile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Install\StoreAdminRequest;
use App\Http\Requests\Install\StoreDatabaseRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class InstallerController extends Controller
{
    public function requirements(CheckRequirements $checkRequirements): Response
    {
        return Inertia::render('install/requirements', [
            'requirements' => $checkRequirements->handle(),
        ]);
    }

    public function database(CheckRequirements $checkRequirements): Response|RedirectResponse
    {
        if (! $checkRequirements->handle()['passes']) {
            return redirect()->route('install.index');
        }

        return Inertia::render('install/database');
    }

    public function storeDatabase(
        StoreDatabaseRequest $request,
        TestDatabaseConnection $testConnection,
        WriteEnvironmentFile $writeEnvironment,
    ): RedirectResponse {
        $connection = $request->validated('connection');
        $overrides = $connection === 'sqlite' ? [] : [
            'host' => $request->validated('host'),
            'port' => $request->validated('port'),
            'database' => $request->validated('database'),
            'username' => $request->validated('username'),
            'password' => $request->validated('password') ?? '',
        ];

        try {
            $testConnection->handle($connection, $overrides);
        } catch (Throwable $exception) {
            throw ValidationException::withMessages([
                'connection' => __('Could not connect to the database: :message', ['message' => $exception->getMessage()]),
            ]);
        }

        $writeEnvironment->handle($connection, $overrides, $request->root());

        return redirect()->route('install.migrations');
    }

    public function migrations(): Response|RedirectResponse
    {
        if (! $this->databaseConnects()) {
            return redirect()->route('install.database');
        }

        return Inertia::render('install/migrations');
    }

    public function runMigrations(RunMigrations $runMigrations): RedirectResponse
    {
        if (! $this->databaseConnects()) {
            return redirect()->route('install.database');
        }

        try {
            $runMigrations->handle();
        } catch (Throwable $exception) {
            throw ValidationException::withMessages(['setup' => $exception->getMessage()]);
        }

        return redirect()->route('install.admin');
    }

    public function admin(): Response|RedirectResponse
    {
        if (! $this->databaseConnects() || ! Schema::hasTable('users')) {
            return redirect()->route('install.migrations');
        }

        return Inertia::render('install/admin');
    }

    public function storeAdmin(StoreAdminRequest $request, CreateAdminAccount $createAdmin): RedirectResponse
    {
        if (! $this->databaseConnects() || ! Schema::hasTable('users')) {
            return redirect()->route('install.migrations');
        }

        $createAdmin->handle(
            $request->validated('name'),
            $request->validated('email'),
            $request->validated('password'),
            $request->validated('company'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Installation complete. Log in with your new account.')]);

        return redirect()->route('login');
    }

    private function databaseConnects(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
