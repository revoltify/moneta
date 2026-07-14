<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Mcp\Concerns\InteractsWithCompany;
use App\Services\Reports\CashFlowReport;
use Carbon\CarbonInterface;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
final class GetCashFlow extends Tool
{
    use InteractsWithCompany;

    protected string $description = 'Cash flow statement for a period: operating and financing flows, opening and closing balance. Defaults to the current month. Amounts are integers in minor units (1/100 of the currency).';

    public function __construct(private CashFlowReport $report) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->description('Company slug. Defaults to your current company.'),
            'from' => $schema->string()->description('Start date YYYY-MM-DD. Defaults to start of the current month.'),
            'to' => $schema->string()->description('End date YYYY-MM-DD. Defaults to end of the current month.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $request->validate(['from' => 'nullable|date', 'to' => 'nullable|date']);

        $company = $this->company($request);
        [$from, $to] = $this->period($request, $company->timezone);

        return Response::json([
            'company' => $company->slug,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'report' => $this->report->generate($company, $from, $to),
        ]);
    }

    /**
     * @return array{CarbonInterface, CarbonInterface}
     */
    private function period(Request $request, string $timezone): array
    {
        $from = $request->get('from') !== null
            ? Carbon::parse((string) $request->get('from'))
            : now($timezone)->startOfMonth();

        $to = $request->get('to') !== null
            ? Carbon::parse((string) $request->get('to'))
            : now($timezone)->endOfMonth();

        return [$from, $to];
    }
}
