<?php

declare(strict_types=1);

namespace App\Actions\Install;

use App\Actions\Companies\CreateCompany;
use App\Models\User;
use App\Support\InstallationState;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class CreateAdminAccount
{
    public function __construct(
        private CreateCompany $createCompany,
        private InstallationState $state,
    ) {}

    public function handle(string $name, string $email, string $password, string $companyName): User
    {
        if (User::exists()) {
            throw new InvalidArgumentException('An account already exists.');
        }

        $user = DB::transaction(function () use ($name, $email, $password, $companyName) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);

            $this->createCompany->handle($user, $companyName);

            return $user;
        });

        $this->state->markInstalled();

        return $user;
    }
}
