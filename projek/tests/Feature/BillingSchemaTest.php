<?php

namespace Tests\Feature;

use App\Enums\InvoiceState;
use App\Enums\PaymentMethod;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BillingSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_tables_are_created(): void
    {
        $this->assertTrue(Schema::hasTable('invoices'));
        $this->assertTrue(Schema::hasTable('invoice_items'));
        $this->assertTrue(Schema::hasTable('payments'));
    }

    public function test_invoices_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('invoices', [
            'id', 'appointment_id', 'total_amount', 'status', 'created_at', 'updated_at',
        ]));
    }

    public function test_invoice_items_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('invoice_items', [
            'id', 'invoice_id', 'description', 'amount', 'created_at', 'updated_at',
        ]));
    }

    public function test_payments_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('payments', [
            'id', 'invoice_id', 'cashier_id', 'amount', 'paid_at', 'payment_method',
            'created_at', 'updated_at',
        ]));
    }

    public function test_monetary_columns_use_decimal_precision_15_2(): void
    {
        foreach (['invoices' => 'total_amount', 'invoice_items' => 'amount', 'payments' => 'amount'] as $table => $column) {
            $columns = Schema::getColumns($table);
            $target = collect($columns)->firstWhere('name', $column);

            $this->assertNotNull($target, "Column {$table}.{$column} should exist.");
            $this->assertSame('numeric(15,2)', $target['type']);
        }
    }

    public function test_invoice_appointment_relationship_is_one_to_one(): void
    {
        $invoice = Invoice::factory()->create();
        $this->assertInstanceOf(Appointment::class, $invoice->appointment);

        $this->expectException(QueryException::class);
        Invoice::factory()->create(['appointment_id' => $invoice->appointment_id]);
    }

    public function test_invoice_has_many_items_and_payments(): void
    {
        $invoice = Invoice::factory()->create();
        $item = InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);
        $payment = Payment::factory()->create(['invoice_id' => $invoice->id]);

        $this->assertTrue($invoice->items->contains($item));
        $this->assertTrue($invoice->payments->contains($payment));
    }

    public function test_invoice_items_cascade_on_invoice_delete(): void
    {
        $invoice = Invoice::factory()->create();
        $item = InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $invoice->delete();

        $this->assertDatabaseMissing('invoice_items', ['id' => $item->id]);
    }

    public function test_invoice_deletion_is_restricted_when_payment_exists(): void
    {
        $invoice = Invoice::factory()->create();
        Payment::factory()->create(['invoice_id' => $invoice->id]);

        $this->expectException(QueryException::class);
        $invoice->delete();
    }

    public function test_payment_belongs_to_invoice_and_cashier(): void
    {
        $invoice = Invoice::factory()->create();
        $cashier = User::factory()->create();
        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'cashier_id' => $cashier->id,
        ]);

        $this->assertTrue($payment->invoice->is($invoice));
        $this->assertTrue($payment->cashier->is($cashier));
    }

    public function test_payment_method_is_plain_string_column(): void
    {
        $payment = Payment::factory()->create(['payment_method' => 'TRANSFER']);

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'payment_method' => 'TRANSFER']);
        $this->assertSame(PaymentMethod::TRANSFER, $payment->payment_method);
    }

    public function test_invoice_status_casts_to_invoice_state_enum(): void
    {
        $invoice = Invoice::factory()->create();

        $this->assertInstanceOf(InvoiceState::class, $invoice->status);
        $this->assertSame(InvoiceState::UNPAID, $invoice->status);
    }

    public function test_amounts_persist_without_float_precision_loss(): void
    {
        $invoice = Invoice::factory()->create(['total_amount' => 123456.78]);
        $item = InvoiceItem::factory()->create(['amount' => 99999.99]);
        $payment = Payment::factory()->create(['amount' => 0.01]);

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'total_amount' => 123456.78]);
        $this->assertDatabaseHas('invoice_items', ['id' => $item->id, 'amount' => 99999.99]);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'amount' => 0.01]);
    }
}
