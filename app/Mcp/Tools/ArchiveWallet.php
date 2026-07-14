<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Wallets\ArchiveWallet as ArchiveWalletAction;
use App\Mcp\Concerns\InteractsWithCompany;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

final class ArchiveWallet extends Tool
{
    use InteractsWithCompany;

    protected string $description = 'Archive a wallet, or restore it if it is already archived. Archived wallets are hidden from entry forms but keep their history.';

    public function __construct(private ArchiveWalletAction $archiveWallet) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->description('Company slug. Defaults to your current company.'),
            'wallet' => $schema->string()->description('Wallet id or name.')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate(['wallet' => 'required']);

        $company = $this->company($request);

        $wallet = $this->wallet($company, $request->get('wallet'));
        $this->archiveWallet->handle($wallet);

        return Response::text(sprintf(
            'Wallet "%s" %s.',
            $wallet->name,
            $wallet->refresh()->isArchived() ? 'archived' : 'restored',
        ));
    }
}
