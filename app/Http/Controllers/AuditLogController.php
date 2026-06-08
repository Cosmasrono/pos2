<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        if (!auth()->user()->isOwner() && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access to Audit Logs.');
        }

        $query = AuditLog::with('user')->latest();

        // Hide Owner's activities from Super Admin
        if (auth()->user()->isSuperAdmin() && !auth()->user()->isOwner()) {
            $query->whereHas('user', function($q) {
                $q->whereDoesntHave('roles', function($roleQuery) {
                    $roleQuery->where('name', 'owner');
                });
            })->orWhereDoesntHave('user'); // Include logs without user (system logs)
        }

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('event', 'like', "%{$search}%")
                  ->orWhere('auditable_type', 'like', "%{$search}%")
                  ->orWhereHas('user', function($qu) use ($search) {
                      $qu->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by Event
        if ($request->has('event') && $request->event != '') {
            $query->where('event', $request->event);
        }

        $logs = $query->paginate(30);
        
        $events = AuditLog::select('event')->distinct()->pluck('event');

        return view('audit_logs.index', compact('logs', 'events'));
    }

    public function show(AuditLog $auditLog): View
    {
        if (!auth()->user()->isOwner() && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access to Audit Logs.');
        }

        return view('audit_logs.show', compact('auditLog'));
    }
}
