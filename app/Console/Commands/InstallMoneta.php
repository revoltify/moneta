<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Install\CreateAdminAccount;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

final class InstallMoneta extends Command
{
    protected $signature = 'moneta:install
        {--name=Admin : Admin name}
        {--email=admin@admin.com : Admin email}
        {--password=12345678 : Admin password}
        {--company=Demo Company : First company name}';

    protected $description = 'Create the Moneta admin account and first company';

    public function __construct(private CreateAdminAccount $createAdminAccount)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (User::exists()) {
            $this->components->error('Moneta is already installed — an account exists. Use the app to manage your data.');

            return self::FAILURE;
        }

        $name = (string) $this->option('name');
        $email = (string) $this->option('email');
        $password = (string) $this->option('password');
        $companyName = (string) $this->option('company');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'company' => $companyName],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:8'],
                'company' => ['required', 'string', 'max:100'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $this->createAdminAccount->handle($name, $email, $password, $companyName);

        $this->components->success("Admin account created for {$email}.");
        $this->components->success("Company \"{$companyName}\" created with default wallets and categories.");
        $this->components->info('Log in at '.rtrim(config('app.url'), '/')."/login as {$email} / {$password}".($password === '12345678' ? ' — change this password in Settings.' : ''));

        return self::SUCCESS;
    }
}
