<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Helpers\PhoneHelper;

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
            // Normalize phone number to ensure +98 format
            $phone = PhoneHelper::normalizePhone('+989123456789');

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
        } catch (\Exception $e) {
            // Log error if phone normalization fails
            \Log::error('Failed to create admin user: ' . $e->getMessage());
        }
    }
}