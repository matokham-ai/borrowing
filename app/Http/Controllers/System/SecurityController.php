<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\SecurityPolicy;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SecurityController extends Controller
{
    private function orgId(): int
    {
        return (int) Auth::user()->organisation_id;
    }

    // ── Overview / Edit (singleton policy) ───────────────────

    public function index()
    {
        $policy = SecurityPolicy::forOrg($this->orgId());

        $stats = [
            'twoFactor'    => $policy->two_factor_required,
            'idleMinutes'  => $policy->session_idle_minutes,
            'maxAttempts'  => $policy->max_login_attempts,
            'ipRestricted' => !empty($policy->ip_allowlist),
        ];

        // Recent security-related audit events
        $recentEvents = AuditLog::where('organisation_id', $this->orgId())
            ->whereIn('action', ['login', 'login_failed', 'logout', 'password_changed', 'user_locked'])
            ->latest()
            ->limit(10)
            ->get();

        return view('security.index', compact('policy', 'stats', 'recentEvents'));
    }

    // ── Update password policy ────────────────────────────────

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'password_min_length'        => ['required', 'integer', 'min:6', 'max:128'],
            'password_require_uppercase' => ['boolean'],
            'password_require_number'    => ['boolean'],
            'password_require_symbol'    => ['boolean'],
            'password_expiry_days'       => ['required', 'integer', 'min:0', 'max:365'],
            'password_history_count'     => ['required', 'integer', 'min:0', 'max:24'],
        ]);

        $policy = SecurityPolicy::forOrg($this->orgId());
        $policy->update([
            'password_min_length'        => $data['password_min_length'],
            'password_require_uppercase' => $request->boolean('password_require_uppercase'),
            'password_require_number'    => $request->boolean('password_require_number'),
            'password_require_symbol'    => $request->boolean('password_require_symbol'),
            'password_expiry_days'       => $data['password_expiry_days'],
            'password_history_count'     => $data['password_history_count'],
        ]);

        return redirect()->route('security.index')
            ->with('success', 'Password policy updated.');
    }

    // ── Update session policy ─────────────────────────────────

    public function updateSession(Request $request)
    {
        $data = $request->validate([
            'session_idle_minutes'  => ['required', 'integer', 'min:5', 'max:480'],
            'session_max_per_user'  => ['required', 'integer', 'min:0', 'max:10'],
            'session_single_device' => ['boolean'],
        ]);

        $policy = SecurityPolicy::forOrg($this->orgId());
        $policy->update([
            'session_idle_minutes'  => $data['session_idle_minutes'],
            'session_max_per_user'  => $data['session_max_per_user'],
            'session_single_device' => $request->boolean('session_single_device'),
        ]);

        return redirect()->route('security.index')
            ->with('success', 'Session policy updated.');
    }

    // ── Update login / 2FA policy ─────────────────────────────

    public function updateLogin(Request $request)
    {
        $data = $request->validate([
            'max_login_attempts'        => ['required', 'integer', 'min:1', 'max:20'],
            'lockout_duration_minutes'  => ['required', 'integer', 'min:1', 'max:1440'],
            'two_factor_required'       => ['boolean'],
            'two_factor_method'         => ['required', 'in:totp,sms,email'],
        ]);

        $policy = SecurityPolicy::forOrg($this->orgId());
        $policy->update([
            'max_login_attempts'       => $data['max_login_attempts'],
            'lockout_duration_minutes' => $data['lockout_duration_minutes'],
            'two_factor_required'      => $request->boolean('two_factor_required'),
            'two_factor_method'        => $data['two_factor_method'],
        ]);

        return redirect()->route('security.index')
            ->with('success', 'Login security policy updated.');
    }

    // ── Update IP allow-list ──────────────────────────────────

    public function updateIpAllowlist(Request $request)
    {
        $request->validate([
            'ip_allowlist' => ['nullable', 'string'],
        ]);

        $parsed = $request->filled('ip_allowlist')
            ? SecurityPolicy::parseIpAllowlist($request->input('ip_allowlist'))
            : [];

        // Basic CIDR / IP format validation
        foreach ($parsed as $entry) {
            if (! filter_var(explode('/', $entry)[0], FILTER_VALIDATE_IP)) {
                return back()->withInput()
                    ->withErrors(['ip_allowlist' => "\"$entry\" is not a valid IP address or CIDR range."]);
            }
        }

        $policy = SecurityPolicy::forOrg($this->orgId());
        $policy->update(['ip_allowlist' => empty($parsed) ? null : $parsed]);

        return redirect()->route('security.index')
            ->with('success', empty($parsed) ? 'IP restriction removed — all IPs allowed.' : 'IP allow-list updated.');
    }
}
