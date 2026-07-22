<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin Audit Log Controller
 * Displays a paginated, filterable log of all system activities.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::with('user')->latest();

        // Filter by area
        if ($area = $request->get('area')) {
            $query->byArea($area);
        }

        // Filter by date range
        if ($from = $request->get('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        // Search description / event
        if ($search = $request->get('search')) {
            $query->search($search);
        }

        $logs = $query->paginate(30)->withQueryString();

        // Stats for the sidebar summary
        $stats = [
            'total'     => AuditLog::count(),
            'today'     => AuditLog::whereDate('created_at', today())->count(),
            'this_week' => AuditLog::where('created_at', '>=', now()->startOfWeek())->count(),
        ];

        $areas = ['auth', 'users', 'approvals', 'bookings', 'gigs', 'lms', 'system'];

        return view('admin.audit-log', compact('logs', 'stats', 'areas'));
    }
}
