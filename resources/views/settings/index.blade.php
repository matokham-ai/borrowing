@extends('layouts.app')
@section('title', 'Settings')

@section('content')

{{-- ══════════════════════════════════════
     PAGE HEADER
══════════════════════════════════════ --}}
<div class="page-header">
    <div>
        <div class="eyebrow">ADMINISTRATION</div>
        <h1>Settings</h1>
        <p>Manage your organisation, access controls and system configuration.</p>
    </div>
</div>

{{-- ══════════════════════════════════════
     USERS & ACCESS — featured card
══════════════════════════════════════ --}}
<div class="settings-section" style="padding: 0 var(--pg); margin-bottom: 14px;">
    <div class="settings-card settings-card--featured">

        {{-- Header --}}
        <div class="sc-head">
            <div class="sc-icon-wrap sc-icon-wrap--indigo">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="sc-head-copy">
                <h2 class="sc-title">Users &amp; Access</h2>
                <p class="sc-desc">Manage who can access LendFlow and define what each person can do.</p>
            </div>
        </div>

        {{-- Live stat tiles --}}
        <div class="sc-stats">
            <a href="{{ route('users.index') }}" class="sc-stat-tile">
                <span class="sc-stat-value">{{ $stats['users'] }}</span>
                <span class="sc-stat-label">Users</span>
            </a>
            <a href="{{ route('roles.index') }}" class="sc-stat-tile">
                <span class="sc-stat-value">{{ $stats['roles'] }}</span>
                <span class="sc-stat-label">Roles</span>
            </a>
            <a href="{{ route('permissions.index') }}" class="sc-stat-tile">
                <span class="sc-stat-value">{{ $stats['permissions'] }}</span>
                <span class="sc-stat-label">Permissions</span>
            </a>
            <a href="{{ route('activities.index') }}" class="sc-stat-tile">
                <span class="sc-stat-value">{{ number_format($stats['auditLogs']) }}</span>
                <span class="sc-stat-label">Audit Logs</span>
            </a>
        </div>

        {{-- Sub-links --}}
        <div class="sc-links">
            <a href="{{ route('users.index') }}" class="sc-link">
                <span class="sc-link-icon sc-link-icon--indigo"><i class="bi bi-person-gear"></i></span>
                <span class="sc-link-body">
                    <span class="sc-link-title">Users</span>
                    <span class="sc-link-hint">Staff accounts, status and assigned roles</span>
                </span>
                <i class="bi bi-chevron-right sc-link-arrow"></i>
            </a>
            <a href="{{ route('roles.index') }}" class="sc-link">
                <span class="sc-link-icon sc-link-icon--violet"><i class="bi bi-shield-lock-fill"></i></span>
                <span class="sc-link-body">
                    <span class="sc-link-title">Roles</span>
                    <span class="sc-link-hint">Define what each role can access across the system</span>
                </span>
                <i class="bi bi-chevron-right sc-link-arrow"></i>
            </a>
            <a href="{{ route('permissions.index') }}" class="sc-link">
                <span class="sc-link-icon sc-link-icon--blue"><i class="bi bi-key-fill"></i></span>
                <span class="sc-link-body">
                    <span class="sc-link-title">Permissions</span>
                    <span class="sc-link-hint">Granular system permissions and their role assignments</span>
                </span>
                <i class="bi bi-chevron-right sc-link-arrow"></i>
            </a>
            <a href="{{ route('activities.index') }}" class="sc-link">
                <span class="sc-link-icon sc-link-icon--slate"><i class="bi bi-clock-history"></i></span>
                <span class="sc-link-body">
                    <span class="sc-link-title">Audit Logs</span>
                    <span class="sc-link-hint">Full trail of who did what, when, and from where</span>
                </span>
                <i class="bi bi-chevron-right sc-link-arrow"></i>
            </a>
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════
     LOWER ROW — two-column grid
