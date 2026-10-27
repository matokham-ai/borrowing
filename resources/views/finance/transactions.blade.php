@extends('layouts.app')

@section('title', 'All Transactions')

@push('styles')
<style>
/* ─── Page shell ──────────────────────────────────────────────────────────── */
.txn-page { max-width: 1560px; margin: 0 auto; }

/* ─── Header ──────────────────────────────────────────────────────────────── */
.txn-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    padding: 0 var(--pg) 22px; gap: 16px; flex-wrap: wrap;
}
.txn-header-left { display: flex; align-items: flex-start; gap: 14px; }
.txn-header-icon {
    width: 44px; height: 44px; border-radius: 13px; flex-shrink: 0;
    background: linear-gradient(135deg, #f0f1ff, #e0e1ff);
    color: var(--primary); font-size: 20px;
    display: grid; place-items: center; margin-top: 3px;
}
.txn-header h1 {
    font-size: 24px; font-weight: 760; color: #101828;
    letter-spacing: -.035em; margin: 0 0 4px; line-height: 1.15;
}
.txn-header p { font-size: 12px; color: #667085; margin: 0; }
.txn-header-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; margin-top: 4px; }

/* Period tabs */
.txn-period-tabs {
    display: flex; align-items: center;
    background: #f2f4f7; border-radius: 9px; padding: 3px; gap: 2px;
}
.txn-period-tab {
    height: 30px; padding: 0 12px; border: none; background: transparent;
    font-size: 11px; font-weight: 600; color: #667085; border-radius: 7px;
    cursor: pointer; text-decoration: none; display: inline-flex; align-items: center;
    transition: background .15s, color .15s, box-shadow .15s;
}
.txn-period-tab.active {
    background: #fff; color: var(--primary);
    box-shadow: 0 1px 4px rgba(16,24,40,.1);
}
.txn-period-tab:hover:not(.active) { background: rgba(255,255,255,.6); color: #344054; }
.txn-export-btn {
    height: 36px; padding: 0 16px; border-radius: 9px;
    border: 1px solid var(--line); background: #fff; color: #344054;
    font-size: 11.5px; font-weight: 650; cursor: pointer;
    display: inline-flex; align-items: center; gap: 7px;
    transition: border-color .14s, background .14s;
}
.txn-export-btn:hover { border-color: #c7c8f6; background: #fafaff; color: var(--primary); }

/* ─── KPI strip ───────────────────────────────────────────────────────────── */
.txn-kpi-strip {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 14px; padding: 0 var(--pg); margin-bottom: 20px;
}
.txn-kpi {
    background: #fff; border: 1px solid var(--line); border-radius: 14px;
    padding: 18px 20px 16px; position: relative; overflow: hidden;
    box-shadow: 0 1px 3px rgba(16,24,40,.04);
    transition: box-shadow .18s, transform .18s;
}
.txn-kpi:hover { box-shadow: 0 5px 18px rgba(16,24,40,.08); transform: translateY(-1px); }
.txn-kpi::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0;
    height: 3px; border-radius: 14px 14px 0 0;
}
.txn-kpi-indigo::before { background: linear-gradient(90deg, #6366f1, #8b5cf6); }
.txn-kpi-green::before  { background: linear-gradient(90deg, #10b981, #34d399); }
.txn-kpi-amber::before  { background: linear-gradient(90deg, #f79009, #fbbf24); }
.txn-kpi-red::before    { background: linear-gradient(90deg, #f04438, #f87171); }

.txn-kpi-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    margin-bottom: 12px;
}
.txn-kpi-icon {
    width: 38px; height: 38px; border-radius: 10px;
    display: grid; place-items: center; font-size: 17px; flex-shrink: 0;
}
.txn-kpi-icon-indigo { background: #eeefff; color: #5b5ce2; }
.txn-kpi-icon-green  { background: #ecfdf3; color: #067647; }
.txn-kpi-icon-amber  { background: #fffaeb; color: #b54708; }
.txn-kpi-icon-red    { background: #fef3f2; color: #b42318; }
.txn-kpi-label  { font-size: 11px; font-weight: 500; color: #667085; margin-bottom: 4px; }
.txn-kpi-value  { font-size: 24px; font-weight: 760; color: #101828; letter-spacing: -.04em; }
.txn-kpi-sub    { font-size: 10.5px; color: #98a2b3; margin-top: 4px; }
.txn-kpi-sub strong { color: #475467; font-weight: 650; }

/* ─── Main layout ─────────────────────────────────────────────────────────── */
.txn-layout {
    display: grid; grid-template-columns: minmax(0,1fr) 280px;
    gap: 16px; padding: 0 var(--pg); margin-bottom: 28px;
    align-items: start;
}

/* ─── Table panel ─────────────────────────────────────────────────────────── */
.txn-panel {
    background: #fff; border: 1px solid var(--line);
    border-radius: 16px; overflow: hidden;
    box-shadow: 0 1px 3px rgba(16,24,40,.04);
}
.txn-panel-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 20px 14px; border-bottom: 1px solid var(--line);
}
.txn-panel-head h2 { font-size: 13px; font-weight: 700; color: #101828; margin: 0; }
.txn-panel-head p  { font-size: 10px; color: #98a2b3; margin: 3px 0 0; }
.txn-result-pill {
    background: #f5f6f8; border: 1px solid var(--line);
    border-radius: 20px; padding: 4px 10px;
    font-size: 10px; font-weight: 650; color: #667085;
}

/* Filter bar */
.txn-filters {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 20px 14px; border-bottom: 1px solid var(--line);
    flex-wrap: wrap;
}
.txn-search {
    height: 36px; flex: 1; max-width: 340px;
    display: flex; align-items: center; gap: 8px;
    border: 1px solid var(--line); border-radius: 9px;
    padding: 0 12px; background: #fff;
    transition: border-color .14s, box-shadow .14s;
}
.txn-search:focus-within {
    border-color: #a5b4fc; box-shadow: 0 0 0 3px rgba(99,102,241,.08);
}
.txn-search i { color: #c0c8d8; font-size: 13px; flex-shrink: 0; }
.txn-search input {
    border: 0; outline: 0; min-width: 0; width: 100%;
    font-size: 11.5px; color: #344054; background: transparent;
}
.txn-filter-select {
    height: 36px; border: 1px solid var(--line); border-radius: 9px;
    background: #fff; color: #475467; padding: 0 10px;
    font-size: 11.5px; cursor: pointer; transition: border-color .14s;
}
.txn-filter-select:focus { outline: none; border-color: #a5b4fc; box-shadow: 0 0 0 3px rgba(99,102,241,.08); }
.txn-filter-btn {
    height: 36px; padding: 0 14px; border-radius: 9px;
    border: 1px solid var(--primary); background: var(--primary);
    color: #fff; font-size: 11.5px; font-weight: 650;
    cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
    transition: background .14s;
}
.txn-filter-btn:hover { background: #4a4bcf; }
.txn-clear-btn {
    height: 36px; padding: 0 12px; border-radius: 9px;
    border: 1px solid var(--line); background: #fff; color: #667085;
    font-size: 11.5px; font-weight: 600; cursor: pointer;
    text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
    transition: border-color .14s, color .14s;
}
.txn-clear-btn:hover { border-color: #c7c8f6; color: var(--primary); }

/* ─── Transaction table ───────────────────────────────────────────────────── */
.txn-table { width: 100%; border-collapse: collapse; min-width: 720px; }
.txn-table thead tr { border-bottom: 2px solid var(--line); }
.txn-table th {
    padding: 10px 14px; font-size: 9.5px; font-weight: 700;
    letter-spacing: .07em; text-transform: uppercase;
    color: #98a2b3; text-align: left; white-space: nowrap;
}
.txn-table th:first-child { padding-left: 20px; }
.txn-table th:last-child  { padding-right: 20px; }
.txn-table tbody tr {
    border-bottom: 1px solid #f5f6f8;
    transition: background .12s; cursor: pointer;
}
.txn-table tbody tr:last-child { border-bottom: none; }
.txn-table tbody tr:hover { background: #fafbff; }
.txn-table td { padding: 13px 14px; vertical-align: middle; }
.txn-table td:first-child { padding-left: 20px; }
.txn-table td:last-child  { padding-right: 20px; }

/* Cell components */
.txn-person { display: flex; align-items: center; gap: 10px; }
.txn-avatar {
    width: 32px; height: 32px; border-radius: 9px;
    background: linear-gradient(135deg,#e0e1ff,#c7c8f6);
    color: #4b4fc4; font-size: 11px; font-weight: 750;
    display: grid; place-items: center; flex-shrink: 0;
}
.txn-avatar-unmatched { background: linear-gradient(135deg,#f0f2f5,#e4e7ec); color: #667085; }
.txn-name  { font-size: 12px; font-weight: 650; color: #101828; display: block; line-height: 1.3; }
.txn-sub   { font-size: 10px; color: #98a2b3; display: block; margin-top: 2px; }
.txn-ref   { font-size: 11px; font-weight: 650; color: #344054; display: block; font-family: monospace; letter-spacing: .01em; }
.txn-amount {
    font-size: 13px; font-weight: 750; color: #101828;
    display: block; letter-spacing: -.02em;
    font-variant-numeric: tabular-nums;
}
.txn-unapplied {
    font-size: 9.5px; color: #dc6803; font-weight: 600;
    background: #fff5e8; border-radius: 5px; padding: 2px 6px;
    display: inline-block; margin-top: 3px;
}
.txn-method {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 11px; font-weight: 600; color: #475467;
}
.txn-method-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }

/* Status badge */
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
.txn-row-btn {
    width: 30px; height: 30px; border-radius: 8px;
    display: grid; place-items: center;
    border: 1px solid var(--line); background: #fff;
    color: #98a2b3; cursor: pointer;
    transition: all .15s; text-decoration: none; font-size: 12px;
}
.txn-row-btn:hover { border-color: #c7c8f6; background: #f5f5ff; color: var(--primary); }

/* ─── Pagination ──────────────────────────────────────────────────────────── */
.txn-pagination { padding: 14px 20px; border-top: 1px solid var(--line); }

/* ─── Side panels ─────────────────────────────────────────────────────────── */
.txn-side-stack { display: flex; flex-direction: column; gap: 14px; }
.txn-side-card {
    background: #fff; border: 1px solid var(--line); border-radius: 16px;
    overflow: hidden; box-shadow: 0 1px 3px rgba(16,24,40,.04);
}
.txn-side-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 18px 12px; border-bottom: 1px solid var(--line);
}
.txn-side-head h3 { font-size: 12.5px; font-weight: 700; color: #101828; margin: 0; }
.txn-side-head p  { font-size: 10px; color: #98a2b3; margin: 3px 0 0; }
.txn-view-link {
    font-size: 11px; font-weight: 650; color: var(--primary);
    text-decoration: none; display: inline-flex; align-items: center; gap: 4px;
    transition: opacity .14s;
}
.txn-view-link:hover { opacity: .75; }

/* Method bars */
.txn-method-list { display: flex; flex-direction: column; gap: 14px; padding: 16px 18px; }
.txn-method-row {}
.txn-method-top {
    display: flex; justify-content: space-between; align-items: center;
    font-size: 11px; color: #475467; margin-bottom: 5px;
}
.txn-method-top strong { color: #101828; font-weight: 650; }
.txn-method-track {
    height: 7px; border-radius: 10px; background: #f0f2f5; overflow: hidden;
}
.txn-method-track span {
    display: block; height: 100%; border-radius: inherit;
    transition: width .5s cubic-bezier(.22,1,.36,1);
}
.txn-method-meta { font-size: 10px; color: #98a2b3; margin-top: 3px; }

/* Quick action list */
.txn-quick-list { display: flex; flex-direction: column; gap: 0; }
.txn-quick-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 18px; border-bottom: 1px solid #f5f6f8;
    text-decoration: none; transition: background .12s;
}
.txn-quick-item:last-child { border-bottom: none; }
.txn-quick-item:hover { background: #fafaff; }
.txn-quick-icon {
    width: 32px; height: 32px; border-radius: 9px;
    display: grid; place-items: center; font-size: 14px; flex-shrink: 0;
}
.txn-quick-icon-indigo { background: #eeefff; color: var(--primary); }
.txn-quick-icon-green  { background: #ecfdf3; color: #067647; }
.txn-quick-icon-amber  { background: #fffaeb; color: #b54708; }
.txn-quick-label { font-size: 11.5px; font-weight: 650; color: #101828; display: block; }
.txn-quick-sub   { font-size: 10px; color: #98a2b3; display: block; margin-top: 1px; }
.txn-quick-arrow { margin-left: auto; color: #c0c8d8; font-size: 13px; }

/* ─── Empty state ─────────────────────────────────────────────────────────── */
.txn-empty { text-align: center; padding: 48px 20px; }
.txn-empty-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: #f5f6f8; display: grid; place-items: center;
    margin: 0 auto 16px; font-size: 22px; color: #c0c8d8;
}
.txn-empty strong { display: block; font-size: 13px; color: #344054; margin-bottom: 6px; }
.txn-empty small  { font-size: 11px; color: #98a2b3; }

/* ─── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 1200px) {
    .txn-layout { grid-template-columns: 1fr; }
    .txn-side-stack { flex-direction: row; }
    .txn-kpi-strip { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 760px) {
    .txn-kpi-strip { grid-template-columns: 1fr 1fr; }
    .txn-side-stack { flex-direction: column; }
    .txn-search { max-width: none; flex-basis: 100%; order: -1; }
}
</style>
@endpush

@section('content')
<div class="txn-page">

    {{-- ═══ PAGE HEADER ════════════════════════════════════════════════════════ --}}
    <div class="txn-header">
        <div class="txn-header-left">
            <div class="txn-header-icon"><i class="bi bi-list-ul"></i></div>
            <div>
                <div class="eyebrow" style="margin-bottom:5px">TRANSACTION LEDGER</div>
                <h1>All Transactions</h1>
                <p>Complete record of every payment received across all channels</p>
            </div>
        </div>
        <div class="txn-header-actions">
            <div class="txn-period-tabs">
                @foreach(['today' => 'Today', '7' => '7 Days', '30' => '30 Days', '90' => '90 Days'] as $p => $label)
                    <a href="{{ route('finance.transactions', array_merge(request()->query(), ['period' => $p])) }}"
                       class="txn-period-tab {{ $period === $p ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
            <button class="txn-export-btn" onclick="window.print()">
                <i class="bi bi-download"></i> Export
            </button>
        </div>
    </div>

    {{-- ═══ KPI STRIP ══════════════════════════════════════════════════════════ --}}
    <div class="txn-kpi-strip">
        <div class="txn-kpi txn-kpi-indigo">
            <div class="txn-kpi-top">
                <div class="txn-kpi-icon txn-kpi-icon-indigo"><i class="bi bi-arrow-down-left-circle-fill"></i></div>
            </div>
            <div class="txn-kpi-label">Total Received</div>
            <div class="txn-kpi-value">KES {{ number_format($stats['total'] / 100) }}</div>
            <div class="txn-kpi-sub">All incoming payments</div>
        </div>
        <div class="txn-kpi txn-kpi-green">
            <div class="txn-kpi-top">
                <div class="txn-kpi-icon txn-kpi-icon-green"><i class="bi bi-check2-circle"></i></div>
            </div>
            <div class="txn-kpi-label">Successfully Posted</div>
            <div class="txn-kpi-value">KES {{ number_format($stats['posted'] / 100) }}</div>
            <div class="txn-kpi-sub">
                <strong>{{ $stats['total'] > 0 ? number_format(($stats['posted'] / $stats['total']) * 100, 1) : '0.0' }}%</strong>
                of total received
            </div>
        </div>
        <div class="txn-kpi txn-kpi-amber">
            <div class="txn-kpi-top">
                <div class="txn-kpi-icon txn-kpi-icon-amber"><i class="bi bi-hourglass-split"></i></div>
            </div>
            <div class="txn-kpi-label">Awaiting Review</div>
            <div class="txn-kpi-value">{{ number_format($stats['pending']) }}</div>
            <div class="txn-kpi-sub">Pending or processing</div>
        </div>
        <div class="txn-kpi txn-kpi-red">
            <div class="txn-kpi-top">
                <div class="txn-kpi-icon txn-kpi-icon-red"><i class="bi bi-arrow-counterclockwise"></i></div>
            </div>
            <div class="txn-kpi-label">Reversed</div>
            <div class="txn-kpi-value">KES {{ number_format($stats['reversed'] / 100) }}</div>
            <div class="txn-kpi-sub">Removed from balances</div>
        </div>
    </div>

    {{-- ═══ MAIN LAYOUT ════════════════════════════════════════════════════════ --}}
    <div class="txn-layout">

        {{-- ── Transaction table panel ────────────────────────────────────── --}}
        <div class="txn-panel">
            <div class="txn-panel-head">
                <div>
                    <h2>Transaction Ledger</h2>
                    <p>TRANSACTION LEDGER &nbsp;·&nbsp; {{ number_format($transactions->total()) }} records</p>
                </div>
                <span class="txn-result-pill">{{ number_format($transactions->total()) }} records</span>
            </div>

            {{-- Filters --}}
            <form method="GET" class="txn-filters" id="txnFilterForm">
                <input type="hidden" name="period" value="{{ $period }}">
                <div class="txn-search">
                    <i class="bi bi-search"></i>
                    <input type="search" name="search" value="{{ $search }}"
                           placeholder="Search name, receipt, loan ID…">
                </div>
                <select name="status" class="txn-filter-select">
                    <option value="">All statuses</option>
                    @foreach(['posted' => 'Posted', 'pending' => 'Pending', 'processing' => 'Processing', 'reversed' => 'Reversed', 'failed' => 'Failed'] as $val => $lbl)
                        <option value="{{ $val }}" @selected($status === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
                <select name="method" class="txn-filter-select">
                    <option value="">All channels</option>
                    @foreach(['mpesa' => 'M-Pesa', 'bank_transfer' => 'Bank Transfer', 'cash' => 'Cash', 'credit_balance' => 'Credit Balance'] as $val => $lbl)
                        <option value="{{ $val }}" @selected($method === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
                <button class="txn-filter-btn" type="submit"><i class="bi bi-funnel"></i> Filter</button>
                @if($search || $status || $method)
                    <a href="{{ route('finance.transactions', ['period' => $period]) }}" class="txn-clear-btn">
                        <i class="bi bi-x"></i> Clear
                    </a>
                @endif
            </form>

            {{-- Table --}}
            <div style="overflow-x:auto">
                <table class="txn-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Reference</th>
                            <th>Amount</th>
                            <th>Channel</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th aria-label="Actions" style="width:44px"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($transactions as $tx)
                        @php
                            $borrower = $tx->borrower ?? $tx->loan?->borrower;
                            $name     = $borrower?->user?->name ?? 'Unmatched payment';
                            $initial  = strtoupper(substr($name, 0, 1));
                            $loanRef  = $tx->loan
                                ? 'Loan #' . str_pad($tx->loan->id, 7, '0', STR_PAD_LEFT)
                                : 'No loan linked';
                            $ref = $tx->mpesa_receipt_number ?: $tx->external_reference ?: ('TXN-' . str_pad($tx->id, 6, '0', STR_PAD_LEFT));
                            $dotColor = match($tx->payment_method) {
                                'mpesa'          => '#12b76a',
                                'bank_transfer'  => '#7f56d9',
                                'cash'           => '#f79009',
                                'credit_balance' => '#3538cd',
                                default          => '#98a2b3',
                            };
                            $methodLabel = match($tx->payment_method) {
                                'mpesa'          => 'M-Pesa',
                                'bank_transfer'  => 'Bank Transfer',
                                'cash'           => 'Cash',
                                'credit_balance' => 'Credit Balance',
                                default          => ucfirst($tx->payment_method ?? '—'),
                            };
                            $badgeClass = match($tx->status) {
                                'posted'               => 'txn-badge-posted',
                                'pending'              => 'txn-badge-pending',
                                'processing'           => 'txn-badge-processing',
                                'reversed'             => 'txn-badge-reversed',
                                'failed'               => 'txn-badge-exception',
                                default                => 'txn-badge-pending',
                            };
                            $badgeLabel = match($tx->status) {
                                'posted'      => 'Posted',
                                'pending'     => 'Pending',
                                'processing'  => 'Processing',
                                'reversed'    => 'Reversed',
                                'failed'      => 'Failed',
                                default       => ucfirst($tx->status),
                            };
                        @endphp
                        <tr onclick="window.location='{{ route('payments.edit', $tx) }}'">
                            <td>
                                <div class="txn-person">
                                    <span class="txn-avatar {{ $tx->loan_id ? '' : 'txn-avatar-unmatched' }}">{{ $initial }}</span>
                                    <div>
                                        <span class="txn-name">{{ $name }}</span>
                                        <span class="txn-sub">{{ $loanRef }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="txn-ref">{{ Str::limit($ref, 16) }}</span>
                                <span class="txn-sub">{{ $tx->loan?->loanProduct?->name ?? 'Repayment' }}</span>
                            </td>
                            <td>
                                <span class="txn-amount">KES {{ number_format($tx->amount_cents / 100) }}</span>
                                @if($tx->unapplied_cents > 0)
                                    <span class="txn-unapplied">
                                        KES {{ number_format($tx->unapplied_cents / 100) }} unapplied
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="txn-method">
                                    <span class="txn-method-dot" style="background:{{ $dotColor }}"></span>
                                    {{ $methodLabel }}
                                </span>
                            </td>
                            <td>
                                <span style="font-size:11.5px; font-weight:600; color:#101828; display:block">
                                    {{ $tx->received_at?->format('d M Y') }}
                                </span>
                                <span style="font-size:10px; color:#98a2b3">{{ $tx->received_at?->format('H:i') }}</span>
                            </td>
                            <td>
                                <span class="txn-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                            </td>
                            <td onclick="event.stopPropagation()">
                                <a href="{{ route('payments.edit', $tx) }}" class="txn-row-btn" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="txn-empty">
                                    <div class="txn-empty-icon"><i class="bi bi-list-ul"></i></div>
                                    <strong>No transactions found</strong>
                                    <small>Try adjusting your filters or selecting a wider period.</small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($transactions->hasPages())
                <div class="txn-pagination">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>

        {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
        <div class="txn-side-stack">

            {{-- Collections by channel --}}
            <div class="txn-side-card">
                <div class="txn-side-head">
                    <div>
                        <h3>By Channel</h3>
                        <p>Collection method breakdown</p>
                    </div>
                </div>
                <div class="txn-method-list">
                    @php
                        $channelConfig = [
                            'mpesa'          => ['label' => 'M-Pesa',         'color' => '#12b76a'],
                            'bank_transfer'  => ['label' => 'Bank Transfer',  'color' => '#7f56d9'],
                            'cash'           => ['label' => 'Cash',           'color' => '#f79009'],
                            'credit_balance' => ['label' => 'Credit Balance', 'color' => '#3538cd'],
                        ];
                        $channelBreakdown = $transactions->getCollection()
                            ->groupBy('payment_method')
                            ->map(fn($g) => $g->sum('amount_cents'));
                        $channelTotal = $channelBreakdown->sum() ?: 1;
                    @endphp
                    @foreach($channelConfig as $key => $cfg)
                        @php $cents = $channelBreakdown[$key] ?? 0; $pct = round(($cents / $channelTotal) * 100); @endphp
                        @if($cents > 0)
                        <div class="txn-method-row">
                            <div class="txn-method-top">
                                <span>{{ $cfg['label'] }}</span>
                                <strong>{{ $pct }}%</strong>
                            </div>
                            <div class="txn-method-track">
                                <span style="width:{{ $pct }}%; background:{{ $cfg['color'] }}"></span>
                            </div>
                            <div class="txn-method-meta">KES {{ number_format($cents / 100) }}</div>
                        </div>
                        @endif
                    @endforeach
                    @if($channelBreakdown->isEmpty())
                        <p style="font-size:11px; color:#98a2b3; text-align:center; padding:12px 0">No data for this period.</p>
                    @endif
                </div>
            </div>

            {{-- Quick actions --}}
            <div class="txn-side-card">
                <div class="txn-side-head">
                    <div><h3>Quick Actions</h3></div>
                </div>
                <div class="txn-quick-list">
                    <a href="{{ route('payments.create') }}" class="txn-quick-item">
                        <div class="txn-quick-icon txn-quick-icon-indigo"><i class="bi bi-plus-circle-fill"></i></div>
                        <div>
                            <span class="txn-quick-label">Record Payment</span>
                            <span class="txn-quick-sub">Add a manual transaction</span>
                        </div>
                        <i class="bi bi-arrow-right txn-quick-arrow"></i>
                    </a>
                    <a href="{{ route('payments.index', ['view' => 'unmatched']) }}" class="txn-quick-item">
                        <div class="txn-quick-icon txn-quick-icon-amber"><i class="bi bi-link-45deg"></i></div>
                        <div>
                            <span class="txn-quick-label">Match Payments</span>
                            <span class="txn-quick-sub">Link unmatched receipts</span>
                        </div>
                        <i class="bi bi-arrow-right txn-quick-arrow"></i>
                    </a>
                    <a href="{{ route('finance.reconciliation') }}" class="txn-quick-item">
                        <div class="txn-quick-icon txn-quick-icon-green"><i class="bi bi-check2-all"></i></div>
                        <div>
                            <span class="txn-quick-label">Reconciliation</span>
                            <span class="txn-quick-sub">Review pending matches</span>
                        </div>
                        <i class="bi bi-arrow-right txn-quick-arrow"></i>
                    </a>
                    <a href="{{ route('finance.reversals') }}" class="txn-quick-item">
                        <div class="txn-quick-icon txn-quick-icon-amber"><i class="bi bi-arrow-counterclockwise"></i></div>
                        <div>
                            <span class="txn-quick-label">View Reversals</span>
                            <span class="txn-quick-sub">Audit reversal history</span>
                        </div>
                        <i class="bi bi-arrow-right txn-quick-arrow"></i>
                    </a>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
