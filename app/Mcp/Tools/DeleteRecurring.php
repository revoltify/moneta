<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Mcp\Concerns\InteractsWithCompany;
use App\Models\RecurringTransaction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
final class DeleteRecurring extends Tool
{
    use InteractsWithCompany;

    protected string $description = 'Delete a recurring schedule. Already-posted transactions are not affected.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->description('Company slug. Defaults to your current company.'),
            'id' => $schema->integer()->description('Recurring schedule id.')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate(['id' => 'required|integer']);

        $company = $this->company($request);

        $recurring = RecurringTransaction::query()->forCompany($company)->whereKey((int) $request->get('id'))->first();

        if ($recurring === null) {
            throw ValidationException::withMessages(['id' => 'Recurring schedule not found in this company.']);
        }

        $name = $recurring->name;
        $recurring->delete();

        return Response::text(sprintf('Recurring schedule "%s" deleted.', $name));
    }
}
