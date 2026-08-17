<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Invoice::class);

        $invoices = Invoice::query()
            ->with(['appointment.patient', 'payments'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('operations.invoices.index', ['invoices' => $invoices]);
    }

    public function show(Invoice $invoice): View
    {
        Gate::authorize('view', $invoice);

        $invoice->load(['appointment.patient', 'items', 'payments.cashier']);

        return view('operations.invoices.show', ['invoice' => $invoice]);
    }
}
