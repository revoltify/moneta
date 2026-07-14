<?php

declare(strict_types=1);

namespace App\Actions\Setup;

use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use RuntimeException;

final class EnsurePersonalAccessClient
{
    public function __construct(private ClientRepository $clients) {}

    public function handle(): Client
    {
        try {
            return $this->clients->personalAccessClient('users');
        } catch (RuntimeException) {
            return $this->clients->createPersonalAccessGrantClient(config('app.name').' Personal Access Client', 'users');
        }
    }
}
