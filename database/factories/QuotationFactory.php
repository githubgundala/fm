<?php

namespace Database\Factories;

use App\Enums\QuotationStatus;
use App\Models\Client;
use App\Models\Quotation;
use App\Models\QuotationTemplate;
use App\Services\QuotationCalculator;
use App\Services\QuotationNumberGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quotation>
 */
class QuotationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issueDate = fake()->dateTimeBetween('-1 month', 'now');

        return [
            'quotation_number' => app(QuotationNumberGenerator::class)->next(),
            'template_id' => QuotationTemplate::factory(),
            'client_id' => Client::factory(),
            'title' => 'Penawaran '.$this->faker->words(3, true),
            'project_summary' => fake()->paragraph(),
            'issue_date' => $issueDate,
            'valid_until' => (clone $issueDate)->modify('+3 days'),
            'status' => QuotationStatus::Draft,
            'subtotal' => 0,
            'discount_type' => null,
            'discount_value' => 0,
            'tax_rate' => 11,
            'tax_amount' => 0,
            'grand_total' => 0,
            'currency' => 'IDR',
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Quotation $quotation): void {
            app(QuotationCalculator::class)->recalculate($quotation);
        });
    }
}
