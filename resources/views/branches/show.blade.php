@extends('layouts.app')

@section('title', $branch->name)
@section('page-title', 'Branch Details: ' . $branch->name)

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h5>{{ $branch->name }}</h5>
    </div>
    <div class="col-md-4 text-end">
        <a href="{{ route('branches.edit', $branch) }}" class="btn btn-warning btn-sm">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">Branch Information</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tbody>
                        <tr>
                            <td><strong>Name</strong></td>
                            <td>{{ $branch->name }}</td>
                        </tr>
                        <tr>
                            <td><strong>Code</strong></td>
                            <td>{{ $branch->code ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Address</strong></td>
                            <td>{{ $branch->address ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Phone</strong></td>
                            <td>{{ $branch->phone ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Type</strong></td>
                            <td>
                                @if ($branch->is_main)
                                    <span class="badge bg-success">Main Branch</span>
                                @else
                                    <span class="badge bg-secondary">Secondary</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Stock Distribution %</strong></td>
                            <td>
                                <span class="badge bg-info" style="font-size: 1em;">{{ $branch->stock_distribution_percentage }}%</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Products in This Branch</h6>
            </div>
            <div class="card-body">
                @forelse ($branch->productBranchStocks as $stock)
                    <div class="mb-2 pb-2 border-bottom">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $stock->product->name }}</strong>
                            <span class="badge bg-primary">{{ $stock->quantity_in_stock }} units</span>
                        </div>
                        <small class="text-muted">SKU: {{ $stock->product->sku }}</small>
                    </div>
                @empty
                    <p class="text-muted">No products assigned to this branch yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endsection
