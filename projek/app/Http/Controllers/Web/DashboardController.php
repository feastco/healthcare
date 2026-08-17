<?php

namespace App\Http\Controllers\Web;

use App\Enums\InvoiceState;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $invoicesAwaitingPayment = Invoice::query()
            ->whereIn('status', [InvoiceState::UNPAID, InvoiceState::PARTIALLY_PAID])
            ->with(['appointment.patient', 'payments'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function (Invoice $invoice): array {
                $paid = (float) $invoice->payments->sum('amount');

                return [
                    'id' => $invoice->id,
                    'patient_name' => $invoice->appointment?->patient?->name,
                    'total_amount' => $invoice->total_amount,
                    'paid_amount' => $paid,
                    'outstanding' => (float) $invoice->total_amount - $paid,
                    'status' => $invoice->status,
                ];
            });

        return view('dashboard', [
            'title' => 'Dashboard',
            'totalPatients' => Patient::count(),
            'totalRevenue' => (float) Payment::query()->sum('amount'),
            'todayAppointments' => Appointment::query()
                ->whereDate('starts_at', now()->toDateString())
                ->with(['patient', 'doctor'])
                ->orderBy('starts_at')
                ->limit(5)
                ->get(),
            'invoicesAwaitingPayment' => $invoicesAwaitingPayment,
        ]);
    }
}
