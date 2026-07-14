<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Mcp\Concerns\InteractsWithCompany;
use App\Services\Reports\BalanceSheetReport;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
final class GetBalanceSheet extends Tool
{
    use InteractsWithCompany;

    protected string $description = 'Balance sheet as of a date: assets (wallets) and equity. Defaults to today. Amounts are integers in minor units (1/100 of the currency).';

    public function __construct(private BalanceSheetReport $report) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->description('Company slug. Defaults to your current company.'),
            'as_of' => $schema->string()->description('Date YYYY-MM-DD. Defaults to today.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate(['as_of' => 'nullable|date']);

        $company = $this->company($request);

        $asOf = $request->get('as_of') !== null
            ? Carbon::parse((string) $request->get('as_of'))
            : now($company->timezone);

        return Response::json([
            'company' => $company->slug,
            'asOf' => $asOf->toDateString(),
            'report' => $this->report->generate($company, $asOf),
        ]);
    }
}