══════════════════════════════════════ --}}
<div class="settings-grid" style="padding: 0 var(--pg); margin-bottom: 14px;">

    {{-- Organisation --}}
    <div class="settings-card">
        <div class="sc-head sc-head--compact">
            <div class="sc-icon-wrap sc-icon-wrap--teal">
                <i class="bi bi-building-fill"></i>
            </div>
            <div class="sc-head-copy">
                <h2 class="sc-title">Organisation</h2>
                <p class="sc-desc">Organisation profile, branches and business setup.</p>
            </div>
        </div>
        <div class="sc-links sc-links--compact">
            <a href="{{ route('organisation.index') }}" class="sc-link sc-link--sm">
                <span class="sc-link-icon sc-link-icon--teal sc-link-icon--sm"><i class="bi bi-building-fill"></i></span>
                <span class="sc-link-body">
                    <span class="sc-link-title">Organisation Overview</span>
                    <span class="sc-link-hint">View organisation profile and performance metrics</span>
                </span>
                <i class="bi bi-chevron-right sc-link-arrow"></i>
            </a>
            <a href="{{ route('organisation.edit') }}" class="sc-link sc-link--sm">
                <span class="sc-link-icon sc-link-icon--teal sc-link-icon--sm"><i class="bi bi-pencil-square"></i></span>
                <span class="sc-link-body">
                    <span class="sc-link-title">Edit Organisation</span>
                    <span class="sc-link-hint">Name, logo, contact details</span>
                </span>
                <i class="bi bi-chevron-right sc-link-arrow"></i>
            </a>
            <a href="{{ route('organisation.general') }}" class="sc-link sc-link--sm">
                <span class="sc-link-icon sc-link-icon--teal sc-link-icon--sm"><i class="bi bi-sliders2-vertical"></i></span>
                <span class="sc-link-body">
                    <span class="sc-link-title">General Configuration</span>
                    <span class="sc-link-hint">Currency, timezone, financial year</span>
                </span>
                <i class="bi bi-chevron-right sc-link-arrow"></i>
            </a>
            <a href="{{ route('organisation.lending') }}" class="sc-link sc-link--sm">
                <span class="sc-link-icon sc-link-icon--teal sc-link-icon--sm"><i class="bi bi-bank"></i></span>
                <span class="sc-link-body">
                    <span class="sc-link-title">Lending Configuration</span>
                    <span class="sc-link-hint">Loan terms, interest calculation, policies</span>
                </span>
                <i class="bi bi-chevron-right sc-link-arrow"></i>
            </a>
            <a href="{{ route('organisation.branding') }}" class="sc-link sc-link--sm">
                <span class="sc-link-icon sc-link-icon--teal sc-link-icon--sm"><i class="bi bi-palette"></i></span>
                <span class="sc-link-body">
                    <span class="sc-link-title">Branding</span>
                    <span class="sc-link-hint">Logo, colors, visual identity</span>
                </span>
                <i class="bi bi-chevron-right sc-link-arrow"></i>
            </a>
        </div>
        <a href="{{ route('organisation.index') }}" class="sc-cta-link">Configure organisation <i class="bi bi-arrow-right"></i></a>
    </div>

    {{-- System Settings --}}
    <div class="settings-card">
        <div class="sc-head sc-head--compact">
            <div class="sc-icon-wrap sc-icon-wrap--orange">
                <i class="bi bi-gear-wide-connected"></i>
            </div>
            <div class="sc-head-copy">
                <h2 class="sc-title">System</h2>
                <p class="sc-desc">Application-wide configuration and defaults.</p>
            </div>
        </div>
        <div class="sc-links sc-links--compact">
            <a href="{{ route('system.index') }}" class="sc-link sc-link--sm">
                <span class="sc-link-icon sc-link-icon--orange sc-link-icon--sm"><i class="bi bi-toggles"></i></span>
                <span class="sc-link-body">
                    <span class="sc-link-title">System Settings</span>
                    <span class="sc-link-hint">Application-wide feature flags and defaults</span>
                </span>
                <i class="bi bi-chevron-right sc-link-arrow"></i>
            </a>
            <a href="{{ route('notifications.index') }}" class="sc-link sc-link--sm">
                <span class="sc-link-icon sc-link-icon--orange sc-link-icon--sm"><i class="bi bi-bell-fill"></i></span>
                <span class="sc-link-body">
                    <span class="sc-link-title">Notifications</span>
                    <span class="sc-link-hint">SMS, email and in-app notification templates</span>
                </span>
                <i class="bi bi-chevron-right sc-link-arrow"></i>
            </a>
            <a href="{{ route('notifications.index', ['channel' => 'email']) }}" class="sc-link sc-link--sm">
                <span class="sc-link-icon sc-link-icon--orange sc-link-icon--sm"><i class="bi bi-envelope-fill"></i></span>
                <span class="sc-link-body">
                    <span class="sc-link-title">Email Templates</span>
                    <span class="sc-link-hint">Transactional and notification email templates</span>
                </span>
                <i class="bi bi-chevron-right sc-link-arrow"></i>
            </a>
        </div>
        <a href="{{ route('system.index') }}" class="sc-cta-link">Configure system <i class="bi bi-arrow-right"></i></a>
    </div>

</div>

