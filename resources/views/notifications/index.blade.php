@extends('layouts.app')
@section('title', 'Notification Templates')

@push('styles')
<style>
/* ══ hero banner ══════════════════════════════════════════════ */
.nt-banner{
    margin:0 var(--pg) 28px;
    border-radius:20px;
    background:linear-gradient(118deg,#0f1f3a 0%,#162f5e 52%,#1a3a72 100%);
    border:1px solid rgba(255,255,255,.07);
    box-shadow:0 12px 40px rgba(10,18,50,.2);
    padding:30px 36px;
    display:flex;align-items:center;gap:20px;
    position:relative;overflow:hidden;
}
.nt-banner::before{
    content:'';position:absolute;right:-60px;top:-60px;
    width:260px;height:260px;border-radius:50%;
    border:1px solid rgba(255,255,255,.05);
    box-shadow:0 0 0 36px rgba(255,255,255,.022),0 0 0 72px rgba(255,255,255,.01);
    pointer-events:none;
}
.nt-banner-icon{
    width:52px;height:52px;border-radius:15px;flex-shrink:0;
    background:rgba(59,130,246,.25);border:1px solid rgba(59,130,246,.4);
    display:grid;place-items:center;font-size:22px;color:#93c5fd;
    position:relative;z-index:1;
}
.nt-banner-copy{flex:1;position:relative;z-index:1}
.nt-banner-eyebrow{font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#60a5fa;margin-bottom:6px}
.nt-banner-title{font-size:21px;font-weight:750;color:#fff;letter-spacing:-.025em;margin:0 0 5px;line-height:1.2}
.nt-banner-sub{font-size:12px;color:#93a8c4;margin:0}
.nt-banner-actions{display:flex;gap:8px;align-items:center;position:relative;z-index:1;flex-shrink:0}
.nt-btn{
    height:36px;padding:0 18px;border-radius:10px;
    font-size:11.5px;font-weight:600;border:none;cursor:pointer;
    display:inline-flex;align-items:center;gap:7px;text-decoration:none;
    transition:all .15s;
}
.nt-btn--primary{background:#3b82f6;color:#fff;box-shadow:0 4px 14px rgba(59,130,246,.4)}
.nt-btn--primary:hover{background:#2563eb;box-shadow:0 6px 18px rgba(59,130,246,.5);transform:translateY(-1px);color:#fff}
.nt-btn--ghost{background:rgba(255,255,255,.1);color:#c7d4ed;border:1px solid rgba(255,255,255,.14)}
.nt-btn--ghost:hover{background:rgba(255,255,255,.16);color:#fff}

/* ══ kpi row ══════════════════════════════════════════════════ */
.nt-kpis{
    display:grid;grid-template-columns:repeat(5,1fr);
    gap:14px;padding:0 var(--pg);margin-bottom:26px;
}
.nt-kpi{
    background:#fff;border:1px solid var(--line);border-radius:14px;
    padding:16px 18px;
    box-shadow:0 1px 3px rgba(16,24,40,.04);
    position:relative;overflow:hidden;
    transition:box-shadow .18s,transform .18s;
}
.nt-kpi:hover{box-shadow:0 6px 20px rgba(16,24,40,.09);transform:translateY(-1px)}
.nt-kpi-val{font-size:24px;font-weight:750;color:#101828;letter-spacing:-.03em;line-height:1;display:block}
.nt-kpi-lbl{font-size:10.5px;color:#667085;margin-top:4px;display:block;font-weight:500;text-transform:uppercase;letter-spacing:.05em}
.nt-kpi-icon{
    position:absolute;right:14px;top:50%;transform:translateY(-50%);
    font-size:22px;opacity:.12;
}
.nt-kpi-bar{position:absolute;bottom:0;left:0;right:0;height:3px;border-radius:0 0 14px 14px}
.nt-kpi-bar--blue  {background:linear-gradient(90deg,#3b82f6,#60a5fa)}
.nt-kpi-bar--green {background:linear-gradient(90deg,#10b981,#34d399)}
.nt-kpi-bar--amber {background:linear-gradient(90deg,#f59e0b,#fbbf24)}
.nt-kpi-bar--indigo{background:linear-gradient(90deg,#6366f1,#8b5cf6)}
.nt-kpi-bar--violet{background:linear-gradient(90deg,#8b5cf6,#a78bfa)}

/* ══ filter bar ═══════════════════════════════════════════════ */
.nt-filterbar{
    display:flex;align-items:center;gap:10px;flex-wrap:wrap;
    padding:0 var(--pg);margin-bottom:20px;
}
/* channel pills */
.ch-pill-tab{
    height:32px;padding:0 14px;border-radius:20px;
    font-size:11px;font-weight:600;
    border:1.5px solid var(--line);background:#fff;color:#667085;
    text-decoration:none;display:inline-flex;align-items:center;gap:6px;
    transition:all .14s;
}
.ch-pill-tab:hover{border-color:#5b5ce2;color:#5b5ce2;background:#f5f5ff}
.ch-pill-tab.active{background:#5b5ce2;border-color:#5b5ce2;color:#fff;box-shadow:0 2px 8px rgba(91,92,226,.3)}
.ch-pill-tab .ch-dot{width:7px;height:7px;border-radius:50%;background:currentColor;opacity:.7}
/* search */
.nt-search{position:relative}
.nt-search input{
    height:34px;padding:0 12px 0 34px;
    border:1px solid var(--line);border-radius:9px;
    font-size:11.5px;color:#344054;background:#fff;
    width:210px;outline:none;transition:border-color .14s,box-shadow .14s;
}
.nt-search input:focus{border-color:#5b5ce2;box-shadow:0 0 0 3px rgba(91,92,226,.12)}
.nt-search .bi{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#98a2b3;font-size:12px;pointer-events:none}
/* status select */
.nt-select{
    height:34px;padding:0 10px;border-radius:9px;
    border:1px solid var(--line);background:#fff;
    font-size:11.5px;color:#344054;outline:none;cursor:pointer;
    transition:border-color .14s;
}
.nt-select:focus{border-color:#5b5ce2;box-shadow:0 0 0 3px rgba(91,92,226,.12)}
.nt-filter-btn{
    height:34px;padding:0 14px;border-radius:9px;
    font-size:11px;font-weight:600;
    border:1px solid var(--line);background:#fff;color:#344054;
    cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:all .14s;
}
.nt-filter-btn:hover{background:#f9fafb;border-color:#d0d5dd}
.nt-clear{font-size:11px;color:#667085;font-weight:500;text-decoration:none;padding:0 6px;transition:color .13s}
.nt-clear:hover{color:#5b5ce2}

/* ══ table card ═══════════════════════════════════════════════ */
.nt-card{
    background:#fff;border:1px solid var(--line);border-radius:16px;
    overflow:hidden;margin:0 var(--pg);
    box-shadow:0 1px 4px rgba(16,24,40,.05);
}
.nt-card-head{
    display:flex;align-items:center;justify-content:space-between;
    padding:14px 20px;border-bottom:1px solid var(--line);
    background:#fafafa;
}
.nt-card-head-title{font-size:12.5px;font-weight:700;color:#101828}
.nt-card-head-count{
    font-size:11px;color:#5b5ce2;font-weight:600;
    background:#eeefff;border:1px solid #d4d5fb;
    border-radius:20px;padding:2px 10px;
}

/* table */
.nt-tbl{width:100%;border-collapse:collapse}
.nt-tbl thead th{
    padding:10px 18px;
    font-size:10.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;
    color:#98a2b3;background:#fafafa;
    border-bottom:1px solid var(--line);white-space:nowrap;
}
.nt-tbl tbody tr{border-bottom:1px solid var(--line);transition:background .12s}
.nt-tbl tbody tr:last-child{border-bottom:none}
.nt-tbl tbody tr:hover{background:#fafbff}
.nt-tbl td{padding:13px 18px;vertical-align:middle}

/* template name cell */
.tpl-name{font-size:12.5px;font-weight:600;color:#101828}
.tpl-subject{font-size:11px;color:#667085;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:240px}
.tpl-body-preview{font-size:11px;color:#98a2b3;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:240px}

/* event badge */
.ev-badge{
    display:inline-flex;align-items:center;
    font-size:10.5px;font-family:'SF Mono','Fira Code',monospace;
    background:#f5f5f5;border:1px solid #e5e7eb;
    border-radius:6px;padding:2px 8px;color:#555;
    white-space:nowrap;
}

/* channel pill */
.ch-badge{
    display:inline-flex;align-items:center;gap:5px;
    font-size:10.5px;font-weight:700;padding:3px 10px;border-radius:20px;
    white-space:nowrap;
}
.ch-badge--sms   {background:#fffbeb;color:#92400e;border:1px solid #fde68a}
.ch-badge--email {background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe}
.ch-badge--in_app{background:#f5f3ff;color:#5b21b6;border:1px solid #ddd6fe}

/* status pill */
.st-pill{display:inline-flex;align-items:center;gap:5px;font-size:10.5px;font-weight:600;padding:3px 9px;border-radius:20px;white-space:nowrap}
.st-pill--on {background:#ecfdf5;color:#027a48;border:1px solid #a7f3d0}
.st-pill--off{background:#f9fafb;color:#667085;border:1px solid var(--line)}
.st-pill .dot{width:5px;height:5px;border-radius:50%;background:currentColor}

/* last tested */
.tested-ts{font-size:11px;color:#667085}
.tested-never{font-size:11px;color:#d1d5db;font-style:italic}

/* action buttons */
.tbl-actions{display:flex;gap:5px;justify-content:flex-end;align-items:center}
.tbl-btn{
    height:30px;width:30px;border-radius:8px;
    border:1px solid var(--line);background:#fff;
    display:grid;place-items:center;font-size:12px;
    cursor:pointer;color:#667085;text-decoration:none;
    transition:all .13s;
}
.tbl-btn:hover            {background:#f9fafb;border-color:#d0d5dd;color:#344054}
.tbl-btn--send:hover      {background:#eff6ff;border-color:#bfdbfe;color:#2563eb}
.tbl-btn--toggle-on:hover {background:#ecfdf5;border-color:#a7f3d0;color:#059669}
.tbl-btn--toggle-off:hover{background:#fefce8;border-color:#fde68a;color:#d97706}
.tbl-btn--danger:hover    {background:#fef2f2;border-color:#fecaca;color:#dc2626}

/* ══ empty state ══════════════════════════════════════════════ */
.nt-empty{
    text-align:center;padding:64px 20px;
    background:#fff;border:1px solid var(--line);border-radius:16px;
    margin:0 var(--pg);box-shadow:0 1px 4px rgba(16,24,40,.04);
}
.nt-empty-icon{
    width:64px;height:64px;border-radius:18px;
    background:#eff6ff;display:grid;place-items:center;
    font-size:26px;color:#3b82f6;margin:0 auto 16px;
}
.nt-empty h3{font-size:15px;font-weight:700;color:#101828;margin:0 0 6px}
.nt-empty p {font-size:12px;color:#667085;margin:0 0 20px}

/* ══ flash ════════════════════════════════════════════════════ */
.nt-alert{
    display:flex;align-items:center;gap:10px;
    background:#ecfdf5;border:1px solid #a7f3d0;border-radius:10px;
    padding:10px 16px;margin:0 var(--pg) 20px;
    font-size:12px;font-weight:500;color:#065f46;
}
</style>
@endpush

@section('content')

{{-- ══ BANNER ══ --}}
<div class="nt-banner">
    <div class="nt-banner-icon"><i class="bi bi-bell-fill"></i></div>
    <div class="nt-banner-copy">
        <div class="nt-banner-eyebrow">System · Notifications</div>
        <h1 class="nt-banner-title">Notification Templates</h1>
        <p class="nt-banner-sub">Design and manage SMS, email and in-app messages that fire automatically on system events.</p>
    </div>
    <div class="nt-banner-actions">
        <a href="{{ route('settings.index') }}" class="nt-btn nt-btn--ghost">
            <i class="bi bi-arrow-left"></i> Settings
        </a>
        <a href="{{ route('notifications.create') }}" class="nt-btn nt-btn--primary">
            <i class="bi bi-plus-lg"></i> New Template
        </a>
    </div>
</div>

{{-- ══ FLASH ══ --}}
@if(session('success'))
<div class="nt-alert"><i class="bi bi-check-circle-fill" style="font-size:15px;color:#059669"></i> {{ session('success') }}</div>
@endif

{{-- ══ KPIs ══ --}}
<div class="nt-kpis">
    <div class="nt-kpi">
        <span class="nt-kpi-val">{{ $stats['total'] }}</span>
        <span class="nt-kpi-lbl">Total</span>
        <i class="bi bi-collection nt-kpi-icon"></i>
        <div class="nt-kpi-bar nt-kpi-bar--blue"></div>
    </div>
    <div class="nt-kpi">
        <span class="nt-kpi-val" style="color:#059669">{{ $stats['active'] }}</span>
        <span class="nt-kpi-lbl">Active</span>
        <i class="bi bi-check-circle nt-kpi-icon"></i>
        <div class="nt-kpi-bar nt-kpi-bar--green"></div>
    </div>
    <div class="nt-kpi">
        <span class="nt-kpi-val" style="color:#d97706">{{ $stats['sms'] }}</span>
        <span class="nt-kpi-lbl">SMS</span>
        <i class="bi bi-chat-dots nt-kpi-icon"></i>
        <div class="nt-kpi-bar nt-kpi-bar--amber"></div>
    </div>
    <div class="nt-kpi">
        <span class="nt-kpi-val" style="color:#2563eb">{{ $stats['email'] }}</span>
        <span class="nt-kpi-lbl">Email</span>
        <i class="bi bi-envelope nt-kpi-icon"></i>
        <div class="nt-kpi-bar nt-kpi-bar--indigo"></div>
    </div>
    <div class="nt-kpi">
        <span class="nt-kpi-val" style="color:#7c3aed">{{ $stats['in_app'] }}</span>
        <span class="nt-kpi-lbl">In-App</span>
        <i class="bi bi-bell nt-kpi-icon"></i>
        <div class="nt-kpi-bar nt-kpi-bar--violet"></div>
    </div>
</div>

{{-- ══ FILTER BAR ══ --}}
<div class="nt-filterbar">

    {{-- channel tabs --}}
    <a href="{{ route('notifications.index', request()->except('channel','page')) }}"
       class="ch-pill-tab {{ !request('channel') ? 'active' : '' }}">
        <i class="bi bi-grid-2x2-fill" style="font-size:9px"></i> All
    </a>
    <a href="{{ route('notifications.index', array_merge(request()->except('channel','page'), ['channel'=>'sms'])) }}"
       class="ch-pill-tab {{ request('channel')==='sms' ? 'active' : '' }}">
        <i class="bi bi-chat-dots-fill" style="font-size:9px"></i> SMS
    </a>
    <a href="{{ route('notifications.index', array_merge(request()->except('channel','page'), ['channel'=>'email'])) }}"
       class="ch-pill-tab {{ request('channel')==='email' ? 'active' : '' }}">
        <i class="bi bi-envelope-fill" style="font-size:9px"></i> Email
    </a>
    <a href="{{ route('notifications.index', array_merge(request()->except('channel','page'), ['channel'=>'in_app'])) }}"
       class="ch-pill-tab {{ request('channel')==='in_app' ? 'active' : '' }}">
        <i class="bi bi-bell-fill" style="font-size:9px"></i> In-App
    </a>

    <div style="width:1px;height:22px;background:var(--line);margin:0 4px"></div>

    {{-- search + status --}}
    <form method="GET" class="d-flex align-items-center gap-2">
        @if(request('channel'))<input type="hidden" name="channel" value="{{ request('channel') }}">@endif
        <div class="nt-search">
            <i class="bi bi-search"></i>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search name or event…" autocomplete="off">
        </div>
        <select name="status" class="nt-select">
            <option value="">Any status</option>
            <option value="1" {{ request('status')==='1' ? 'selected':'' }}>Active</option>
            <option value="0" {{ request('status')==='0' ? 'selected':'' }}>Inactive</option>
        </select>
        <button type="submit" class="nt-filter-btn">
            <i class="bi bi-funnel"></i> Filter
        </button>
    </form>

    @if(request()->hasAny(['search','status']))
    <a href="{{ route('notifications.index', request()->only('channel')) }}" class="nt-clear">
        <i class="bi bi-x-circle me-1"></i>Clear
    </a>
    @endif

</div>

{{-- ══ TABLE ══ --}}
@if($templates->count())
<div class="nt-card">
    <div class="nt-card-head">
        <span class="nt-card-head-title">
            @if(request('channel'))
                {{ $channels[request('channel')] ?? request('channel') }} Templates
            @else
                All Templates
            @endif
        </span>
        <span class="nt-card-head-count">{{ $templates->total() }} template{{ $templates->total() !== 1 ? 's' : '' }}</span>
    </div>

    <table class="nt-tbl">
        <thead>
            <tr>
                <th>Template</th>
                <th>Event</th>
                <th>Channel</th>
                <th>Last Tested</th>
                <th>Status</th>
                <th style="text-align:right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($templates as $tpl)
            <tr>
                {{-- name --}}
                <td>
                    <div class="tpl-name">{{ $tpl->name }}</div>
                    @if($tpl->subject)
                        <div class="tpl-subject">{{ Str::limit($tpl->subject, 52) }}</div>
                    @else
                        <div class="tpl-body-preview">{{ Str::limit(strip_tags($tpl->body), 52) }}</div>
                    @endif
                </td>

                {{-- event --}}
                <td><span class="ev-badge">{{ $tpl->event }}</span></td>

                {{-- channel --}}
                <td>
                    <span class="ch-badge ch-badge--{{ $tpl->channel }}">
                        @if($tpl->channel==='sms')<i class="bi bi-chat-dots-fill" style="font-size:10px"></i>
                        @elseif($tpl->channel==='email')<i class="bi bi-envelope-fill" style="font-size:10px"></i>
                        @else<i class="bi bi-bell-fill" style="font-size:10px"></i>@endif
                        {{ $tpl->channelLabel() }}
                    </span>
                </td>

                {{-- last tested --}}
                <td>
                    @if($tpl->last_tested_at)
                        <span class="tested-ts">{{ $tpl->last_tested_at->diffForHumans() }}</span>
                    @else
                        <span class="tested-never">Never</span>
                    @endif
                </td>

                {{-- status --}}
                <td>
                    <span class="st-pill {{ $tpl->is_active ? 'st-pill--on' : 'st-pill--off' }}">
                        <span class="dot"></span>{{ $tpl->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>

                {{-- actions --}}
                <td>
                    <div class="tbl-actions">
                        {{-- send test --}}
                        <form method="POST" action="{{ route('notifications.test', $tpl) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="tbl-btn tbl-btn--send" title="Send test notification">
                                <i class="bi bi-send-fill" style="font-size:11px"></i>
                            </button>
                        </form>
                        {{-- edit --}}
                        <a href="{{ route('notifications.edit', $tpl) }}"
                           class="tbl-btn" title="Edit template">
                            <i class="bi bi-pencil" style="font-size:11px"></i>
                        </a>
                        {{-- toggle --}}
                        <form method="POST" action="{{ route('notifications.toggle-active', $tpl) }}" class="d-inline">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    class="tbl-btn tbl-btn--toggle-{{ $tpl->is_active ? 'on' : 'off' }}"
                                    title="{{ $tpl->is_active ? 'Disable' : 'Enable' }}">
                                <i class="bi bi-toggle-{{ $tpl->is_active ? 'on' : 'off' }}"
                                   style="font-size:14px;color:{{ $tpl->is_active ? '#059669' : '#d1d5db' }}"></i>
                            </button>
                        </form>
                        {{-- delete --}}
                        <form method="POST" action="{{ route('notifications.destroy', $tpl) }}" class="d-inline"
                              onsubmit="return confirm('Delete template «{{ addslashes($tpl->name) }}»?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="tbl-btn tbl-btn--danger" title="Delete">
                                <i class="bi bi-trash3" style="font-size:11px"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if($templates->hasPages())
<div style="padding:16px var(--pg) 0">{{ $templates->withQueryString()->links() }}</div>
@endif

@else
<div class="nt-empty">
    <div class="nt-empty-icon"><i class="bi bi-bell-slash"></i></div>
    <h3>No templates found</h3>
    <p>Create your first notification template to start sending automated messages.</p>
    <a href="{{ route('notifications.create') }}" class="nt-btn nt-btn--primary" style="display:inline-flex;margin:0 auto">
        <i class="bi bi-plus-lg"></i> New Template
    </a>
</div>
@endif

@endsection
