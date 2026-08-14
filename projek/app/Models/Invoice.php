<?php

namespace App\Models;

use App\Enums\InvoiceState;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['appointment_id', 'total_amount', 'status'])]
class Invoice extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'status' => InvoiceState::class,
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
