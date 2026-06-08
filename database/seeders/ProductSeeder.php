<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use App\Models\Branch;
use App\Models\ProductBranchStock;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    private int $skuCounter = 1;

    private function generateSku(string $prefix): string
    {
        return $prefix . '-' . str_pad($this->skuCounter++, 4, '0', STR_PAD_LEFT);
    }

    public function run(): void
    {
        
        $cat = fn(string $name) => Category::where('name', $name)->first()
            ?? Category::firstOrCreate(['name' => $name]);

        $prescription   = $cat('Prescription Medicines');
        $otc            = $cat('Over-the-Counter (OTC)');
        $painFever      = $cat('Pain & Fever Relief');
        $antibiotics    = $cat('Antibiotics & Anti-Infectives');
        $vitamins       = $cat('Vitamins & Supplements');
        $firstAid       = $cat('First Aid & Wound Care');
        $babyCare       = $cat('Baby & Maternal Care');
        $personalCare   = $cat('Personal Care & Hygiene');
        $devices        = $cat('Medical Devices & Equipment');
        $respiratory    = $cat('Respiratory & Allergy');
        $digestive      = $cat('Digestive Health');
        $chronic        = $cat('Chronic & Lifestyle');
        $eyeEarDental   = $cat('Eye, Ear & Dental Care');
        $herbal         = $cat('Herbal & Traditional');

        // ------------------------------------------------------------------
        // Products – prices in KES
        // ------------------------------------------------------------------
        $products = [
            // ── Pain & Fever Relief ──────────────────────────
            ['name' => 'Paracetamol 500mg (100 tabs)',    'description' => 'Panadol-equivalent generic paracetamol tablets', 'barcode' => '5000000000001', 'cost_price' => 120, 'selling_price' => 200, 'quantity_in_stock' => 300, 'reorder_level' => 80,  'category_id' => $painFever->id],
            ['name' => 'Ibuprofen 400mg (100 tabs)',      'description' => 'Anti-inflammatory analgesic tablets',           'barcode' => '5000000000002', 'cost_price' => 180, 'selling_price' => 300, 'quantity_in_stock' => 250, 'reorder_level' => 60,  'category_id' => $painFever->id],
            ['name' => 'Diclofenac 50mg (30 tabs)',       'description' => 'NSAID for pain and inflammation',              'barcode' => '5000000000003', 'cost_price' => 90,  'selling_price' => 150, 'quantity_in_stock' => 200, 'reorder_level' => 50,  'category_id' => $painFever->id],
            ['name' => 'Aspirin 300mg (100 tabs)',        'description' => 'Acetylsalicylic acid tablets',                 'barcode' => '5000000000004', 'cost_price' => 100, 'selling_price' => 170, 'quantity_in_stock' => 180, 'reorder_level' => 40,  'category_id' => $painFever->id],
            ['name' => 'Mefenamic Acid 500mg (20 tabs)',  'description' => 'Ponstan-equivalent for menstrual pain',        'barcode' => '5000000000005', 'cost_price' => 80,  'selling_price' => 140, 'quantity_in_stock' => 150, 'reorder_level' => 30,  'category_id' => $painFever->id],

            // ── Antibiotics & Anti-Infectives ────────────────
            ['name' => 'Amoxicillin 500mg (21 caps)',     'description' => 'Broad-spectrum antibiotic capsules',            'barcode' => '5000000000010', 'cost_price' => 150, 'selling_price' => 280, 'quantity_in_stock' => 200, 'reorder_level' => 50,  'category_id' => $antibiotics->id],
            ['name' => 'Azithromycin 500mg (3 tabs)',     'description' => 'Zithromax-equivalent macrolide antibiotic',     'barcode' => '5000000000011', 'cost_price' => 200, 'selling_price' => 350, 'quantity_in_stock' => 150, 'reorder_level' => 30,  'category_id' => $antibiotics->id],
            ['name' => 'Metronidazole 400mg (21 tabs)',   'description' => 'Flagyl-equivalent anti-infective',             'barcode' => '5000000000012', 'cost_price' => 80,  'selling_price' => 150, 'quantity_in_stock' => 180, 'reorder_level' => 40,  'category_id' => $antibiotics->id],
            ['name' => 'Ciprofloxacin 500mg (10 tabs)',   'description' => 'Fluoroquinolone antibiotic',                   'barcode' => '5000000000013', 'cost_price' => 130, 'selling_price' => 250, 'quantity_in_stock' => 120, 'reorder_level' => 25,  'category_id' => $antibiotics->id],
            ['name' => 'Fluconazole 150mg (1 cap)',       'description' => 'Antifungal capsule for candida infections',     'barcode' => '5000000000014', 'cost_price' => 50,  'selling_price' => 100, 'quantity_in_stock' => 200, 'reorder_level' => 50,  'category_id' => $antibiotics->id],

            // ── Over-the-Counter (OTC) ───────────────────────
            ['name' => 'Oral Rehydration Salts (ORS) x20','description' => 'WHO-formula rehydration sachets',              'barcode' => '5000000000020', 'cost_price' => 60,  'selling_price' => 120, 'quantity_in_stock' => 250, 'reorder_level' => 60,  'category_id' => $otc->id],
            ['name' => 'Cetirizine 10mg (30 tabs)',       'description' => 'Non-drowsy antihistamine tablets',              'barcode' => '5000000000021', 'cost_price' => 100, 'selling_price' => 180, 'quantity_in_stock' => 180, 'reorder_level' => 40,  'category_id' => $otc->id],
            ['name' => 'Loperamide 2mg (10 caps)',        'description' => 'Anti-diarrhoeal capsules (Imodium-equivalent)','barcode' => '5000000000022', 'cost_price' => 60,  'selling_price' => 120, 'quantity_in_stock' => 200, 'reorder_level' => 50,  'category_id' => $otc->id],
            ['name' => 'Antacid Suspension 200ml',        'description' => 'Aluminium hydroxide + magnesium hydroxide',    'barcode' => '5000000000023', 'cost_price' => 150, 'selling_price' => 250, 'quantity_in_stock' => 120, 'reorder_level' => 30,  'category_id' => $otc->id],
            ['name' => 'Zinc Sulphate 20mg (100 tabs)',   'description' => 'Zinc supplement for diarrhoea management',     'barcode' => '5000000000024', 'cost_price' => 80,  'selling_price' => 150, 'quantity_in_stock' => 160, 'reorder_level' => 30,  'category_id' => $otc->id],

            // ── Vitamins & Supplements ───────────────────────
            ['name' => 'Multivitamin Tablets (100 tabs)', 'description' => 'Daily multivitamin and mineral supplement',     'barcode' => '5000000000030', 'cost_price' => 250, 'selling_price' => 450, 'quantity_in_stock' => 180, 'reorder_level' => 40,  'category_id' => $vitamins->id],
            ['name' => 'Vitamin C 1000mg (30 tabs)',      'description' => 'Ascorbic acid effervescent tablets',            'barcode' => '5000000000031', 'cost_price' => 200, 'selling_price' => 350, 'quantity_in_stock' => 150, 'reorder_level' => 30,  'category_id' => $vitamins->id],
            ['name' => 'Iron + Folic Acid (100 tabs)',    'description' => 'Ferrous sulphate 200mg + folic acid 0.25mg',    'barcode' => '5000000000032', 'cost_price' => 120, 'selling_price' => 220, 'quantity_in_stock' => 200, 'reorder_level' => 50,  'category_id' => $vitamins->id],
            ['name' => 'Calcium + Vitamin D3 (60 tabs)',  'description' => 'Bone health supplement',                        'barcode' => '5000000000033', 'cost_price' => 300, 'selling_price' => 500, 'quantity_in_stock' => 120, 'reorder_level' => 25,  'category_id' => $vitamins->id],
            ['name' => 'Omega-3 Fish Oil (60 softgels)',  'description' => 'EPA/DHA cardiovascular health supplement',      'barcode' => '5000000000034', 'cost_price' => 450, 'selling_price' => 750, 'quantity_in_stock' => 90,  'reorder_level' => 20,  'category_id' => $vitamins->id],

            // ── Respiratory & Allergy ────────────────────────
            ['name' => 'Cough Syrup 100ml',               'description' => 'Dextromethorphan-based dry cough suppressant', 'barcode' => '5000000000040', 'cost_price' => 150, 'selling_price' => 280, 'quantity_in_stock' => 160, 'reorder_level' => 35,  'category_id' => $respiratory->id],
            ['name' => 'Salbutamol Inhaler 100mcg',        'description' => 'Ventolin-equivalent reliever inhaler',        'barcode' => '5000000000041', 'cost_price' => 350, 'selling_price' => 600, 'quantity_in_stock' => 80,  'reorder_level' => 15,  'category_id' => $respiratory->id],
            ['name' => 'Loratadine 10mg (30 tabs)',        'description' => 'Non-sedating antihistamine for allergies',     'barcode' => '5000000000042', 'cost_price' => 90,  'selling_price' => 170, 'quantity_in_stock' => 180, 'reorder_level' => 40,  'category_id' => $respiratory->id],
            ['name' => 'Nasal Decongestant Spray 15ml',    'description' => 'Oxymetazoline nasal spray',                   'barcode' => '5000000000043', 'cost_price' => 180, 'selling_price' => 300, 'quantity_in_stock' => 100, 'reorder_level' => 20,  'category_id' => $respiratory->id],
            ['name' => 'Menthol Vapour Rub 50g',           'description' => 'Topical decongestant for cold relief',        'barcode' => '5000000000044', 'cost_price' => 120, 'selling_price' => 200, 'quantity_in_stock' => 140, 'reorder_level' => 30,  'category_id' => $respiratory->id],

            // ── Digestive Health ─────────────────────────────
            ['name' => 'Omeprazole 20mg (28 caps)',        'description' => 'Proton pump inhibitor for acid reflux',       'barcode' => '5000000000050', 'cost_price' => 150, 'selling_price' => 280, 'quantity_in_stock' => 180, 'reorder_level' => 40,  'category_id' => $digestive->id],
            ['name' => 'Ranitidine 150mg (30 tabs)',       'description' => 'H2 blocker for heartburn and ulcers',         'barcode' => '5000000000051', 'cost_price' => 100, 'selling_price' => 180, 'quantity_in_stock' => 150, 'reorder_level' => 30,  'category_id' => $digestive->id],
            ['name' => 'Bisacodyl 5mg (30 tabs)',          'description' => 'Stimulant laxative tablets',                  'barcode' => '5000000000052', 'cost_price' => 70,  'selling_price' => 130, 'quantity_in_stock' => 120, 'reorder_level' => 25,  'category_id' => $digestive->id],
            ['name' => 'Probiotic Capsules (30 caps)',     'description' => 'Lactobacillus gut flora restoration',         'barcode' => '5000000000053', 'cost_price' => 350, 'selling_price' => 600, 'quantity_in_stock' => 90,  'reorder_level' => 15,  'category_id' => $digestive->id],

            // ── Chronic & Lifestyle ──────────────────────────
            ['name' => 'Metformin 500mg (100 tabs)',       'description' => 'Oral anti-diabetic for type 2 diabetes',      'barcode' => '5000000000060', 'cost_price' => 200, 'selling_price' => 380, 'quantity_in_stock' => 150, 'reorder_level' => 30,  'category_id' => $chronic->id],
            ['name' => 'Amlodipine 5mg (30 tabs)',         'description' => 'Calcium channel blocker for hypertension',    'barcode' => '5000000000061', 'cost_price' => 80,  'selling_price' => 150, 'quantity_in_stock' => 180, 'reorder_level' => 40,  'category_id' => $chronic->id],
            ['name' => 'Atorvastatin 20mg (30 tabs)',      'description' => 'Statin for cholesterol management',           'barcode' => '5000000000062', 'cost_price' => 120, 'selling_price' => 230, 'quantity_in_stock' => 140, 'reorder_level' => 30,  'category_id' => $chronic->id],
            ['name' => 'Losartan 50mg (30 tabs)',          'description' => 'ARB for high blood pressure',                 'barcode' => '5000000000063', 'cost_price' => 100, 'selling_price' => 200, 'quantity_in_stock' => 160, 'reorder_level' => 35,  'category_id' => $chronic->id],
            ['name' => 'Levothyroxine 50mcg (100 tabs)',   'description' => 'Thyroid hormone replacement therapy',         'barcode' => '5000000000064', 'cost_price' => 250, 'selling_price' => 420, 'quantity_in_stock' => 100, 'reorder_level' => 20,  'category_id' => $chronic->id],

            // ── First Aid & Wound Care ───────────────────────
            ['name' => 'Adhesive Bandages (100 pcs)',      'description' => 'Assorted sizes plaster strips',               'barcode' => '5000000000070', 'cost_price' => 150, 'selling_price' => 280, 'quantity_in_stock' => 120, 'reorder_level' => 25,  'category_id' => $firstAid->id],
            ['name' => 'Cotton Wool Roll 500g',            'description' => 'Absorbent cotton for wound care',             'barcode' => '5000000000071', 'cost_price' => 200, 'selling_price' => 350, 'quantity_in_stock' => 100, 'reorder_level' => 20,  'category_id' => $firstAid->id],
            ['name' => 'Povidone Iodine 10% 100ml',       'description' => 'Betadine-equivalent antiseptic solution',     'barcode' => '5000000000072', 'cost_price' => 180, 'selling_price' => 320, 'quantity_in_stock' => 90,  'reorder_level' => 15,  'category_id' => $firstAid->id],
            ['name' => 'Gauze Swabs (100 pcs)',            'description' => 'Sterile 10x10cm gauze pads',                  'barcode' => '5000000000073', 'cost_price' => 120, 'selling_price' => 220, 'quantity_in_stock' => 110, 'reorder_level' => 20,  'category_id' => $firstAid->id],
            ['name' => 'Hydrogen Peroxide 3% 200ml',       'description' => 'Topical antiseptic for wound cleaning',      'barcode' => '5000000000074', 'cost_price' => 100, 'selling_price' => 180, 'quantity_in_stock' => 80,  'reorder_level' => 15,  'category_id' => $firstAid->id],

            // ── Baby & Maternal Care ─────────────────────────
            ['name' => 'Baby Diapers Medium (40 pcs)',     'description' => 'Disposable baby diapers size M (5-10 kg)',    'barcode' => '5000000000080', 'cost_price' => 600, 'selling_price' => 950, 'quantity_in_stock' => 80,  'reorder_level' => 15,  'category_id' => $babyCare->id],
            ['name' => 'Baby Gripe Water 150ml',           'description' => 'Woodwards-equivalent colic relief',           'barcode' => '5000000000081', 'cost_price' => 200, 'selling_price' => 350, 'quantity_in_stock' => 100, 'reorder_level' => 20,  'category_id' => $babyCare->id],
            ['name' => 'Infant Paracetamol Drops 15ml',    'description' => 'Calpol-equivalent fever drops for babies',   'barcode' => '5000000000082', 'cost_price' => 180, 'selling_price' => 320, 'quantity_in_stock' => 90,  'reorder_level' => 20,  'category_id' => $babyCare->id],
            ['name' => 'Prenatal Vitamins (60 tabs)',      'description' => 'Folic acid + iron + DHA for pregnancy',       'barcode' => '5000000000083', 'cost_price' => 350, 'selling_price' => 580, 'quantity_in_stock' => 70,  'reorder_level' => 15,  'category_id' => $babyCare->id],
            ['name' => 'Baby Petroleum Jelly 250g',        'description' => 'Skin protectant for diaper rash',            'barcode' => '5000000000084', 'cost_price' => 150, 'selling_price' => 250, 'quantity_in_stock' => 100, 'reorder_level' => 20,  'category_id' => $babyCare->id],

            // ── Personal Care & Hygiene ──────────────────────
            ['name' => 'Hand Sanitizer 500ml',             'description' => '70% alcohol-based hand sanitizer',            'barcode' => '5000000000090', 'cost_price' => 250, 'selling_price' => 420, 'quantity_in_stock' => 120, 'reorder_level' => 25,  'category_id' => $personalCare->id],
            ['name' => 'Surgical Face Masks (50 pcs)',     'description' => '3-ply disposable medical face masks',         'barcode' => '5000000000091', 'cost_price' => 200, 'selling_price' => 350, 'quantity_in_stock' => 150, 'reorder_level' => 30,  'category_id' => $personalCare->id],
            ['name' => 'Toothpaste 100ml',                 'description' => 'Fluoride toothpaste for cavity protection',  'barcode' => '5000000000092', 'cost_price' => 120, 'selling_price' => 200, 'quantity_in_stock' => 180, 'reorder_level' => 40,  'category_id' => $personalCare->id],
            ['name' => 'Antiseptic Soap 100g',             'description' => 'Dettol-equivalent antibacterial soap',       'barcode' => '5000000000093', 'cost_price' => 80,  'selling_price' => 150, 'quantity_in_stock' => 200, 'reorder_level' => 50,  'category_id' => $personalCare->id],
            ['name' => 'Sanitary Pads (10 pcs)',           'description' => 'Ultra-thin winged sanitary towels',           'barcode' => '5000000000094', 'cost_price' => 100, 'selling_price' => 180, 'quantity_in_stock' => 200, 'reorder_level' => 50,  'category_id' => $personalCare->id],

            // ── Medical Devices & Equipment ──────────────────
            ['name' => 'Digital Thermometer',              'description' => 'Oral/axillary digital thermometer',           'barcode' => '5000000000100', 'cost_price' => 350, 'selling_price' => 600, 'quantity_in_stock' => 60,  'reorder_level' => 10,  'category_id' => $devices->id],
            ['name' => 'Blood Pressure Monitor',           'description' => 'Automatic upper-arm BP monitor',             'barcode' => '5000000000101', 'cost_price' => 2500,'selling_price' => 4200, 'quantity_in_stock' => 20, 'reorder_level' => 5,   'category_id' => $devices->id],
            ['name' => 'Glucometer Kit',                   'description' => 'Blood glucose monitor with 25 strips',       'barcode' => '5000000000102', 'cost_price' => 1800,'selling_price' => 3000, 'quantity_in_stock' => 25, 'reorder_level' => 5,   'category_id' => $devices->id],
            ['name' => 'Nebulizer Machine',                'description' => 'Compressor nebulizer for respiratory therapy','barcode' => '5000000000103', 'cost_price' => 3500,'selling_price' => 5500, 'quantity_in_stock' => 10, 'reorder_level' => 3,   'category_id' => $devices->id],
            ['name' => 'Disposable Syringes 5ml (100 pcs)','description' => 'Sterile single-use syringes with needles',   'barcode' => '5000000000104', 'cost_price' => 350, 'selling_price' => 600, 'quantity_in_stock' => 80,  'reorder_level' => 15,  'category_id' => $devices->id],

            // ── Eye, Ear & Dental Care ───────────────────────
            ['name' => 'Eye Drops (Lubricant) 10ml',       'description' => 'Artificial tears for dry eyes',              'barcode' => '5000000000110', 'cost_price' => 200, 'selling_price' => 380, 'quantity_in_stock' => 100, 'reorder_level' => 20,  'category_id' => $eyeEarDental->id],
            ['name' => 'Chloramphenicol Eye Drops 10ml',   'description' => 'Antibiotic eye drops for infections',        'barcode' => '5000000000111', 'cost_price' => 120, 'selling_price' => 220, 'quantity_in_stock' => 80,  'reorder_level' => 15,  'category_id' => $eyeEarDental->id],
            ['name' => 'Ear Drops (Wax Removal) 10ml',    'description' => 'Olive oil-based ear wax softener',            'barcode' => '5000000000112', 'cost_price' => 150, 'selling_price' => 280, 'quantity_in_stock' => 70,  'reorder_level' => 15,  'category_id' => $eyeEarDental->id],
            ['name' => 'Oral Gel for Toothache 10g',       'description' => 'Benzocaine topical pain relief gel',         'barcode' => '5000000000113', 'cost_price' => 100, 'selling_price' => 180, 'quantity_in_stock' => 90,  'reorder_level' => 20,  'category_id' => $eyeEarDental->id],

            // ── Herbal & Traditional ─────────────────────────
            ['name' => 'Aloe Vera Gel 200ml',              'description' => 'Pure aloe vera for skin care and burns',     'barcode' => '5000000000120', 'cost_price' => 250, 'selling_price' => 420, 'quantity_in_stock' => 90,  'reorder_level' => 15,  'category_id' => $herbal->id],
            ['name' => 'Eucalyptus Oil 50ml',              'description' => 'Essential oil for congestion relief',         'barcode' => '5000000000121', 'cost_price' => 180, 'selling_price' => 300, 'quantity_in_stock' => 80,  'reorder_level' => 15,  'category_id' => $herbal->id],
            ['name' => 'Honey & Lemon Syrup 200ml',        'description' => 'Natural cough and sore-throat remedy',       'barcode' => '5000000000122', 'cost_price' => 200, 'selling_price' => 350, 'quantity_in_stock' => 100, 'reorder_level' => 20,  'category_id' => $herbal->id],
            ['name' => 'Turmeric Capsules (60 caps)',      'description' => 'Anti-inflammatory curcumin supplement',       'barcode' => '5000000000123', 'cost_price' => 300, 'selling_price' => 500, 'quantity_in_stock' => 70,  'reorder_level' => 15,  'category_id' => $herbal->id],

            // ── Prescription Medicines ───────────────────────
            ['name' => 'Prednisolone 5mg (100 tabs)',      'description' => 'Corticosteroid anti-inflammatory',            'barcode' => '5000000000130', 'cost_price' => 200, 'selling_price' => 380, 'quantity_in_stock' => 100, 'reorder_level' => 20,  'category_id' => $prescription->id],
            ['name' => 'Amoxicillin Suspension 125mg/5ml', 'description' => 'Antibiotic oral suspension for children',     'barcode' => '5000000000131', 'cost_price' => 180, 'selling_price' => 320, 'quantity_in_stock' => 120, 'reorder_level' => 25,  'category_id' => $prescription->id],
            ['name' => 'Tramadol 50mg (20 caps)',          'description' => 'Opioid analgesic for moderate-severe pain',   'barcode' => '5000000000132', 'cost_price' => 250, 'selling_price' => 420, 'quantity_in_stock' => 60,  'reorder_level' => 10,  'category_id' => $prescription->id],
            ['name' => 'Enalapril 10mg (30 tabs)',         'description' => 'ACE inhibitor for heart failure/hypertension','barcode' => '5000000000133', 'cost_price' => 100, 'selling_price' => 200, 'quantity_in_stock' => 140, 'reorder_level' => 30,  'category_id' => $prescription->id],
            ['name' => 'Glibenclamide 5mg (100 tabs)',     'description' => 'Oral hypoglycaemic for type 2 diabetes',      'barcode' => '5000000000134', 'cost_price' => 150, 'selling_price' => 280, 'quantity_in_stock' => 100, 'reorder_level' => 20,  'category_id' => $prescription->id],
        ];

        // ------------------------------------------------------------------
        // Fetch all active branches
        // ------------------------------------------------------------------
        $branches = Branch::where('is_active', true)->get();

        foreach ($products as $productData) {
            $productData['sku'] = $this->generateSku('PH');

            $product = Product::updateOrCreate(
                ['barcode' => $productData['barcode']],
                $productData
            );

            // Distribute stock across branches
            if ($branches->isNotEmpty()) {
                $totalStock = $product->quantity_in_stock;
                $branchCount = $branches->count();

                foreach ($branches as $index => $branch) {
                    // Main branch gets ~40%, others split the rest equally
                    if ($branch->is_main) {
                        $branchQty = (int) ceil($totalStock * 0.40);
                    } else {
                        $remaining = $totalStock - (int) ceil($totalStock * 0.40);
                        $otherCount = $branchCount - 1;
                        $branchQty = $otherCount > 0 ? (int) ceil($remaining / $otherCount) : 0;
                    }

                    ProductBranchStock::updateOrCreate(
                        ['product_id' => $product->id, 'branch_id' => $branch->id],
                        ['quantity_in_stock' => $branchQty, 'initial_allocation' => $branchQty]
                    );
                }
            }
        }

        $this->command->info('Pharmacy products seeded and distributed across ' . $branches->count() . ' branches!');
    }
}
