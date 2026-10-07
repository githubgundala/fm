<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\InvoiceNumberGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_number' => app(InvoiceNumberGenerator::class)->next(InvoiceNumberGenerator::TYPE_PAYMENT),
            'invoice_id' => Invoice::factory(),
            'payment_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'amount' => fake()->randomFloat(2, 100000, 1000000),
            'method' => fake()->randomElement(PaymentMethod::cases()),
            'reference' => fake()->optional()->bothify('REF-#####'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
