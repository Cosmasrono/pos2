<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Prescription Medicines', 'description' => 'Drugs dispensed with a valid prescription'],
            ['name' => 'Over-the-Counter (OTC)', 'description' => 'Non-prescription medicines available off the shelf'],
            ['name' => 'Pain & Fever Relief', 'description' => 'Analgesics, antipyretics and anti-inflammatory drugs'],
            ['name' => 'Antibiotics & Anti-Infectives', 'description' => 'Antibacterial, antifungal and antiviral medicines'],
            ['name' => 'Vitamins & Supplements', 'description' => 'Nutritional supplements, multivitamins and minerals'],
            ['name' => 'First Aid & Wound Care', 'description' => 'Bandages, antiseptics, dressings and first-aid kits'],
            ['name' => 'Baby & Maternal Care', 'description' => 'Baby formula, diapers, maternal health products'],
            ['name' => 'Personal Care & Hygiene', 'description' => 'Skin care, oral care, sanitary products and toiletries'],
            ['name' => 'Medical Devices & Equipment', 'description' => 'Thermometers, BP monitors, nebulizers and glucometers'],
            ['name' => 'Respiratory & Allergy', 'description' => 'Cough syrups, inhalers, antihistamines and decongestants'],
            ['name' => 'Digestive Health', 'description' => 'Antacids, laxatives, anti-diarrheals and probiotics'],
            ['name' => 'Chronic & Lifestyle', 'description' => 'Diabetes, hypertension, cholesterol and thyroid management'],
            ['name' => 'Eye, Ear & Dental Care', 'description' => 'Eye drops, ear drops, dental care solutions'],
            ['name' => 'Herbal & Traditional', 'description' => 'Herbal remedies and traditional medicine products'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category['name']], $category);
        }

        $this->command->info('Pharmacy categories seeded successfully!');
    }
}
