<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\QuotationStatus;
use Database\Factories\QuotationFactory;
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
 * @property string $quotation_number
 * @property int|null $template_id
 * @property int $client_id
 * @property string|null $title
 * @property string|null $project_summary
 * @property Carbon $issue_date
 * @property Carbon $valid_until
 * @property QuotationStatus $status
 * @property float $subtotal
 * @property DiscountType|null $discount_type
 * @property float $discount_value
 * @property float|null $tax_rate
 * @property float $tax_amount
 * @property float $grand_total
 * @property string $currency
 * @property string|null $notes
 * @property string|null $terms
 * @property string|null $exclusions
 * @property array|null $sender_snapshot
 * @property string|null $sender_name
 * @property string|null $receiver_name
 * @property Carbon|null $sent_at
 * @property Carbon|null $approved_at
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['quotation_number', 'template_id', 'client_id', 'title', 'project_summary', 'issue_date', 'valid_until', 'status', 'subtotal', 'discount_type', 'discount_value', 'tax_rate', 'tax_amount', 'grand_total', 'currency', 'notes', 'terms', 'exclusions', 'sender_snapshot', 'sender_name', 'receiver_name', 'sent_at', 'approved_at', 'created_by'])]
class Quotation extends Model
{
    /** @use HasFactory<QuotationFactory> */
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
            'valid_until' => 'date',
            'status' => QuotationStatus::class,
            'discount_type' => DiscountType::class,
            'subtotal' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'sender_snapshot' => 'array',
            'sent_at' => 'datetime',
            'approved_at' => 'datetime',
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
     * @return BelongsTo<QuotationTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(QuotationTemplate::class, 'template_id');
    }

    /**
     * @return HasMany<QuotationItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->orderBy('sort_order');
    }

    /**
     * Invoices generated from this quotation (one-to-many).
     *
     * @return BelongsToMany<Invoice, $this>
     */
    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(Invoice::class, 'quotation_invoice')->withTimestamps();
    }

    /**
     * Determine whether the quotation content is still editable.
     */
    public function isEditable(): bool
    {
        return $this->status === QuotationStatus::Draft;
    }

    /**
     * Determine whether the quotation has expired.
     */
    public function isExpired(): bool
    {
        return $this->valid_until->isPast() && in_array($this->status, [QuotationStatus::Draft, QuotationStatus::Sent], true);
    }
}
