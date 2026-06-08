<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SaleItem;
use Illuminate\Support\Facades\Http;  
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AIInventoryService
{
    /**
     * Predict demand for a product over a given number of days.
     */
    public function predictDemand(Product $product, int $days = 30): array
    {
        // Simple Moving Average implementation
        // 1. Get daily sales for the last 90 days
        $startDate = now()->subDays(90);
        $dailySales = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sale_items.product_id', $product->id)
            ->where('sales.status', 'completed')
            ->where('sales.created_at', '>=', $startDate)
            ->select(DB::raw('CAST(sales.created_at AS DATE) as date'), DB::raw('SUM(sale_items.quantity) as total_qty'))
            ->groupBy(DB::raw('CAST(sales.created_at AS DATE)'))
            ->get()
            ->pluck('total_qty', 'date')
            ->toArray();

        // 2. Calculate average daily sales
        $totalSold = array_sum($dailySales);
        $daysWithSales = count($dailySales);
        $averageDailySales = $daysWithSales > 0 ? $totalSold / 90 : 0;

        // 3. Simple forecast
        $forecastedQty = $averageDailySales * $days;

        // 4. Calculate trend (last 30 days vs 30-60 days ago)
        $last30Start = now()->subDays(30);
        $prev30Start = now()->subDays(60);
        
        $last30Sales = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sale_items.product_id', $product->id)
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$last30Start, now()])
            ->sum('sale_items.quantity');

        $prev30Sales = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sale_items.product_id', $product->id)
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$prev30Start, $last30Start])
            ->sum('sale_items.quantity');

        $trend = $prev30Sales > 0 ? (($last30Sales - $prev30Sales) / $prev30Sales) * 100 : 0;

        // 5. Confidence Score
        $confidence = min(100, ($daysWithSales / 90) * 100);
        if ($totalSold < 10) $confidence *= 0.5;

        return [
            'predicted_qty' => round($forecastedQty, 2),
            'trend_percentage' => round($trend, 2),
            'confidence_score' => round($confidence, 2),
            'average_daily_sales' => round($averageDailySales, 2),
            'days_analyzed' => 90,
            'period' => $days
        ];
    }

