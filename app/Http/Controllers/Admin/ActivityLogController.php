<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivityLog;

class ActivityLogController extends Controller
{
    /**
     * Display activity logs
     */
    public function index(Request $request)
    {
        $query = ActivityLog::with('user');

        // Filter by system source
        if ($request->has('system_source') && $request->system_source) {
            $query->where('system_source', $request->system_source);
        }

        // Filter by action
        if ($request->has('action') && $request->action) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(50);

        $stats = [
            'total' => ActivityLog::count(),
            'sip' => ActivityLog::where('system_source', 'SIP')->count(),
            'erp' => ActivityLog::where('system_source', 'ERP')->count(),
            'api' => ActivityLog::where('system_source', 'API')->count(),
        ];

        return view('admin.erp.activity-logs', compact('logs', 'stats'));
    }

    /**
     * View single log entry
     */
    public function show(ActivityLog $activityLog)
    {
        $activityLog->load('user');
        return view('admin.erp.activity-log-show', compact('activityLog'));
    }
}

