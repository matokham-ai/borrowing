@extends('layouts.app')
@section('title', 'System Settings')

@push('styles')
<style>
/* ─── hero banner ─────────────────────────────────────── */
.sys-banner{
    margin:0 var(--pg) 28px;
    border-radius:20px;
    background:linear-gradient(118deg,#111936 0%,#1a2756 55%,#273d82 100%);
    border:1px solid rgba(255,255,255,.07);
    box-shadow:0 12px 40px rgba(12,20,60,.18);
    padding:32px 36px;
    display:flex;align-items:center;justify-content:space-between;gap:24px;
    position:relative;overflow:hidden;
}
.sys-banner::before{
    content:'';position:absolute;right:-80px;top:-80px;
    width:300px;height:300px;border-radius:50%;
    border:1px solid rgba(255,255,255,.06);
    box-shadow:0 0 0 40px rgba(255,255,255,.025),0 0 0 80px rgba(255,255,255,.012);
}
.sys-banner::after{
    content:'';position:absolute;left:220px;bottom:-40px;
    width:160px;height:160px;border-radius:50%;
    background:radial-gradient(circle,rgba(99,102,241,.18) 0%,transparent 70%);
}
.sys-banner-icon{
    width:56px;height:56px;border-radius:16px;
    background:rgba(99,102,241,.25);border:1px solid rgba(99,102,241,.35);
    display:grid;place-items:center;font-size:24px;color:#a5b4fc;
    flex-shrink:0;position:relative;z-index:1;
}
.sys-banner-copy{flex:1;position:relative;z-index:1}
.sys-banner-eyebrow{font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#818cf8;margin-bottom:6px}
.sys-banner-title{font-size:22px;font-weight:750;color:#fff;letter-spacing:-.025em;margin:0 0 6px;line-height:1.2}
.sys-banner-sub{font-size:12px;color:#94a3c0;margin:0;line-height:1.5}
.sys-banner-actions{display:flex;gap:10px;align-items:center;position:relative;z-index:1;flex-shrink:0}
.sys-banner-btn{
    height:36px;padding:0 18px;border-radius:10px;
    font-size:11.5px;font-weight:600;
    display:inline-flex;align-items:center;gap:7px;
    cursor:pointer;border:none;text-decoration:none;
    transition:all .15s;
}
.sys-banner-btn--primary{background:#5b5ce2;color:#fff;box-shadow:0 4px 14px rgba(91,92,226,.4)}
.sys-banner-btn--primary:hover{background:#4a4bcf;box-shadow:0 6px 18px rgba(91,92,226,.5);transform:translateY(-1px)}
.sys-banner-btn--ghost{background:rgba(255,255,255,.1);color:#c7d2fe;border:1px solid rgba(255,255,255,.15)}
.sys-banner-btn--ghost:hover{background:rgba(255,255,255,.16)}

/* ─── stat strip ──────────────────────────────────────── */
.sys-kpis{
    display:grid;grid-template-columns:repeat(3,1fr);
    gap:14px;padding:0 var(--pg);margin-bottom:28px;
}
.sys-kpi{
    background:#fff;border:1px solid var(--line);border-radius:14px;
    padding:18px 20px;
    box-shadow:0 1px 3px rgba(16,24,40,.04);
    position:relative;overflow:hidden;
    transition:box-shadow .18s,transform .18s;
    display:flex;align-items:center;gap:16px;
}
.sys-kpi:hover{box-shadow:0 6px 20px rgba(16,24,40,.09);transform:translateY(-1px)}
.sys-kpi-icon{
    width:44px;height:44px;border-radius:12px;
    display:grid;place-items:center;font-size:18px;flex-shrink:0;
}
.sys-kpi-icon--indigo{background:#eeefff;color:#5b5ce2}
.sys-kpi-icon--green {background:#ecfdf5;color:#059669}
.sys-kpi-icon--amber {background:#fffbeb;color:#d97706}
.sys-kpi-val{font-size:26px;font-weight:750;color:#101828;letter-spacing:-.03em;line-height:1;display:block}
.sys-kpi-lbl{font-size:11px;color:#667085;margin-top:3px;display:block;font-weight:500}
.sys-kpi-accent{
    position:absolute;bottom:0;left:0;right:0;height:3px;border-radius:0 0 14px 14px;
}
.sys-kpi-accent--indigo{background:linear-gradient(90deg,#6366f1,#8b5cf6)}
.sys-kpi-accent--green {background:linear-gradient(90deg,#10b981,#34d399)}
.sys-kpi-accent--amber {background:linear-gradient(90deg,#f59e0b,#fbbf24)}

/* ─── section header / toolbar ───────────────────────── */
.sys-toolbar{
    display:flex;align-items:center;gap:10px;
    padding:0 var(--pg);margin-bottom:18px;flex-wrap:wrap;
}
.sys-toolbar-title{font-size:14px;font-weight:700;color:#101828;margin-right:auto}
.sys-search{
    position:relative;
}
.sys-search input{
    height:34px;padding:0 12px 0 34px;
    border:1px solid var(--line);border-radius:9px;
    font-size:11.5px;color:#344054;background:#fff;
    width:220px;outline:none;
    transition:border-color .15s,box-shadow .15s;
}
.sys-search input:focus{border-color:#5b5ce2;box-shadow:0 0 0 3px rgba(91,92,226,.12)}
.sys-search .bi{
    position:absolute;left:10px;top:50%;transform:translateY(-50%);
    color:#98a2b3;font-size:12px;pointer-events:none;
}
.sys-filter-btn{
    height:34px;padding:0 14px;border-radius:9px;
    font-size:11px;font-weight:600;
    border:1px solid var(--line);background:#fff;color:#344054;
    cursor:pointer;display:inline-flex;align-items:center;gap:6px;
    transition:all .14s;
}
.sys-filter-btn:hover{background:#f9fafb;border-color:#d0d5dd}
.sys-clear-btn{
    font-size:11px;color:#667085;font-weight:500;
    text-decoration:none;padding:0 8px;line-height:34px;
    border-radius:8px;transition:color .13s;
}
.sys-clear-btn:hover{color:#5b5ce2}

/* ─── group block ─────────────────────────────────────── */
.sg-section{margin:0 var(--pg) 20px}
.sg-section-label{
    display:flex;align-items:center;gap:10px;
    margin-bottom:10px;
}
.sg-section-label-text{
    font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;
    color:#667085;
}
.sg-section-label-line{flex:1;height:1px;background:var(--line)}
.sg-section-label-count{
    font-size:10px;color:#98a2b3;
    background:#f9fafb;border:1px solid var(--line);
    border-radius:20px;padding:1px 9px;font-weight:600;
}

/* ─── settings table card ─────────────────────────────── */
.sg-card{
    background:#fff;border:1px solid var(--line);border-radius:16px;
    overflow:hidden;
    box-shadow:0 1px 4px rgba(16,24,40,.05);
}
.sg-card-head{
    display:flex;align-items:center;gap:12px;
    padding:13px 20px;border-bottom:1px solid var(--line);
    background:#fafafa;
}
.sg-card-head-icon{
    width:32px;height:32px;border-radius:9px;
    display:grid;place-items:center;font-size:14px;
    background:#eeefff;color:#5b5ce2;flex-shrink:0;
}
.sg-card-head-name{font-weight:700;font-size:12.5px;color:#101828;text-transform:capitalize}
.sg-card-head-count{
    margin-left:auto;font-size:10px;color:#667085;
    background:#f0f1ff;border:1px solid #d4d5fb;
    border-radius:20px;padding:2px 10px;font-weight:600;color:#5b5ce2;
}

/* ─── row ─────────────────────────────────────────────── */
.sg-row{
    display:grid;
    grid-template-columns:1fr auto auto;
    align-items:center;gap:14px;
    padding:14px 20px;
    border-bottom:1px solid var(--line);
    transition:background .12s;
}
.sg-row:last-child{border-bottom:none}
.sg-row:hover{background:#fafbff}
.sg-row-label{font-size:12.5px;font-weight:600;color:#101828}
.sg-row-meta{display:flex;align-items:center;gap:7px;margin-top:4px;flex-wrap:wrap}
.sg-row-key{
    font-size:11px;font-family:'SF Mono','Fira Code',monospace;
    background:#f5f5f5;border:1px solid #e8e8e8;
    border-radius:5px;padding:1px 7px;color:#555;
}
.sg-row-type{
    font-size:10px;text-transform:uppercase;letter-spacing:.06em;
    color:#98a2b3;font-weight:600;
}
.sg-row-public{
    font-size:10px;font-weight:600;color:#5b5ce2;
    display:inline-flex;align-items:center;gap:3px;
}
.sg-row-value{
    font-size:11.5px;color:#667085;margin-top:4px;
    max-width:380px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
    font-family:'SF Mono','Fira Code',monospace;
}
/* status pill */
.sg-pill{
    display:inline-flex;align-items:center;gap:5px;
    font-size:10.5px;font-weight:600;padding:3px 10px;border-radius:20px;
    white-space:nowrap;flex-shrink:0;
}
.sg-pill--on {background:#ecfdf5;color:#027a48}
.sg-pill--off{background:#f9fafb;color:#667085;border:1px solid var(--line)}
.sg-pill .dot{width:5px;height:5px;border-radius:50%;background:currentColor;display:block}
/* action buttons */
.sg-actions{display:flex;gap:5px;align-items:center;flex-shrink:0}
.sg-btn{
    height:30px;width:30px;border-radius:8px;
    border:1px solid var(--line);background:#fff;
    display:grid;place-items:center;font-size:12px;
    cursor:pointer;color:#667085;
    transition:all .13s;text-decoration:none;
}
.sg-btn:hover{background:#f9fafb;border-color:#d0d5dd;color:#344054}
.sg-btn--danger:hover{background:#fef2f2;border-color:#fecaca;color:#dc2626}
.sg-btn--success{color:#059669}
.sg-btn--success:hover{background:#ecfdf5;border-color:#a7f3d0}

/* ─── empty state ─────────────────────────────────────── */
.sg-empty{
    text-align:center;padding:64px 20px;
    background:#fff;border:1px solid var(--line);
    border-radius:16px;margin:0 var(--pg);
    box-shadow:0 1px 4px rgba(16,24,40,.04);
}
.sg-empty-icon{
    width:64px;height:64px;border-radius:18px;
    background:#eeefff;display:grid;place-items:center;
    font-size:26px;color:#5b5ce2;margin:0 auto 16px;
}
.sg-empty h3{font-size:15px;font-weight:700;color:#101828;margin:0 0 6px}
.sg-empty p{font-size:12px;color:#667085;margin:0 0 20px}

/* ─── alert ───────────────────────────────────────────── */
.sg-alert{
    display:flex;align-items:center;gap:10px;
    background:#ecfdf5;border:1px solid #a7f3d0;border-radius:10px;
    padding:10px 16px;margin:0 var(--pg) 20px;
    font-size:12px;font-weight:500;color:#065f46;
}
</style>
@endpush

@section('content')

{{-- ══ HERO BANNER ══ --}}
<div class="sys-banner">
    <div class="sys-banner-icon"><i class="bi bi-toggles2"></i></div>
    <div class="sys-banner-copy">
        <div class="sys-banner-eyebrow">System · Administration</div>
        <h1 class="sys-banner-title">System Settings</h1>
        <p class="sys-banner-sub">Application-wide feature flags and configuration values — all scoped to your organisation and version-tracked.</p>
    </div>
    <div class="sys-banner-actions">
        <a href="{{ route('settings.index') }}" class="sys-banner-btn sys-banner-btn--ghost">
            <i class="bi bi-arrow-left"></i> Settings
        </a>
        <a href="{{ route('system.create') }}" class="sys-banner-btn sys-banner-btn--primary">
            <i class="bi bi-plus-lg"></i> New Setting
        </a>
    </div>
</div>

{{-- ══ FLASH ══ --}}
@if(session('success'))
<div class="sg-alert">
    <i class="bi bi-check-circle-fill" style="font-size:15px;color:#059669"></i>
    {{ session('success') }}
</div>
@endif

{{-- ══ KPI STRIP ══ --}}
<div class="sys-kpis">
    <div class="sys-kpi">
        <div class="sys-kpi-icon sys-kpi-icon--indigo"><i class="bi bi-sliders2-vertical"></i></div>
        <div>
            <span class="sys-kpi-val">{{ $stats['total'] }}</span>
            <span class="sys-kpi-lbl">Total Settings</span>
        </div>
        <div class="sys-kpi-accent sys-kpi-accent--indigo"></div>
    </div>
    <div class="sys-kpi">
        <div class="sys-kpi-icon sys-kpi-icon--green"><i class="bi bi-check-circle-fill"></i></div>
        <div>
            <span class="sys-kpi-val">{{ $stats['active'] }}</span>
            <span class="sys-kpi-lbl">Active</span>
        </div>
        <div class="sys-kpi-accent sys-kpi-accent--green"></div>
    </div>
    <div class="sys-kpi">
        <div class="sys-kpi-icon sys-kpi-icon--amber"><i class="bi bi-collection-fill"></i></div>
        <div>
            <span class="sys-kpi-val">{{ $stats['groups'] }}</span>
            <span class="sys-kpi-lbl">Groups</span>
        </div>
        <div class="sys-kpi-accent sys-kpi-accent--amber"></div>
    </div>
</div>

{{-- ══ TOOLBAR ══ --}}
<div class="sys-toolbar">
    <span class="sys-toolbar-title">
        All Settings
        @if(request('search'))
            <span style="font-size:11px;font-weight:500;color:#667085;margin-left:6px">— filtered by "{{ request('search') }}"</span>
        @endif
    </span>
    <form method="GET" class="d-flex align-items-center gap-2">
        <div class="sys-search">
            <i class="bi bi-search"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search settings…" autocomplete="off">
        </div>
        <button type="submit" class="sys-filter-btn">
            <i class="bi bi-funnel"></i> Filter
        </button>
    </form>
    @if(request('search'))
        <a href="{{ route('system.index') }}" class="sys-clear-btn">
            <i class="bi bi-x-circle me-1"></i>Clear
        </a>
    @endif
</div>

{{-- ══ SETTINGS GROUPS ══ --}}
@forelse($settings as $group => $items)

<div class="sg-section">
    {{-- group label divider --}}
    <div class="sg-section-label">
        <span class="sg-section-label-text">{{ $group }}</span>
        <span class="sg-section-label-line"></span>
        <span class="sg-section-label-count">{{ $items->count() }}</span>
    </div>

    <div class="sg-card">
        <div class="sg-card-head">
            <div class="sg-card-head-icon"><i class="bi bi-folder2-open"></i></div>
            <span class="sg-card-head-name">{{ $group }}</span>
            <span class="sg-card-head-count">{{ $items->count() }} setting{{ $items->count() !== 1 ? 's' : '' }}</span>
        </div>

        @foreach($items->sortBy('sort_order') as $setting)
        <div class="sg-row">

            {{-- left: text info --}}
            <div>
                <div class="sg-row-label">{{ $setting->label }}</div>
                <div class="sg-row-meta">
                    <span class="sg-row-key">{{ $setting->group }}.{{ $setting->key }}</span>
                    <span class="sg-row-type">{{ $setting->type }}</span>
                    @if($setting->is_public)
                        <span class="sg-row-public"><i class="bi bi-globe2"></i> public</span>
                    @endif
                </div>
                @if(!is_null($setting->value) && $setting->value !== '')
                <div class="sg-row-value" title="{{ $setting->value }}">
                    {{ Str::limit($setting->value, 72) }}
                </div>
                @endif
            </div>

            {{-- centre: status pill --}}
            <span class="sg-pill {{ $setting->is_active ? 'sg-pill--on' : 'sg-pill--off' }}">
                <span class="dot"></span>
                {{ $setting->is_active ? 'Active' : 'Disabled' }}
            </span>

            {{-- right: action buttons --}}
            <div class="sg-actions">
                <a href="{{ route('system.edit', $setting) }}"
                   class="sg-btn" title="Edit setting">
                    <i class="bi bi-pencil"></i>
                </a>

                <form method="POST" action="{{ route('system.toggle-active', $setting) }}" class="d-inline">
                    @csrf @method('PATCH')
                    <button type="submit"
                            class="sg-btn {{ $setting->is_active ? 'sg-btn--success' : '' }}"
                            title="{{ $setting->is_active ? 'Disable' : 'Enable' }}">
                        <i class="bi bi-toggle-{{ $setting->is_active ? 'on' : 'off' }}"></i>
                    </button>
                </form>

                <form method="POST" action="{{ route('system.destroy', $setting) }}" class="d-inline"
                      onsubmit="return confirm('Delete «{{ addslashes($setting->label) }}»? This cannot be undone.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="sg-btn sg-btn--danger" title="Delete">
                        <i class="bi bi-trash3"></i>
                    </button>
                </form>
            </div>

        </div>
        @endforeach
    </div>
</div>

@empty

{{-- empty state --}}
<div class="sg-empty">
    <div class="sg-empty-icon"><i class="bi bi-gear"></i></div>
    <h3>No settings yet</h3>
    <p>Create your first system-wide configuration entry to get started.</p>
    <a href="{{ route('system.create') }}" class="sys-banner-btn sys-banner-btn--primary" style="display:inline-flex;margin:0 auto">
        <i class="bi bi-plus-lg"></i> New Setting
    </a>
</div>

@endforelse

@endsection
