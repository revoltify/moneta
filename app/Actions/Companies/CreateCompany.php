<?php

declare(strict_types=1);

namespace App\Actions\Companies;

use App\Actions\Categories\SetupDefaultCategories;
use App\Actions\Wallets\SetupDefaultWallets;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateCompany
{
    public function __construct(
        private SetupDefaultWallets $setupDefaultWallets,
        private SetupDefaultCategories $setupDefaultCategories,
    ) {}

    public function handle(User $user, string $name): Company
    {
        return DB::transaction(function () use ($user, $name) {
            $company = Company::create(['name' => $name]);

            $this->setupDefaultWallets->handle($company);
            $this->setupDefaultCategories->handle($company);

            $user->switchCompany($company);

            return $company;
        });
    }
}
