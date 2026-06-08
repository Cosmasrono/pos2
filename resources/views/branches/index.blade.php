@extends('layouts.app')

@section('title', 'Branches')
@section('page-title', 'Branch Management')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h5>All Branches</h5>
    </div>
    <div class="col-md-4 text-end">
        <a href="{{ route('branches.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Branch
        </a>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Address</th>
                    <th>Stock Distribution %</th>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($branches as $branch)
                    <tr>
                        <td><strong>{{ $branch->name }}</strong></td>
                        <td>{{ $branch->code ?? 'N/A' }}</td>
                        <td>{{ $branch->address ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-info">{{ $branch->stock_distribution_percentage }}%</span>
                        </td>
                        <td>
                            @if ($branch->is_main)
                                <span class="badge bg-success">Main Branch</span>
                            @else
                                <span class="badge bg-secondary">Secondary</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('branches.show', $branch) }}" class="btn btn-sm btn-info">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('branches.edit', $branch) }}" class="btn btn-sm btn-warning">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('branches.destroy', $branch) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Sure?')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No branches found. <a href="{{ route('branches.create') }}">Create one</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="alert alert-info mt-4">
    <i class="bi bi-info-circle"></i>
    <strong>Stock Distribution Percentage:</strong> When you create a product, the total stock will be automatically distributed to each branch based on this percentage. 
    For example, if you set Branch A to 40%, it will receive 40% of the total stock you specify.
</div>

@endsection
