<?php

namespace Database\Factories;

use App\Models\QuotationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuotationTemplate>
 */
class QuotationTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'project_type' => fake()->words(2, true),
            'default_title' => 'Penawaran '.$this->faker->words(3, true),
            'default_summary' => fake()->paragraph(),
            'default_scope' => null,
            'default_terms' => fake()->paragraph(),
            'default_exclusions' => fake()->sentence(),
            'default_notes' => fake()->sentence(),
            'default_validity_days' => 3,
            'default_tax_rate' => 11,
            'is_active' => true,
        ];
    }
}
