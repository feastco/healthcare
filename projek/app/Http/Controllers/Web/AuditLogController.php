<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', AuditLog::class);

        $logs = AuditLog::query()
            ->with('user')
            ->latest('id')
            ->paginate(10);

        return view('monitoring.audit-logs.index', ['logs' => $logs]);
    }

    public function show(AuditLog $auditLog): View
    {
        Gate::authorize('viewAny', AuditLog::class);

        $auditLog->load('user');

        return view('monitoring.audit-logs.show', ['log' => $auditLog]);
    }
}
