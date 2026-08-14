<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamp('paid_at');
            $table->string('payment_method');
            $table->timestamps();

            $table->index('invoice_id');
            $table->index('cashier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
