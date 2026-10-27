<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SystemEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
    /**
     * Paginated activity feed combining AuditLog (user actions) and
     * SystemEvent (system-generated events) for the authenticated org.
     *
     * Supports filters: type (audit|system|all), search, date range.
     */
    public function index(Request $request)
    {
        $orgId  = (int) Auth::user()->organisation_id;
        $type   = $request->input('type', 'all');   // all | audit | system
        $search = trim((string) $request->input('search', ''));
        $from   = $request->input('from');
        $to     = $request->input('to');

        // ── Audit log entries ─────────────────────────────────────────────
        $auditQuery = AuditLog::with('user')
            ->where('organisation_id', $orgId)
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            }))
            ->when($from, fn ($q) => $q->where('occurred_at', '>=', $from))
            ->when($to,   fn ($q) => $q->where('occurred_at', '<=', $to . ' 23:59:59'));

        // ── System event entries ──────────────────────────────────────────
        $systemQuery = SystemEvent::where('organisation_id', $orgId)
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('event_type', 'like', "%{$search}%")
                  ->orWhere('aggregate_type', 'like', "%{$search}%");
            }))
            ->when($from, fn ($q) => $q->where('occurred_at', '>=', $from))
            ->when($to,   fn ($q) => $q->where('occurred_at', '<=', $to . ' 23:59:59'));

        // ── Counts for filter tabs ────────────────────────────────────────
        $auditCount  = $auditQuery->count();
        $systemCount = $systemQuery->count();
        $totalCount  = $auditCount + $systemCount;

        // ── Paginate the selected source ──────────────────────────────────
        if ($type === 'audit') {
            $entries = $auditQuery->orderByDesc('occurred_at')->paginate(30)->withQueryString();
            $entryType = 'audit';
        } elseif ($type === 'system') {
            $entries = $systemQuery->orderByDesc('occurred_at')->paginate(30)->withQueryString();
            $entryType = 'system';
        } else {
            // "all" — show audit logs (primary user-facing source) paginated
            // System events appear in a separate tab via ?type=system
            $entries = $auditQuery->orderByDesc('occurred_at')->paginate(30)->withQueryString();
            $entryType = 'audit';
        }

        return view('admin.activities.index', compact(
            'entries', 'entryType', 'type',
            'search', 'from', 'to',
            'auditCount', 'systemCount', 'totalCount'
        ));
    }
}
