@extends('layouts.app')

@section('title', 'Sales Report')
@section('page-title', 'Sales Report')

@section('content')
<div class="container-fluid px-4" id="report-container">
    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('reports.sales') ? 'active' : '' }}" href="{{ route('reports.sales') }}">
                <i class="bi bi-receipt"></i> Sales Report
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('reports.pnl') ? 'active' : '' }}" href="{{ route('reports.pnl') }}">
                <i class="bi bi-calculator"></i> Profit & Loss Statement
            </a>
        </li>
    </ul>

    <div class="card mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Report</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('reports.sales') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                    @if(isset($sales) && $sales->count() > 0)
                        <button type="button" onclick="downloadPdf()" class="btn btn-outline-danger">
                            <i class="bi bi-file-pdf"></i> Download PDF
                        </button>
                        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-success">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h6>Total Sales</h6>
                    <div class="stat-value">KES {{ number_format($summary['total_sales'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h6>Transactions</h6>
                    <div class="stat-value">{{ $summary['total_transactions'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h6>Average Transaction</h6>
                    <div class="stat-value">KES {{ number_format($summary['average_transaction'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h6>M-Pesa Sales</h6>
                    <div class="stat-value">KES {{ number_format($summary['mpesa_sales'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Daily Revenue Breakdown</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th class="text-end">Transactions</th>
                                    <th class="text-end">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dailyRevenue as $date => $data)
                                    <tr>
                                        <td>{{ Carbon\Carbon::parse($date)->format('M d, Y') }}</td>
                                        <td class="text-end">{{ $data['transactions'] }}</td>
                                        <td class="text-end fw-bold text-success">KES {{ number_format($data['revenue'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">No data available</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top Selling Products</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end">Quantity Sold</th>
                                    <th class="text-end">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topProducts as $product)
                                    <tr>
                                        <td>{{ $product->name }}</td>
                                        <td class="text-end">{{ $product->total_quantity }}</td>
                                        <td class="text-end fw-bold">KES {{ number_format($product->total_revenue, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">No data available</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Sales Transactions (Revenue List)</h6>
            <span class="badge bg-primary">{{ $sales->count() }} Transactions</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Receipt #</th>
                            <th>Time</th>
                            <th>Cashier</th>
                            <th>Items</th>
                            <th>Payment</th>
                            <th class="text-end">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                            <tr>
                                <td class="fw-bold">{{ $sale->receipt_number }}</td>
                                <td>{{ $sale->created_at->format('H:i') }}</td>
                                <td>{{ $sale->cashier->name }}</td>
                                <td>{{ $sale->getTotalQuantity() }} items</td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ ucfirst($sale->primary_payment_method) }}
                                    </span>
                                </td>
                                <td class="text-end fw-bold">KES {{ number_format($sale->total_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No sales recorded for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function downloadPdf() {
    const element = document.getElementById('report-container');
    const opt = {
        margin: 10,
        filename: 'Sales_Report_{{ $startDate }}_to_{{ $endDate }}.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
}
</script>
@endsection
@endsection
