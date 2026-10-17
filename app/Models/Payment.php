<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Services\InvoiceCalculator;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $payment_number
 * @property int $invoice_id
 * @property Carbon $payment_date
 * @property float $amount
 * @property PaymentMethod $method
 * @property string|null $reference
 * @property string|null $notes
 * @property string|null $proof_path
 * @property int|null $received_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['payment_number', 'invoice_id', 'payment_date', 'amount', 'method', 'reference', 'notes', 'proof_path', 'received_by'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'method' => PaymentMethod::class,
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (Payment $payment): void {
            app(InvoiceCalculator::class)->recalculate($payment->invoice);
        });

        static::deleted(function (Payment $payment): void {
            app(InvoiceCalculator::class)->recalculate($payment->invoice);
        });
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
