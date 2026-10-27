@extends('layouts.app')
@section('title', 'Integrations')

@push('styles')
<style>
/* ── page header ── */
.pghd{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;flex-wrap:wrap;padding:28px var(--pg) 26px;background:linear-gradient(120deg,#121b36 0%,#1d2a55 58%,#283e73 100%);position:relative;overflow:hidden;color:#fff}
.pghd:after{content:'';position:absolute;width:320px;height:320px;border:1px solid rgba(255,255,255,.11);border-radius:50%;right:-90px;top:-180px;box-shadow:0 0 0 22px rgba(255,255,255,.025),0 0 0 44px rgba(255,255,255,.018)}
.pghd-copy{position:relative;z-index:1}.pghd-copy .eyebrow{font-size:10px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:#aab8ff;margin-bottom:8px}.pghd-copy h1{font-size:1.75rem;font-weight:700;letter-spacing:-.03em;margin:0 0 6px;color:#fff}.pghd-copy p{margin:0;color:#bdc8e4;font-size:.85rem}.pghd .btn-primary{background:#fff;color:#27377b;border-color:#fff;position:relative;z-index:1;box-shadow:0 8px 18px rgba(5,12,38,.2)}
/* ── stats ── */
.int-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;padding:22px var(--pg) 0;margin-bottom:22px}.int-stat{background:var(--clr-card);border:1px solid var(--clr-border);border-radius:12px;padding:16px 18px;min-width:100px;box-shadow:0 4px 14px rgba(16,24,40,.035);position:relative;overflow:hidden}.int-stat:after{content:'';position:absolute;right:-18px;bottom:-28px;width:76px;height:76px;border-radius:50%;background:var(--clr-primary-soft);opacity:.55}.int-stat-val{font-size:1.6rem;font-weight:750;color:var(--clr-text);display:block;line-height:1.1;position:relative;z-index:1}.int-stat-lbl{font-size:.68rem;text-transform:uppercase;letter-spacing:.1em;color:var(--clr-muted);margin-top:6px;display:block;position:relative;z-index:1}
/* ── type filter ── */
.type-tabs{display:flex;gap:7px;flex-wrap:wrap;padding:0 var(--pg);margin-bottom:14px}.type-tab{padding:7px 14px;border-radius:8px;font-size:.76rem;font-weight:650;border:1px solid var(--clr-border);background:var(--clr-card);color:var(--clr-muted);text-decoration:none;transition:all .15s}.type-tab.active,.type-tab:hover{background:var(--clr-primary);border-color:var(--clr-primary);color:#fff;box-shadow:0 5px 12px rgba(91,92,226,.2)}
/* ── toolbar ── */
.int-toolbar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;padding:0 var(--pg);margin-bottom:20px}.int-toolbar .sw{position:relative;flex:1;min-width:200px}.int-toolbar .sw input{padding-left:36px;border-radius:9px;font-size:.82rem;height:38px;border-color:var(--clr-border);background:var(--clr-card)}.int-toolbar .sw .bi{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--clr-muted);pointer-events:none}.int-toolbar .form-select{height:38px;border-radius:9px;border-color:var(--clr-border)}.int-toolbar .btn{height:38px;border-radius:9px}
/* ── card grid ── */
.int-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(310px,1fr));gap:14px;padding:0 var(--pg) 40px}
/* ── integration card ── */
.int-card{background:var(--clr-card);border:1px solid var(--clr-border);border-radius:14px;overflow:hidden;transition:transform .18s,box-shadow .18s,border-color .18s;display:flex;flex-direction:column}.int-card:hover{transform:translateY(-3px);box-shadow:0 14px 28px rgba(16,24,40,.1);border-color:#b8b9f3}.int-card-head{padding:18px 20px 14px;display:flex;gap:14px;align-items:flex-start}.int-card-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;background:var(--clr-primary-soft);color:var(--clr-primary);box-shadow:inset 0 0 0 1px rgba(91,92,226,.08)}
.int-card-icon--payment{background:#fef3c7;color:#92400e}
.int-card-icon--sms{background:#dcfce7;color:#166534}
.int-card-icon--email{background:#dbeafe;color:#1e40af}
.int-card-icon--kyc{background:#ede9fe;color:#5b21b6}
.int-card-icon--webhook{background:#fce7f3;color:#9d174d}
.int-card-title{font-weight:700;font-size:.95rem;color:var(--clr-text);margin:0 0 3px;letter-spacing:-.01em}.int-card-sub{font-size:.76rem;color:var(--clr-muted)}
.int-card-status{margin-left:auto;flex-shrink:0}
/* status badge */
.badge-status{display:inline-flex;align-items:center;gap:5px;font-size:.7rem;font-weight:700;padding:4px 10px;border-radius:20px;white-space:nowrap}
.badge-status--active{background:#d1fae5;color:#065f46}
.badge-status--inactive{background:#f3f4f6;color:#6b7280}
.badge-status--error{background:#fee2e2;color:#991b1b}
.badge-status .dot{width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block}
/* card body */
.int-card-body{padding:0 20px 16px;flex:1}
.int-card-meta{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:8px}
.int-meta-pill{font-size:.72rem;padding:2px 8px;border-radius:4px;background:var(--clr-surface);border:1px solid var(--clr-border);color:var(--clr-muted)}
.int-meta-pill--prod{background:#fef3c7;border-color:#fbbf24;color:#92400e}
.int-error{font-size:.75rem;color:#dc2626;background:#fee2e2;border-radius:6px;padding:6px 10px;margin-top:6px;word-break:break-all}
/* card footer */
.int-card-foot{padding:12px 20px;border-top:1px solid var(--clr-border);display:flex;gap:6px;align-items:center;background:var(--clr-surface-2)}
.int-tested{font-size:.72rem;color:var(--clr-muted);margin-left:auto}
/* empty */
.int-empty{text-align:center;padding:56px 20px;color:var(--clr-muted);background:var(--clr-card);border:1px solid var(--clr-border);border-radius:14px;margin:0 var(--pg)}
.int-empty i{font-size:2.4rem;opacity:.35;display:block;margin-bottom:12px}
@media(max-width:760px){.int-stats{grid-template-columns:repeat(2,1fr)}.pghd{padding-top:22px}.pghd .btn-primary{width:100%;justify-content:center}.int-toolbar form{width:100%}.int-toolbar .form-select{flex:1}.int-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')

{{-- HEADER --}}
<div class="pghd">
    <div class="pghd-copy">
        <div class="eyebrow">System · Integrations</div>
        <h1>Integrations</h1>
        <p>Manage external service connections — payments, SMS, email, KYC, and webhooks.</p>
    </div>
    <a href="{{ route('integrations.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
        <i class="bi bi-plus-lg"></i> New Integration
    </a>
</div>

{{-- FLASH --}}
@if(session('success'))
<div class="alert alert-success d-flex align-items-center gap-2 mx-4 mb-4" role="alert">
    <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
</div>
@endif

{{-- STATS --}}
<div class="int-stats">
    <div class="int-stat">
        <span class="int-stat-val">{{ $stats['total'] }}</span>
        <span class="int-stat-lbl">Total</span>
    </div>
    <div class="int-stat">
        <span class="int-stat-val" style="color:#065f46">{{ $stats['active'] }}</span>
        <span class="int-stat-lbl">Active</span>
    </div>
    <div class="int-stat">
        <span class="int-stat-val" style="color:#dc2626">{{ $stats['error'] }}</span>
        <span class="int-stat-lbl">Error</span>
    </div>
    <div class="int-stat">
        <span class="int-stat-val" style="color:#6b7280">{{ $stats['inactive'] }}</span>
        <span class="int-stat-lbl">Inactive</span>
    </div>
</div>

{{-- TYPE FILTER TABS --}}
<div class="type-tabs">
    <a href="{{ route('integrations.index') }}"
       class="type-tab {{ !request('type') ? 'active' : '' }}">All</a>
    @foreach($types as $key => $label)
    <a href="{{ route('integrations.index', ['type' => $key] + request()->except('type','page')) }}"
       class="type-tab {{ request('type') === $key ? 'active' : '' }}">
        {{ $label }}
    </a>
    @endforeach
</div>

{{-- TOOLBAR --}}
<div class="int-toolbar">
    <form method="GET" class="d-flex gap-2 flex-grow-1 flex-wrap align-items-center">
        @if(request('type'))<input type="hidden" name="type" value="{{ request('type') }}">@endif
        <div class="sw">
            <i class="bi bi-search"></i>
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control" placeholder="Search integrations…">
        </div>
        <select name="status" class="form-select form-select-sm" style="width:auto">
            <option value="">All status</option>
            <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            <option value="error"    {{ request('status') === 'error'    ? 'selected' : '' }}>Error</option>
        </select>
        <button type="submit" class="btn btn-outline-secondary btn-sm">Filter</button>
        @if(request()->hasAny(['search','status']))
        <a href="{{ route('integrations.index', request()->only('type')) }}" class="btn btn-outline-secondary btn-sm">Clear</a>
        @endif
    </form>
</div>

{{-- CARD GRID --}}
@if($integrations->count())
<div class="int-grid">
    @foreach($integrations as $int)
    <div class="int-card">
        <div class="int-card-head">
            <div class="int-card-icon int-card-icon--{{ $int->type }}">
                <i class="bi {{ $int->icon() }}"></i>
            </div>
            <div class="flex-grow-1">
                <div class="int-card-title">{{ $int->label }}</div>
                <div class="int-card-sub">{{ ucfirst($int->provider) }} · {{ $int->typeLabel() }}</div>
            </div>
            <div class="int-card-status">
                <span class="badge-status {{ $int->statusBadgeClass() }}">
                    <span class="dot"></span>{{ ucfirst($int->status) }}
                </span>
            </div>
        </div>

        <div class="int-card-body">
            <div class="int-card-meta">
                <span class="int-meta-pill int-meta-pill--{{ $int->environment === 'production' ? 'prod' : '' }}">
                    <i class="bi bi-{{ $int->environment === 'production' ? 'cloud-fill' : 'cpu' }}"></i>
                    {{ ucfirst($int->environment) }}
                </span>
                @if(!empty($int->config['callback_url']))
                <span class="int-meta-pill" title="{{ $int->config['callback_url'] }}">
                    <i class="bi bi-link-45deg"></i> Callback set
                </span>
                @endif
            </div>
            @if($int->last_error)
            <div class="int-error"><i class="bi bi-exclamation-triangle-fill me-1"></i>{{ Str::limit($int->last_error, 100) }}</div>
            @endif
        </div>

        <div class="int-card-foot">
            <a href="{{ route('integrations.edit', $int) }}"
               class="btn btn-sm btn-outline-secondary" title="Edit">
                <i class="bi bi-pencil"></i>
            </a>
            <form method="POST" action="{{ route('integrations.test', $int) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Test connection">
                    <i class="bi bi-wifi"></i>
                </button>
            </form>
            <form method="POST" action="{{ route('integrations.toggle-active', $int) }}" class="d-inline">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-sm btn-outline-secondary"
                        title="{{ $int->is_active ? 'Deactivate' : 'Activate' }}">
                    <i class="bi bi-toggle-{{ $int->is_active ? 'on text-success' : 'off text-muted' }}"></i>
                </button>
            </form>
            <form method="POST" action="{{ route('integrations.destroy', $int) }}" class="d-inline"
                  onsubmit="return confirm('Remove integration «{{ addslashes($int->label) }}»?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                    <i class="bi bi-trash3"></i>
                </button>
            </form>
            @if($int->last_tested_at)
            <span class="int-tested">Tested {{ $int->last_tested_at->diffForHumans() }}</span>
            @endif
        </div>
    </div>
    @endforeach
</div>

{{-- Pagination --}}
@if($integrations->hasPages())
<div class="px-4 pb-6">{{ $integrations->withQueryString()->links() }}</div>
@endif

@else
<div class="int-empty">
    <i class="bi bi-plug-fill"></i>
    <p class="mb-3">No integrations configured yet.</p>
    <a href="{{ route('integrations.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Add First Integration
    </a>
</div>
@endif

@endsection
