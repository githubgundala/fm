<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $qty = fake()->randomFloat(2, 1, 10);
        $unitPrice = fake()->randomFloat(2, 50000, 5000000);

        return [
            'invoice_id' => Invoice::factory(),
            'description' => fake()->sentence(4),
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'line_total' => round($qty * $unitPrice, 2),
            'sort_order' => 0,
        ];
    }
}
