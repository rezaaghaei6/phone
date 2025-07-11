<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Helpers\PhoneHelper;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call the AdminUserSeeder
        $this->call(AdminUserSeeder::class);
    }
}

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        try {
            // Normalize phone number to ensure correct format
            $phone = PhoneHelper::normalize('+989123456789');

            // Create or update admin user
            User::updateOrCreate(
                ['phone' => $phone],
                [
                    'name' => 'ادمین',
                    'surname' => 'مدیر',
                    'is_admin' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $this->command->info('Admin user created successfully with phone: ' . $phone);
        } catch (\Exception $e) {
            // Log error if phone normalization fails
            \Log::error('Failed to create admin user: ' . $e->getMessage());
            $this->command->error('Failed to create admin user: ' . $e->getMessage());
        }
    }
}