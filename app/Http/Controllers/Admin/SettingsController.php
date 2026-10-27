<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SystemEvent;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    /**
     * Settings landing page — card-based administration & governance center.
     *
     * Provides live counts for each section so the overview cards stay
     * informative without navigating away.
     */
    public function index()
    {
        $orgId = (int) Auth::user()->organisation_id;

        $stats = [
            // Users & Access
            'users'       => User::where('organisation_id', $orgId)
                                 ->where('id', '!=', Auth::id())
                                 ->count(),
            'roles'       => Role::where('organisation_id', $orgId)->count(),
            'permissions' => Permission::where('is_active', true)->count(),
            'auditLogs'   => AuditLog::where('organisation_id', $orgId)->count()
                           + SystemEvent::where('organisation_id', $orgId)->count(),

            // Integration status hints (simple boolean flags for the badges)
            'mpesaActive' => true,   // M-Pesa STK routes exist and are in use
            'smsActive'   => false,
            'emailActive' => false,
        ];

        return view('settings.index', compact('stats'));
    }
}
