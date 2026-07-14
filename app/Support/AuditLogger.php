<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class AuditLogger
{
    /**
     * @param  array<string, mixed>  $changes
     */
    public static function log(Company $company, ?User $user, string $action, Model $auditable, array $changes = []): void
    {
        AuditLog::create([
            'company_id' => $company->id,
            'user_id' => $user?->id,
            'action' => $action,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'changes' => $changes === [] ? null : $changes,
            'created_at' => now(),
        ]);
    }
}
