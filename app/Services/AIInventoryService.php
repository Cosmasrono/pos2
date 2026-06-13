<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SaleItem;
use App\Models\InventoryPrediction;
use App\Models\PredictionLog;
use Anthropic\Client as AnthropicClient;
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
        $startDate = now()->subDays(90);

        // 1. Daily sales for last 90 days
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

        $totalSold     = array_sum($dailySales);
        $daysWithSales = count($dailySales);

        // 2. Use product age as denominator so new products are not underestimated
        $productAgeDays = max(1, min(90, (int) Carbon::parse($product->created_at)->diffInDays(now())));
        $averageDailySales = $daysWithSales > 0 ? $totalSold / $productAgeDays : 0;

        // 3. Trend: last 30 days vs prior 30 days
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

        // 4. Apply trend to forecast (capped at ±50% to avoid wild swings)
        $trendMultiplier = 1 + (min(max($trend, -50), 50) / 100);

        // 5. Apply seasonal index for the target month
        $seasonality   = $this->detectSeasonality($product);
        $targetMonth   = now()->addDays($days)->month;
        $avgMonthly    = count($seasonality) > 0 ? array_sum($seasonality) / count($seasonality) : 0;
        $seasonalIndex = ($avgMonthly > 0 && isset($seasonality[$targetMonth]))
            ? $seasonality[$targetMonth] / $avgMonthly
            : 1.0;
        // Cap seasonal index to avoid extreme distortion from thin data
        $seasonalIndex = min(max($seasonalIndex, 0.5), 2.0);

        $forecastedQty = $averageDailySales * $days * $trendMultiplier * $seasonalIndex;

        // 6. Confidence: data coverage + volume penalty for thin history
        $confidence = min(100, ($daysWithSales / max(1, $productAgeDays)) * 100);
        if ($totalSold < 10) $confidence *= 0.5;
        // Reduce confidence when seasonal data is sparse (fewer than 6 months of history)
        if (count($seasonality) < 6) $confidence *= 0.85;

        $result = [
            'predicted_qty'       => round($forecastedQty, 2),
            'trend_percentage'    => round($trend, 2),
            'confidence_score'    => round($confidence, 2),
            'average_daily_sales' => round($averageDailySales, 2),
            'seasonal_index'      => round($seasonalIndex, 3),
            'days_analyzed'       => $productAgeDays,
            'period'              => $days,
        ];

        // 7. Persist prediction so accuracy can be measured later
        try {
            InventoryPrediction::updateOrCreate(
                [
                    'product_id'         => $product->id,
                    'prediction_type'    => 'demand_forecast',
                    'predicted_for_date' => now()->addDays($days)->toDateString(),
                ],
                [
                    'prediction_data'  => $result,
                    'confidence_score' => $result['confidence_score'],
                ]
            );

            PredictionLog::create([
                'product_id'      => $product->id,
                'prediction_type' => 'demand_forecast',
                'predicted_value' => round($forecastedQty, 2),
                'prediction_date' => now()->toDateString(),
            ]);
        } catch (\Exception $e) {
            Log::warning("Could not save prediction for product {$product->id}: " . $e->getMessage());
        }

        return $result;
    }

