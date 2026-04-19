<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'roles' => [UserRole::Admin->value],
        ]);

        // Create discount store manager
        User::factory()->create([
            'name' => 'Store Manager',
            'email' => 'store@example.com',
            'password' => bcrypt('password'),
            'roles' => [UserRole::DiscountStore->value],
        ]);

        // Create a user with multiple roles
        User::factory()->create([
            'name' => 'Multi Role User',
            'email' => 'multi@example.com',
            'password' => bcrypt('password'),
            'roles' => [UserRole::Admin->value, UserRole::DiscountStore->value],
        ]);

        // Create additional test users
        User::factory(5)->create();
    }
}
