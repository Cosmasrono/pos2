@extends('layouts.app')

@section('title', 'System Audit Trail')
@section('page-title', 'Audit Trail')

@section('content')
<div class="container-fluid px-4">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('audit-logs.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search by user or event..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="event" class="form-select">
                        <option value="">All Events</option>
                        @foreach($events as $event)
                            <option value="{{ $event }}" {{ request('event') == $event ? 'selected' : '' }}>{{ ucfirst($event) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill">Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('audit-logs.index') }}" class="btn btn-light w-100 rounded-pill">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small uppercase">
                        <tr>
                            <th class="ps-4">Time</th>
                            <th>User</th>
                            <th>Event</th>
                            <th>Target</th>
                            <th>IP Address</th>
                            <th class="text-end pe-4">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr>
                            <td class="ps-4">
                                <span class="text-dark fw-bold">{{ $log->created_at->format('H:i:s') }}</span><br>
                                <small class="text-muted">{{ $log->created_at->format('M d, Y') }}</small>
                            </td>
                            <td>
                                @if($log->user)
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                            <i class="bi bi-person text-primary"></i>
                                        </div>
                                        <span>{{ $log->user->name }}</span>
                                    </div>
                                @else
                                    <span class="text-muted">System/Guest</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge rounded-pill px-3 
                                    @if($log->event == 'login') bg-success-subtle text-success
                                    @elseif($log->event == 'failed_login') bg-danger-subtle text-danger
                                    @elseif($log->event == 'deleted') bg-danger
                                    @elseif($log->event == 'updated') bg-warning-subtle text-warning
                                    @else bg-info-subtle text-info @endif">
                                    {{ strtoupper($log->event) }}
                                </span>
                            </td>
                            <td>
                                @if($log->auditable_type)
                                    <small class="text-muted">{{ str_replace('App\\Models\\', '', $log->auditable_type) }} #{{ $log->auditable_id }}</small>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                <code class="small">{{ $log->ip_address }}</code>
                            </td>
                            <td class="text-end pe-4">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#logModal{{ $log->id }}">
                                    View
                                </button>

                                <!-- Modal -->
                                <div class="modal fade" id="logModal{{ $log->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg text-start">
                                        <div class="modal-content border-0 shadow">
                                            <div class="modal-header border-0 pb-0">
                                                <h5 class="modal-title fw-bold">Audit Detail #{{ $log->id }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row mb-4">
                                                    <div class="col-md-6">
                                                        <label class="small text-muted mb-1">USER</label>
                                                        <p class="fw-bold mb-0">{{ $log->user ? $log->user->name : 'System' }}</p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="small text-muted mb-1">EVENT</label>
                                                        <p class="fw-bold mb-0 text-primary">{{ strtoupper($log->event) }}</p>
                                                    </div>
                                                </div>
                                                
                                                @if($log->old_values || $log->new_values)
                                                    <div class="bg-light rounded-3 p-3 mb-3">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <label class="small text-muted mb-2 d-block">OLD VALUES</label>
                                                                <pre class="small bg-white p-2 rounded border mb-0" style="max-height: 200px; overflow-y: auto;">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="small text-muted mb-2 d-block">NEW VALUES</label>
                                                                <pre class="small bg-white p-2 rounded border mb-0" style="max-height: 200px; overflow-y: auto;">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                                <div class="small">
                                                    <span class="text-muted">Browser:</span> {{ $log->user_agent }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-shield-check display-4 opacity-25"></i>
                                <p class="mt-3">No activity logs found.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($logs->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $logs->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
