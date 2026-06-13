@extends('layouts.app')

@section('title', 'Product Categories')
@section('page-title', 'Product Categories')

@section('content')
<div class="container-fluid px-4">

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
            <div>
                <h6 class="m-0 fw-bold text-primary">Product Categories</h6>
                <small class="text-muted">Group your products so they are easy to find.</small>
            </div>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="bi bi-plus-circle me-1"></i> Add New Category
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Category Name</th>
                            <th>Description</th>
                            <th>No. of Products</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr>
                                <td class="text-muted small">{{ $loop->iteration }}</td>
                                <td>
                                    <span class="fw-bold">{{ $category->name }}</span>
                                </td>
                                <td class="text-muted">{{ $category->description ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-primary rounded-pill">{{ $category->products_count }} product{{ $category->products_count != 1 ? 's' : '' }}</span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('categories.edit', $category) }}"
                                           class="btn btn-sm btn-outline-primary"
                                           title="Edit this category">
                                            <i class="bi bi-pencil me-1"></i>Edit
                                        </a>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="confirmDelete('{{ $category->name }}', '{{ route('categories.destroy', $category) }}')"
                                                title="Delete this category"
                                                {{ $category->products_count > 0 ? 'disabled' : '' }}>
                                            <i class="bi bi-trash me-1"></i>Delete
                                        </button>
                                        @if($category->products_count > 0)
                                            <small class="text-muted align-self-center">
                                                <i class="bi bi-info-circle"></i> Has products
                                            </small>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="text-center py-5">
                                        <i class="bi bi-tags fs-1 text-muted opacity-25 d-block mb-3"></i>
                                        <h6 class="text-muted">No categories yet</h6>
                                        <p class="text-muted small mb-3">Categories help you organise products — for example: <em>Medicines</em>, <em>Food</em>, <em>Electronics</em>.</p>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                            <i class="bi bi-plus-circle me-1"></i> Add Your First Category
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $categories->links() }}</div>
        </div>
    </div>
</div>

{{-- Add Category Modal --}}
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('categories.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title text-primary"><i class="bi bi-plus-circle me-2"></i>Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_name" class="form-label fw-bold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" id="add_name" name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" placeholder="e.g. Medicines, Food, Electronics" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-1">
                        <label for="add_description" class="form-label fw-bold">Description <span class="text-muted small">(optional)</span></label>
                        <textarea id="add_description" name="description" rows="2"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="What goes in this category?">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Delete Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-exclamation-triangle-fill text-warning fs-1 d-block mb-3"></i>
                <p class="mb-1">Are you sure you want to delete <strong id="deleteCategoryName"></strong>?</p>
                <p class="text-muted small">This cannot be undone.</p>
            </div>
            <div class="modal-footer border-0 justify-content-center gap-3">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">
                    <i class="bi bi-x me-1"></i>No, Keep It
                </button>
                <form id="deleteForm" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger px-4">
                        <i class="bi bi-trash me-1"></i>Yes, Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function confirmDelete(name, url) {
    document.getElementById('deleteCategoryName').textContent = name;
    document.getElementById('deleteForm').action = url;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Re-open the Add modal if the server rejected the form (e.g. duplicate name).
@if($errors->any() && old('name') !== null)
    document.addEventListener('DOMContentLoaded', () => {
        new bootstrap.Modal(document.getElementById('addCategoryModal')).show();
    });
@endif
</script>
@endpush
