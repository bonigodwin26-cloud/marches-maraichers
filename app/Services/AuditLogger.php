<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(Request $request, string $action, Model $auditable, array $metadata = []): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $auditable::class,
            'auditable_id' => $auditable->getKey(),
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
        ]);
    }
}
