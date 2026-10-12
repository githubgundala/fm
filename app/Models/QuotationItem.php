<?php

namespace App\Models;

use App\Services\QuotationCalculator;
use Database\Factories\QuotationItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $quotation_id
 * @property string $section
 * @property string $description
 * @property float $qty
 * @property float $unit_price
 * @property float $line_total
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['quotation_id', 'section', 'description', 'qty', 'unit_price', 'line_total', 'sort_order'])]
class QuotationItem extends Model
{
    /** @use HasFactory<QuotationItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (QuotationItem $item): void {
            $item->line_total = round((float) $item->qty * (float) $item->unit_price, 2);
        });

        static::saved(function (QuotationItem $item): void {
            app(QuotationCalculator::class)->recalculate($item->quotation);
        });

        static::deleted(function (QuotationItem $item): void {
            app(QuotationCalculator::class)->recalculate($item->quotation);
        });
    }

    /**
     * @return BelongsTo<Quotation, $this>
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }
}
