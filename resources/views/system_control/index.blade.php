@extends('layouts.app')

@section('title', 'System Control Panel')
@section('page-title', 'System Control')

@section('content')
<div class="container-fluid px-4">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white py-3 border-0 text-center">
                    <h5 class="mb-0 fw-bold">System Status Toggle</h5>
                </div>
                <div class="card-body p-4 p-md-5 text-center">
                    @if($isSystemActive)
                        <div class="mb-4">
                            <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px;">
                                <i class="bi bi-broadcast fs-2"></i>
                            </div>
                            <h4 class="mt-3 fw-bold">System is ONLINE</h4>
                            <p class="text-muted small">All users can access the POS and Dashboard.</p>
                        </div>
                        <button type="button" class="btn btn-outline-danger rounded-pill px-4 shadow-sm btn-sm" data-bs-toggle="modal" data-bs-target="#deactivateSystemModal">
                            <i class="bi bi-power me-2"></i> Deactivate System
                        </button>
                    @else
                        <div class="mb-4">
                            <div class="bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px;">
                                <i class="bi bi-exclamation-octagon fs-2"></i>
                            </div>
                            <h4 class="mt-3 fw-bold">System is OFFLINE</h4>
                            <p class="text-muted small">Only you can access the system. Others are redirected.</p>
                        </div>
                        <button type="button" class="btn btn-outline-success rounded-pill px-4 shadow-sm btn-sm" data-bs-toggle="modal" data-bs-target="#activateSystemModal">
                            <i class="bi bi-play-fill me-2"></i> Activate System
                        </button>
                    @endif
                </div>
            </div>
        </div>

        @if($isSystemActive)
        <!-- Deactivation Modal -->
        <div class="modal fade" id="deactivateSystemModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content text-start">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-danger">Security Verification</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('system.toggle') }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="deactivate">
                        <div class="modal-body">
                            <p>You are about to deactivate the system. This will log out all other users. Are you sure you want to proceed?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger px-4">Confirm & Deactivate</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif

        @if(!$isSystemActive)
        <!-- Activation Modal -->
        <div class="modal fade" id="activateSystemModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content text-start">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-success">Security Verification</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('system.toggle') }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="activate">
                        <div class="modal-body">
                            <p>You are about to activate the system. All users will regain access. Are you sure you want to proceed?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success px-4">Confirm & Activate</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white py-3 border-0 text-center">
                    <h5 class="mb-0 fw-bold">Subscription Management</h5>
                </div>
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        @if($subscriptionStatus === 'active')
                            <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px;">
                                <i class="bi bi-calendar-check fs-2"></i>
                            </div>
                            <h4 class="mt-3 fw-bold text-success">Active Subscription</h4>
                        @else
                            <div class="bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px;">
                                <i class="bi bi-calendar-x fs-2"></i>
                            </div>
                            <h4 class="mt-3 fw-bold text-danger">Subscription Expired</h4>
                        @endif
                        
                        <p class="text-muted mt-2 small">
                            Expires: <strong>{{ $subscriptionExpiresAt ? $subscriptionExpiresAt->format('M d, Y') : 'Not Set' }}</strong>
                            ({{ $subscriptionExpiresAt ? $subscriptionExpiresAt->diffForHumans() : '' }})
                        </p>
                    </div>

                    <form action="{{ route('system.subscription.update') }}" method="POST">
                        @csrf
                        <div class="row g-2 mb-3">
                            <div class="col-md-7">
                                <label for="expires_at" class="form-label small fw-bold text-uppercase">Expiry Date</label>
                                <input type="date" class="form-control form-control-sm" id="expires_at" name="expires_at" 
                                       value="{{ $subscriptionExpiresAt ? $subscriptionExpiresAt->format('Y-m-d') : '' }}" required>
                            </div>
                            <div class="col-md-5">
                                <label for="status" class="form-label small fw-bold text-uppercase">Status</label>
                                <select class="form-select form-select-sm" id="status" name="status">
                                    <option value="active" {{ $subscriptionStatus === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="expired" {{ $subscriptionStatus === 'expired' ? 'selected' : '' }}>Expired</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-dark btn-sm w-100 rounded-pill py-2 shadow-sm">
                            <i class="bi bi-save me-2"></i> Update Subscription
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center mt-4">
        <div class="col-lg-12">
            <div class="p-4 bg-white shadow-sm rounded-4 small">
                <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-primary"></i> Owner Security Information</h6>
                <div class="row">
                    <div class="col-md-6">
                        <ul class="mb-0 text-muted">
                            <li>The <strong>Kill-Switch</strong> status is permanent until toggled back.</li>
                            <li>Your account ({{ auth()->user()->email }}) bypasses all restrictions.</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="mb-0 text-muted">
                            <li>Subscription expiry blocks all non-owner access automatically.</li>
                            <li>Audit logs will track all changes to these settings.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
