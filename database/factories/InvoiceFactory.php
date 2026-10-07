<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\InvoiceCalculator;
use App\Services\InvoiceNumberGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issueDate = fake()->dateTimeBetween('-2 months', 'now');

        return [
            'invoice_number' => app(InvoiceNumberGenerator::class)->next(InvoiceNumberGenerator::TYPE_INVOICE),
            'client_id' => Client::factory(),
            'issue_date' => $issueDate,
            'due_date' => (clone $issueDate)->modify('+14 days'),
            'status' => InvoiceStatus::Draft,
            'subtotal' => 0,
            'discount_type' => null,
            'discount_value' => 0,
            'tax_rate' => null,
            'tax_amount' => 0,
            'grand_total' => 0,
            'amount_paid' => 0,
            'amount_due' => 0,
            'currency' => 'IDR',
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Invoice $invoice): void {
            app(InvoiceCalculator::class)->recalculate($invoice);
        });
    }
}
