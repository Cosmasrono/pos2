@extends('layouts.app')

@section('title', 'Profit & Loss')
@section('page-title', 'Profit & Loss Statement')

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
            <form action="{{ route('reports.pnl') }}" method="GET" class="row g-3">
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
                    @if(isset($revenue) && $revenue > 0)
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

    @if($pendingCount > 0)
        <div class="alert alert-warning border-0 shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                <div>
                    <h6 class="alert-heading fw-bold mb-1">Pending Expenses Detected</h6>
                    <p class="mb-0">There are <strong>{{ $pendingCount }}</strong> pending expenses totaling <strong>KES {{ number_format($pendingAmount, 2) }}</strong> for this period. These are <strong>not included</strong> in the Profit & Loss statement below until they are approved.</p>
                </div>
                <div class="ms-auto">
                    <a href="{{ route('expenses.index') }}" class="btn btn-warning btn-sm fw-bold">Review Expenses</a>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Financial Summary</h6>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tbody>
                            <tr>
                                <td class="fw-bold">Total Revenue (Sales)</td>
                                <td class="text-end">KES {{ number_format($revenue, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="fw-bold ps-4 text-muted">Cost of Goods Sold (COGS)</td>
                                <td class="text-end text-danger">({{ number_format($cogs, 2) }})</td>
                            </tr>
                            <tr class="table-active">
                                <td class="fw-bold">Gross Profit</td>
                                <td class="text-end fw-bold">KES {{ number_format($grossProfit, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="fw-bold ps-4 text-muted">Total Operating Expenses</td>
                                <td class="text-end text-danger">({{ number_format($expenses, 2) }})</td>
                            </tr>
                            <tr class="table-success">
                                <td class="fw-bold fs-5">Net Profit</td>
                                <td class="text-end fw-bold fs-5 {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">
                                    KES {{ number_format($netProfit, 2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center py-2">
                                    <h6 class="text-muted small">Gross Margin</h6>
                                    <h5 class="mb-0">{{ $revenue > 0 ? number_format(($grossProfit / $revenue) * 100, 1) : 0 }}%</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center py-2">
                                    <h6 class="text-muted small">Net Margin</h6>
                                    <h5 class="mb-0">{{ $revenue > 0 ? number_format(($netProfit / $revenue) * 100, 1) : 0 }}%</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Expense Breakdown</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">% of Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expenseBreakdown as $item)
                                    <tr>
                                        <td>{{ $item->display_name }}</td>
                                        <td class="text-end">KES {{ number_format($item->total_amount, 2) }}</td>
                                        <td class="text-end">
                                            {{ $expenses > 0 ? number_format(($item->total_amount / $expenses) * 100, 1) : 0 }}%
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No expenses recorded for this period.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if($expenses > 0)
                            <tfoot>
                                <tr class="table-light fw-bold">
                                    <td>Total</td>
                                    <td class="text-end">KES {{ number_format($expenses, 2) }}</td>
                                    <td class="text-end">100%</td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
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
        filename: 'Profit_Loss_Report_{{ $startDate }}_to_{{ $endDate }}.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
}
</script>
@endsection
@endsection