public function getGroqInsight(string $prompt, int $maxTokens = 300): string
{
    $apiKey = env('GROQ_API_KEY');
    if (!$apiKey) return "AI unavailable.";

    try {
        $response = Http::withoutVerifying()->timeout(30)
            ->withHeaders(['Authorization' => 'Bearer ' . $apiKey, 'Content-Type' => 'application/json'])
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model'      => 'llama-3.3-70b-versatile',
                'messages'   => [['role' => 'user', 'content' => $prompt]],
                'max_tokens' => $maxTokens,
            ]);

        return $response->successful()
            ? $response->json()['choices'][0]['message']['content'] ?? "AI analysis unavailable."
            : "AI analysis unavailable.";
    } catch (\Exception $e) {
        Log::error("Groq AI Error: " . $e->getMessage());
        return "AI analysis unavailable.";
    }
}
    
    /**
     * Get reorder recommendation for a product.
     */
    public function getReorderRecommendation(Product $product): array
    {
        $prediction = $this->predictDemand($product, 30);
        $avgDailySales = $prediction['average_daily_sales'];
        
        $leadTime = 7; 
        $safetyStock = $avgDailySales * 3;
        $reorderPoint = ($avgDailySales * $leadTime) + $safetyStock;
        
        $currentStock = $product->branch_stocks_sum_quantity_in_stock ?? $product->quantity_in_stock;
        $needsReorder = $currentStock <= $reorderPoint;
        
        $recommendedQty = max(0, ($avgDailySales * 30) + $safetyStock - $currentStock);

        return [
            'reorder_point' => round($reorderPoint, 2),
            'current_stock' => $currentStock,
            'needs_reorder' => $needsReorder,
            'recommended_qty' => round($recommendedQty, 0),
            'urgency' => $currentStock <= ($avgDailySales * $leadTime) ? 'high' : ($needsReorder ? 'medium' : 'low'),
            'days_to_stockout' => $avgDailySales > 0 ? round($currentStock / $avgDailySales, 0) : 999
        ];
    }

    /**
     * Detect seasonal patterns.
     */
    public function detectSeasonality(Product $product): array
    {
        $salesByMonth = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sale_items.product_id', $product->id)
            ->where('sales.status', 'completed')
            ->select(DB::raw('MONTH(sales.created_at) as month'), DB::raw('SUM(sale_items.quantity) as total_qty'))
            ->groupBy(DB::raw('MONTH(sales.created_at)'))
            ->get()
            ->pluck('total_qty', 'month')
            ->toArray();

        return $salesByMonth;
    }

    /**
     * Identify slow-moving items.
     */
    public function getSlowMovingItems(int $limit = 10)
    {
        $activeProductIds = Product::where('is_active', true)->pluck('id');
        
        $recentSales = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.created_at', '>=', now()->subDays(30))
            ->pluck('product_id')
            ->unique();

        return Product::whereIn('id', $activeProductIds)
            ->whereNotIn('id', $recentSales)
            ->orderBy('quantity_in_stock', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get advanced AI insights from Groq.
     */
    public function getSmartInsights(Product $product): string
    {
        $apiKey = env('GROQ_API_KEY');
        if (!$apiKey) {
            return "Groq API Key is not configured. Please add it to your .env file.";
        }

        $prediction = $this->predictDemand($product, 30);
        $reorder = $this->getReorderRecommendation($product);
        
        $prompt = "As an expert retail inventory consultant, analyze the following data for the product '{$product->name}' and provide a concise (max 3 sentences) strategy. 
        All prices are in Kenya Shillings (KSh).
        Current Stock: {$product->quantity_in_stock}
        Predicted 30-day Demand: {$prediction['predicted_qty']}
        Current Trend: {$prediction['trend_percentage']}%
        Urgency Level: {$reorder['urgency']}
        
        Suggest a specific action (e.g., reorder quantity, promotion, or pricing shift).";

        try {
            // Using Groq API
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $apiKey,
                ])->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'max_tokens' => 500
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? "AI could not generate an insight at this time.";
            }

            $errorBody = $response->json();
            if (isset($errorBody['error']['code']) && $errorBody['error']['code'] === 'insufficient_quota') {
                \Illuminate\Support\Facades\Log::warning("Groq Quota Exceeded: " . ($errorBody['error']['message'] ?? 'Quota exceeded'));
                return "AI service is currently unavailable due to quota limits. Please try again later.";
            }

            \Illuminate\Support\Facades\Log::error("Groq AI Error: " . $response->body());
            return "Error connecting to AI service: " . $response->status();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Groq AI Exception: " . $e->getMessage());
            return "AI Insight unavailable: " . $e->getMessage();
        }
    }

    /**
     * Get daily executive briefing.
     * Using Groq API
     */
    public function getDailyExecutiveBriefing(): string
    {
        $apiKey = env('GROQ_API_KEY');
        if (!$apiKey) return "Groq API is not configured.";

        $today = Carbon::today();
        
        $sales = DB::table('sales')
            ->whereDate('created_at', $today)
            ->where('status', 'completed')
            ->select(
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(total_amount), 0) as total'),
                DB::raw('COALESCE(SUM(cash_paid), 0) as cash'),
                DB::raw('COALESCE(SUM(mpesa_paid), 0) as mpesa')
            )->first();

        $lowStockCount = Product::where('is_active', true)
            ->whereRaw('quantity_in_stock <= reorder_level')
            ->count();
            
        $outOfStockCount = Product::where('is_active', true)
            ->where('quantity_in_stock', '<=', 0)
            ->count();

        $topItem = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereDate('sales.created_at', $today)
            ->select('products.name', DB::raw('SUM(sale_items.quantity) as qty'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('qty')
            ->first();

        $greeting = $this->getTimeBasedGreeting();
        $prompt = "As an AI business analyst at Wing POS, provide a high-level, professional 'Daily Briefing' for the store owner. 
        Start your response with '{$greeting}'.
        
        Today's Data (All amounts are in Kenya Shillings - KSh):
        Today's Sales: {$sales->count} transactions, Totaling: KSh {$sales->total} (Cash: {$sales->cash}, Mpesa: {$sales->mpesa})
        Inventory Status: {$lowStockCount} items are low on stock, and {$outOfStockCount} are completely out of stock.
        Top Selling Product Today: " . ($topItem ? "{$topItem->name} ({$topItem->qty} sold)" : "No sales yet") . ".
        
        Provide a 2-3 sentence executive briefing following the greeting. Highlight the most important thing to focus on tonight or tomorrow morning.";

        try {
            // Using Groq API
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $apiKey,
                ])->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'max_tokens' => 500
                ]);

            if ($response->successful()) {
                return $response->json()['choices'][0]['message']['content'] ?? "Unable to generate briefing.";
            }
            
            $errorBody = $response->json();
            if (isset($errorBody['error']['code']) && $errorBody['error']['code'] === 'insufficient_quota') {
                \Illuminate\Support\Facades\Log::warning("Groq Quota Exceeded for Briefing: " . ($errorBody['error']['message'] ?? 'Quota exceeded'));
                return "AI briefing temporarily unavailable. Please check back soon.";
            }
            
            \Illuminate\Support\Facades\Log::error("Groq AI Error: " . $response->body());
            return "AI Briefing unavailable.";
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Groq AI Exception: " . $e->getMessage());
            return "AI Briefing unavailable.";
        }
    }

    /**
     * Suggest optimal pricing.
     */
    public function suggestOptimalPrice(Product $product): array
    {
        $prediction = $this->predictDemand($product, 30);
        $currentPrice = (float) $product->selling_price;
        $costPrice = (float) $product->cost_price;
        $trend = $prediction['trend_percentage'];
        
        $suggestedPrice = $currentPrice;
        $reason = "Current price is optimal based on stable demand.";
        $confidence = 85;

        if ($trend > 20) {
            $suggestedPrice = $currentPrice * 1.05;
            $reason = "High demand trend detected. Suggesting a slight price increase to maximize profit.";
        } elseif ($trend < -20 && $product->quantity_in_stock > 10) {
            $suggestedPrice = max($costPrice * 1.1, $currentPrice * 0.9);
            $reason = "Slow sales detected with high stock. Suggesting a promotion to clear inventory.";
        }

        return [
            'current_price' => round($currentPrice, 2),
            'suggested_price' => round($suggestedPrice, 2),
            'price_change' => round($suggestedPrice - $currentPrice, 2),
            'price_change_percentage' => $currentPrice > 0 ? round((($suggestedPrice - $currentPrice) / $currentPrice) * 100, 1) : 0,
            'reason' => $reason,
            'confidence' => $confidence
        ];
    }

    /**
     * Predict waste risk.
     */
    public function predictWasteRisk(Product $product): array
    {
        if (!$product->expiry_date) {
            return ['has_risk' => false, 'risk_level' => 'low'];
        }

        $expiry = Carbon::parse($product->expiry_date);
        $daysUntilExpiry = (int) now()->diffInDays($expiry, false);
        $prediction = $this->predictDemand($product, max(1, $daysUntilExpiry));
        
        $predictedSalesUntilExpiry = $prediction['predicted_qty'];
        $stockAtRisk = max(0, $product->quantity_in_stock - $predictedSalesUntilExpiry);
        
        $hasRisk = $stockAtRisk > 0 && $daysUntilExpiry < 60;
        $riskLevel = 'low';
        
        if ($daysUntilExpiry < 14 && $stockAtRisk > 0) $riskLevel = 'critical';
        elseif ($daysUntilExpiry < 30 && $stockAtRisk > 0) $riskLevel = 'high';
        elseif ($hasRisk) $riskLevel = 'medium';

        return [
            'has_risk' => $hasRisk,
            'risk_level' => $riskLevel,
            'days_until_expiry' => $daysUntilExpiry,
            'units_at_risk' => round($stockAtRisk, 0),
            'action' => $hasRisk ? "Implement a 'Quick Sale' discount to move $stockAtRisk units before expiry." : "Stock velocity is sufficient."
        ];
    }

    /**
     * Suggest product bundles.
     */
    public function suggestProductBundles(int $limit = 5): array
    {
        $sales = DB::table('sale_items as si1')
            ->join('sale_items as si2', 'si1.sale_id', '=', 'si2.sale_id')
            ->join('products as p1', 'si1.product_id', '=', 'p1.id')
            ->join('products as p2', 'si2.product_id', '=', 'p2.id')
            ->whereColumn('si1.product_id', '<', 'si2.product_id')
            ->select('p1.name as p1_name', 'p2.name as p2_name', 'p1.selling_price as p1_price', 'p2.selling_price as p2_price', DB::raw('COUNT(*) as frequency'))
            ->groupBy('si1.product_id', 'si2.product_id', 'p1.name', 'p2.name', 'p1.selling_price', 'p2.selling_price')
            ->orderByDesc('frequency')
            ->limit($limit)
            ->get();

        $bundles = [];
        foreach ($sales as $sale) {
            $totalPrice = $sale->p1_price + $sale->p2_price;
            $suggestedPrice = $totalPrice * 0.9;
            
            $bundles[] = [
                'product1' => $sale->p1_name,
                'product2' => $sale->p2_name,
                'frequency' => $sale->frequency,
                'individual_total' => round($totalPrice, 2),
                'suggested_bundle_price' => round($suggestedPrice, 2),
                'discount_amount' => round($totalPrice - $suggestedPrice, 2)
            ];
        }

        return $bundles;
    }

    /**
     * Get top selling products per branch and overall.
     */
    public function getBranchSalesAnalysis(int $topLimit = 5): array
    {
        // --- Per Branch Top Products ---
        $branchTopProducts = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('branches', 'sales.branch_id', '=', 'branches.id')
            ->where('sales.status', 'completed')
            ->select(
                'branches.id as branch_id',
                'branches.name as branch_name',
                'products.id as product_id',
                'products.name as product_name',
                DB::raw('SUM(sale_items.quantity) as total_qty_sold'),
                DB::raw('SUM(sale_items.quantity * sale_items.unit_price) as total_revenue')
            )
            ->groupBy('branches.id', 'branches.name', 'products.id', 'products.name')
            ->orderBy('branches.id')
            ->orderByDesc('total_qty_sold')
            ->get();

        // Group by branch and take top N per branch
        $byBranch = [];
        foreach ($branchTopProducts as $row) {
            if (!isset($byBranch[$row->branch_id])) {
                $byBranch[$row->branch_id] = [
                    'branch_id'   => $row->branch_id,
                    'branch_name' => $row->branch_name,
                    'top_products' => []
                ];
            }
            if (count($byBranch[$row->branch_id]['top_products']) < $topLimit) {
                $byBranch[$row->branch_id]['top_products'][] = [
                    'product_id'    => $row->product_id,
                    'product_name'  => $row->product_name,
                    'total_qty_sold'=> (int) $row->total_qty_sold,
                    'total_revenue' => round($row->total_revenue, 2),
                ];
            }
        }

        // --- Overall Top Products (across all branches) ---
        $overallTop = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.status', 'completed')
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                DB::raw('SUM(sale_items.quantity) as total_qty_sold'),
                DB::raw('SUM(sale_items.quantity * sale_items.unit_price) as total_revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_qty_sold')
            ->limit($topLimit)
            ->get()
            ->map(fn($r) => [
                'product_id'     => $r->product_id,
                'product_name'   => $r->product_name,
                'total_qty_sold' => (int) $r->total_qty_sold,
                'total_revenue'  => round($r->total_revenue, 2),
            ])->toArray();

        // --- Branch Revenue Summary ---
        $branchSummary = DB::table('sales')
            ->join('branches', 'sales.branch_id', '=', 'branches.id')
            ->where('sales.status', 'completed')
            ->select(
                'branches.id as branch_id',
                'branches.name as branch_name',
                DB::raw('COUNT(sales.id) as total_transactions'),
                DB::raw('SUM(sales.total_amount) as total_revenue'),
                DB::raw('SUM(sales.cash_paid) as cash_revenue'),
                DB::raw('SUM(sales.mpesa_paid) as mpesa_revenue')
            )
            ->groupBy('branches.id', 'branches.name')
            ->orderByDesc('total_revenue')
            ->get()
            ->map(fn($r) => [
                'branch_id'          => $r->branch_id,
                'branch_name'        => $r->branch_name,
                'total_transactions' => (int) $r->total_transactions,
                'total_revenue'      => round($r->total_revenue, 2),
                'cash_revenue'       => round($r->cash_revenue, 2),
                'mpesa_revenue'      => round($r->mpesa_revenue, 2),
            ])->toArray();

        return [
            'by_branch'      => array_values($byBranch),
            'overall_top'    => $overallTop,
            'branch_summary' => $branchSummary,
        ];
    }

    /**
     * Get AI insight on branch sales performance using Groq.
     */


    /**
 * Compare product prices against typical Kenyan market prices using Groq AI.
 */
