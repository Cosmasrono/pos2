<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PredictionLog;
use App\Services\AIInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AIInventoryController extends Controller
{
    public function __construct(protected AIInventoryService $aiService) {}

    public function dashboard()
    {
        $products = Product::where('is_active', true)->get();

        $reorderRecommendations = [];
        $trendingUp = [];
        $trendingDown = [];
        $pricingRecommendations = [];
        $wasteRisks = [];

        foreach ($products as $product) {
            $recommendation = $this->aiService->getReorderRecommendation($product);
            if ($recommendation['needs_reorder']) {
                $reorderRecommendations[] = compact('product', 'recommendation');
            }

            $prediction = $this->aiService->predictDemand($product, 30);
            $trend = $prediction['trend_percentage'];
            if ($trend > 10)       $trendingUp[]   = ['product' => $product, 'trend' => $trend];
            elseif ($trend < -10)  $trendingDown[] = ['product' => $product, 'trend' => $trend];

            $pricing = $this->aiService->suggestOptimalPrice($product);
            if ($pricing['price_change'] != 0 && $pricing['confidence'] > 70) {
                $pricingRecommendations[] = compact('product', 'pricing');
            }

            if ($product->expiry_date) {
                $waste = $this->aiService->predictWasteRisk($product);
                if ($waste['has_risk'] && $waste['risk_level'] !== 'low') {
                    $wasteRisks[] = ['product' => $product, 'risk' => $waste];
                }
            }
        }

        usort($reorderRecommendations, fn($a, $b) =>
            ['high' => 0, 'medium' => 1, 'low' => 2][$a['recommendation']['urgency']] <=>
            ['high' => 0, 'medium' => 1, 'low' => 2][$b['recommendation']['urgency']]
        );

        usort($pricingRecommendations, fn($a, $b) =>
            $b['pricing']['confidence'] <=> $a['pricing']['confidence']
        );

        $globalPrompt = "Inventory snapshot: " . count($reorderRecommendations) . " items need reorder, "
            . count($trendingUp) . " trending up, " . count($pricingRecommendations)
            . " pricing opportunities. Give a 1-sentence executive summary.";

        return view('ai.dashboard', [
            'reorderRecommendations' => $reorderRecommendations,
            'trendingUp'             => $trendingUp,
            'trendingDown'           => $trendingDown,
            'slowMoving'             => $this->aiService->getSlowMovingItems(10),
            'pricingRecommendations' => $pricingRecommendations,
            'wasteRisks'             => $wasteRisks,
            'bundleSuggestions'      => $this->aiService->suggestProductBundles(),
            'dailyBriefing'          => $this->aiService->getDailyExecutiveBriefing(),
            'globalAIInsight'        => $this->aiService->getClaudeInsight($globalPrompt, 150),
        ]);
    }

    public function productAnalysis(Product $product)
    {
        $salesHistory = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sale_items.product_id', $product->id)
            ->where('sales.status', 'completed')
            ->where('sales.created_at', '>=', now()->subDays(30))
            ->select(DB::raw('CAST(sales.created_at AS DATE) as date'), DB::raw('SUM(sale_items.quantity) as total_qty'))
            ->groupBy(DB::raw('CAST(sales.created_at AS DATE)'))
            ->orderBy('date')
            ->get();

        return view('ai.product-analysis', [
            'product'             => $product,
            'demandForecast'      => $this->aiService->predictDemand($product, 30),
            'reorderInfo'         => $this->aiService->getReorderRecommendation($product),
            'seasonality'         => $this->aiService->detectSeasonality($product),
            'salesHistory'        => $salesHistory,
            'smartInsight'        => $this->aiService->getSmartInsights($product),
            'pricingRecommendation' => $this->aiService->suggestOptimalPrice($product),
            'wasteRisk'           => $this->aiService->predictWasteRisk($product),
        ]);
    }

    

    public function pricingDashboard()
    {
        $recommendations = Product::where('is_active', true)->get()
            ->map(fn($product) => [
                'product' => $product,
                'pricing' => $this->aiService->suggestOptimalPrice($product),
            ])
            ->filter(fn($item) => $item['pricing']['price_change'] != 0)
            ->sortByDesc(fn($item) => abs($item['pricing']['price_change']))
            ->values()->all();

        return view('ai.pricing', compact('recommendations'));
    }


    public function kenyanMarketPricing()
    {
        $data = $this->aiService->getKenyanMarketPricingAnalysis();
        return view('ai.pricing-health', $data);
    }   

    public function bundleSuggestions()
    {
        return view('ai.bundles', ['bundles' => $this->aiService->suggestProductBundles()]);
    }

    public function wasteManagement()
    {
        $risks = Product::where('is_active', true)->whereNotNull('expiry_date')->get()
            ->map(fn($product) => ['product' => $product, 'risk' => $this->aiService->predictWasteRisk($product)])
            ->filter(fn($item) => $item['risk']['has_risk'])
            ->sortBy(fn($item) => ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3][$item['risk']['risk_level']])
            ->values()->all();

        return view('ai.waste-management', compact('risks'));
    }

    public function branchSalesAnalysis()
    {
        $analysis   = $this->aiService->getBranchSalesAnalysis(5);
        $aiInsight  = $this->aiService->getBranchSalesAIInsight($analysis);

        return view('ai.branch-sales', compact('analysis', 'aiInsight'));
    }

    public function smartPurchaseOrder()
    {
        $data = $this->aiService->draftSmartPurchaseOrder();
        return view('ai.smart-purchase-order', $data);
    }

    public function customerChurn()
    {
        $customers = $this->aiService->getChurnedCustomers(30);
        return view('ai.customer-churn', compact('customers'));
    }

    public function financialHealth()
    {
        $data = $this->aiService->getFinancialHealthScore();
        return view('ai.financial-health', $data);
    }

    public function promotionSuggestions()
    {
        $data = $this->aiService->suggestPromotion();
        return view('ai.promotion-suggestions', $data);
    }

    public function staffPerformance()
    {
        $data = $this->aiService->getStaffPerformanceReport();
        return view('ai.staff-performance', $data);
    }

    public function seasonalForecast()
    {
        $data = $this->aiService->getSeasonalForecast();
        return view('ai.seasonal-forecast', $data);
    }

    public function loanRiskAssessment(Request $request)
    {
        $request->validate(['customer_id' => 'required|exists:customers,id', 'amount' => 'required|numeric|min:1']);
        return response()->json($this->aiService->assessLoanRisk((int) $request->customer_id, (float) $request->amount));
    }

    public function productSetupHelper(Request $request)
    {
        $request->validate(['name' => 'required|string', 'category' => 'required|string']);
        return response()->json($this->aiService->suggestProductSetup($request->name, $request->category));
    }

    public function lossPreventionAlerts()
    {
        $data = $this->aiService->getLossPreventionAlerts();
        return view('ai.loss-prevention', $data);
    }

    public function predictionAccuracy()
    {
        $logs = PredictionLog::whereNotNull('accuracy_score')
            ->where('prediction_type', 'demand_forecast')
            ->orderByDesc('prediction_date')
            ->limit(200)
            ->get();

        $overall   = $logs->avg('accuracy_score') ?? 0;
        $last30    = $logs->where('prediction_date', '>=', now()->subDays(30)->toDateString())->avg('accuracy_score') ?? 0;
        $hitTarget = $logs->where('accuracy_score', '>=', 80)->count();
        $total     = $logs->count();

        // Worst performers — products where predictions are consistently off
        $byProduct = $logs->groupBy('product_id')->map(fn($g) => [
            'product_id'   => $g->first()->product_id,
            'product_name' => $g->first()->product?->name ?? 'Unknown',
            'avg_accuracy' => round($g->avg('accuracy_score'), 1),
            'count'        => $g->count(),
        ])->sortBy('avg_accuracy')->take(10)->values();

        return response()->json([
            'overall_accuracy'  => round($overall, 1),
            'last_30d_accuracy' => round($last30, 1),
            'target_hit_rate'   => $total > 0 ? round(($hitTarget / $total) * 100, 1) : 0,
            'total_evaluated'   => $total,
            'target'            => 80,
            'on_track'          => $last30 >= 80,
            'worst_products'    => $byProduct,
        ]);
    }

    public function executeRecommendation(Request $request)
    {
        $request->validate([
            'action_type' => 'required|in:reorder,price_change,bundle_create',
            'product_id'  => 'required|exists:products,id',
            'parameters'  => 'required|array',
        ]);

        $product = Product::findOrFail($request->product_id);

        return match ($request->action_type) {
            'reorder'      => response()->json(['success' => true, 'message' => "Reorder initiated for {$product->name}"]),
            'price_change' => tap(response()->json(['success' => true, 'message' => "Price updated to KSh " . number_format($request->parameters['new_price'], 2) . " for {$product->name}"]),
                                fn() => $product->update(['selling_price' => $request->parameters['new_price']])),
            'bundle_create' => response()->json(['success' => true, 'message' => 'Bundle created successfully']),
            default         => response()->json(['success' => false, 'message' => 'Unknown action type'], 400),
        };
    }
}