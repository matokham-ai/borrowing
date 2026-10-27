@extends('layouts.app')

@section('title', 'Payment Reversals')

@push('styles')
<style>
/* ─── Page shell ──────────────────────────────────────────────────────────── */
.rev2-page { max-width: 1560px; margin: 0 auto; }

/* ─── Breadcrumb ──────────────────────────────────────────────────────────── */
.rev2-breadcrumb {
    display: flex; align-items: center; gap: 6px;
    padding: 0 var(--pg) 14px; font-size: 11px; color: #98a2b3; font-weight: 500;
}
.rev2-breadcrumb a { color: #667085; text-decoration: none; transition: color .14s; }
.rev2-breadcrumb a:hover { color: var(--primary); }
.rev2-breadcrumb i { font-size: 9px; color: #c0c8d8; }
.rev2-breadcrumb span { color: #344054; font-weight: 600; }

/* ─── Header ──────────────────────────────────────────────────────────────── */
.rev2-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    padding: 0 var(--pg) 22px; gap: 16px; flex-wrap: wrap;
}
.rev2-header-left { display: flex; align-items: flex-start; gap: 14px; }
.rev2-header-icon {
    width: 44px; height: 44px; border-radius: 13px; flex-shrink: 0;
    background: linear-gradient(135deg, #fff0ee, #fecdca);
    color: #d92d20; font-size: 20px;
    display: grid; place-items: center; margin-top: 3px;
}
.rev2-header h1 {
    font-size: 24px; font-weight: 760; color: #101828;
    letter-spacing: -.035em; margin: 0 0 4px; line-height: 1.15;
}
.rev2-header p { font-size: 12px; color: #667085; margin: 0; }
.rev2-header-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; margin-top: 4px; }

/* Period tabs */
.rev2-period-tabs {
    display: flex; align-items: center;
    background: #f2f4f7; border-radius: 9px; padding: 3px; gap: 2px;
}
.rev2-period-tab {
    height: 30px; padding: 0 12px; border: none; background: transparent;
    font-size: 11px; font-weight: 600; color: #667085; border-radius: 7px;
    cursor: pointer; text-decoration: none; display: inline-flex; align-items: center;
    transition: background .15s, color .15s, box-shadow .15s;
}
.rev2-period-tab.active {
    background: #fff; color: var(--primary);
    box-shadow: 0 1px 4px rgba(16,24,40,.1);
}
.rev2-period-tab:hover:not(.active) { background: rgba(255,255,255,.6); color: #344054; }

/* ─── KPI strip ───────────────────────────────────────────────────────────── */
.rev2-kpi-strip {
    display: grid; grid-template-columns: repeat(3, 1fr);
    gap: 14px; padding: 0 var(--pg); margin-bottom: 22px;
}
.rev2-kpi {
    background: #fff; border: 1px solid var(--line); border-radius: 16px;
    padding: 20px 22px 18px; position: relative; overflow: hidden;
    box-shadow: 0 1px 3px rgba(16,24,40,.04);
    transition: box-shadow .18s, transform .18s;
}
.rev2-kpi:hover { box-shadow: 0 5px 18px rgba(16,24,40,.08); transform: translateY(-1px); }
.rev2-kpi::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0;
    height: 3px; border-radius: 16px 16px 0 0;
}
.rev2-kpi-red::before    { background: linear-gradient(90deg, #f04438, #f87171); }
.rev2-kpi-amber::before  { background: linear-gradient(90deg, #f79009, #fbbf24); }
.rev2-kpi-indigo::before { background: linear-gradient(90deg, #6366f1, #8b5cf6); }

.rev2-kpi-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    margin-bottom: 14px;
}
.rev2-kpi-icon {
    width: 40px; height: 40px; border-radius: 11px;
    display: grid; place-items: center; font-size: 18px; flex-shrink: 0;
}
.rev2-kpi-icon-red    { background: #fef3f2; color: #b42318; }
.rev2-kpi-icon-amber  { background: #fffaeb; color: #b54708; }
.rev2-kpi-icon-indigo { background: #eeefff; color: #5b5ce2; }

/* Sparkline */
.rev2-sparkline {
    display: flex; align-items: flex-end; gap: 3px; height: 36px; flex-shrink: 0;
}
.rev2-spark-bar {
    flex: 1; border-radius: 3px 3px 0 0; min-height: 4px;
    background: rgba(240,68,56,.2);
    transition: background .15s;
}
.rev2-sparkline:hover .rev2-spark-bar { background: rgba(240,68,56,.38); }

.rev2-kpi-label { font-size: 11px; font-weight: 500; color: #667085; margin-bottom: 4px; }
.rev2-kpi-value { font-size: 26px; font-weight: 760; color: #101828; letter-spacing: -.04em; line-height: 1.1; }
.rev2-kpi-sub   { font-size: 10.5px; color: #98a2b3; margin-top: 4px; }

/* ─── Layout ──────────────────────────────────────────────────────────────── */
.rev2-layout {
    display: grid; grid-template-columns: minmax(0,1fr) 280px;
    gap: 16px; padding: 0 var(--pg); margin-bottom: 28px;
    align-items: start;
}

/* ─── Table panel ─────────────────────────────────────────────────────────── */
.rev2-panel {
    background: #fff; border: 1px solid var(--line);
    border-radius: 16px; overflow: hidden;
    box-shadow: 0 1px 3px rgba(16,24,40,.04);
}
.rev2-panel-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 20px 14px; border-bottom: 1px solid var(--line);
}
.rev2-panel-head h2 { font-size: 13px; font-weight: 700; color: #101828; margin: 0; }
.rev2-panel-head p  { font-size: 10px; color: #98a2b3; margin: 3px 0 0; }
.rev2-result-pill {
    background: #f5f6f8; border: 1px solid var(--line);
    border-radius: 20px; padding: 4px 10px;
    font-size: 10px; font-weight: 650; color: #667085;
}

/* Filters */
.rev2-filters {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 20px 14px; border-bottom: 1px solid var(--line);
    flex-wrap: wrap;
}
.rev2-search {
    height: 36px; flex: 1; max-width: 340px;
    display: flex; align-items: center; gap: 8px;
    border: 1px solid var(--line); border-radius: 9px;
    padding: 0 12px; background: #fff;
    transition: border-color .14s, box-shadow .14s;
}
.rev2-search:focus-within { border-color: #a5b4fc; box-shadow: 0 0 0 3px rgba(99,102,241,.08); }
.rev2-search i { color: #c0c8d8; font-size: 13px; flex-shrink: 0; }
.rev2-search input { border: 0; outline: 0; min-width: 0; width: 100%; font-size: 11.5px; color: #344054; background: transparent; }
.rev2-filter-select {
    height: 36px; border: 1px solid var(--line); border-radius: 9px;
    background: #fff; color: #475467; padding: 0 10px; font-size: 11.5px;
    cursor: pointer; transition: border-color .14s;
}
.rev2-filter-select:focus { outline: none; border-color: #a5b4fc; }
.rev2-filter-btn {
    height: 36px; padding: 0 14px; border-radius: 9px;
    border: 1px solid var(--primary); background: var(--primary);
    color: #fff; font-size: 11.5px; font-weight: 650;
    cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
    transition: background .14s;
}
.rev2-filter-btn:hover { background: #4a4bcf; }
.rev2-clear-btn {
    height: 36px; padding: 0 12px; border-radius: 9px;
    border: 1px solid var(--line); background: #fff; color: #667085;
    font-size: 11.5px; font-weight: 600; cursor: pointer;
    text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
    transition: border-color .14s, color .14s;
}
.rev2-clear-btn:hover { border-color: #c7c8f6; color: var(--primary); }

/* ─── Table ───────────────────────────────────────────────────────────────── */
.rev2-table { width: 100%; border-collapse: collapse; min-width: 700px; }
.rev2-table thead tr { border-bottom: 2px solid var(--line); }
.rev2-table th {
    padding: 10px 14px; font-size: 9.5px; font-weight: 700;
    letter-spacing: .07em; text-transform: uppercase;
    color: #98a2b3; text-align: left; white-space: nowrap;
}
.rev2-table th:first-child { padding-left: 20px; }
.rev2-table th:last-child  { padding-right: 20px; }
.rev2-table tbody tr { border-bottom: 1px solid #f5f6f8; transition: background .12s; }
.rev2-table tbody tr:last-child { border-bottom: none; }
.rev2-table tbody tr:hover { background: #fff8f8; }
.rev2-table td { padding: 13px 14px; vertical-align: middle; }
.rev2-table td:first-child { padding-left: 20px; }
.rev2-table td:last-child  { padding-right: 20px; }

/* Cell components */
.rev2-person { display: flex; align-items: center; gap: 10px; }
.rev2-avatar {
    width: 32px; height: 32px; border-radius: 9px;
    background: linear-gradient(135deg, #fff0ee, #fecdca);
    color: #d92d20; font-size: 11px; font-weight: 750;
    display: grid; place-items: center; flex-shrink: 0;
}
.rev2-name   { font-size: 12px; font-weight: 650; color: #101828; display: block; }
.rev2-sub    { font-size: 10px; color: #98a2b3; display: block; margin-top: 2px; }
.rev2-ref    { font-size: 11px; font-weight: 650; color: #344054; font-family: monospace; display: block; }
.rev2-amount { font-size: 13px; font-weight: 750; color: #b42318; display: block; letter-spacing: -.02em; font-variant-numeric: tabular-nums; }
.rev2-method { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; color: #475467; }
.rev2-method-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }

.rev2-reason {
    display: inline-block; max-width: 180px;
    font-size: 10.5px; font-weight: 600; color: #b54708;
    background: #fffaeb; border: 1px solid #fde68a;
    border-radius: 6px; padding: 3px 8px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    vertical-align: middle;
}
.rev2-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 9.5px; font-weight: 700; padding: 4px 9px;
    border-radius: 20px; background: #fef3f2; color: #b42318;
    white-space: nowrap; letter-spacing: .02em;
}
.rev2-badge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: currentColor; flex-shrink: 0; }

.rev2-row-btn {
    width: 30px; height: 30px; border-radius: 8px; display: grid; place-items: center;
    border: 1px solid var(--line); background: #fff; color: #98a2b3;
    cursor: pointer; transition: all .15s; text-decoration: none; font-size: 12px;
}
.rev2-row-btn:hover { border-color: #c7c8f6; background: #f5f5ff; color: var(--primary); }

/* ─── Pagination ──────────────────────────────────────────────────────────── */
.rev2-pagination { padding: 14px 20px; border-top: 1px solid var(--line); }

/* ─── Sidebar ─────────────────────────────────────────────────────────────── */
.rev2-side-stack { display: flex; flex-direction: column; gap: 14px; }
.rev2-side-card {
    background: #fff; border: 1px solid var(--line);
    border-radius: 16px; overflow: hidden;
    box-shadow: 0 1px 3px rgba(16,24,40,.04);
}
.rev2-side-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 18px 12px; border-bottom: 1px solid var(--line);
}
.rev2-side-head h3 { font-size: 12.5px; font-weight: 700; color: #101828; margin: 0; }
.rev2-side-head p  { font-size: 10px; color: #98a2b3; margin: 3px 0 0; }

/* Info list */
.rev2-info-list { display: flex; flex-direction: column; }
.rev2-info-item {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 13px 18px; border-bottom: 1px solid #f5f6f8;
}
.rev2-info-item:last-child { border-bottom: none; }
.rev2-info-icon {
    width: 30px; height: 30px; border-radius: 8px;
    display: grid; place-items: center; font-size: 13px; flex-shrink: 0;
}
.rev2-info-icon-red    { background: #fef3f2; color: #b42318; }
.rev2-info-icon-amber  { background: #fffaeb; color: #b54708; }
.rev2-info-icon-indigo { background: #eeefff; color: #5b5ce2; }
.rev2-info-title { font-size: 11.5px; font-weight: 650; color: #101828; display: block; margin-bottom: 2px; }
.rev2-info-body  { font-size: 10.5px; color: #667085; line-height: 1.55; display: block; }

/* Quick link list */
.rev2-quick-list { display: flex; flex-direction: column; }
.rev2-quick-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 18px; border-bottom: 1px solid #f5f6f8;
    text-decoration: none; transition: background .12s;
}
.rev2-quick-item:last-child { border-bottom: none; }
.rev2-quick-item:hover { background: #fafaff; }
.rev2-quick-icon {
    width: 32px; height: 32px; border-radius: 9px;
    display: grid; place-items: center; font-size: 14px; flex-shrink: 0;
}
.rev2-quick-icon-indigo { background: #eeefff; color: var(--primary); }
.rev2-quick-icon-green  { background: #ecfdf3; color: #067647; }
.rev2-quick-icon-amber  { background: #fffaeb; color: #b54708; }
.rev2-quick-label { font-size: 11.5px; font-weight: 650; color: #101828; display: block; }
.rev2-quick-sub   { font-size: 10px; color: #98a2b3; display: block; margin-top: 1px; }
.rev2-quick-arrow { margin-left: auto; color: #c0c8d8; font-size: 13px; }

/* ─── Empty state ─────────────────────────────────────────────────────────── */
.rev2-empty { text-align: center; padding: 48px 20px; }
.rev2-empty-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: #ecfdf3; display: grid; place-items: center;
    margin: 0 auto 16px; font-size: 22px; color: #067647;
}
.rev2-empty strong { display: block; font-size: 13px; color: #344054; margin-bottom: 6px; }
.rev2-empty small  { font-size: 11px; color: #98a2b3; }

/* ─── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 1200px) {
    .rev2-layout { grid-template-columns: 1fr; }
    .rev2-side-stack { flex-direction: row; flex-wrap: wrap; }
    .rev2-side-card { flex: 1; min-width: 240px; }
    .rev2-kpi-strip { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 680px) {
    .rev2-kpi-strip { grid-template-columns: 1fr; }
    .rev2-side-stack { flex-direction: column; }
}
</style>
@endpush

@section('content')
<div class="rev2-page">

    {{-- ═══ BREADCRUMB ══════════════════════════════════════════════════════════ --}}
    <div class="rev2-breadcrumb">
        <a href="{{ route('finance.overview') }}">Finance</a>
        <i class="bi bi-chevron-right"></i>
        <span>Reversals</span>
    </div>

    {{-- ═══ PAGE HEADER ════════════════════════════════════════════════════════ --}}
    <div class="rev2-header">
        <div class="rev2-header-left">
            <div class="rev2-header-icon"><i class="bi bi-arrow-counterclockwise"></i></div>
            <div>
                <div class="eyebrow" style="margin-bottom:5px">AUDIT HISTORY</div>
                <h1>Payment Reversals</h1>
                <p>Payments removed from active loan balances and their full audit trail</p>
            </div>
        </div>
        <div class="rev2-header-actions">
            <div class="rev2-period-tabs">
                @foreach(['today' => 'Today', '7' => '7 Days', '30' => '30 Days', '90' => '90 Days'] as $p => $label)
                    <a href="{{ route('finance.reversals', array_merge(request()->query(), ['period' => $p])) }}"
                       class="rev2-period-tab {{ $period === $p ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
            <button class="btn btn-light" onclick="window.print()">
                <i class="bi bi-download"></i> Export
            </button>
        </div>
    </div>

    {{-- ═══ KPI STRIP ══════════════════════════════════════════════════════════ --}}
    <div class="rev2-kpi-strip">

        {{-- Total reversed --}}
        <div class="rev2-kpi rev2-kpi-red">
            <div class="rev2-kpi-top">
                <div class="rev2-kpi-icon rev2-kpi-icon-red">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </div>
                <div class="rev2-sparkline">
                    @foreach($stats['sparkline'] as $h)
                        <div class="rev2-spark-bar" style="height:{{ $h }}%"></div>
                    @endforeach
                </div>
            </div>
            <div class="rev2-kpi-label">Total Reversed</div>
            <div class="rev2-kpi-value">KES {{ number_format($stats['total_cents'] / 100) }}</div>
            <div class="rev2-kpi-sub">{{ number_format($stats['count']) }} {{ Str::plural('transaction', $stats['count']) }}</div>
        </div>

        {{-- Average reversal --}}
        <div class="rev2-kpi rev2-kpi-amber">
            <div class="rev2-kpi-top">
                <div class="rev2-kpi-icon rev2-kpi-icon-amber">
                    <i class="bi bi-calculator"></i>
                </div>
            </div>
            <div class="rev2-kpi-label">Average Reversal</div>
            <div class="rev2-kpi-value">
                KES {{ $stats['count'] > 0 ? number_format($stats['avg_cents'] / 100) : '0' }}
            </div>
            <div class="rev2-kpi-sub">Per reversal in selected period</div>
        </div>

        {{-- Count --}}
        <div class="rev2-kpi rev2-kpi-indigo">
            <div class="rev2-kpi-top">
                <div class="rev2-kpi-icon rev2-kpi-icon-indigo">
                    <i class="bi bi-hash"></i>
                </div>
            </div>
            <div class="rev2-kpi-label">Reversal Count</div>
            <div class="rev2-kpi-value">{{ number_format($stats['count']) }}</div>
            <div class="rev2-kpi-sub">In the selected period</div>
        </div>

    </div>

    {{-- ═══ MAIN LAYOUT ════════════════════════════════════════════════════════ --}}
    <div class="rev2-layout">

        {{-- ── Reversals table ────────────────────────────────────────────── --}}
        <div class="rev2-panel">
            <div class="rev2-panel-head">
                <div>
                    <h2>Reversal History</h2>
                    <p>AUDIT HISTORY &nbsp;·&nbsp; {{ number_format($reversals->total()) }} records</p>
                </div>
                <span class="rev2-result-pill">{{ number_format($reversals->total()) }} records</span>
            </div>

            {{-- Filters --}}
            <form method="GET" class="rev2-filters">
                <input type="hidden" name="period" value="{{ $period }}">
                <div class="rev2-search">
                    <i class="bi bi-search"></i>
                    <input type="search" name="search" value="{{ $search }}"
                           placeholder="Search name, receipt, reason…">
                </div>
                <select name="method" class="rev2-filter-select">
                    <option value="">All channels</option>
                    @foreach(['mpesa' => 'M-Pesa', 'bank_transfer' => 'Bank Transfer', 'cash' => 'Cash'] as $val => $lbl)
                        <option value="{{ $val }}" @selected($method === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
                <button class="rev2-filter-btn" type="submit">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                @if($search || $method)
                    <a href="{{ route('finance.reversals', ['period' => $period]) }}" class="rev2-clear-btn">
                        <i class="bi bi-x"></i> Clear
                    </a>
                @endif
            </form>

            {{-- Table --}}
            <div style="overflow-x:auto">
                <table class="rev2-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Reference</th>
                            <th>Amount</th>
                            <th>Channel</th>
                            <th>Reversed</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th aria-label="Actions" style="width:44px"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($reversals as $rev)
                        @php
                            $borrower    = $rev->borrower ?? $rev->loan?->borrower;
                            $name        = $borrower?->user?->name ?? 'Unknown client';
                            $initial     = strtoupper(substr($name, 0, 1));
                            $loanRef     = $rev->loan
                                ? 'Loan #' . str_pad($rev->loan->id, 7, '0', STR_PAD_LEFT)
                                : 'No loan linked';
                            $ref         = $rev->mpesa_receipt_number
                                ?: $rev->external_reference
                                ?: ('REV-' . str_pad($rev->id, 6, '0', STR_PAD_LEFT));
                            $reason      = $rev->reversal_reason ?: 'Manual administrator reversal';
                            $dotColor    = match($rev->payment_method) {
                                'mpesa'         => '#12b76a',
                                'bank_transfer' => '#7f56d9',
                                'cash'          => '#f79009',
                                default         => '#98a2b3',
                            };
                            $methodLabel = match($rev->payment_method) {
                                'mpesa'          => 'M-Pesa',
                                'bank_transfer'  => 'Bank Transfer',
                                'cash'           => 'Cash',
                                'credit_balance' => 'Credit Balance',
                                default          => ucfirst($rev->payment_method ?? '—'),
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="rev2-person">
                                    <span class="rev2-avatar">{{ $initial }}</span>
                                    <div>
                                        <span class="rev2-name">{{ $name }}</span>
                                        <span class="rev2-sub">{{ $loanRef }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="rev2-ref">{{ Str::limit($ref, 16) }}</span>
                                <span class="rev2-sub">{{ $rev->loan?->loanProduct?->name ?? 'Repayment' }}</span>
                            </td>
                            <td>
                                <span class="rev2-amount">−KES {{ number_format($rev->amount_cents / 100) }}</span>
                            </td>
                            <td>
                                <span class="rev2-method">
                                    <span class="rev2-method-dot" style="background:{{ $dotColor }}"></span>
                                    {{ $methodLabel }}
                                </span>
                            </td>
                            <td>
                                <span style="font-size:11.5px; font-weight:600; color:#101828; display:block">
                                    {{ $rev->reversed_at?->format('d M Y') }}
                                </span>
                                <span style="font-size:10px; color:#98a2b3">
                                    {{ $rev->reversed_at?->format('H:i') }}
                                    &nbsp;·&nbsp; {{ $rev->reversed_at?->diffForHumans() }}
                                </span>
                            </td>
                            <td>
                                <span class="rev2-reason" title="{{ $reason }}">{{ $reason }}</span>
                            </td>
                            <td>
                                <span class="rev2-badge">Reversed</span>
                            </td>
                            <td>
                                <a href="{{ route('payments.edit', $rev) }}" class="rev2-row-btn" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="rev2-empty">
                                    <div class="rev2-empty-icon"><i class="bi bi-check2-circle"></i></div>
                                    <strong>No reversals found</strong>
                                    <small>No payments have been reversed in this period.</small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($reversals->hasPages())
                <div class="rev2-pagination">
                    {{ $reversals->links() }}
                </div>
            @endif
        </div>

        {{-- ── Sidebar ─────────────────────────────────────────────────────── --}}
        <div class="rev2-side-stack">

            {{-- What is a reversal? --}}
            <div class="rev2-side-card">
                <div class="rev2-side-head">
                    <div><h3>About Reversals</h3></div>
                </div>
                <div class="rev2-info-list">
                    <div class="rev2-info-item">
                        <div class="rev2-info-icon rev2-info-icon-red">
                            <i class="bi bi-shield-exclamation"></i>
                        </div>
                        <div>
                            <span class="rev2-info-title">Permanently reversed</span>
                            <span class="rev2-info-body">
                                Reversed payments are removed from loan balances and cannot be re-applied.
                            </span>
                        </div>
                    </div>
                    <div class="rev2-info-item">
                        <div class="rev2-info-icon rev2-info-icon-amber">
                            <i class="bi bi-journal-text"></i>
                        </div>
                        <div>
                            <span class="rev2-info-title">Audit preserved</span>
                            <span class="rev2-info-body">
                                Every reversal is logged with its reason and the administrator who performed it.
                            </span>
                        </div>
                    </div>
                    <div class="rev2-info-item">
                        <div class="rev2-info-icon rev2-info-icon-indigo">
                            <i class="bi bi-arrow-repeat"></i>
                        </div>
                        <div>
                            <span class="rev2-info-title">Re-record if needed</span>
                            <span class="rev2-info-body">
                                If a reversal was made in error, record a new manual payment on the loan.
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick links --}}
            <div class="rev2-side-card">
                <div class="rev2-side-head">
                    <div><h3>Quick Actions</h3></div>
                </div>
                <div class="rev2-quick-list">
                    <a href="{{ route('payments.reversals') }}" class="rev2-quick-item">
                        <div class="rev2-quick-icon rev2-quick-icon-indigo">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </div>
                        <div>
                            <span class="rev2-quick-label">Payments Reversals</span>
                            <span class="rev2-quick-sub">Full reversal operations view</span>
                        </div>
                        <i class="bi bi-arrow-right rev2-quick-arrow"></i>
                    </a>
                    <a href="{{ route('payments.create') }}" class="rev2-quick-item">
                        <div class="rev2-quick-icon rev2-quick-icon-green">
                            <i class="bi bi-plus-circle-fill"></i>
                        </div>
                        <div>
                            <span class="rev2-quick-label">Record Payment</span>
                            <span class="rev2-quick-sub">Add a new manual payment</span>
                        </div>
                        <i class="bi bi-arrow-right rev2-quick-arrow"></i>
                    </a>
                    <a href="{{ route('finance.reconciliation') }}" class="rev2-quick-item">
                        <div class="rev2-quick-icon rev2-quick-icon-amber">
                            <i class="bi bi-check2-all"></i>
                        </div>
                        <div>
                            <span class="rev2-quick-label">Reconciliation</span>
                            <span class="rev2-quick-sub">Review pending matches</span>
                        </div>
                        <i class="bi bi-arrow-right rev2-quick-arrow"></i>
                    </a>
                    <a href="{{ route('finance.reports') }}" class="rev2-quick-item">
                        <div class="rev2-quick-icon rev2-quick-icon-indigo">
                            <i class="bi bi-bar-chart-line-fill"></i>
                        </div>
                        <div>
                            <span class="rev2-quick-label">Financial Reports</span>
                            <span class="rev2-quick-sub">Reversals report tab</span>
                        </div>
                        <i class="bi bi-arrow-right rev2-quick-arrow"></i>
                    </a>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
