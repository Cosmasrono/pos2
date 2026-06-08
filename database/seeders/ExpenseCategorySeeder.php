<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // Staff-related expenses
            ['name' => '👥 Staff Meals', 'description' => 'Employee meals and refreshments'],
            ['name' => '💼 Staff Uniforms', 'description' => 'Staff clothing and uniforms'],
            ['name' => '📚 Staff Training', 'description' => 'Staff training and development'],
            
            // Utility expenses
            ['name' => '⚡ Electricity', 'description' => 'Power bills and electricity costs'],
            ['name' => '💧 Water', 'description' => 'Water supply bills'],
            ['name' => '🔥 Gas', 'description' => 'Cooking gas and LPG'],
            
            // Maintenance & Repairs
            ['name' => '🔧 Equipment Repair', 'description' => 'Fixing cookers, fridges, etc.'],
            ['name' => '🧹 Maintenance', 'description' => 'General maintenance and cleaning'],
            ['name' => '🪟 Building Repairs', 'description' => 'Building structure repairs'],
            
            // Supplies
            ['name' => '📦 Kitchen Supplies', 'description' => 'Containers, trays, utensils'],
            ['name' => '🧼 Cleaning Supplies', 'description' => 'Soap, sanitizers, detergents'],
            ['name' => '📋 Packaging', 'description' => 'Bags, labels, takeaway packaging'],
            
            // Operating costs
            ['name' => '🏠 Rent/Lease', 'description' => 'Building rent or equipment lease'],
            ['name' => '📱 Utilities (Internet/Phone)', 'description' => 'Internet, phone, internet bills'],
            ['name' => '🚚 Transportation', 'description' => 'Delivery fees and transport'],
            
            // Other
            ['name' => '❓ Other Expenses', 'description' => 'Miscellaneous expenses'],
        ];

        foreach ($categories as $category) {
            ExpenseCategory::firstOrCreate(
                ['name' => $category['name']],
                ['description' => $category['description']]
            );
        }
    }
}
