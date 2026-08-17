<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class InvoiceController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Invoice::class);

        return InvoiceResource::collection(Invoice::latest('id')->paginate());
    }

    public function show(int $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        Gate::authorize('view', $invoice);

        return response()->json([
            'data' => new InvoiceResource($invoice),
        ]);
    }
}
