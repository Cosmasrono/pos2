@extends('layouts.app')

@section('title', 'No Active Shift')
@section('page-title', 'Point of Sale')

@section('content')
<div class="row">
    <div class="col-md-6 mx-auto">
        <div class="card text-center">
            <div class="card-body py-5">
                <i class="bi bi-exclamation-circle" style="font-size: 48px; color: #ffc107;"></i>
                <h4 class="mt-3">No Active Shift</h4>
                <p class="text-muted">You need to open a shift before you can process sales.</p>
                
                <button class="btn btn-success btn-lg mt-3" data-bs-toggle="modal" data-bs-target="#openShiftModal">
                    <i class="bi bi-play-circle"></i> Open Shift
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Open Shift Modal -->
<div class="modal fade" id="openShiftModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Open New Shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('shifts.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="opening_cash" class="form-label">Opening Cash (KES)</label>
                        <input type="number" step="0.01" id="opening_cash" name="opening_cash" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="opening_notes" class="form-label">Notes</label>
                        <textarea id="opening_notes" name="opening_notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Open Shift</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
