<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        // User::factory(10)->create();

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        if (app()->isLocal() && ! $user->hasRole('super_admin')) {
            $user->assignRole('super_admin');
        }

        if (app()->isLocal()) {
            $this->call(InvoiceDemoSeeder::class);
            $this->call(QuotationTemplateSeeder::class);
        }
    }
}
