<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\InvoiceStatus;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $invoice_number
 * @property int $client_id
 * @property Carbon $issue_date
 * @property Carbon $due_date
 * @property InvoiceStatus $status
 * @property float $subtotal
 * @property DiscountType|null $discount_type
 * @property float $discount_value
 * @property float|null $tax_rate
 * @property float $tax_amount
 * @property float $grand_total
 * @property float $amount_paid
 * @property float $amount_due
 * @property string $currency
 * @property string|null $notes
 * @property string|null $terms
 * @property string|null $footer
 * @property string|null $receiver_name
 * @property string|null $billing_address
 * @property Carbon|null $sent_at
 * @property Carbon|null $paid_at
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['invoice_number', 'client_id', 'issue_date', 'due_date', 'status', 'subtotal', 'discount_type', 'discount_value', 'tax_rate', 'tax_amount', 'grand_total', 'amount_paid', 'amount_due', 'currency', 'notes', 'terms', 'footer', 'receiver_name', 'billing_address', 'sent_at', 'paid_at', 'created_by'])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'status' => InvoiceStatus::class,
            'discount_type' => DiscountType::class,
            'subtotal' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'amount_due' => 'decimal:2',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('payment_date');
    }

    /**
     * Quotations this invoice was converted from (usually one, may be linked to several invoices).
     *
     * @return BelongsToMany<Quotation, $this>
     */
    public function quotations(): BelongsToMany
    {
        return $this->belongsToMany(Quotation::class, 'quotation_invoice')->withTimestamps();
    }

    /**
     * Determine whether the invoice content is still editable.
     */
    public function isEditable(): bool
    {
        return $this->status === InvoiceStatus::Draft;
    }

    /**
     * Determine whether the invoice can receive a payment.
     */
    public function canReceivePayment(): bool
    {
        return in_array($this->status, [InvoiceStatus::Sent, InvoiceStatus::PartiallyPaid, InvoiceStatus::Overdue], true);
    }
}
