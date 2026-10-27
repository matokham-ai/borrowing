@extends('layouts.app')

@section('title', 'Disbursed Loans')

@push('styles')
<style>
/* ─── Page shell ──────────────────────────────────────────────────────────── */
.dis-page { max-width: 1560px; margin: 0 auto; }

/* ─── Header ──────────────────────────────────────────────────────────────── */
.dis-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    padding: 0 var(--pg) 22px; gap: 16px; flex-wrap: wrap;
}
.dis-header-left { display: flex; align-items: flex-start; gap: 14px; }
.dis-header-icon {
    width: 44px; height: 44px; border-radius: 13px; flex-shrink: 0;
    background: linear-gradient(135deg, #f0f1ff, #e0e1ff);
    color: var(--primary); font-size: 20px;
    display: grid; place-items: center; margin-top: 3px;
}
.dis-header h1 {
    font-size: 24px; font-weight: 760; color: #101828;
    letter-spacing: -.035em; margin: 0 0 4px; line-height: 1.15;
}
.dis-header p { font-size: 12px; color: #667085; margin: 0; }
.dis-header-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; margin-top: 4px; }

/* Period tabs */
.dis-period-tabs {
    display: flex; align-items: center;
    background: #f2f4f7; border-radius: 9px; padding: 3px; gap: 2px;
}
.dis-period-tab {
    height: 30px; padding: 0 12px; border: none; background: transparent;
    font-size: 11px; font-weight: 600; color: #667085; border-radius: 7px;
    cursor: pointer; text-decoration: none; display: inline-flex; align-items: center;
    transition: background .15s, color .15s, box-shadow .15s;
}
.dis-period-tab.active {
    background: #fff; color: var(--primary);
    box-shadow: 0 1px 4px rgba(16,24,40,.1);
}
.dis-period-tab:hover:not(.active) { background: rgba(255,255,255,.6); color: #344054; }
.dis-export-btn {
    height: 36px; padding: 0 16px; border-radius: 9px;
    border: 1px solid var(--line); background: #fff; color: #344054;
    font-size: 11.5px; font-weight: 650; cursor: pointer;
    display: inline-flex; align-items: center; gap: 7px;
    transition: border-color .14s, background .14s;
}
.dis-export-btn:hover { border-color: #c7c8f6; background: #fafaff; color: var(--primary); }

/* ─── KPI strip ───────────────────────────────────────────────────────────── */
.dis-kpi-strip {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 14px; padding: 0 var(--pg); margin-bottom: 20px;
}
.dis-kpi {
    background: #fff; border: 1px solid var(--line); border-radius: 14px;
    padding: 18px 20px 16px; position: relative; overflow: hidden;
    box-shadow: 0 1px 3px rgba(16,24,40,.04);
    transition: box-shadow .18s, transform .18s;
}
.dis-kpi:hover { box-shadow: 0 5px 18px rgba(16,24,40,.08); transform: translateY(-1px); }
.dis-kpi::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0;
    height: 3px; border-radius: 14px 14px 0 0;
}
.dis-kpi-indigo::before { background: linear-gradient(90deg, #6366f1, #8b5cf6); }
.dis-kpi-green::before  { background: linear-gradient(90deg, #10b981, #34d399); }
.dis-kpi-amber::before  { background: linear-gradient(90deg, #f79009, #fbbf24); }
.dis-kpi-red::before    { background: linear-gradient(90deg, #f04438, #f87171); }

.dis-kpi-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    margin-bottom: 12px;
}
.dis-kpi-icon {
    width: 38px; height: 38px; border-radius: 10px;
    display: grid; place-items: center; font-size: 17px; flex-shrink: 0;
}
.dis-kpi-icon-indigo { background: #eeefff; color: #5b5ce2; }
.dis-kpi-icon-green  { background: #ecfdf3; color: #067647; }
.dis-kpi-icon-amber  { background: #fffaeb; color: #b54708; }
.dis-kpi-icon-red    { background: #fef3f2; color: #b42318; }
.dis-kpi-label  { font-size: 11px; font-weight: 500; color: #667085; margin-bottom: 4px; }
.dis-kpi-value  { font-size: 24px; font-weight: 760; color: #101828; letter-spacing: -.04em; }
.dis-kpi-sub    { font-size: 10.5px; color: #98a2b3; margin-top: 4px; }
.dis-kpi-sub strong { color: #475467; font-weight: 650; }

/* ─── Main layout ─────────────────────────────────────────────────────────── */
.dis-layout {
    display: grid; grid-template-columns: minmax(0,1fr) 280px;
    gap: 16px; padding: 0 var(--pg); margin-bottom: 28px;
    align-items: start;
}

/* ─── Table panel ─────────────────────────────────────────────────────────── */
.dis-panel {
    background: #fff; border: 1px solid var(--line);
    border-radius: 16px; overflow: hidden;
    box-shadow: 0 1px 3px rgba(16,24,40,.04);
}
.dis-panel-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 20px 14px; border-bottom: 1px solid var(--line);
}
.dis-panel-head h2 { font-size: 13px; font-weight: 700; color: #101828; margin: 0; }
.dis-panel-head p  { font-size: 10px; color: #98a2b3; margin: 3px 0 0; }
.dis-result-pill {
    background: #f5f6f8; border: 1px solid var(--line);
    border-radius: 20px; padding: 4px 10px;
    font-size: 10px; font-weight: 650; color: #667085;
}

/* Filter bar */
.dis-filters {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 20px 14px; border-bottom: 1px solid var(--line);
    flex-wrap: wrap;
}
.dis-search {
    height: 36px; flex: 1; max-width: 340px;
    display: flex; align-items: center; gap: 8px;
    border: 1px solid var(--line); border-radius: 9px;
    padding: 0 12px; background: #fff;
    transition: border-color .14s, box-shadow .14s;
}
.dis-search:focus-within {
    border-color: #a5b4fc; box-shadow: 0 0 0 3px rgba(99,102,241,.08);
}
.dis-search i { color: #c0c8d8; font-size: 13px; flex-shrink: 0; }
.dis-search input {
    border: 0; outline: 0; min-width: 0; width: 100%;
    font-size: 11.5px; color: #344054; background: transparent;
}
.dis-filter-select {
    height: 36px; border: 1px solid var(--line); border-radius: 9px;
    background: #fff; color: #475467; padding: 0 10px;
    font-size: 11.5px; cursor: pointer; transition: border-color .14s;
}
.dis-filter-select:focus { outline: none; border-color: #a5b4fc; box-shadow: 0 0 0 3px rgba(99,102,241,.08); }
.dis-filter-btn {
    height: 36px; padding: 0 14px; border-radius: 9px;
    border: 1px solid var(--primary); background: var(--primary);
    color: #fff; font-size: 11.5px; font-weight: 650;
    cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
    transition: background .14s;
}
.dis-filter-btn:hover { background: #4a4bcf; }
.dis-clear-btn {
    height: 36px; padding: 0 12px; border-radius: 9px;
    border: 1px solid var(--line); background: #fff; color: #667085;
    font-size: 11.5px; font-weight: 600; cursor: pointer;
    text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
    transition: border-color .14s, color .14s;
}
.dis-clear-btn:hover { border-color: #c7c8f6; color: var(--primary); }

/* ─── Loans table ─────────────────────────────────────────────────────────── */
.dis-table { width: 100%; border-collapse: collapse; min-width: 820px; }
.dis-table thead tr { border-bottom: 2px solid var(--line); }
.dis-table th {
    padding: 10px 14px; font-size: 9.5px; font-weight: 700;
    letter-spacing: .07em; text-transform: uppercase;
    color: #98a2b3; text-align: left; white-space: nowrap;
}
.dis-table th:first-child { padding-left: 20px; }
.dis-table th:last-child  { padding-right: 20px; }
.dis-table tbody tr {
    border-bottom: 1px solid #f5f6f8;
    transition: background .12s; cursor: pointer;
}
.dis-table tbody tr:last-child { border-bottom: none; }
.dis-table tbody tr:hover { background: #fafbff; }
.dis-table td { padding: 13px 14px; vertical-align: middle; }
.dis-table td:first-child { padding-left: 20px; }
.dis-table td:last-child  { padding-right: 20px; }

/* Cell components */
.dis-person { display: flex; align-items: center; gap: 10px; }
.dis-avatar {
    width: 32px; height: 32px; border-radius: 9px;
    background: linear-gradient(135deg,#e0e1ff,#c7c8f6);
    color: #4b4fc4; font-size: 11px; font-weight: 750;
    display: grid; place-items: center; flex-shrink: 0;
}
.dis-name  { font-size: 12px; font-weight: 650; color: #101828; display: block; line-height: 1.3; }
.dis-sub   { font-size: 10px; color: #98a2b3; display: block; margin-top: 2px; }
.dis-amount {
    font-size: 13px; font-weight: 750; color: #101828;
    display: block; letter-spacing: -.02em;
    font-variant-numeric: tabular-nums;
}

/* Status badge — reuse txn-badge colour classes */
.txn-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 9.5px; font-weight: 700; padding: 4px 9px;
    border-radius: 20px; white-space: nowrap; letter-spacing: .02em;
}
.txn-badge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: currentColor; flex-shrink: 0; }
.txn-badge-posted     { background: #ecfdf3; color: #067647; }
.txn-badge-pending,
.txn-badge-processing { background: #fffaeb; color: #b54708; }
.txn-badge-failed,
.txn-badge-exception  { background: #fef3f2; color: #b42318; }
.txn-badge-reversed   { background: #f2f4f7; color: #344054; }

/* Row action */
.dis-row-btn {
    width: 30px; height: 30px; border-radius: 8px;
    display: grid; place-items: center;
    border: 1px solid var(--line); background: #fff;
    color: #98a2b3; cursor: pointer;
    transition: all .15s; text-decoration: none; font-size: 12px;
}
.dis-row-btn:hover { border-color: #c7c8f6; background: #f5f5ff; color: var(--primary); }

/* ─── Pagination ──────────────────────────────────────────────────────────── */
.dis-pagination { padding: 14px 20px; border-top: 1px solid var(--line); }

/* ─── Side panels ─────────────────────────────────────────────────────────── */
.dis-side-stack { display: flex; flex-direction: column; gap: 14px; }
.dis-side-card {
    background: #fff; border: 1px solid var(--line); border-radius: 16px;
    overflow: hidden; box-shadow: 0 1px 3px rgba(16,24,40,.04);
}
.dis-side-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 18px 12px; border-bottom: 1px solid var(--line);
}
.dis-side-head h3 { font-size: 12.5px; font-weight: 700; color: #101828; margin: 0; }
.dis-side-head p  { font-size: 10px; color: #98a2b3; margin: 3px 0 0; }

/* Status bars */
.dis-method-list { display: flex; flex-direction: column; gap: 14px; padding: 16px 18px; }
.dis-method-row {}
.dis-method-top {
    display: flex; justify-content: space-between; align-items: center;
    font-size: 11px; color: #475467; margin-bottom: 5px;
}
.dis-method-top strong { color: #101828; font-weight: 650; }
.dis-method-track {
    height: 7px; border-radius: 10px; background: #f0f2f5; overflow: hidden;
}
.dis-method-track span {
    display: block; height: 100%; border-radius: inherit;
    transition: width .5s cubic-bezier(.22,1,.36,1);
}
.dis-method-meta { font-size: 10px; color: #98a2b3; margin-top: 3px; }

/* Quick action list */
.dis-quick-list { display: flex; flex-direction: column; gap: 0; }
.dis-quick-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 18px; border-bottom: 1px solid #f5f6f8;
    text-decoration: none; transition: background .12s;
}
.dis-quick-item:last-child { border-bottom: none; }
.dis-quick-item:hover { background: #fafaff; }
.dis-quick-icon {
    width: 32px; height: 32px; border-radius: 9px;
    display: grid; place-items: center; font-size: 14px; flex-shrink: 0;
}
.dis-quick-icon-indigo { background: #eeefff; color: var(--primary); }
.dis-quick-icon-green  { background: #ecfdf3; color: #067647; }
.dis-quick-icon-amber  { background: #fffaeb; color: #b54708; }
.dis-quick-label { font-size: 11.5px; font-weight: 650; color: #101828; display: block; }
.dis-quick-sub   { font-size: 10px; color: #98a2b3; display: block; margin-top: 1px; }
.dis-quick-arrow { margin-left: auto; color: #c0c8d8; font-size: 13px; }

/* ─── Empty state ─────────────────────────────────────────────────────────── */
.dis-empty { text-align: center; padding: 48px 20px; }
.dis-empty-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: #f5f6f8; display: grid; place-items: center;
    margin: 0 auto 16px; font-size: 22px; color: #c0c8d8;
}
.dis-empty strong { display: block; font-size: 13px; color: #344054; margin-bottom: 6px; }
.dis-empty small  { font-size: 11px; color: #98a2b3; }

/* ─── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 1200px) {
    .dis-layout { grid-template-columns: 1fr; }
    .dis-side-stack { flex-direction: row; }
    .dis-kpi-strip { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 760px) {
    .dis-kpi-strip { grid-template-columns: 1fr 1fr; }
    .dis-side-stack { flex-direction: column; }
    .dis-search { max-width: none; flex-basis: 100%; order: -1; }
}
</style>
@endpush

@section('content')
<div class="dis-page">

    {{-- ═══ PAGE HEADER ════════════════════════════════════════════════════════ --}}
    <div class="dis-header">
        <div class="dis-header-left">
            <div class="dis-header-icon"><i class="bi bi-cash-coin"></i></div>
            <div>
                <div class="eyebrow" style="margin-bottom:5px">DISBURSED LOANS</div>
                <h1>Disbursed Loans</h1>
                <p>All loans that have been successfully disbursed</p>
            </div>
        </div>
        <div class="dis-header-actions">
            <div class="dis-period-tabs">
                @foreach(['today' => 'Today', '7' => '7 Days', '30' => '30 Days', '90' => '90 Days'] as $p => $label)
                    <a href="{{ route('finance.disbursed', array_merge(request()->query(), ['period' => $p])) }}"
                       class="dis-period-tab {{ $period === $p ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
            <button class="dis-export-btn" onclick="window.print()">
                <i class="bi bi-download"></i> Export
            </button>
        </div>
    </div>

    {{-- ═══ KPI STRIP ══════════════════════════════════════════════════════════ --}}
    <div class="dis-kpi-strip">
        <div class="dis-kpi dis-kpi-indigo">
            <div class="dis-kpi-top">
                <div class="dis-kpi-icon dis-kpi-icon-indigo"><i class="bi bi-cash-coin"></i></div>
            </div>
            <div class="dis-kpi-label">Total Disbursed</div>
            <div class="dis-kpi-value">KES {{ number_format($stats['total_disbursed_cents'] / 100) }}</div>
            <div class="dis-kpi-sub"><strong>{{ number_format($stats['total_count']) }}</strong> loans in period</div>
        </div>
        <div class="dis-kpi dis-kpi-green">
            <div class="dis-kpi-top">
                <div class="dis-kpi-icon dis-kpi-icon-green"><i class="bi bi-check2-circle"></i></div>
            </div>
            <div class="dis-kpi-label">Active Loans</div>
            <div class="dis-kpi-value">{{ number_format($stats['active_count']) }}</div>
            <div class="dis-kpi-sub">Currently repaying</div>
        </div>
        <div class="dis-kpi dis-kpi-amber">
            <div class="dis-kpi-top">
                <div class="dis-kpi-icon dis-kpi-icon-amber"><i class="bi bi-exclamation-circle-fill"></i></div>
            </div>
            <div class="dis-kpi-label">Overdue Loans</div>
            <div class="dis-kpi-value">{{ number_format($stats['overdue_count']) }}</div>
            <div class="dis-kpi-sub">Past due date</div>
        </div>
        <div class="dis-kpi dis-kpi-red">
            <div class="dis-kpi-top">
                <div class="dis-kpi-icon dis-kpi-icon-red"><i class="bi bi-x-circle-fill"></i></div>
            </div>
            <div class="dis-kpi-label">Defaulted</div>
            <div class="dis-kpi-value">{{ number_format($stats['defaulted_count']) }}</div>
            <div class="dis-kpi-sub">In default</div>
        </div>
    </div>

    {{-- ═══ MAIN LAYOUT ════════════════════════════════════════════════════════ --}}
    <div class="dis-layout">

        {{-- ── Loans table panel ───────────────────────────────────────────── --}}
        <div class="dis-panel">
            <div class="dis-panel-head">
                <div>
                    <h2>Disbursed Loans</h2>
                    <p>DISBURSED LOANS &nbsp;·&nbsp; {{ number_format($loans->total()) }} records</p>
                </div>
                <span class="dis-result-pill">{{ number_format($loans->total()) }} records</span>
            </div>

            {{-- Filters --}}
            <form method="GET" class="dis-filters" id="disFilterForm">
                <input type="hidden" name="period" value="{{ $period }}">
                <div class="dis-search">
                    <i class="bi bi-search"></i>
                    <input type="search" name="search" value="{{ $search }}"
                           placeholder="Search name, loan ID, reference…">
                </div>
                <select name="status" class="dis-filter-select">
                    <option value="">All statuses</option>
                    @foreach(['active' => 'Active', 'overdue' => 'Overdue', 'defaulted' => 'Defaulted', 'completed' => 'Completed'] as $val => $lbl)
                        <option value="{{ $val }}" @selected($status === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
                <select name="product" class="dis-filter-select">
                    <option value="">All products</option>
                    @foreach($loanProducts as $lp)
                        <option value="{{ $lp->code }}" @selected($product === $lp->code)>{{ $lp->name }}</option>
                    @endforeach
                </select>
                <button class="dis-filter-btn" type="submit"><i class="bi bi-funnel"></i> Filter</button>
                @if($search || $status || $product)
                    <a href="{{ route('finance.disbursed', ['period' => $period]) }}" class="dis-clear-btn">
                        <i class="bi bi-x"></i> Clear
                    </a>
                @endif
            </form>

            {{-- Table --}}
            <div style="overflow-x:auto">
                <table class="dis-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Product</th>
                            <th>Principal</th>
                            <th>Total Payable</th>
                            <th>Disbursed At</th>
                            <th>Matures At</th>
                            <th>Status</th>
                            <th aria-label="Actions" style="width:44px"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($loans as $loan)
                        @php
                            $borrower   = $loan->borrower;
                            $name       = $borrower?->user?->name ?? '—';
                            $initial    = strtoupper(substr($name, 0, 1));
                            $loanRef    = 'Loan #' . str_pad($loan->id, 7, '0', STR_PAD_LEFT);
                            $badgeClass = match($loan->status) {
                                'active'                => 'txn-badge-posted',
                                'overdue'               => 'txn-badge-pending',
                                'defaulted'             => 'txn-badge-exception',
                                'completed'             => 'txn-badge-reversed',
                                'disbursement_pending'  => 'txn-badge-processing',
                                'written_off'           => 'txn-badge-reversed',
                                default                 => 'txn-badge-pending',
                            };
                            $badgeLabel = match($loan->status) {
                                'active'                => 'Active',
                                'overdue'               => 'Overdue',
                                'defaulted'             => 'Defaulted',
                                'completed'             => 'Completed',
                                'disbursement_pending'  => 'Pending',
                                'written_off'           => 'Written Off',
                                default                 => ucfirst($loan->status ?? '—'),
                            };
                            $rowHref = $loan->loan_application_id
                                ? route('applications.show', $loan->loan_application_id)
                                : '#';
                        @endphp
                        <tr onclick="window.location='{{ $rowHref }}'">
                            <td>
                                <div class="dis-person">
                                    <span class="dis-avatar">{{ $initial }}</span>
                                    <div>
                                        <span class="dis-name">{{ $name }}</span>
                                        <span class="dis-sub">{{ $loanRef }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span style="font-size:12px; font-weight:600; color:#344054">
                                    {{ $loan->loanProduct?->name ?? '—' }}
                                </span>
                            </td>
                            <td>
                                <span class="dis-amount">KES {{ number_format($loan->principal_cents / 100) }}</span>
                            </td>
                            <td>
                                <span class="dis-amount">KES {{ number_format($loan->total_payable_cents / 100) }}</span>
                            </td>
                            <td>
                                <span style="font-size:11.5px; font-weight:600; color:#101828; display:block">
                                    {{ $loan->disbursed_at?->format('d M Y') ?? '—' }}
                                </span>
                                <span style="font-size:10px; color:#98a2b3">{{ $loan->disbursed_at?->format('H:i') }}</span>
                            </td>
                            <td>
                                <span style="font-size:11.5px; font-weight:600; color:#101828; display:block">
                                    {{ $loan->matures_at?->format('d M Y') ?? '—' }}
                                </span>
                            </td>
                            <td>
                                <span class="txn-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                            </td>
                            <td onclick="event.stopPropagation()">
                                @if($loan->loan_application_id)
                                    <a href="{{ route('applications.show', $loan->loan_application_id) }}"
                                       class="dis-row-btn" title="View">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                @else
                                    <span class="dis-row-btn" style="cursor:default; opacity:.4">
                                        <i class="bi bi-chevron-right"></i>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="dis-empty">
                                    <div class="dis-empty-icon"><i class="bi bi-cash-coin"></i></div>
                                    <strong>No disbursed loans found</strong>
                                    <small>Try adjusting your filters or selecting a wider period.</small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($loans->hasPages())
                <div class="dis-pagination">
                    {{ $loans->links() }}
                </div>
            @endif
        </div>

        {{-- ── Sidebar ──────────────────────────────────────────────────────── --}}
        <div class="dis-side-stack">

            {{-- Portfolio by Status --}}
            <div class="dis-side-card">
                <div class="dis-side-head">
                    <div>
                        <h3>Portfolio by Status</h3>
                        <p>Loan status breakdown</p>
                    </div>
                </div>
                <div class="dis-method-list">
                    @php
                        $totalForBars = max(1, $stats['total_count']);
                        $statusBars = [
                            ['label' => 'Active',    'count' => $stats['active_count'],    'color' => '#10b981'],
                            ['label' => 'Overdue',   'count' => $stats['overdue_count'],   'color' => '#f79009'],
                            ['label' => 'Defaulted', 'count' => $stats['defaulted_count'], 'color' => '#f04438'],
                            ['label' => 'Completed', 'count' => $stats['completed_count'], 'color' => '#98a2b3'],
                        ];
                    @endphp
                    @foreach($statusBars as $bar)
                        @php $pct = round(($bar['count'] / $totalForBars) * 100); @endphp
                        <div class="dis-method-row">
                            <div class="dis-method-top">
                                <span>{{ $bar['label'] }}</span>
                                <strong>{{ number_format($bar['count']) }}</strong>
                            </div>
                            <div class="dis-method-track">
                                <span style="width:{{ $pct }}%; background:{{ $bar['color'] }}"></span>
                            </div>
                            <div class="dis-method-meta">{{ $pct }}% of portfolio</div>
                        </div>
                    @endforeach
                    @if($stats['total_count'] === 0)
                        <p style="font-size:11px; color:#98a2b3; text-align:center; padding:12px 0">No data for this period.</p>
                    @endif
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="dis-side-card">
                <div class="dis-side-head">
                    <div><h3>Quick Actions</h3></div>
                </div>
                <div class="dis-quick-list">
                    <a href="{{ route('applications.create') }}" class="dis-quick-item">
                        <div class="dis-quick-icon dis-quick-icon-indigo"><i class="bi bi-plus-circle-fill"></i></div>
                        <div>
                            <span class="dis-quick-label">New Application</span>
                            <span class="dis-quick-sub">Start a new loan application</span>
                        </div>
                        <i class="bi bi-arrow-right dis-quick-arrow"></i>
                    </a>
                    <a href="{{ route('loans.active') }}" class="dis-quick-item">
                        <div class="dis-quick-icon dis-quick-icon-green"><i class="bi bi-bank2"></i></div>
                        <div>
                            <span class="dis-quick-label">Active Loans</span>
                            <span class="dis-quick-sub">View all active loans</span>
                        </div>
                        <i class="bi bi-arrow-right dis-quick-arrow"></i>
                    </a>
                    <a href="{{ route('finance.overview') }}" class="dis-quick-item">
                        <div class="dis-quick-icon dis-quick-icon-amber"><i class="bi bi-grid-fill"></i></div>
                        <div>
                            <span class="dis-quick-label">Finance Overview</span>
                            <span class="dis-quick-sub">Finance cockpit dashboard</span>
                        </div>
                        <i class="bi bi-arrow-right dis-quick-arrow"></i>
                    </a>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
