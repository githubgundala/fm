<?php

namespace Database\Factories;

use App\Models\Quotation;
use App\Models\QuotationItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuotationItem>
 */
class QuotationItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $qty = fake()->randomFloat(2, 1, 10);
        $unitPrice = fake()->randomFloat(2, 100000, 5000000);

        return [
            'quotation_id' => Quotation::factory(),
            'section' => fake()->randomElement(['Ringkasan', 'Scope', 'Investasi', 'Timeline']),
            'description' => fake()->sentence(6),
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'line_total' => round($qty * $unitPrice, 2),
            'sort_order' => 0,
        ];
    }
}
