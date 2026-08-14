<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ProcessPaymentAction;
use App\Enums\PaymentMethod;
use App\Exceptions\OverpaymentException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class InvoicePaymentController extends Controller
{
    public function index(int $invoiceId): AnonymousResourceCollection
    {
        $invoice = Invoice::findOrFail($invoiceId);

        Gate::authorize('viewAny', Payment::class);

        return PaymentResource::collection(
            $invoice->payments()->with('cashier')->latest('id')->paginate()
        );
    }

    public function store(
        StorePaymentRequest $request,
        int $invoiceId,
        ProcessPaymentAction $processPayment,
    ): JsonResponse {
        $invoice = Invoice::findOrFail($invoiceId);

        Gate::authorize('create', Payment::class);

        try {
            $payment = $processPayment->handle(
                invoiceId: $invoice->id,
                cashier: $request->user(),
                amount: $request->validated('amount'),
                method: PaymentMethod::from($request->validated('payment_method')),
            );
        } catch (OverpaymentException) {
            return response()->json([
                'message' => 'Payment amount exceeds the invoice outstanding amount.',
                'errors' => ['amount' => ['Payment amount exceeds the outstanding amount.']],
            ], 422);
        }

        return response()->json([
            'data' => new PaymentResource($payment->load('cashier')),
        ], 201);
    }
}