public function getKenyanMarketPricingAnalysis(): array
{
    $products = Product::where('is_active', true)
        ->where('selling_price', '>', 0)
        ->get(['id', 'name', 'selling_price', 'cost_price', 'category_id']);

    if ($products->isEmpty()) return ['overpriced' => [], 'underpriced' => [], 'fair' => [], 'ai_summary' => ''];

    // Build product list for Groq
    $productLines = $products->map(fn($p) =>
        "{$p->id}|{$p->name}|{$p->selling_price}"
    )->implode("\n");

    $prompt = "You are a Kenyan retail market expert. Below is a list of products with their current selling prices in Kenya Shillings (KSh), in format: ID|ProductName|CurrentPrice.

{$productLines}

For each product, compare the current price against the typical Kenyan market/supermarket price for that product.
Respond ONLY in this exact JSON format, no explanation, no markdown:
[
  {\"id\": 1, \"market_price\": 50, \"status\": \"overpriced\", \"reason\": \"Typical market price is KSh 50\"},
  {\"id\": 2, \"market_price\": 200, \"status\": \"fair\", \"reason\": \"Price is within normal range\"},
  {\"id\": 3, \"market_price\": 300, \"status\": \"underpriced\", \"reason\": \"Could sell for KSh 300 in Kenyan market\"}
]
Status must be one of: overpriced, underpriced, fair.";

    $raw = $this->getGroqInsight($prompt, 2000);

    // Parse JSON response
    $results = [];
    try {
        $clean   = preg_replace('/```json|```/', '', $raw);
        $results = json_decode(trim($clean), true) ?? [];
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error("Pricing JSON parse error: " . $e->getMessage());
    }

    // Map results back to products
    $overpriced  = [];
    $underpriced = [];
    $fair        = [];

    $productMap = $products->keyBy('id');

    foreach ($results as $result) {
        $product = $productMap->get($result['id'] ?? null);
        if (!$product) continue;

        $item = [
            'product'        => $product,
            'current_price'  => $product->selling_price,
            'market_price'   => $result['market_price'] ?? 0,
            'difference'     => round($product->selling_price - ($result['market_price'] ?? 0), 2),
            'difference_pct' => $result['market_price'] > 0
                ? round((($product->selling_price - $result['market_price']) / $result['market_price']) * 100, 1)
                : 0,
            'reason'         => $result['reason'] ?? '',
        ];

        match ($result['status'] ?? 'fair') {
            'overpriced'  => $overpriced[]  = $item,
            'underpriced' => $underpriced[] = $item,
            default       => $fair[]        = $item,
        };
    }

    // Sort overpriced by biggest price gap
    usort($overpriced,  fn($a, $b) => $b['difference_pct'] <=> $a['difference_pct']);
    usort($underpriced, fn($a, $b) => $a['difference_pct'] <=> $b['difference_pct']);

    $summaryPrompt = "I have " . count($overpriced) . " overpriced and " . count($underpriced) 
        . " underpriced products compared to Kenyan market rates. Give a 1-sentence business advice for the owner.";

    return [
        'overpriced'  => $overpriced,
        'underpriced' => $underpriced,
        'fair'        => $fair,
        'ai_summary'  => $this->getGroqInsight($summaryPrompt, 150),
        'summary'     => [
            'overpriced_count'  => count($overpriced),
            'underpriced_count' => count($underpriced),
            'fair_count'        => count($fair),
            'total'             => count($results),
        ]
    ];
}
    public function getBranchSalesAIInsight(array $analysisData): string
    {
        // Build a summary string for the prompt
        $branchLines = collect($analysisData['branch_summary'])->map(function ($b) use ($analysisData) {
            $top = collect($analysisData['by_branch'])
                ->firstWhere('branch_id', $b['branch_id']);
            $topProduct = $top['top_products'][0]['product_name'] ?? 'N/A';
            return "{$b['branch_name']}: KSh {$b['total_revenue']} revenue, {$b['total_transactions']} transactions, top product: {$topProduct}";
        })->implode("\n");

        $overallTop = $analysisData['overall_top'][0]['product_name'] ?? 'N/A';

        $prompt = "As a retail business analyst, review this branch performance data (amounts in KSh):

        {$branchLines}

        Overall best-selling product across all branches: {$overallTop}

        In 3 sentences: identify the best-performing branch, any underperforming branch to watch, and one actionable recommendation for the owner.";

        return $this->getGroqInsight($prompt, 300);
    }

    /**
     * Get a time-based greeting for the dashboard.
     */
    private function getTimeBasedGreeting(): string
    {
        $hour = (int) now()->format('H');
        
        if ($hour >= 5 && $hour < 12) {
            return "Good morning Wing POS";
        } elseif ($hour >= 12 && $hour < 17) {
            return "Good afternoon Wing POS";
        } else {
            return "Good evening Wing POS";
        }
    }
}