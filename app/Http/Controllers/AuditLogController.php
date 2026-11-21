<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $actionFilter = $request->query('action');

        $logs = AuditLog::with('user')
            ->when($actionFilter, fn ($q) => $q->where('action', $actionFilter))
            ->latest('logged_at')
            ->paginate(30)
            ->withQueryString();

        $actions = AuditLog::select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('owner.audit-logs', compact('logs', 'actions', 'actionFilter'));
    }
}