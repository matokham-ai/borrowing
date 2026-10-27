@extends('layouts.app')

@section('title', $viewMeta['title'])

@push('styles')
<style>
/* ─── Page shell ─────────────────────────────────────────────────────────── */
.payments-page{max-width:1520px;margin:0 auto}
.payments-header{align-items:center}
.payments-header .eyebrow{display:flex;align-items:center;gap:7px}
.eyebrow-dot{width:7px;height:7px;border-radius:50%;background:#18b981;box-shadow:0 0 0 4px #e8faf3}
.payment-period{height:34px;border:1px solid var(--line);border-radius:8px;background:#fff;padding:0 10px;color:#344054;font-size:11px;font-weight:600;cursor:pointer}

/* ─── Stat cards ─────────────────────────────────────────────────────────── */
.payment-stat-grid{grid-template-columns:repeat(4,1fr);margin-bottom:18px}
.payment-stat{padding:20px 20px 16px;position:relative;overflow:hidden;border-radius:14px}
.payment-stat::after{content:'';position:absolute;width:100px;height:100px;border-radius:50%;right:-28px;top:-36px;background:var(--stat-wash,#f0f1ff);opacity:.7;pointer-events:none}
.payment-stat-top{display:flex;align-items:center;justify-content:space-between;position:relative;z-index:1}
.payment-stat-icon{width:36px;height:36px;display:grid;place-items:center;border-radius:10px;background:var(--stat-wash,#f0f1ff);color:var(--stat-color,var(--primary));font-size:16px}
.payment-stat-kicker{font-size:9px;letter-spacing:.1em;color:#98a2b3;font-weight:700}
.payment-stat-label{margin-top:18px;color:#667085;font-size:11px;font-weight:500}
.payment-stat-value{font-size:24px;letter-spacing:-.03em;font-weight:750;margin-top:3px;color:#0d1320}
.payment-stat-note{font-size:10px;color:#98a2b3;margin-top:6px}
.payment-stat-note strong{color:#475467;font-weight:650}
.payment-stat-green{--stat-color:#079455;--stat-wash:#e9faf2}
.payment-stat-blue{--stat-color:#175cd3;--stat-wash:#edf4ff}
.payment-stat-amber{--stat-color:#dc6803;--stat-wash:#fff5e8}
.payment-stat-rose{--stat-color:#d92d20;--stat-wash:#fff0ee}

/* ─── Page layout ────────────────────────────────────────────────────────── */
.payments-layout{display:grid;grid-template-columns:minmax(0,1fr) 288px;gap:16px;align-items:start}
.payments-panel{padding:0;overflow:hidden}

/* ─── Panel head ─────────────────────────────────────────────────────────── */
.payments-panel-head{display:flex;align-items:center;justify-content:space-between;padding:20px 22px 16px}
.payments-panel-head h2{font-size:13px;font-weight:700;margin:0;color:#0d1320}
.payments-panel-head p{font-size:10px;color:#98a2b3;margin:3px 0 0;font-weight:500}
.result-count{color:#667085;font-size:10px;font-weight:650;background:#f5f6f8;border-radius:20px;padding:5px 10px;border:1px solid var(--line)}

/* ─── Filters ────────────────────────────────────────────────────────────── */
.payment-filters{display:flex;align-items:center;gap:8px;padding:0 22px 16px;flex-wrap:wrap}
.payment-filter-search{height:36px;max-width:300px;flex:1;display:flex;align-items:center;gap:8px;border:1px solid var(--line);border-radius:9px;padding:0 12px;background:#fff;transition:border-color .15s,box-shadow .15s}
.payment-filter-search:focus-within{border-color:#a5b4fc;box-shadow:0 0 0 3px rgba(99,102,241,.1)}
.payment-filter-search i{color:#c0c8d8;font-size:13px;flex-shrink:0}
.payment-filter-search input{border:0;outline:0;min-width:0;width:100%;font-size:11.5px;color:#344054;background:transparent}
.payment-filter-select{height:36px;border:1px solid var(--line);border-radius:9px;background:#fff;color:#475467;padding:0 10px;font-size:11.5px;cursor:pointer;transition:border-color .15s}
.payment-filter-select:focus{border-color:#a5b4fc;outline:none;box-shadow:0 0 0 3px rgba(99,102,241,.1)}
.filter-btn{height:36px;display:inline-flex;align-items:center;gap:6px;border-radius:9px;padding:0 14px;font-size:11.5px;font-weight:600;cursor:pointer;transition:all .15s;border:1px solid var(--primary);background:var(--primary);color:#fff}
.filter-btn:hover{background:#4a4bcf;border-color:#4a4bcf}
.filter-clear{height:36px;display:inline-flex;align-items:center;gap:6px;border-radius:9px;padding:0 12px;font-size:11.5px;font-weight:600;cursor:pointer;border:1px solid var(--line);background:#fff;color:#667085;text-decoration:none;transition:border-color .15s,color .15s}
.filter-clear:hover{border-color:#c7c8f6;color:var(--primary)}

/* ─── Allocations table ──────────────────────────────────────────────────── */
.alloc-table{width:100%;border-collapse:collapse;min-width:860px}
.alloc-table thead tr{border-bottom:2px solid var(--line)}
.alloc-table th{padding:10px 14px;font-size:9.5px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#98a2b3;text-align:left;white-space:nowrap}
.alloc-table th:first-child{padding-left:22px}
.alloc-table th:last-child{padding-right:22px}
.alloc-table tbody tr{border-bottom:1px solid #f5f6f8;transition:background .12s}
.alloc-table tbody tr:hover{background:#fafbff}
.alloc-table tbody tr:last-child{border-bottom:0}
.alloc-table td{padding:13px 14px;vertical-align:middle}
.alloc-table td:first-child{padding-left:22px}
.alloc-table td:last-child{padding-right:22px}

/* ─── Table cell components ──────────────────────────────────────────────── */
.pa-person{display:flex;align-items:center;gap:10px}
.pa-avatar{width:32px;height:32px;border-radius:10px;background:linear-gradient(135deg,#e0e1ff,#c7c8f6);color:#4b4fc4;font-size:11px;font-weight:750;display:grid;place-items:center;flex-shrink:0}
.pa-avatar-unmatched{background:linear-gradient(135deg,#f0f2f5,#e4e7ec);color:#667085}
.pa-name{font-size:12px;font-weight:650;color:#101828;display:block;line-height:1.3}
.pa-sub{font-size:10px;color:#98a2b3;display:block;margin-top:2px}
.pa-ref-main{font-size:11.5px;font-weight:650;color:#344054;display:block;font-variant-numeric:tabular-nums;letter-spacing:.01em}
.pa-ref-sub{font-size:10px;color:#98a2b3;display:block;margin-top:2px}
.pa-amount{font-size:13px;font-weight:750;color:#0d1320;display:block;letter-spacing:-.015em}
.pa-unapplied{font-size:9.5px;color:#dc6803;font-weight:600;display:block;margin-top:3px;background:#fff5e8;border-radius:5px;padding:2px 6px;width:fit-content}

/* ─── Allocation chips inside table ─────────────────────────────────────── */
.pa-alloc-chips{display:flex;flex-direction:column;gap:5px}
.pa-alloc-row{display:flex;align-items:center;gap:7px}
.pa-alloc-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.pa-alloc-type{font-size:10px;font-weight:600;color:#475467;min-width:54px}
.pa-alloc-bar-wrap{flex:1;height:4px;background:#f0f2f5;border-radius:10px;overflow:hidden;min-width:40px;max-width:80px}
.pa-alloc-bar{height:100%;border-radius:10px}
.pa-alloc-amount{font-size:10px;font-weight:650;color:#101828;text-align:right;min-width:80px;font-variant-numeric:tabular-nums}
.pa-alloc-total{font-size:10px;color:#98a2b3;margin-top:5px;padding-top:5px;border-top:1px solid #f0f2f5;display:flex;justify-content:space-between}
.pa-alloc-total strong{color:#344054}

/* ─── Method badge ───────────────────────────────────────────────────────── */
.pa-method{display:inline-flex;align-items:center;gap:6px;font-size:10.5px;font-weight:600;color:#475467}
.pa-method-dot{width:8px;height:8px;border-radius:50%;background:#12b76a}
.pa-method-dot-bank{background:#7f56d9}
.pa-method-dot-cash{background:#f79009}
.pa-method-dot-credit{background:#3538cd}

/* ─── Status pill ────────────────────────────────────────────────────────── */
.pa-status{display:inline-flex;align-items:center;gap:5px;border-radius:20px;padding:4px 9px;font-size:9.5px;font-weight:700;letter-spacing:.02em}
.pa-status::before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0}
.pa-status-posted{color:#067647;background:#ecfdf3}
.pa-status-pending,.pa-status-processing{color:#b54708;background:#fffaeb}
.pa-status-reversed,.pa-status-failed{color:#b42318;background:#fff1f0}

/* ─── Row action button ──────────────────────────────────────────────────── */
.pa-row-btn{width:30px;height:30px;border-radius:8px;display:grid;place-items:center;border:1px solid var(--line);background:#fff;color:#98a2b3;cursor:pointer;transition:all .15s;text-decoration:none}
.pa-row-btn:hover{border-color:#c7c8f6;background:#f5f5ff;color:var(--primary)}

/* ─── Pagination ─────────────────────────────────────────────────────────── */
.pagination-wrap{padding:14px 22px;border-top:1px solid var(--line)}

/* ─── Sidebar panels ─────────────────────────────────────────────────────── */
.payment-side-stack{display:grid;gap:14px}
.payment-insight{padding:20px 20px 18px}
.payment-insight-head{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:2px}
.payment-insight h2{font-size:12.5px;font-weight:700;margin:0;color:#0d1320}
.payment-insight p{font-size:10px;line-height:1.6;color:#98a2b3;margin:4px 0 0}
.insight-icon{width:32px;height:32px;display:grid;place-items:center;border-radius:9px;background:#eef4ff;color:#175cd3;font-size:15px;flex-shrink:0}
.collection-ring{width:128px;height:128px;border-radius:50%;margin:20px auto 18px;display:grid;place-items:center;background:conic-gradient(#5b5ce2 0 var(--posted-rate,76%),#edeefc var(--posted-rate,76%) 100%);position:relative}
.collection-ring::after{content:'';position:absolute;inset:12px;border-radius:50%;background:#fff}
.collection-ring-copy{position:relative;z-index:1;text-align:center}
.collection-ring-copy strong{display:block;font-size:23px;font-weight:750;letter-spacing:-.02em;color:#0d1320}
.collection-ring-copy span{font-size:9px;color:#98a2b3;font-weight:600;letter-spacing:.05em;text-transform:uppercase}
.insight-foot{display:flex;justify-content:space-between;border-top:1px solid var(--line);padding-top:13px;font-size:10px;color:#667085}
.insight-foot strong{color:#101828;font-weight:650}
.payment-method-list{display:grid;gap:15px;margin-top:18px}
.method-line{display:grid;gap:7px}
.method-line-top{display:flex;justify-content:space-between;font-size:10.5px;color:#475467}
.method-line-top strong{color:#101828;font-weight:650}
.method-track{height:6px;border-radius:10px;background:#f0f2f5;overflow:hidden}
.method-track span{display:block;height:100%;border-radius:inherit;background:#5b5ce2;transition:width .4s ease}
.method-track .mpesa{background:#12b76a}
.method-track .cash{background:#f79009}
.method-track .bank_transfer{background:#7f56d9}
.quick-actions{display:grid;gap:8px;margin-top:18px}
.quick-action{display:flex;align-items:center;justify-content:space-between;padding:11px 13px;border:1px solid var(--line);border-radius:10px;color:#344054;text-decoration:none;font-size:10.5px;font-weight:650;transition:all .15s}
.quick-action i{color:#b0bbc8}
.quick-action:hover{border-color:#c7c8f6;background:#fafaff;color:var(--primary)}
.quick-action:hover i{color:var(--primary)}

/* ─── Detail drawer (slide-in panel) ─────────────────────────────────────── */
.detail-overlay{position:fixed;inset:0;background:rgba(10,16,30,.35);z-index:150;opacity:0;pointer-events:none;transition:opacity .22s ease;backdrop-filter:blur(2px)}
.detail-overlay.open{opacity:1;pointer-events:all}
.detail-drawer{position:fixed;top:0;right:0;bottom:0;width:400px;background:#fff;z-index:151;display:flex;flex-direction:column;box-shadow:-8px 0 40px rgba(10,16,30,.12);transform:translateX(100%);transition:transform .25s cubic-bezier(.22,1,.36,1)}
.detail-drawer.open{transform:translateX(0)}

.dd-head{padding:20px 22px 16px;border-bottom:1px solid var(--line);flex-shrink:0}
.dd-head-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:2px}
.dd-title{font-size:14px;font-weight:750;color:#0d1320;margin:0}
.dd-subtitle{font-size:10.5px;color:#98a2b3;margin:3px 0 0}
.dd-close{width:28px;height:28px;border-radius:8px;border:1px solid var(--line);background:#fff;display:grid;place-items:center;cursor:pointer;color:#667085;font-size:15px;transition:all .15s;flex-shrink:0}
.dd-close:hover{border-color:#f04438;color:#f04438;background:#fff1f0}

.dd-body{flex:1;overflow-y:auto;padding:0 22px 24px;scrollbar-width:thin;scrollbar-color:#e4e7ec transparent}
.dd-body::-webkit-scrollbar{width:4px}
.dd-body::-webkit-scrollbar-thumb{background:#e4e7ec;border-radius:4px}

/* Borrower identity card */
.dd-identity{display:flex;align-items:center;gap:12px;padding:18px 0 16px;border-bottom:1px solid var(--line)}
.dd-id-avatar{width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#e0e1ff,#c7c8f6);color:#4b4fc4;font-size:14px;font-weight:750;display:grid;place-items:center;flex-shrink:0}
.dd-id-name{font-size:13.5px;font-weight:700;color:#0d1320;display:block}
.dd-id-loan{font-size:10.5px;color:#667085;display:block;margin-top:3px}
.dd-id-status{margin-left:auto;flex-shrink:0}

/* Meta grid */
.dd-meta-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;padding:18px 0 16px;border-bottom:1px solid var(--line)}
.dd-meta-item label{font-size:9.5px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#98a2b3;display:block;margin-bottom:4px}
.dd-meta-item span{font-size:12px;font-weight:600;color:#0d1320;display:block}
.dd-meta-item small{font-size:10px;color:#667085;display:block;margin-top:2px}

/* Section heading */
.dd-section-label{font-size:11px;font-weight:700;color:#0d1320;margin:20px 0 12px;display:flex;align-items:center;justify-content:space-between}
.dd-section-label span{font-size:9.5px;font-weight:600;color:#98a2b3}

/* Allocation breakdown table */
.dd-alloc-table{width:100%;border-collapse:collapse;border-radius:10px;overflow:hidden;border:1px solid var(--line)}
.dd-alloc-table thead tr{background:#f9fafb}
.dd-alloc-table th{padding:8px 12px;font-size:9px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#98a2b3;text-align:left;border-bottom:1px solid var(--line)}
.dd-alloc-table th:last-child{text-align:right}
.dd-alloc-table td{padding:11px 12px;font-size:11px;border-bottom:1px solid #f5f6f8;vertical-align:middle}
.dd-alloc-table tbody tr:last-child td{border-bottom:0}
.dd-alloc-table td:last-child{text-align:right;font-weight:650}
.dd-alloc-table tfoot tr{background:#f9fafb;border-top:2px solid var(--line)}
.dd-alloc-table tfoot td{padding:9px 12px;font-size:11px;font-weight:750;color:#0d1320}
.dd-alloc-table tfoot td:last-child{text-align:right;color:var(--primary)}
.dd-type-dot{width:9px;height:9px;border-radius:50%;display:inline-block;margin-right:7px;flex-shrink:0;vertical-align:middle}

/* Waterfall steps */
.dd-waterfall{display:grid;gap:0}
.dd-wf-step{display:flex;align-items:flex-start;gap:12px;padding:11px 0;border-bottom:1px solid #f5f6f8;position:relative}
.dd-wf-step:last-child{border-bottom:0}
.dd-wf-num{width:26px;height:26px;border-radius:50%;background:#f0f2f5;color:#667085;font-size:10px;font-weight:700;display:grid;place-items:center;flex-shrink:0;border:1.5px solid #e4e7ec}
.dd-wf-num.applied{background:#ecfdf3;color:#067647;border-color:#a9efc5}
.dd-wf-body{flex:1;min-width:0}
.dd-wf-label{font-size:11.5px;font-weight:650;color:#344054;display:block}
.dd-wf-value{font-size:10.5px;color:#98a2b3;display:block;margin-top:2px}
.dd-wf-value.has-amount{color:#067647;font-weight:600}
.dd-wf-check{width:18px;height:18px;border-radius:50%;background:#ecfdf3;color:#067647;font-size:10px;display:grid;place-items:center;flex-shrink:0;margin-top:4px}
.dd-wf-dash{width:18px;height:18px;display:grid;place-items:center;color:#c0c8d8;flex-shrink:0;margin-top:4px}

/* Related loan card */
.dd-loan-card{display:flex;align-items:center;gap:12px;padding:13px 14px;border:1px solid var(--line);border-radius:11px;background:#fafbff;margin-bottom:4px;text-decoration:none;transition:border-color .15s,background .15s}
.dd-loan-card:hover{border-color:#c7c8f6;background:#f5f5ff}
.dd-loan-icon{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#eef4ff,#dde9ff);color:#175cd3;font-size:15px;display:grid;place-items:center;flex-shrink:0}
.dd-loan-name{font-size:12px;font-weight:650;color:#0d1320;display:block}
.dd-loan-product{font-size:10px;color:#667085;display:block;margin-top:2px}
.dd-loan-balance{margin-left:auto;text-align:right;flex-shrink:0}
.dd-loan-balance strong{font-size:12px;font-weight:750;color:#0d1320;display:block;letter-spacing:-.01em}
.dd-loan-balance span{font-size:9.5px;color:#98a2b3;display:block;margin-top:2px}

/* Drawer footer */
.dd-foot{padding:16px 22px;border-top:1px solid var(--line);display:flex;gap:10px;flex-shrink:0;background:#fff}
.dd-foot-btn{flex:1;height:38px;border-radius:9px;font-size:12px;font-weight:650;display:inline-flex;align-items:center;justify-content:center;gap:7px;cursor:pointer;transition:all .15s;text-decoration:none;border:1px solid var(--line);background:#fff;color:#344054}
.dd-foot-btn:hover{border-color:#c7c8f6;background:#f5f5ff;color:var(--primary)}
.dd-foot-btn-primary{background:var(--primary);border-color:var(--primary);color:#fff}
.dd-foot-btn-primary:hover{background:#4a4bcf;border-color:#4a4bcf;color:#fff}

/* ─── Loading skeleton ───────────────────────────────────────────────────── */
.dd-skeleton{animation:pulse 1.5s ease-in-out infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
.dd-skel-line{height:12px;background:#f0f2f5;border-radius:6px;margin-bottom:10px}

/* ─── Empty state ────────────────────────────────────────────────────────── */
.pa-empty{text-align:center;padding:48px 20px}
.pa-empty-icon{width:52px;height:52px;border-radius:14px;background:#f5f6f8;display:grid;place-items:center;margin:0 auto 16px;font-size:22px;color:#c0c8d8}
.pa-empty strong{display:block;font-size:13px;color:#344054;margin-bottom:6px}
.pa-empty small{font-size:11px;color:#98a2b3}

/* ─── Responsive ─────────────────────────────────────────────────────────── */
@media(max-width:1200px){
  .payments-layout{grid-template-columns:1fr}
  .payment-side-stack{grid-template-columns:1fr 1fr}
  .payment-stat-grid{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:760px){
  .payments-header{align-items:flex-start}
  .payments-header-actions{width:100%;display:flex;justify-content:space-between}
  .payment-filters{padding-left:16px;padding-right:16px}
  .payment-filter-search{max-width:none;flex-basis:100%;order:-1}
  .payment-side-stack{grid-template-columns:1fr}
  .payment-stat-grid{gap:10px}
  .payment-stat{padding:15px}
  .payment-stat-value{font-size:20px}
  .payments-panel-head{padding-left:16px;padding-right:16px}
  .detail-drawer{width:100%}
}
</style>
@endpush

@section('content')
<div class="payments-page">
    {{-- Dynamic ring fill --}}
    <style>
        .collection-ring{--posted-rate:{{ $stats['received'] ? round(($stats['posted'] / $stats['received']) * 100) : 0 }}%}
    </style>

    {{-- ═══ PAGE HEADER ═══════════════════════════════════════════════════ --}}
    <div class="page-header payments-header">
        <div>
            <div class="eyebrow"><span class="eyebrow-dot"></span> {{ $viewMeta['eyebrow'] }}</div>
            <h1>{{ $viewMeta['title'] }}</h1>
            <p>{{ $viewMeta['description'] }}</p>
        </div>
        <div class="payments-header-actions page-actions">
            <select class="payment-period" aria-label="Payment period"
                onchange="window.location='{{ route('payments.index', ['view' => $view]) }}&period='+this.value">
                @foreach(['today' => 'Today', '7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days'] as $v => $l)
                    <option value="{{ $v }}" @selected($period === $v)>{{ $l }}</option>
                @endforeach
            </select>
            <a href="{{ route('payments.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add payment</a>
            <button type="button" class="btn btn-light" onclick="window.print()"><i class="bi bi-download"></i> Export</button>
        </div>
    </div>

    {{-- ═══ STAT CARDS ═════════════════════════════════════════════════════ --}}
    <div class="stats-grid payment-stat-grid">
        <div class="stat-card payment-stat payment-stat-green">
            <div class="payment-stat-top">
                <span class="payment-stat-icon"><i class="bi bi-arrow-down-left"></i></span>
                <span class="payment-stat-kicker">SELECTED PERIOD</span>
            </div>
            <div class="payment-stat-label">Received</div>
            <div class="payment-stat-value">KSh {{ number_format($stats['received'] / 100) }}</div>
            <div class="payment-stat-note">All incoming repayments</div>
        </div>

        <div class="stat-card payment-stat payment-stat-blue">
            <div class="payment-stat-top">
                <span class="payment-stat-icon"><i class="bi bi-check2-circle"></i></span>
                <span class="payment-stat-kicker">CASH COLLECTED</span>
            </div>
            <div class="payment-stat-label">Posted successfully</div>
            <div class="payment-stat-value">KSh {{ number_format($stats['posted'] / 100) }}</div>
            <div class="payment-stat-note">
                <strong>{{ $stats['received'] ? number_format(($stats['posted'] / $stats['received']) * 100, 1) : '0.0' }}%</strong> of received value
            </div>
        </div>

        <div class="stat-card payment-stat payment-stat-amber">
            <div class="payment-stat-top">
                <span class="payment-stat-icon"><i class="bi bi-hourglass-split"></i></span>
                <span class="payment-stat-kicker">ACTION NEEDED</span>
            </div>
            <div class="payment-stat-label">Awaiting review</div>
            <div class="payment-stat-value">{{ number_format($stats['pending']) }}</div>
            <div class="payment-stat-note">Pending or processing records</div>
        </div>

        <div class="stat-card payment-stat payment-stat-rose">
            <div class="payment-stat-top">
                <span class="payment-stat-icon"><i class="bi bi-link-45deg"></i></span>
                <span class="payment-stat-kicker">RECONCILIATION</span>
            </div>
            <div class="payment-stat-label">Unapplied balance</div>
            <div class="payment-stat-value">KSh {{ number_format($stats['unapplied'] / 100) }}</div>
            <div class="payment-stat-note">Needs allocation to a loan</div>
        </div>
    </div>

    {{-- ═══ TWO-COLUMN LAYOUT ══════════════════════════════════════════════ --}}
    <div class="payments-layout">

        {{-- ── Main panel ─────────────────────────────────────────────── --}}
        <section class="panel payments-panel">
            <div class="payments-panel-head">
                <div>
                    <h2>{{ $viewMeta['title'] }}</h2>
                    <p>{{ $viewMeta['eyebrow'] }} · {{ number_format($payments->total()) }} records in this view</p>
                </div>
                <span class="result-count">{{ number_format($payments->total()) }} records</span>
            </div>

            {{-- Filters --}}
            <form method="GET" class="payment-filters" id="paymentsFilterForm">
                <input type="hidden" name="view" value="{{ $view }}">
                <input type="hidden" name="period" value="{{ $period }}">
                <div class="payment-filter-search">
                    <i class="bi bi-search"></i>
                    <input type="search" name="search" value="{{ $search }}"
                           placeholder="Search name, receipt or loan ID">
                </div>
                <select name="status" class="payment-filter-select">
                    <option value="">All statuses</option>
                    @foreach(['posted','pending','processing','reversed','failed'] as $val)
                        <option value="{{ $val }}" @selected($status === $val)>{{ ucfirst($val) }}</option>
                    @endforeach
                </select>
                <select name="method" class="payment-filter-select">
                    <option value="">All methods</option>
                    @foreach(['mpesa' => 'M-Pesa','bank_transfer' => 'Bank transfer','cash' => 'Cash','credit_balance' => 'Credit balance'] as $val => $lbl)
                        <option value="{{ $val }}" @selected($method === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
                <button class="filter-btn" type="submit"><i class="bi bi-funnel"></i> Filter</button>
                @if($search || $status || $method)
                    <a href="{{ route('payments.index', ['view' => $view, 'period' => $period]) }}" class="filter-clear">
                        <i class="bi bi-x"></i> Clear
                    </a>
                @endif
            </form>

            {{-- Table --}}
            <div class="admin-table-wrap">
                <table class="alloc-table">
                    <thead>
                        <tr>
                            <th>Borrower</th>
                            <th>Reference</th>
                            <th>Amount</th>
                            @if($view === 'allocations')
                                <th>Applied to</th>
                            @endif
                            <th>Method</th>
                            <th>{{ $view === 'reversals' ? 'Reversed' : 'Received' }}</th>
                            <th>Status</th>
                            <th aria-label="Actions" style="width:48px"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($payments as $payment)
                        @php
                            $borrower = $payment->borrower ?? $payment->loan?->borrower;
                            $name     = $borrower?->user?->name ?? 'Unmatched payment';
                            $initial  = strtoupper(substr($name, 0, 1));
                            $loanRef  = $payment->loan
                                ? 'Loan #' . str_pad($payment->loan->id, 7, '0', STR_PAD_LEFT)
                                : 'No loan linked';
                            $ref      = $payment->mpesa_receipt_number ?: $payment->external_reference ?: '—';
                            $ts       = $view === 'reversals' ? $payment->reversed_at : $payment->received_at;

                            // Allocation colours
                            $typeColors = [
                                'interest'  => '#f79009',
                                'principal' => '#5b5ce2',
                                'penalty'   => '#f04438',
                                'fees'      => '#9e77ed',
                                'other'     => '#12b76a',
                            ];
                            $allocGroups = $payment->allocations->groupBy('allocation_type')
                                ->map(fn($rows) => $rows->sum('amount_cents'));
                            $allocTotal  = $allocGroups->sum();
                        @endphp
                        <tr class="alloc-row"
                            data-payment-id="{{ $payment->id }}"
                            style="cursor:pointer"
                            onclick="openPaymentDetail({{ $payment->id }})">
                            <td>
                                <div class="pa-person">
                                    <span class="pa-avatar {{ $payment->loan_id ? '' : 'pa-avatar-unmatched' }}">{{ $initial }}</span>
                                    <div>
                                        <span class="pa-name">{{ $name }}</span>
                                        <span class="pa-sub">{{ $loanRef }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="pa-ref-main">{{ $ref }}</span>
                                <span class="pa-ref-sub">{{ $payment->loan?->loanProduct?->name ?? 'Repayment' }}</span>
                            </td>
                            <td>
                                <span class="pa-amount">KSh {{ number_format($payment->amount_cents / 100, 2) }}</span>
                                @if($payment->unapplied_cents > 0)
                                    <span class="pa-unapplied">KSh {{ number_format($payment->unapplied_cents / 100, 2) }} unapplied</span>
                                @endif
                            </td>

                            @if($view === 'allocations')
                            <td>
                                @if($allocGroups->isNotEmpty())
                                    <div class="pa-alloc-chips">
                                        @foreach($allocGroups as $type => $cents)
                                            @php
                                                $pct   = $allocTotal > 0 ? round(($cents / $allocTotal) * 100) : 0;
                                                $color = $typeColors[$type] ?? '#667085';
                                            @endphp
                                            <div class="pa-alloc-row">
                                                <span class="pa-alloc-dot" style="background:{{ $color }}"></span>
                                                <span class="pa-alloc-type">{{ ucfirst($type) }}</span>
                                                <div class="pa-alloc-bar-wrap">
                                                    <div class="pa-alloc-bar" style="width:{{ $pct }}%;background:{{ $color }}"></div>
                                                </div>
                                                <span class="pa-alloc-amount">KSh {{ number_format($cents / 100, 2) }}</span>
                                            </div>
                                        @endforeach
                                        <div class="pa-alloc-total">
                                            <span>Total</span>
                                            <strong>KSh {{ number_format($allocTotal / 100, 2) }}</strong>
                                        </div>
                                    </div>
                                @else
                                    <span style="font-size:10px;color:#98a2b3">No allocations yet</span>
                                @endif
                            </td>
                            @endif

                            <td>
                                @php
                                    $methodDot = match($payment->payment_method) {
                                        'mpesa'          => 'pa-method-dot',
                                        'bank_transfer'  => 'pa-method-dot pa-method-dot-bank',
                                        'cash'           => 'pa-method-dot pa-method-dot-cash',
                                        'credit_balance' => 'pa-method-dot pa-method-dot-credit',
                                        default          => 'pa-method-dot',
                                    };
                                    $methodLabel = match($payment->payment_method) {
                                        'mpesa'          => 'M-Pesa',
                                        'bank_transfer'  => 'Bank transfer',
                                        'cash'           => 'Cash',
                                        'credit_balance' => 'Credit balance',
                                        default          => ucfirst($payment->payment_method),
                                    };
                                @endphp
                                <span class="pa-method">
                                    <span class="{{ $methodDot }}"></span>
                                    {{ $methodLabel }}
                                </span>
                            </td>

                            <td>
                                <strong style="font-size:11.5px;color:#101828;display:block">{{ optional($ts)->format('d M Y') }}</strong>
                                <small style="font-size:10px;color:#98a2b3">{{ optional($ts)->format('H:i') }}</small>
                            </td>

                            <td>
                                <span class="pa-status pa-status-{{ $payment->status }}">{{ $payment->status }}</span>
                            </td>

                            <td>
                                <a href="{{ route('payments.edit', $payment) }}"
                                   class="pa-row-btn"
                                   title="Edit payment"
                                   onclick="event.stopPropagation()">
                                    <i class="bi bi-pencil" style="font-size:12px"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $view === 'allocations' ? 8 : 7 }}">
                                <div class="pa-empty">
                                    <div class="pa-empty-icon"><i class="bi bi-inbox"></i></div>
                                    <strong>No {{ strtolower($viewMeta['title']) }} yet</strong>
                                    <small>New records will appear here automatically as payments move through the workflow.</small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pagination-wrap">{{ $payments->links() }}</div>
        </section>

        {{-- ── Sidebar ─────────────────────────────────────────────────── --}}
        <aside class="payment-side-stack">
            {{-- Collection health --}}
            <section class="panel payment-insight">
                <div class="payment-insight-head">
                    <div>
                        <h2>Collection health</h2>
                        <p>Posted value vs received value.</p>
                    </div>
                    <span class="insight-icon"><i class="bi bi-activity"></i></span>
                </div>
                <div class="collection-ring">
                    <div class="collection-ring-copy">
                        <strong>{{ $stats['received'] ? number_format(($stats['posted'] / $stats['received']) * 100) : 0 }}%</strong>
                        <span>posted</span>
                    </div>
                </div>
                <div class="insight-foot">
                    <span>Received <strong>KSh {{ number_format($stats['received'] / 100) }}</strong></span>
                    <span>Posted <strong>KSh {{ number_format($stats['posted'] / 100) }}</strong></span>
                </div>
            </section>

            {{-- Payment mix --}}
            <section class="panel payment-insight">
                <div class="payment-insight-head">
                    <div>
                        <h2>Payment mix</h2>
                        <p>By method this period.</p>
                    </div>
                    <span class="insight-icon" style="background:#f5f0ff;color:#7f56d9"><i class="bi bi-pie-chart"></i></span>
                </div>
                <div class="payment-method-list">
                    @forelse($methodMix as $mix)
                        @php $pct = $stats['received'] ? round(($mix['cents'] / $stats['received']) * 100) : 0; @endphp
                        <div class="method-line">
                            <div class="method-line-top">
                                <span>{{ $mix['label'] }}</span>
                                <strong>{{ $pct }}%</strong>
                            </div>
                            <div class="method-track">
                                <span class="{{ $mix['key'] }}" style="width:{{ $pct }}%"></span>
                            </div>
                        </div>
                    @empty
                        <p style="font-size:10.5px;color:#98a2b3;margin:0">No payment activity in this period.</p>
                    @endforelse
                </div>
                <div class="quick-actions">
                    <a href="{{ route('loans.active') }}" class="quick-action">
                        View active loans <i class="bi bi-arrow-up-right"></i>
                    </a>
                    <a href="{{ route('borrowers.index') }}" class="quick-action">
                        Find a borrower <i class="bi bi-arrow-up-right"></i>
                    </a>
                </div>
            </section>
        </aside>
    </div>{{-- end .payments-layout --}}
</div>{{-- end .payments-page --}}

{{-- ═══ DETAIL DRAWER ══════════════════════════════════════════════════════ --}}
<div class="detail-overlay" id="detailOverlay" onclick="closePaymentDetail()"></div>
<div class="detail-drawer" id="detailDrawer" role="dialog" aria-modal="true" aria-labelledby="ddTitle">

    <div class="dd-head">
        <div class="dd-head-top">
            <div>
                <h2 class="dd-title" id="ddTitle">Payment Details</h2>
                <p class="dd-subtitle">See the full allocation breakdown for this payment.</p>
            </div>
            <button class="dd-close" onclick="closePaymentDetail()" aria-label="Close detail panel">
                <i class="bi bi-x"></i>
            </button>
        </div>
    </div>

    <div class="dd-body" id="ddBody">
        {{-- Content injected by JS --}}
        <div id="ddContent"></div>
    </div>

    <div class="dd-foot" id="ddFoot" style="display:none">
        <a href="#" class="dd-foot-btn" id="ddEditBtn">
            <i class="bi bi-pencil" style="font-size:11px"></i> Edit payment
        </a>
        <a href="#" class="dd-foot-btn dd-foot-btn-primary" id="ddReceiptBtn" target="_blank" rel="noopener">
            <i class="bi bi-receipt" style="font-size:11px"></i> View payment receipt
        </a>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Force drawer above sidebar on mobile */
@media(max-width:760px){
  .detail-drawer,.detail-overlay{z-index:151}
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    const overlay = document.getElementById('detailOverlay');
    const drawer  = document.getElementById('detailDrawer');
    const content = document.getElementById('ddContent');
    const foot    = document.getElementById('ddFoot');
    const editBtn = document.getElementById('ddEditBtn');
    const rcptBtn = document.getElementById('ddReceiptBtn');

    let activeRow = null;

    window.openPaymentDetail = function (id) {
        // Highlight active row
        document.querySelectorAll('.alloc-row').forEach(r => r.style.background = '');
        const row = document.querySelector(`.alloc-row[data-payment-id="${id}"]`);
        if (row) { row.style.background = '#f5f5ff'; activeRow = row; }

        // Open panel with skeleton loader
        overlay.classList.add('open');
        drawer.classList.add('open');
        foot.style.display = 'none';
        content.innerHTML = skeletonHTML();

        fetch(`/admin/payments/${id}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => {
            if (!r.ok) throw new Error('Request failed');
            return r.json();
        })
        .then(data => {
            content.innerHTML = buildDetail(data);
            editBtn.href  = data.edit_url || '#';
            rcptBtn.href  = data.receipt_url || '#';
            foot.style.display = 'flex';
        })
        .catch(() => {
            content.innerHTML = `<div style="padding:30px 0;text-align:center;color:#98a2b3;font-size:12px">
                <i class="bi bi-exclamation-circle" style="font-size:28px;display:block;margin-bottom:10px"></i>
                Could not load payment details.
            </div>`;
        });
    };

    window.closePaymentDetail = function () {
        overlay.classList.remove('open');
        drawer.classList.remove('open');
        if (activeRow) { activeRow.style.background = ''; activeRow = null; }
    };

    // ESC key
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closePaymentDetail(); });

    // ── Skeleton ──────────────────────────────────────────────────────────
    function skeletonHTML() {
        return `<div class="dd-skeleton" style="padding-top:18px">
            <div style="display:flex;align-items:center;gap:12px;padding-bottom:18px;border-bottom:1px solid var(--line)">
                <div style="width:42px;height:42px;border-radius:12px;background:#f0f2f5;flex-shrink:0"></div>
                <div style="flex:1"><div class="dd-skel-line" style="width:60%"></div><div class="dd-skel-line" style="width:40%;margin-bottom:0"></div></div>
            </div>
            <div style="padding:18px 0 16px;border-bottom:1px solid var(--line)">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    ${[1,2,3,4].map(() => `<div><div class="dd-skel-line" style="width:50%;height:8px"></div><div class="dd-skel-line" style="margin-bottom:0"></div></div>`).join('')}
                </div>
            </div>
            <div style="margin-top:20px"><div class="dd-skel-line" style="width:40%;height:8px"></div>
                <div style="height:120px;background:#f9fafb;border-radius:10px;border:1px solid var(--line)"></div>
            </div>
        </div>`;
    }

    // ── Main render ───────────────────────────────────────────────────────
    function buildDetail(d) {
        const statusClass = { posted: 'pa-status-posted', pending: 'pa-status-pending', processing: 'pa-status-processing', reversed: 'pa-status-reversed', failed: 'pa-status-failed' }[d.status] || '';
        const statusLabel = (d.status || '').charAt(0).toUpperCase() + (d.status || '').slice(1);

        // Allocation breakdown rows
        const allocRows = (d.allocations || []).map(a =>
            `<tr>
                <td><span class="dd-type-dot" style="background:${a.color}"></span>${a.type}</td>
                <td>${a.amount}</td>
                <td>${a.percentage}%</td>
            </tr>`
        ).join('');

        // Waterfall steps
        const waterfallSteps = (d.waterfall || []).map((step, i) => {
            const applied = step.has_amount;
            return `<div class="dd-wf-step">
                <div class="dd-wf-num ${applied ? 'applied' : ''}">${i + 1}</div>
                <div class="dd-wf-body">
                    <span class="dd-wf-label">${step.step}</span>
                    <span class="dd-wf-value ${applied ? 'has-amount' : ''}">${step.applied}</span>
                </div>
                ${applied
                    ? `<div class="dd-wf-check"><i class="bi bi-check2"></i></div>`
                    : `<div class="dd-wf-dash"><i class="bi bi-dash"></i></div>`
                }
            </div>`;
        }).join('');

        // Excess / unapplied row
        const excess = d.excess_unapplied || 'KSh 0.00';

        // Related loan card
        const loanCard = d.loan_ref ? `
            <div class="dd-section-label">Related loan</div>
            <a href="${d.edit_url || '#'}" class="dd-loan-card" onclick="event.stopPropagation()">
                <div class="dd-loan-icon"><i class="bi bi-bank2"></i></div>
                <div>
                    <span class="dd-loan-name">${d.loan_ref}</span>
                    <span class="dd-loan-product">${d.loan_product || ''}</span>
                </div>
                <div class="dd-loan-balance">
                    ${d.outstanding_cents !== null
                        ? `<strong>KSh ${Number(d.outstanding_cents / 100).toLocaleString('en-KE', {minimumFractionDigits:2})}</strong>
                           <span>Outstanding balance</span>
                           <span style="font-size:9px;color:#98a2b3">Term: ${d.loan_term || '—'}</span>`
                        : ''
                    }
                </div>
            </a>` : '';

        return `
        <div class="dd-identity">
            <div class="dd-id-avatar">${d.initial || '?'}</div>
            <div>
                <span class="dd-id-name">${d.name}</span>
                <span class="dd-id-loan">${d.loan_ref || 'No loan linked'}${d.loan_product ? ' · ' + d.loan_product : ''}</span>
            </div>
            <div class="dd-id-status">
                <span class="pa-status ${statusClass}">${statusLabel}</span>
            </div>
        </div>

        <div class="dd-meta-grid">
            <div class="dd-meta-item">
                <label>Payment reference</label>
                <span>${d.reference}</span>
            </div>
            <div class="dd-meta-item">
                <label>Method</label>
                <span>${d.method}</span>
            </div>
            <div class="dd-meta-item">
                <label>Received on</label>
                <span>${d.received_at || '—'}</span>
            </div>
            <div class="dd-meta-item">
                <label>Total amount</label>
                <span>KSh ${Number(d.amount_cents / 100).toLocaleString('en-KE', {minimumFractionDigits:2})}</span>
            </div>
            <div class="dd-meta-item">
                <label>Unapplied balance</label>
                <span style="color:${d.unapplied_cents > 0 ? '#dc6803' : '#067647'}">
                    KSh ${Number(d.unapplied_cents / 100).toLocaleString('en-KE', {minimumFractionDigits:2})}
                </span>
            </div>
            <div class="dd-meta-item">
                <label>Processed by</label>
                <span>${d.processed_by || 'System'}</span>
            </div>
        </div>

        ${allocRows ? `
        <div class="dd-section-label">
            Allocation breakdown
            <span>How this payment was applied (repayment waterfall)</span>
        </div>
        <table class="dd-alloc-table">
            <thead>
                <tr>
                    <th>Allocation type</th>
                    <th>Amount (KSh)</th>
                    <th style="text-align:right">Percentage</th>
                </tr>
            </thead>
            <tbody>${allocRows}</tbody>
            <tfoot>
                <tr>
                    <td>Total allocated</td>
                    <td>${d.total_allocated}</td>
                    <td style="text-align:right;color:var(--primary)">100.00%</td>
                </tr>
            </tfoot>
        </table>` : ''}

        ${waterfallSteps ? `
        <div class="dd-section-label">
            Repayment waterfall
            <span>Payment allocation follows this priority order</span>
        </div>
        <div class="dd-waterfall">${waterfallSteps}
            <div class="dd-wf-step">
                <div class="dd-wf-num">6</div>
                <div class="dd-wf-body">
                    <span class="dd-wf-label">Excess / Unapplied</span>
                    <span class="dd-wf-value ${d.unapplied_cents > 0 ? '' : 'has-amount'}">${excess}</span>
                </div>
                <div class="dd-wf-${d.unapplied_cents > 0 ? 'dash' : 'check'}">
                    <i class="bi bi-${d.unapplied_cents > 0 ? 'dash' : 'check2'}"></i>
                </div>
            </div>
        </div>` : ''}

        ${loanCard}
        `;
    }
})();
</script>
@endpush
