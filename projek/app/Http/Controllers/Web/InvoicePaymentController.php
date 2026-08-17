<?php

namespace App\Http\Controllers\Web;

use App\Actions\ProcessPaymentAction;
use App\Enums\PaymentMethod;
use App\Exceptions\OverpaymentException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class InvoicePaymentController extends Controller
{
    public function store(StorePaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        Gate::authorize('create', Payment::class);

        try {
            app(ProcessPaymentAction::class)->handle(
                $invoice->id,
                $request->user(),
                $request->validated('amount'),
                PaymentMethod::from($request->validated('payment_method'))
            );
        } catch (OverpaymentException) {
            return back()->with('error', 'Payment amount exceeds the outstanding amount.');
        }

        return back()->with('success', 'Payment recorded successfully.');
    }
}
