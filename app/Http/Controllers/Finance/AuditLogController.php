<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AuditLogController extends Controller
{
    public function index(Request $request, Company $current_company): Response
    {
        $logs = AuditLog::query()
            ->forCompany($current_company)
            ->with('user')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(50);

        return Inertia::render('audit/index', [
            'logs' => collect($logs->items())->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'userName' => $log->user->name ?? 'System',
                'action' => $log->action,
                'subjectType' => class_basename($log->auditable_type),
                'subjectId' => $log->auditable_id,
                'changes' => $log->changes,
                'createdAt' => $log->created_at->timezone($current_company->timezone)->format('M j, Y g:i A'),
            ]),
            'pagination' => [
                'currentPage' => $logs->currentPage(),
                'lastPage' => $logs->lastPage(),
                'total' => $logs->total(),
            ],
        ]);
    }
}
