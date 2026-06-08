<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            OwnerSeeder::class,
            ExpenseCategorySeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
        ]);
    }
}