{{-- ══════════════════════════════════════
     BOTTOM ROW — Security + Integrations
══════════════════════════════════════ --}}
<div class="settings-grid" style="padding: 0 var(--pg); margin-bottom: 40px;">

    {{-- Security --}}
    <div class="settings-card settings-card--security">
        <div class="sc-head sc-head--compact">
            <div class="sc-icon-wrap sc-icon-wrap--red">
                <i class="bi bi-shield-shaded"></i>
            </div>
            <div class="sc-head-copy">
                <h2 class="sc-title">Security</h2>
                <p class="sc-desc">Authentication, sessions, passwords and login controls.</p>
            </div>
        </div>
        <div class="sc-links sc-links--compact">
            <a href="{{ route('security.index') }}" class="sc-link sc-link--sm">
                <span class="sc-link-icon sc-link-icon--red sc-link-icon--sm"><i class="bi bi-lock-fill"></i></span>
                <span class="sc-link-body">
                    <span class="sc-link-title">Password Policies</span>
                    <span class="sc-link-hint">Complexity rules and expiry settings</span>
                </span>
                <i class="bi bi-chevron-right sc-link-arrow"></i>
            </a>
            <a href="{{ route('security.index') }}#two-factor" class="sc-link sc-link--sm">
                <span class="sc-link-icon sc-link-icon--red sc-link-icon--sm"><i class="bi bi-phone-fill"></i></span>
                <span class="sc-link-body">
                    <span class="sc-link-title">Two-Factor Authentication</span>
                    <span class="sc-link-hint">Enforce 2FA for staff accounts</span>
                </span>
                <i class="bi bi-chevron-right sc-link-arrow"></i>
            </a>
            <a href="{{ route('security.index') }}#session" class="sc-link sc-link--sm">
                <span class="sc-link-icon sc-link-icon--red sc-link-icon--sm"><i class="bi bi-clock-fill"></i></span>
                <span class="sc-link-body">
                    <span class="sc-link-title">Session Management</span>
                    <span class="sc-link-hint">Idle timeouts and active session controls</span>
                </span>
                <i class="bi bi-chevron-right sc-link-arrow"></i>
            </a>
        </div>
        <a href="{{ route('security.index') }}" class="sc-cta-link sc-cta-link--red">Manage security <i class="bi bi-arrow-right"></i></a>
    </div>

    {{-- Integrations --}}
    <div class="settings-card settings-card--integrations">
        <div class="sc-head sc-head--compact">
            <div class="sc-icon-wrap sc-icon-wrap--green">
                <i class="bi bi-plug-fill"></i>
            </div>
            <div class="sc-head-copy">
                <h2 class="sc-title">Integrations</h2>
                <p class="sc-desc">M-Pesa, SMS providers, email and external services.</p>
            </div>
        </div>
        <div class="sc-links sc-links--compact">
            <a href="{{ route('mpesa.stk.index') }}" class="sc-link sc-link--sm">
                <span class="sc-link-icon sc-link-icon--green sc-link-icon--sm"><i class="bi bi-phone-vibrate-fill"></i></span>
                <span class="sc-link-body">
                    <span class="sc-link-title">M-Pesa</span>
                    <span class="sc-link-hint">STK push, B2C and webhook configuration</span>
                </span>
                <span class="sc-int-badge {{ $stats['mpesaActive'] ? 'sc-int-badge--active' : 'sc-int-badge--inactive' }}">
                    {{ $stats['mpesaActive'] ? 'Active' : 'Not configured' }}
                </span>
                <i class="bi bi-chevron-right sc-link-arrow"></i>
            </a>
            <a href="{{ route('integrations.index', ['type' => 'sms']) }}" class="sc-link sc-link--sm">
                <span class="sc-link-icon sc-link-icon--green sc-link-icon--sm"><i class="bi bi-chat-dots-fill"></i></span>
                <span class="sc-link-body">
                    <span class="sc-link-title">SMS Gateway</span>
                    <span class="sc-link-hint">Provider, sender ID and message templates</span>
                </span>
                <span class="sc-int-badge {{ $stats['smsActive'] ? 'sc-int-badge--active' : 'sc-int-badge--inactive' }}">
                    {{ $stats['smsActive'] ? 'Active' : 'Not configured' }}
                </span>
                <i class="bi bi-chevron-right sc-link-arrow"></i>
            </a>
            <a href="{{ route('integrations.index', ['type' => 'email']) }}" class="sc-link sc-link--sm">
                <span class="sc-link-icon sc-link-icon--green sc-link-icon--sm"><i class="bi bi-envelope-at-fill"></i></span>
                <span class="sc-link-body">
                    <span class="sc-link-title">Email Service</span>
                    <span class="sc-link-hint">SMTP or API-based transactional email</span>
                </span>
                <span class="sc-int-badge {{ $stats['emailActive'] ? 'sc-int-badge--active' : 'sc-int-badge--inactive' }}">
                    {{ $stats['emailActive'] ? 'Active' : 'Not configured' }}
                </span>
                <i class="bi bi-chevron-right sc-link-arrow"></i>
            </a>
        </div>
        <a href="{{ route('integrations.index') }}" class="sc-cta-link sc-cta-link--green">Manage integrations <i class="bi bi-arrow-right"></i></a>
    </div>

</div>

@endsection