public function getClaudeInsight(string $prompt, int $maxTokens = 300): string
{
    $apiKey = env('ANTHROPIC_API_KEY');
    if (!$apiKey) return "AI unavailable. Please configure ANTHROPIC_API_KEY.";

    try {
        $client = new AnthropicClient($apiKey);
        $message = $client->messages->create(
            maxTokens: max(1024, $maxTokens),
            messages: [['role' => 'user', 'content' => $prompt]],
            model: 'claude-opus-4-8',
            thinking: ['type' => 'adaptive'],
        );

        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                return $block->text;
            }
        }
        return "AI analysis unavailable.";
    } catch (\Exception $e) {
        Log::error("Claude AI Error: " . $e->getMessage());
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
     * Get advanced AI insights using Claude.
     */
    public function getSmartInsights(Product $product): string
    {
        $prediction = $this->predictDemand($product, 30);
        $reorder = $this->getReorderRecommendation($product);

        $prompt = "As an expert retail inventory consultant, analyze the following data for the product '{$product->name}' and provide a concise (max 3 sentences) strategy.
        All prices are in Kenya Shillings (KSh).
        Current Stock: {$product->quantity_in_stock}
        Predicted 30-day Demand: {$prediction['predicted_qty']}
        Current Trend: {$prediction['trend_percentage']}%
        Urgency Level: {$reorder['urgency']}

        Suggest a specific action (e.g., reorder quantity, promotion, or pricing shift).";

        return $this->getClaudeInsight($prompt, 500);
    }

    /**
     * Get daily executive briefing using Claude.
     */
    public function getDailyExecutiveBriefing(): string
    {
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

        return $this->getClaudeInsight($prompt, 500);
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
     * Get AI insight on branch sales performance using Claude.
     */


    /**
 * Compare product prices against typical Kenyan market prices using Claude AI.
 */
public function getKenyanMarketPricingAnalysis(): array
{
    $products = Product::where('is_active', true)
        ->where('selling_price', '>', 0)
        ->get(['id', 'name', 'selling_price', 'cost_price', 'category_id']);

    if ($products->isEmpty()) return ['overpriced' => [], 'underpriced' => [], 'fair' => [], 'ai_summary' => ''];

    // Build product list for Claude
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

    $raw = $this->getClaudeInsight($prompt, 2000);

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
        'ai_summary'  => $this->getClaudeInsight($summaryPrompt, 150),
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

        return $this->getClaudeInsight($prompt, 300);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FEATURE 1: Shift Close AI Summary
    // ─────────────────────────────────────────────────────────────────────────

    public function generateShiftSummary(array $data): string
    {
        $prompt = "Write a concise 2-sentence professional shift handover summary for Wing POS (Kenya).
Cashier: {$data['cashier_name']}
Duration: {$data['duration_hours']} hours
Total Sales: KSh {$data['total_sales']} ({$data['sale_count']} transactions)
Cash: KSh {$data['cash_sales']} | M-Pesa: KSh {$data['mpesa_sales']}
Cash Variance: KSh {$data['variance']} ({$data['variance_label']})
Top Product: {$data['top_product']}
Write in third person. Be direct. Include the variance status and top product.";

        return $this->getClaudeInsight($prompt, 200);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FEATURE 2: Auto Expense Categorization
    // ─────────────────────────────────────────────────────────────────────────

    public function suggestExpenseCategory(string $description, float $amount): string
    {
        $categories = \App\Models\ExpenseCategory::pluck('name')->toArray();
        $list = implode(', ', $categories);

        $prompt = "You are categorizing a business expense for a Kenyan retail shop.
Description: \"{$description}\"
Amount: KSh {$amount}
Existing categories: {$list}

Reply with ONLY the single best category name. If none fit, suggest a new short category (2-3 words max). No explanation.";

        return trim($this->getClaudeInsight($prompt, 50));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FEATURE 3: Upsell Suggestions at Checkout
    // ─────────────────────────────────────────────────────────────────────────

    public function getUpsellSuggestions(array $productIds): array
    {
        if (empty($productIds)) return [];

        $suggestions = DB::table('sale_items as si1')
            ->join('sale_items as si2', 'si1.sale_id', '=', 'si2.sale_id')
            ->join('products as p', 'si2.product_id', '=', 'p.id')
            ->whereIn('si1.product_id', $productIds)
            ->whereNotIn('si2.product_id', $productIds)
            ->where('p.is_active', true)
            ->where('p.quantity_in_stock', '>', 0)
            ->select('si2.product_id', 'p.name', 'p.selling_price', DB::raw('COUNT(*) as frequency'))
            ->groupBy('si2.product_id', 'p.name', 'p.selling_price')
            ->orderByDesc('frequency')
            ->limit(3)
            ->get();

        return $suggestions->map(fn($s) => [
            'id'        => $s->product_id,
            'name'      => $s->name,
            'price'     => (float) $s->selling_price,
            'frequency' => (int) $s->frequency,
        ])->toArray();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FEATURE 4: Loss Prevention Alerts
    // ─────────────────────────────────────────────────────────────────────────

    public function getLossPreventionAlerts(): array
    {
        $alerts = [];

        // Alert: cash variance > KSh 500 in last 7 days
        $shiftsWithVariance = DB::table('shifts')
            ->join('users', 'shifts.cashier_id', '=', 'users.id')
            ->where('status', 'closed')
            ->where('closed_at', '>=', now()->subDays(7))
            ->whereRaw('ABS(cash_shortage_overage) > 500')
            ->select('shifts.*', 'users.name as cashier_name')
            ->get();

        foreach ($shiftsWithVariance as $shift) {
            $variance = (float) $shift->cash_shortage_overage;
            $alerts[] = [
                'type'     => 'cash_variance',
                'severity' => abs($variance) > 2000 ? 'high' : 'medium',
                'message'  => "KSh " . number_format(abs($variance), 2) . " cash " .
                              ($variance < 0 ? 'shortage' : 'overage') .
                              " — {$shift->cashier_name} on " .
                              Carbon::parse($shift->closed_at)->format('M d, g:ia'),
                'date'     => Carbon::parse($shift->closed_at)->format('Y-m-d'),
            ];
        }

        // Alert: high discount rate (>40% of sales discounted) in last 7 days
        $shiftDiscounts = DB::table('shifts')
            ->join('users', 'shifts.cashier_id', '=', 'users.id')
            ->leftJoin('sales', 'sales.shift_id', '=', 'shifts.id')
            ->where('shifts.opened_at', '>=', now()->subDays(7))
            ->select(
                'shifts.id',
                'shifts.opened_at',
                'users.name as cashier_name',
                DB::raw('COUNT(sales.id) as total_sales'),
                DB::raw('SUM(CASE WHEN sales.discount_amount > 0 THEN 1 ELSE 0 END) as discounted_sales')
            )
            ->groupBy('shifts.id', 'shifts.opened_at', 'users.name')
            ->having(DB::raw('COUNT(sales.id)'), '>=', 5)
            ->get();

        foreach ($shiftDiscounts as $row) {
            $rate = $row->total_sales > 0 ? ($row->discounted_sales / $row->total_sales) * 100 : 0;
            if ($rate >= 40) {
                $alerts[] = [
                    'type'     => 'high_discounts',
                    'severity' => 'medium',
                    'message'  => round($rate) . "% discount rate — {$row->cashier_name} applied discounts on {$row->discounted_sales}/{$row->total_sales} sales on " .
                                  Carbon::parse($row->opened_at)->format('M d'),
                    'date'     => Carbon::parse($row->opened_at)->format('Y-m-d'),
                ];
            }
        }

        // Sort by date descending
        usort($alerts, fn($a, $b) => strcmp($b['date'], $a['date']));

        $aiSummary = '';
        if (!empty($alerts)) {
            $text = collect($alerts)->pluck('message')->implode('; ');
            $aiSummary = $this->getClaudeInsight(
                "Retail loss prevention alerts for Wing POS: {$text}. Give ONE actionable recommendation in 1 sentence.",
                150
            );
        }

        return [
            'alerts'      => $alerts,
            'ai_summary'  => $aiSummary,
            'alert_count' => count($alerts),
            'high_count'  => collect($alerts)->where('severity', 'high')->count(),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FEATURE 1: Smart Purchase Order Draft
    // ─────────────────────────────────────────────────────────────────────────

    public function draftSmartPurchaseOrder(): array
    {
        $lowStock = Product::where('is_active', true)
            ->whereRaw('quantity_in_stock <= reorder_level')
            ->with('category')
            ->get(['id', 'name', 'quantity_in_stock', 'reorder_level', 'cost_price', 'category_id']);

        if ($lowStock->isEmpty()) {
            return ['items' => [], 'ai_summary' => 'All products are above reorder levels. No purchase order needed right now.', 'total_cost' => 0];
        }

        $items = $lowStock->map(function ($p) {
            $reorder = $this->getReorderRecommendation($p);
            return [
                'product_id'   => $p->id,
                'product_name' => $p->name,
                'category'     => $p->category->name ?? 'General',
                'current_stock'    => $p->quantity_in_stock,
                'reorder_level'    => $p->reorder_level,
                'suggested_qty'    => max(1, (int) $reorder['recommended_qty']),
                'cost_price'       => (float) $p->cost_price,
                'estimated_cost'   => round(max(1, (int) $reorder['recommended_qty']) * (float) $p->cost_price, 2),
                'urgency'          => $reorder['urgency'],
            ];
        })->sortBy(fn($i) => ['high' => 0, 'medium' => 1, 'low' => 2][$i['urgency']])->values()->toArray();

        $totalCost = collect($items)->sum('estimated_cost');

        $lines = collect($items)->map(fn($i) => "{$i['product_name']}: stock={$i['current_stock']}, order={$i['suggested_qty']}, urgency={$i['urgency']}")->implode('; ');
        $aiSummary = $this->getClaudeInsight(
            "Generate a 2-sentence purchase order brief for a Kenyan retail manager (Wing POS). Items: {$lines}. Total estimated cost: KSh " . number_format($totalCost, 2) . ". Prioritize and advise.",
            200
        );

        return ['items' => $items, 'ai_summary' => $aiSummary, 'total_cost' => $totalCost];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FEATURE 2: Customer Churn Prevention
    // ─────────────────────────────────────────────────────────────────────────

    public function getChurnedCustomers(int $daysSince = 30): array
    {
        $customers = DB::table('customers')
            ->leftJoin('sales', function ($join) use ($daysSince) {
                $join->on('customers.id', '=', 'sales.customer_id')
                     ->where('sales.created_at', '>=', now()->subDays($daysSince))
                     ->where('sales.status', 'completed');
            })
            ->whereNull('sales.id')
            ->whereNotNull('customers.phone')
            ->select(
                'customers.id',
                'customers.name',
                'customers.phone',
                DB::raw('(SELECT MAX(sales2.created_at) FROM sales sales2 WHERE sales2.customer_id = customers.id AND sales2.status = \'completed\') as last_purchase'),
                DB::raw('(SELECT COUNT(*) FROM sales sales3 WHERE sales3.customer_id = customers.id AND sales3.status = \'completed\') as total_purchases'),
                DB::raw('(SELECT SUM(sales4.total_amount) FROM sales sales4 WHERE sales4.customer_id = customers.id AND sales4.status = \'completed\') as lifetime_value')
            )
            ->orderByDesc('lifetime_value')
            ->limit(20)
            ->get();

        $result = [];
        foreach ($customers as $customer) {
            $daysSinceLastPurchase = $customer->last_purchase
                ? (int) Carbon::parse($customer->last_purchase)->diffInDays(now())
                : null;

            $topProduct = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->where('sales.customer_id', $customer->id)
                ->select('products.name', DB::raw('SUM(sale_items.quantity) as qty'))
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('qty')
                ->first();

            $prompt = "Write a warm, friendly 1-sentence WhatsApp message (in English) to bring back a customer to Wing Store, Kenya.
Customer: {$customer->name}
Favourite product: " . ($topProduct ? $topProduct->name : 'various items') . "
Last visited: " . ($daysSinceLastPurchase ? "{$daysSinceLastPurchase} days ago" : "a while ago") . "
Keep it personal, under 30 words. No emojis. Start with 'Hi {$customer->name}'";

            $result[] = [
                'id'                    => $customer->id,
                'name'                  => $customer->name,
                'phone'                 => $customer->phone,
                'last_purchase'         => $customer->last_purchase,
                'days_since'            => $daysSinceLastPurchase,
                'total_purchases'       => (int) $customer->total_purchases,
                'lifetime_value'        => (float) $customer->lifetime_value,
                'top_product'           => $topProduct?->name ?? 'N/A',
                'whatsapp_message'      => $this->getClaudeInsight($prompt, 100),
            ];
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FEATURE 3: Invoice Payment Reminder
    // ─────────────────────────────────────────────────────────────────────────

    public function generateInvoiceReminder(\App\Models\Invoice $invoice): string
    {
        $daysOverdue = (int) Carbon::parse($invoice->due_date)->diffInDays(now(), false);
        $tone = $daysOverdue > 30 ? 'firm and serious' : ($daysOverdue > 7 ? 'politely persistent' : 'friendly');

        $prompt = "Write a {$tone} invoice payment reminder message for a Kenyan business owner (Wing Store).
Customer: {$invoice->customer->name}
Invoice #: {$invoice->invoice_number}
Amount Due: KSh " . number_format($invoice->balance_due, 2) . "
Due Date: " . Carbon::parse($invoice->due_date)->format('M d, Y') . "
Days Overdue: {$daysOverdue} days
Write a single professional WhatsApp/SMS message under 50 words. Be direct and include the amount and invoice number.";

        return $this->getClaudeInsight($prompt, 150);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FEATURE 4: Monthly Financial Health Score
    // ─────────────────────────────────────────────────────────────────────────

    public function getFinancialHealthScore(): array
    {
        $start = now()->startOfMonth();
        $end   = now()->endOfMonth();
        $prevStart = now()->subMonth()->startOfMonth();
        $prevEnd   = now()->subMonth()->endOfMonth();

        $revenue = DB::table('sales')->where('status', 'completed')->whereBetween('created_at', [$start, $end])->sum('total_amount');
        $prevRevenue = DB::table('sales')->where('status', 'completed')->whereBetween('created_at', [$prevStart, $prevEnd])->sum('total_amount');

        $cogs = DB::table('sale_items')->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.status', 'completed')->whereBetween('sales.created_at', [$start, $end])
            ->sum(DB::raw('sale_items.quantity * products.cost_price'));

        $expenses = DB::table('expenses')->where('status', 'approved')->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])->sum('amount');
        $prevExpenses = DB::table('expenses')->where('status', 'approved')->whereBetween('expense_date', [$prevStart->toDateString(), $prevEnd->toDateString()])->sum('amount');

        $grossProfit = $revenue - $cogs;
        $netProfit   = $grossProfit - $expenses;
        $grossMargin = $revenue > 0 ? round(($grossProfit / $revenue) * 100, 1) : 0;
        $revenueGrowth = $prevRevenue > 0 ? round((($revenue - $prevRevenue) / $prevRevenue) * 100, 1) : 0;
        $expenseGrowth = $prevExpenses > 0 ? round((($expenses - $prevExpenses) / $prevExpenses) * 100, 1) : 0;

        $overdueInvoices = DB::table('invoices')->where('status', 'overdue')->sum('balance_due');
        $activeLoans     = DB::table('loans')->where('status', 'active')->sum(DB::raw('total_amount - amount_paid'));
        $lowStockCount   = Product::where('is_active', true)->whereRaw('quantity_in_stock <= reorder_level')->count();

        $prompt = "Score this Kenyan retail business (Wing POS) on a scale of 0-100 for financial health this month.
Revenue: KSh " . number_format($revenue, 2) . " (" . ($revenueGrowth >= 0 ? '+' : '') . "{$revenueGrowth}% vs last month)
Expenses: KSh " . number_format($expenses, 2) . " (" . ($expenseGrowth >= 0 ? '+' : '') . "{$expenseGrowth}% vs last month)
Gross Margin: {$grossMargin}%
Net Profit: KSh " . number_format($netProfit, 2) . "
Overdue Invoices: KSh " . number_format($overdueInvoices, 2) . "
Active Loans Outstanding: KSh " . number_format($activeLoans, 2) . "
Low Stock Items: {$lowStockCount}

Reply in this exact JSON format:
{\"score\": 72, \"grade\": \"B\", \"summary\": \"One sentence summary\", \"strengths\": [\"point1\", \"point2\"], \"risks\": [\"risk1\", \"risk2\"], \"action\": \"One specific action to take this week\"}";

        $raw = $this->getClaudeInsight($prompt, 400);

        $health = ['score' => 0, 'grade' => 'N/A', 'summary' => $raw, 'strengths' => [], 'risks' => [], 'action' => ''];
        try {
            $clean = preg_replace('/```json|```/', '', $raw);
            $parsed = json_decode(trim($clean), true);
            if ($parsed) $health = $parsed;
        } catch (\Exception $e) {}

        return array_merge($health, [
            'revenue' => $revenue, 'prev_revenue' => $prevRevenue, 'revenue_growth' => $revenueGrowth,
            'expenses' => $expenses, 'expense_growth' => $expenseGrowth,
            'gross_margin' => $grossMargin, 'net_profit' => $netProfit,
            'overdue_invoices' => $overdueInvoices, 'active_loans' => $activeLoans, 'low_stock_count' => $lowStockCount,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FEATURE 5: AI-Generated Promotion Suggestions
    // ─────────────────────────────────────────────────────────────────────────

    public function suggestPromotion(): array
    {
        $slowMoving = $this->getSlowMovingItems(5);
        if ($slowMoving->isEmpty()) {
            return ['promotions' => [], 'ai_summary' => 'All products are selling well. No promotions needed right now.'];
        }

        $lines = $slowMoving->map(fn($p) =>
            "{$p->name} (stock: {$p->quantity_in_stock}, price: KSh {$p->selling_price})"
        )->implode('; ');

        $prompt = "Suggest specific promotions for these slow-moving products in a Kenyan retail shop (Wing POS).
Products: {$lines}
For each product, suggest ONE of: bundle deal, percentage discount, buy-X-get-Y, or clearance price.
Reply ONLY in this JSON format, no explanation:
[{\"product\": \"name\", \"type\": \"percentage\", \"value\": 15, \"label\": \"15% OFF\", \"reason\": \"short reason\"}]";

        $raw = $this->getClaudeInsight($prompt, 600);
        $promotions = [];
        try {
            $clean = preg_replace('/```json|```/', '', $raw);
            $data  = json_decode(trim($clean), true) ?? [];
            foreach ($data as $promo) {
                $product = $slowMoving->firstWhere('name', $promo['product'] ?? '');
                if ($product) {
                    $promotions[] = array_merge($promo, [
                        'product_id'    => $product->id,
                        'selling_price' => $product->selling_price,
                        'stock'         => $product->quantity_in_stock,
                    ]);
                }
            }
        } catch (\Exception $e) {}

        $aiSummary = $this->getClaudeInsight("Write a 1-sentence intro for these {$slowMoving->count()} promotion suggestions for a retail shop owner.", 80);

        return ['promotions' => $promotions, 'ai_summary' => $aiSummary];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FEATURE 6: Staff Performance Weekly Report
    // ─────────────────────────────────────────────────────────────────────────

    public function getStaffPerformanceReport(): array
    {
        $start = now()->startOfWeek();
        $end   = now()->endOfWeek();

        $cashiers = DB::table('users')
            ->join('sales', 'users.id', '=', 'sales.cashier_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$start, $end])
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(sales.id) as total_sales'),
                DB::raw('SUM(sales.total_amount) as total_revenue'),
                DB::raw('AVG(sales.total_amount) as avg_transaction'),
                DB::raw('SUM(sales.discount_amount) as total_discounts'),
                DB::raw('SUM(sales.total_amount) / NULLIF(COUNT(sales.id), 0) as avg_sale')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_revenue')
            ->get();

        $report = [];
        foreach ($cashiers as $cashier) {
            $discountRate = $cashier->total_revenue > 0
                ? round(($cashier->total_discounts / $cashier->total_revenue) * 100, 1)
                : 0;

            $prompt = "Write a 1-sentence fair performance note for a cashier at Wing POS Kenya.
Name: {$cashier->name} | Sales: {$cashier->total_sales} transactions | Revenue: KSh " . number_format($cashier->total_revenue, 2) . " | Avg transaction: KSh " . number_format($cashier->avg_transaction, 2) . " | Discount rate: {$discountRate}%
Be encouraging and data-driven. Mention one strength and one area to watch.";

            $report[] = [
                'cashier_id'     => $cashier->id,
                'name'           => $cashier->name,
                'total_sales'    => (int) $cashier->total_sales,
                'total_revenue'  => round($cashier->total_revenue, 2),
                'avg_transaction'=> round($cashier->avg_transaction, 2),
                'discount_rate'  => $discountRate,
                'ai_note'        => $this->getClaudeInsight($prompt, 120),
            ];
        }

        $teamSummary = '';
        if (!empty($report)) {
            $top = $report[0]['name'];
            $totalRev = number_format(collect($report)->sum('total_revenue'), 2);
            $teamSummary = $this->getClaudeInsight(
                "Write a 1-sentence weekly team summary: {$top} was the top performer. Team total revenue: KSh {$totalRev}. " . count($report) . " staff active this week. Be motivating.",
                100
            );
        }

        return ['report' => $report, 'period' => $start->format('M d') . ' – ' . $end->format('M d, Y'), 'team_summary' => $teamSummary];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FEATURE 7: Seasonal Demand Forecasting
    // ─────────────────────────────────────────────────────────────────────────

    public function getSeasonalForecast(): array
    {
        $thisMonthStart = now()->startOfMonth();
        $lastYearSameStart = now()->subYear()->startOfMonth();
        $lastYearSameEnd   = now()->subYear()->endOfMonth();

        $lastYearTopProducts = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$lastYearSameStart, $lastYearSameEnd])
            ->select('products.id', 'products.name', 'products.quantity_in_stock', DB::raw('SUM(sale_items.quantity) as last_year_qty'))
            ->groupBy('products.id', 'products.name', 'products.quantity_in_stock')
            ->orderByDesc('last_year_qty')
            ->limit(10)
            ->get();

        $forecasts = $lastYearTopProducts->map(function ($p) use ($thisMonthStart) {
            $thisMonthSoFar = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->where('sale_items.product_id', $p->id)
                ->where('sales.status', 'completed')
                ->where('sales.created_at', '>=', $thisMonthStart)
                ->sum('sale_items.quantity');

            $daysElapsed  = max(1, now()->day);
            $daysInMonth  = now()->daysInMonth;
            $projectedQty = round(($thisMonthSoFar / $daysElapsed) * $daysInMonth);
            $gap          = $p->last_year_qty - $projectedQty;
            $stockNeeded  = max(0, $p->last_year_qty - $p->quantity_in_stock);

            return [
                'product_id'      => $p->id,
                'product_name'    => $p->name,
                'last_year_qty'   => (int) $p->last_year_qty,
                'projected_qty'   => $projectedQty,
                'current_stock'   => (int) $p->quantity_in_stock,
                'stock_gap'       => $stockNeeded,
                'needs_attention' => $stockNeeded > 0,
            ];
        })->toArray();

        $attentionItems = collect($forecasts)->where('needs_attention', true);
        $lines = $attentionItems->map(fn($f) =>
            "{$f['product_name']}: last year sold {$f['last_year_qty']}, current stock {$f['current_stock']}, need {$f['stock_gap']} more"
        )->implode('; ');

        $aiSummary = $lines
            ? $this->getClaudeInsight("Seasonal alert for Wing POS Kenya ({$thisMonthStart->format('F')}): {$lines}. Write a 2-sentence action plan for the owner.", 180)
            : "Stock levels look good compared to last year's same period. Keep monitoring weekly.";

        return [
            'forecasts'  => $forecasts,
            'ai_summary' => $aiSummary,
            'month'      => $thisMonthStart->format('F Y'),
            'last_year_month' => $lastYearSameStart->format('F Y'),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FEATURE 8: Loan Risk Assessment
    // ─────────────────────────────────────────────────────────────────────────

    public function assessLoanRisk(int $customerId, float $loanAmount): array
    {
        $customer = DB::table('customers')->where('id', $customerId)->first();
        if (!$customer) return ['risk' => 'unknown', 'score' => 0, 'reason' => 'Customer not found'];

        $purchaseHistory = DB::table('sales')
            ->where('customer_id', $customerId)
            ->where('status', 'completed')
            ->select(DB::raw('COUNT(*) as total_purchases'), DB::raw('SUM(total_amount) as lifetime_value'), DB::raw('AVG(total_amount) as avg_purchase'))
            ->first();

        $existingLoans = DB::table('loans')
            ->where('customer_id', $customerId)
            ->where('status', 'active')
            ->sum(DB::raw('total_amount - amount_paid'));

        $overdueLoans = DB::table('loans')
            ->where('customer_id', $customerId)
            ->where('status', 'overdue')
            ->count();

        $prompt = "Assess loan risk for a customer at Wing POS Kenya.
Customer: {$customer->name}
Loan Requested: KSh " . number_format($loanAmount, 2) . "
Purchase History: " . ($purchaseHistory->total_purchases ?? 0) . " purchases, lifetime value KSh " . number_format($purchaseHistory->lifetime_value ?? 0, 2) . "
Existing Outstanding Loans: KSh " . number_format($existingLoans, 2) . "
Overdue Loan History: {$overdueLoans} times

Reply ONLY in this JSON format:
{\"risk\": \"low\", \"score\": 75, \"reason\": \"short reason\", \"recommendation\": \"approve/approve with conditions/decline\"}";

        $raw = $this->getClaudeInsight($prompt, 200);
        $assessment = ['risk' => 'medium', 'score' => 50, 'reason' => $raw, 'recommendation' => 'approve with conditions'];
        try {
            $clean = preg_replace('/```json|```/', '', $raw);
            $parsed = json_decode(trim($clean), true);
            if ($parsed) $assessment = $parsed;
        } catch (\Exception $e) {}

        return array_merge($assessment, [
            'customer_name'    => $customer->name,
            'loan_amount'      => $loanAmount,
            'total_purchases'  => (int) ($purchaseHistory->total_purchases ?? 0),
            'lifetime_value'   => (float) ($purchaseHistory->lifetime_value ?? 0),
            'existing_loans'   => (float) $existingLoans,
            'overdue_count'    => (int) $overdueLoans,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FEATURE 9: Receipt Thank-You Message
    // ─────────────────────────────────────────────────────────────────────────

    public function generateReceiptThankYou(\App\Models\Sale $sale): string
    {
        $items = $sale->items->map(fn($i) => $i->product->name ?? 'item')->implode(', ');
        $isReturn = DB::table('sales')->where('customer_id', $sale->customer_id)->where('status', 'completed')->count() > 1;

        $prompt = "Write a warm, brief 1-sentence thank-you message for the bottom of a Kenyan retail receipt (Wing Store).
Items purchased: {$items}
Returning customer: " . ($isReturn ? 'Yes' : 'No') . "
Make it feel personal and relevant to what they bought. Under 20 words. No emojis.";

        return $this->getClaudeInsight($prompt, 80);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FEATURE 10: New Product Setup Helper
    // ─────────────────────────────────────────────────────────────────────────

    public function suggestProductSetup(string $productName, string $categoryName): array
    {
        $similar = Product::where('is_active', true)
            ->whereHas('category', fn($q) => $q->where('name', 'like', "%{$categoryName}%"))
            ->select('name', 'selling_price', 'cost_price', 'reorder_level')
            ->limit(5)->get();

        $similarText = $similar->map(fn($p) => "{$p->name}: sells at KSh {$p->selling_price}, cost KSh {$p->cost_price}, reorder at {$p->reorder_level}")->implode('; ');

        $prompt = "Suggest setup details for a new product at Wing Store, Kenya.
Product: {$productName}
Category: {$categoryName}
Similar products in this category: {$similarText}

Reply ONLY in this JSON format:
{\"selling_price\": 150, \"cost_price\": 100, \"reorder_level\": 10, \"description\": \"Short product description under 15 words\"}";

        $raw = $this->getClaudeInsight($prompt, 200);
        $suggestion = ['selling_price' => null, 'cost_price' => null, 'reorder_level' => 10, 'description' => ''];
        try {
            $clean = preg_replace('/```json|```/', '', $raw);
            $parsed = json_decode(trim($clean), true);
            if ($parsed) $suggestion = $parsed;
        } catch (\Exception $e) {}

        return $suggestion;
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