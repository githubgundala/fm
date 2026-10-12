<?php

namespace App\Models;

use Database\Factories\QuotationTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $project_type
 * @property string|null $default_title
 * @property string|null $default_summary
 * @property array|null $default_scope
 * @property string|null $default_terms
 * @property string|null $default_exclusions
 * @property string|null $default_notes
 * @property int $default_validity_days
 * @property float|null $default_tax_rate
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'project_type', 'default_title', 'default_summary', 'default_scope', 'default_terms', 'default_exclusions', 'default_notes', 'default_validity_days', 'default_tax_rate', 'is_active'])]
class QuotationTemplate extends Model
{
    /** @use HasFactory<QuotationTemplateFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_scope' => 'array',
            'default_tax_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Quotation, $this>
     */
    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class, 'template_id');
    }
}
