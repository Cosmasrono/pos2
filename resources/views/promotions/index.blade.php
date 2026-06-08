@extends('layouts.app')

@section('title', 'Manage Promotions')
@section('page-title', 'Promotions')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">All Promotions</h4>
        <a href="{{ route('promotions.create') }}" class="btn btn-primary rounded-pill">
            <i class="bi bi-plus-lg"></i> Create Promotion
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted">
                        <tr>
                            <th class="ps-4">Name</th>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Min Spend</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($promotions as $promotion)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark">{{ $promotion->name }}</div>
                                <small class="text-muted">
                                    {{ $promotion->start_date ? $promotion->start_date->format('M d, Y') : 'No start' }} - 
                                    {{ $promotion->end_date ? $promotion->end_date->format('M d, Y') : 'No end' }}
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark fw-normal border">{{ $promotion->code ?: 'N/A' }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $promotion->type === 'percentage' ? 'bg-info-subtle text-info' : 'bg-success-subtle text-success' }} px-3">
                                    {{ ucfirst($promotion->type) }}
                                </span>
                            </td>
                            <td>
                                @if($promotion->type === 'percentage')
                                    {{ $promotion->value }}%
                                @else
                                    KES {{ number_format($promotion->value, 2) }}
                                @endif
                            </td>
                            <td>KES {{ number_format($promotion->min_spend, 2) }}</td>
                            <td>
                                @if($promotion->is_active && (!$promotion->end_date || $promotion->end_date->isFuture()))
                                    <span class="badge bg-success rounded-pill px-3">Active</span>
                                @else
                                    <span class="badge bg-danger rounded-pill px-3">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group">
                                    <a href="{{ route('promotions.edit', $promotion) }}" class="btn btn-sm btn-outline-primary rounded-circle me-1 border-0">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="{{ route('promotions.destroy', $promotion) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this promotion?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle border-0">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-ticket-perforated display-4 d-block mb-3 opacity-25"></i>
                                No promotions found. Start by creating one!
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($promotions->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $promotions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
