<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class AuditController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', AuditLog::class);

        return AuditLogResource::collection(
            AuditLog::with('user')->latest('id')->paginate()
        );
    }
}
