@extends('layouts.app')

@section('title', 'Finance Overview')

@push('styles')
<style>
/* ─── Page shell ──────────────────────────────────────────────────────────── */
.fin-page { max-width: 1560px; margin: 0 auto; }

/* ─── Page header ─────────────────────────────────────────────────────────── */
.fin-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    padding: 0 var(--pg) 22px; gap: 16px; flex-wrap: wrap;
}
.fin-header-left { display: flex; align-items: flex-start; gap: 14px; }
.fin-header-icon {
    width: 44px; height: 44px; border-radius: 13px; flex-shrink: 0;
    background: linear-gradient(135deg, #e0e1ff, #c7c8f6);
    color: var(--primary); font-size: 20px;
    display: grid; place-items: center; margin-top: 3px;
}
.fin-header h1 {
    font-size: 24px; font-weight: 760; color: #101828;
    letter-spacing: -.035em; margin: 0 0 4px; line-height: 1.15;
}
.fin-header p { font-size: 12px; color: #667085; margin: 0; }
.fin-header-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; margin-top: 4px; }

/* Period pill tabs */
.period-tabs {
    display: flex; align-items: center;
    background: #f2f4f7; border-radius: 9px; padding: 3px; gap: 2px;
}
.period-tab {
    height: 30px; padding: 0 12px; border: none; background: transparent;
    font-size: 11px; font-weight: 600; color: #667085; border-radius: 7px;
    cursor: pointer; text-decoration: none; display: inline-flex; align-items: center;
    transition: background .15s, color .15s, box-shadow .15s;
}
.period-tab.active, .period-tab:hover {
    background: #fff; color: #101828;
    box-shadow: 0 1px 4px rgba(16,24,40,.1);
}
.period-tab.active { color: var(--primary); }

/* Export btn */
.fin-export-btn {
    height: 36px; padding: 0 16px; border-radius: 9px;
    border: 1px solid var(--line); background: #fff; color: #344054;
    font-size: 11.5px; font-weight: 650; cursor: pointer;
    display: inline-flex; align-items: center; gap: 7px;
    transition: border-color .14s, background .14s;
}
.fin-export-btn:hover { border-color: #c7c8f6; background: #fafaff; color: var(--primary); }

/* ─── KPI banner ──────────────────────────────────────────────────────────── */
.fin-kpi-banner {
    margin: 0 var(--pg) 18px;
    background: #fff; border: 1px solid var(--line);
    border-radius: 18px; overflow: hidden;
    box-shadow: 0 1px 4px rgba(16,24,40,.05);
}
.fin-kpi-grid {
    display: grid; grid-template-columns: repeat(5, 1fr);
}
.fin-kpi-cell {
    padding: 26px 28px 22px; position: relative;
    border-right: 1px solid var(--line);
    transition: background .15s;
}
.fin-kpi-cell:last-child { border-right: none; }
.fin-kpi-cell:hover { background: #fafbff; }
.fin-kpi-top {
    display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;
}
.fin-kpi-icon {
    width: 38px; height: 38px; border-radius: 10px;
    display: grid; place-items: center; font-size: 17px; flex-shrink: 0;
}
.fin-kpi-icon-green  { background: #ecfdf3; color: #067647; }
.fin-kpi-icon-blue   { background: #eef4ff; color: #175cd3; }
.fin-kpi-icon-amber  { background: #fffaeb; color: #b54708; }
.fin-kpi-icon-red    { background: #fef3f2; color: #b42318; }
.fin-kpi-icon-teal   { background: #e6fef8; color: #0d7459; }
.fin-kpi-badge {
    font-size: 10px; font-weight: 700; padding: 3px 8px;
    border-radius: 20px; white-space: nowrap;
    display: inline-flex; align-items: center; gap: 4px;
}
.fin-kpi-badge-up   { background: #ecfdf3; color: #067647; }
.fin-kpi-badge-down { background: #fef3f2; color: #b42318; }
.fin-kpi-badge-neu  { background: #f2f4f7; color: #667085; }
.fin-kpi-label {
    font-size: 10.5px; font-weight: 600; color: #667085;
    text-transform: uppercase; letter-spacing: .06em; margin-bottom: 6px;
}
.fin-kpi-value {
    font-size: 28px; font-weight: 760; color: #101828;
    letter-spacing: -.04em; line-height: 1; margin-bottom: 6px;
}
.fin-kpi-sub { font-size: 11px; color: #98a2b3; margin: 0; }
.fin-kpi-sub strong { color: #475467; font-weight: 650; }

/* Reco rate bar in KPI */
.fin-reco-bar-wrap {
    height: 5px; background: #f0f2f5; border-radius: 10px; overflow: hidden;
    margin-top: 10px;
}
.fin-reco-bar {
    height: 100%; border-radius: 10px;
    background: linear-gradient(90deg, #12b76a, #3ccf7e);
    transition: width .6s ease;
}

/* ─── Charts row ──────────────────────────────────────────────────────────── */
.fin-charts-row {
    display: grid; grid-template-columns: 1fr 340px 280px;
    gap: 16px; padding: 0 var(--pg); margin-bottom: 18px;
}
.fin-chart-panel {
    background: #fff; border: 1px solid var(--line);
    border-radius: 16px; padding: 22px 22px 16px;
    box-shadow: 0 1px 3px rgba(16,24,40,.04);
}
.fin-panel-head {
    display: flex; align-items: flex-start; justify-content: space-between;
    margin-bottom: 18px; gap: 12px;
}
.fin-panel-head h2 { font-size: 13px; font-weight: 700; color: #101828; margin: 0; }
.fin-panel-head p { font-size: 10.5px; color: #98a2b3; margin: 4px 0 0; }
.fin-chart-legend {
    display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
}
.fin-legend-item {
    display: flex; align-items: center; gap: 6px;
    font-size: 10.5px; color: #475467; font-weight: 500;
}
.fin-legend-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.fin-chart-wrap { height: 200px; position: relative; }

/* ─── Donut (reconciliation health) ──────────────────────────────────────── */
.fin-donut-wrap {
    position: relative; height: 160px;
    display: flex; align-items: center; justify-content: center;
    margin: 8px 0 12px;
}
.fin-donut-center {
    position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
    text-align: center; pointer-events: none;
}
.fin-donut-center strong {
    display: block; font-size: 26px; font-weight: 760;
    color: #101828; letter-spacing: -.04em; line-height: 1;
}
.fin-donut-center span {
    font-size: 9.5px; color: #98a2b3; font-weight: 600;
    letter-spacing: .06em; text-transform: uppercase; display: block; margin-top: 4px;
}
.fin-donut-legend { display: flex; flex-direction: column; gap: 9px; }
.fin-donut-leg-row {
    display: flex; align-items: center; gap: 8px; font-size: 11px; color: #475467;
}
.fin-donut-leg-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
.fin-donut-leg-row strong { margin-left: auto; color: #101828; font-weight: 700; }

/* ─── Channel bars panel ──────────────────────────────────────────────────── */
.fin-channel-list { display: flex; flex-direction: column; gap: 14px; margin-top: 4px; }
.fin-channel-row { display: flex; flex-direction: column; gap: 5px; }
.fin-channel-top {
    display: flex; justify-content: space-between; align-items: center;
    font-size: 11px; color: #475467;
}
.fin-channel-top strong { font-size: 11px; color: #101828; font-weight: 650; }
.fin-channel-track {
    height: 7px; border-radius: 10px; background: #f0f2f5; overflow: hidden;
}
.fin-channel-track span {
    display: block; height: 100%; border-radius: inherit;
    transition: width .5s ease;
}

/* ─── Bottom row ──────────────────────────────────────────────────────────── */
.fin-bottom-row {
    display: grid; grid-template-columns: 1fr 380px;
    gap: 16px; padding: 0 var(--pg); margin-bottom: 18px;
}
.fin-activity-panel, .fin-exceptions-panel {
    background: #fff; border: 1px solid var(--line);
    border-radius: 16px; overflow: hidden;
    box-shadow: 0 1px 3px rgba(16,24,40,.04);
}

/* ─── Section header inside panel ────────────────────────────────────────── */
.fin-section-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 22px 14px; border-bottom: 1px solid var(--line);
}
.fin-section-head h2 { font-size: 13px; font-weight: 700; color: #101828; margin: 0; }
.fin-section-head p { font-size: 10px; color: #98a2b3; margin: 3px 0 0; }
.fin-view-all {
    font-size: 11px; font-weight: 650; color: var(--primary);
    text-decoration: none; display: inline-flex; align-items: center; gap: 4px;
    white-space: nowrap; transition: opacity .14s;
}
.fin-view-all:hover { opacity: .75; }

/* ─── Activity table ──────────────────────────────────────────────────────── */
.fin-activity-table { width: 100%; border-collapse: collapse; }
.fin-activity-table th {
    padding: 9px 16px; font-size: 9.5px; font-weight: 700;
    letter-spacing: .07em; text-transform: uppercase;
    color: #98a2b3; text-align: left; border-bottom: 1px solid var(--line);
    white-space: nowrap;
}
.fin-activity-table td {
    padding: 13px 16px; font-size: 11.5px; color: #344054;
    border-bottom: 1px solid #f5f6f8; vertical-align: middle;
}
.fin-activity-table tbody tr:last-child td { border-bottom: none; }
.fin-activity-table tbody tr:hover { background: #fafbff; }

.fin-tx-method {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 11px; font-weight: 600; color: #475467;
}
.fin-tx-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.fin-tx-ref { font-size: 11px; color: #98a2b3; display: block; margin-top: 1px; }
.fin-tx-name { font-size: 12px; font-weight: 650; color: #101828; display: block; }
.fin-tx-amount {
    font-size: 13px; font-weight: 750; color: #101828;
    letter-spacing: -.02em; display: block; font-variant-numeric: tabular-nums;
}

/* Status badges */
.fin-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 9.5px; font-weight: 700; padding: 4px 9px;
    border-radius: 20px; white-space: nowrap; letter-spacing: .02em;
}
.fin-badge::before {
    content: ''; width: 5px; height: 5px; border-radius: 50%;
    background: currentColor; flex-shrink: 0;
}
.fin-badge-reconciled  { background: #ecfdf3; color: #067647; }
.fin-badge-pending     { background: #fffaeb; color: #b54708; }
.fin-badge-exception   { background: #fef3f2; color: #b42318; }
.fin-badge-processing  { background: #eef4ff; color: #175cd3; }
.fin-badge-reversed    { background: #f2f4f7; color: #344054; }

/* ─── Exceptions panel ────────────────────────────────────────────────────── */
.fin-exception-list { display: flex; flex-direction: column; }
.fin-exception-item {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 14px 18px; border-bottom: 1px solid #f5f6f8;
    transition: background .12s;
}
.fin-exception-item:last-child { border-bottom: none; }
.fin-exception-item:hover { background: #fffbf5; }
.fin-exc-icon {
    width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
    display: grid; place-items: center; font-size: 15px;
    background: #fff3e0; color: #e65100;
}
.fin-exc-icon-red { background: #fff0ee; color: #d92d20; }
.fin-exc-icon-blue { background: #eef4ff; color: #175cd3; }
.fin-exc-body { flex: 1; min-width: 0; }
.fin-exc-title { font-size: 11.5px; font-weight: 650; color: #101828; display: block; margin-bottom: 2px; }
.fin-exc-meta { font-size: 10px; color: #98a2b3; display: block; }
.fin-exc-amount {
    font-size: 12px; font-weight: 750; color: #101828;
    display: block; text-align: right; letter-spacing: -.01em; flex-shrink: 0;
}
.fin-exc-time { font-size: 9.5px; color: #b2bcc8; display: block; text-align: right; margin-top: 2px; }
.fin-exc-review-btn {
    display: inline-flex; align-items: center; justify-content: center;
    height: 26px; padding: 0 10px; border-radius: 7px;
    border: 1px solid var(--line); background: #fff; color: #344054;
    font-size: 10px; font-weight: 650; cursor: pointer; text-decoration: none;
    margin-top: 6px; transition: border-color .14s, color .14s;
}
.fin-exc-review-btn:hover { border-color: #c7c8f6; color: var(--primary); }

/* ─── Quick actions footer ────────────────────────────────────────────────── */
.fin-quick-actions {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 12px; padding: 0 var(--pg); margin-bottom: 28px;
}
.fin-qa-card {
    background: #fff; border: 1px solid var(--line); border-radius: 14px;
    padding: 18px 20px; display: flex; align-items: center; gap: 14px;
    text-decoration: none; transition: border-color .15s, box-shadow .15s, transform .15s;
    cursor: pointer;
}
.fin-qa-card:hover {
    border-color: #c7c8f6; box-shadow: 0 4px 16px rgba(91,92,226,.1);
    transform: translateY(-1px);
}
.fin-qa-icon {
    width: 40px; height: 40px; border-radius: 11px;
    display: grid; place-items: center; font-size: 18px; flex-shrink: 0;
}
.fin-qa-icon-indigo { background: #eeefff; color: var(--primary); }
.fin-qa-icon-green  { background: #ecfdf3; color: #067647; }
.fin-qa-icon-blue   { background: #eef4ff; color: #175cd3; }
.fin-qa-icon-amber  { background: #fffaeb; color: #b54708; }
.fin-qa-label { font-size: 12px; font-weight: 700; color: #101828; display: block; }
.fin-qa-sub { font-size: 10px; color: #98a2b3; display: block; margin-top: 2px; }

/* ─── Attention banner ────────────────────────────────────────────────────── */
.fin-attention-banner {
    margin: 0 var(--pg) 18px;
    background: #fffbf0; border: 1px solid #fde68a;
    border-radius: 14px; padding: 14px 20px;
    display: flex; align-items: center; gap: 14px;
}
.fin-attention-icon {
    width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
    background: #fff3cd; color: #92400e; font-size: 17px;
    display: grid; place-items: center;
}
.fin-attention-body { flex: 1; min-width: 0; }
.fin-attention-body strong { font-size: 12.5px; font-weight: 700; color: #92400e; display: block; }
.fin-attention-body span { font-size: 11px; color: #a16207; display: block; margin-top: 2px; }
.fin-attention-link {
    font-size: 11.5px; font-weight: 650; color: #92400e;
    text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
    white-space: nowrap; border: 1px solid #fde68a; background: #fff;
    padding: 6px 14px; border-radius: 8px; transition: background .14s;
    flex-shrink: 0;
}
.fin-attention-link:hover { background: #fffbf0; }

/* ─── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 1280px) {
    .fin-charts-row { grid-template-columns: 1fr 300px; }
    .fin-charts-row .fin-channel-panel { display: none; }
    .fin-kpi-grid { grid-template-columns: repeat(3, 1fr); }
    .fin-kpi-cell { border-bottom: 1px solid var(--line); }
}
@media (max-width: 960px) {
    .fin-charts-row { grid-template-columns: 1fr; }
    .fin-bottom-row { grid-template-columns: 1fr; }
    .fin-quick-actions { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .fin-kpi-grid { grid-template-columns: 1fr; }
    .fin-quick-actions { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
<div class="fin-page">

    {{-- ═══ PAGE HEADER ═══════════════════════════════════════════════════════ --}}
    <div class="fin-header">
        <div class="fin-header-left">
            <div class="fin-header-icon"><i class="bi bi-bank2"></i></div>
            <div>
                <div class="eyebrow" style="margin-bottom:5px">FINANCIAL OPERATIONS COCKPIT</div>
                <h1>Finance</h1>
                <p>Financial operations &amp; transaction intelligence</p>
            </div>
        </div>
        <div class="fin-header-actions">
            <div class="period-tabs">
                @foreach(['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month'] as $p => $label)
                    <a href="{{ route('finance.overview', ['period' => $p]) }}"
                       class="period-tab {{ $period === $p ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
            <button class="fin-export-btn" onclick="window.print()">
                <i class="bi bi-download"></i> Export
            </button>
        </div>
    </div>

    {{-- ═══ ATTENTION BANNER (only if exceptions exist) ═══════════════════════ --}}
    @if($exceptionsCount > 0)
    <div class="fin-attention-banner">
        <div class="fin-attention-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div class="fin-attention-body">
            <strong>{{ number_format($exceptionsCount) }} {{ Str::plural('transaction', $exceptionsCount) }} require attention</strong>
            <span>Unmatched or failed payments need review before they can be applied to loan balances.</span>
        </div>
        <a href="{{ route('finance.reconciliation', ['tab' => 'exceptions']) }}" class="fin-attention-link">
            View exceptions <i class="bi bi-arrow-right"></i>
        </a>
    </div>
    @endif

    {{-- ═══ KPI BANNER ═══════════════════════════════════════════════════════ --}}
    <div class="fin-kpi-banner">
        <div class="fin-kpi-grid">

            {{-- Total Collections --}}
            <div class="fin-kpi-cell">
                <div class="fin-kpi-top">
                    <div class="fin-kpi-icon fin-kpi-icon-green"><i class="bi bi-arrow-down-left-circle-fill"></i></div>
                    @if($totalDeltaPct !== null)
                        <span class="fin-kpi-badge {{ $totalDeltaPct >= 0 ? 'fin-kpi-badge-up' : 'fin-kpi-badge-down' }}">
                            <i class="bi bi-arrow-{{ $totalDeltaPct >= 0 ? 'up' : 'down' }}-right"></i>
                            {{ abs($totalDeltaPct) }}%
                        </span>
                    @else
                        <span class="fin-kpi-badge fin-kpi-badge-neu">—</span>
                    @endif
                </div>
                <div class="fin-kpi-label">Total Collections</div>
                <div class="fin-kpi-value">KES {{ number_format($totalCollected / 100) }}</div>
                <p class="fin-kpi-sub">All incoming payments · {{ $period }}</p>
            </div>

            {{-- Reconciled --}}
            <div class="fin-kpi-cell">
                <div class="fin-kpi-top">
                    <div class="fin-kpi-icon fin-kpi-icon-blue"><i class="bi bi-check2-circle"></i></div>
                    <span class="fin-kpi-badge fin-kpi-badge-up">
                        <i class="bi bi-shield-check"></i> {{ number_format($matchedCount) }} matched
                    </span>
                </div>
                <div class="fin-kpi-label">Reconciled</div>
                <div class="fin-kpi-value">KES {{ number_format($reconciled / 100) }}</div>
                <div class="fin-reco-bar-wrap">
                    <div class="fin-reco-bar" style="width:{{ $recoRate }}%"></div>
                </div>
                <p class="fin-kpi-sub" style="margin-top:6px"><strong>{{ $recoRate }}%</strong> reconciliation rate</p>
            </div>

            {{-- Pending --}}
            <div class="fin-kpi-cell">
                <div class="fin-kpi-top">
                    <div class="fin-kpi-icon fin-kpi-icon-amber"><i class="bi bi-hourglass-split"></i></div>
                    <span class="fin-kpi-badge fin-kpi-badge-neu">{{ number_format($pendingCount) }} txns</span>
                </div>
                <div class="fin-kpi-label">Pending</div>
                <div class="fin-kpi-value">KES {{ number_format($pending / 100) }}</div>
                <p class="fin-kpi-sub">Awaiting matching or review</p>
            </div>

            {{-- Exceptions --}}
            <div class="fin-kpi-cell">
                <div class="fin-kpi-top">
                    <div class="fin-kpi-icon fin-kpi-icon-red"><i class="bi bi-exclamation-circle-fill"></i></div>
                    @if($exceptionsCount > 0)
                        <span class="fin-kpi-badge fin-kpi-badge-down">Requires action</span>
                    @else
                        <span class="fin-kpi-badge fin-kpi-badge-up">All clear</span>
                    @endif
                </div>
                <div class="fin-kpi-label">Exceptions</div>
                <div class="fin-kpi-value" style="font-size:32px">{{ number_format($exceptionsCount) }}</div>
                <p class="fin-kpi-sub">Unmatched or failed payments</p>
            </div>

            {{-- M-Pesa Account Balance --}}
            <div class="fin-kpi-cell" style="background:linear-gradient(135deg,#f0fdf9 0%,#fff 60%)">
                <div class="fin-kpi-top">
                    <div class="fin-kpi-icon fin-kpi-icon-teal"><i class="bi bi-phone-fill"></i></div>
                    @if($mpesaBalance !== null)
                        <span class="fin-kpi-badge fin-kpi-badge-up">
                            <i class="bi bi-wifi"></i> Live
                        </span>
                    @else
                        <span class="fin-kpi-badge fin-kpi-badge-neu">No data</span>
                    @endif
                </div>
                <div class="fin-kpi-label">M-Pesa Balance</div>
                @if($mpesaBalance !== null)
                    <div class="fin-kpi-value" style="font-size:22px; color:#0d7459">
                        KES {{ number_format($mpesaBalance / 100) }}
                    </div>
                    <p class="fin-kpi-sub">
                        Paybill working account
                        @if($mpesaBalanceAt)
                            &nbsp;·&nbsp;
                            <strong>{{ $mpesaBalanceAt->diffForHumans() }}</strong>
                        @endif
                    </p>
                @else
                    <div class="fin-kpi-value" style="font-size:22px; color:#98a2b3">—</div>
                    <p class="fin-kpi-sub">Balance reported on next C2B payment</p>
                @endif
            </div>

        </div>
    </div>

    {{-- ═══ CHARTS ROW ════════════════════════════════════════════════════════ --}}
    <div class="fin-charts-row">

        {{-- Collections Trend --}}
        <div class="fin-chart-panel">
            <div class="fin-panel-head">
                <div>
                    <h2>Collections Trend</h2>
                    <p>Daily collections vs reconciled · last 14 days</p>
                </div>
                <div class="fin-chart-legend">
                    <span class="fin-legend-item">
                        <span class="fin-legend-dot" style="background:#5b5ce2"></span> Collected
                    </span>
                    <span class="fin-legend-item">
                        <span class="fin-legend-dot" style="background:#12b76a"></span> Reconciled
                    </span>
                </div>
            </div>
            <div class="fin-chart-wrap">
                <canvas id="finTrendChart"></canvas>
            </div>
        </div>

        {{-- Reconciliation Health Donut --}}
        <div class="fin-chart-panel">
            <div class="fin-panel-head">
                <div>
                    <h2>Reconciliation Health</h2>
                    <p>Transaction status breakdown</p>
                </div>
            </div>
            <div class="fin-donut-wrap">
                <canvas id="finDonutChart" width="160" height="160"></canvas>
                <div class="fin-donut-center">
                    <strong>{{ $recoRate }}%</strong>
                    <span>Reconciled</span>
                </div>
            </div>
            <div class="fin-donut-legend">
                <div class="fin-donut-leg-row">
                    <span class="fin-donut-leg-dot" style="background:#12b76a"></span>
                    Reconciled
                    <strong>{{ number_format($matchedCount) }}</strong>
                </div>
                <div class="fin-donut-leg-row">
                    <span class="fin-donut-leg-dot" style="background:#f79009"></span>
                    Pending
                    <strong>{{ number_format($pendingCount) }}</strong>
                </div>
                <div class="fin-donut-leg-row">
                    <span class="fin-donut-leg-dot" style="background:#f04438"></span>
                    Exceptions
                    <strong>{{ number_format($exceptionsCount) }}</strong>
                </div>
            </div>
        </div>

        {{-- Collections by Channel --}}
        <div class="fin-chart-panel fin-channel-panel">
            <div class="fin-panel-head">
                <div>
                    <h2>By Channel</h2>
                    <p>Collection method breakdown</p>
                </div>
            </div>
            <div class="fin-channel-list">
                @forelse($channelData as $ch)
                <div class="fin-channel-row">
                    <div class="fin-channel-top">
                        <span>{{ $ch['label'] }}</span>
                        <strong>{{ $ch['pct'] }}%</strong>
                    </div>
                    <div class="fin-channel-track">
                        <span style="width:{{ $ch['pct'] }}%; background:{{ $ch['color'] }}"></span>
                    </div>
                    <div style="font-size:10px; color:#98a2b3; margin-top:2px">
                        KES {{ number_format($ch['cents'] / 100) }}
                    </div>
                </div>
                @empty
                <p style="font-size:11px; color:#98a2b3; text-align:center; padding:20px 0">No data for this period</p>
                @endforelse
            </div>
        </div>

    </div>

    {{-- ═══ BOTTOM ROW — Activity + Exceptions ═══════════════════════════════ --}}
    <div class="fin-bottom-row">

        {{-- Recent Financial Activity --}}
        <div class="fin-activity-panel">
            <div class="fin-section-head">
                <div>
                    <h2>Recent Financial Activity</h2>
                    <p>Latest transactions across all channels</p>
                </div>
                <a href="{{ route('finance.transactions') }}" class="fin-view-all">
                    View all <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="admin-table-wrap">
                <table class="fin-activity-table">
                    <thead>
                        <tr>
                            <th>Date &amp; Time</th>
                            <th>Channel</th>
                            <th>Reference</th>
                            <th>Client</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($recentActivity as $tx)
                        @php
                            $borrower  = $tx->borrower ?? $tx->loan?->borrower;
                            $name      = $borrower?->user?->name ?? 'Unmatched';
                            $ref       = $tx->mpesa_receipt_number ?: $tx->external_reference ?: '—';
                            $methodLabel = match($tx->payment_method) {
                                'mpesa'         => 'M-Pesa',
                                'bank_transfer' => 'Bank',
                                'cash'          => 'Cash',
                                default         => ucfirst($tx->payment_method),
                            };
                            $dotColor = match($tx->payment_method) {
                                'mpesa'         => '#12b76a',
                                'bank_transfer' => '#7f56d9',
                                'cash'          => '#f79009',
                                default         => '#98a2b3',
                            };
                            $badgeClass = match($tx->status) {
                                'posted'     => 'fin-badge-reconciled',
                                'pending','processing' => 'fin-badge-pending',
                                'failed'     => 'fin-badge-exception',
                                'reversed'   => 'fin-badge-reversed',
                                default      => 'fin-badge-processing',
                            };
                            $badgeLabel = match($tx->status) {
                                'posted'     => 'Reconciled',
                                'pending'    => 'Pending',
                                'processing' => 'Processing',
                                'failed'     => 'Exception',
                                'reversed'   => 'Reversed',
                                default      => ucfirst($tx->status),
                            };
                        @endphp
                        <tr>
                            <td>
                                <span style="font-size:11.5px; font-weight:600; color:#101828; display:block">
                                    {{ $tx->received_at?->format('d M Y') }}
                                </span>
                                <span style="font-size:10px; color:#98a2b3">{{ $tx->received_at?->format('H:i') }}</span>
                            </td>
                            <td>
                                <span class="fin-tx-method">
                                    <span class="fin-tx-dot" style="background:{{ $dotColor }}"></span>
                                    {{ $methodLabel }}
                                </span>
                            </td>
                            <td>
                                <span style="font-size:11px; font-weight:600; color:#344054; font-family:monospace">
                                    {{ Str::limit($ref, 12) }}
                                </span>
                            </td>
                            <td>
                                <span class="fin-tx-name">{{ $name }}</span>
                            </td>
                            <td>
                                <span class="fin-tx-amount">KES {{ number_format($tx->amount_cents / 100) }}</span>
                            </td>
                            <td>
                                <span class="fin-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center; padding:32px; color:#98a2b3; font-size:11.5px">
                                No recent activity to display.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Exceptions Requiring Attention --}}
        <div class="fin-exceptions-panel">
            <div class="fin-section-head">
                <div>
                    <h2>Exceptions Requiring Attention</h2>
                    <p>{{ number_format($exceptionsCount) }} open exceptions</p>
                </div>
                <a href="{{ route('finance.reconciliation', ['tab' => 'exceptions']) }}" class="fin-view-all">
                    View all <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="fin-exception-list">
                @forelse($openExceptions as $exc)
                    @php
                        $borrower = $exc->borrower ?? $exc->loan?->borrower;
                        $name     = $borrower?->user?->name ?? 'Unknown client';
                        $ref      = $exc->mpesa_receipt_number ?: $exc->external_reference ?: ('PAY-' . str_pad($exc->id, 5, '0', STR_PAD_LEFT));
                        $reason   = $exc->loan_id === null ? 'Missing external transaction' : 'Payment exception';
                        if ($exc->status === 'failed') $reason = 'Failed payment';
                        $iconClass = $exc->status === 'failed' ? 'fin-exc-icon-red' : '';
                        $timeAgo = $exc->received_at?->diffForHumans() ?? '—';
                    @endphp
                    <div class="fin-exception-item">
                        <div class="fin-exc-icon {{ $iconClass }}">
                            <i class="bi bi-{{ $exc->status === 'failed' ? 'x-circle' : 'question-circle' }}-fill"></i>
                        </div>
                        <div class="fin-exc-body">
                            <span class="fin-exc-title">{{ $reason }}</span>
                            <span class="fin-exc-meta">
                                {{ $exc->payment_method === 'mpesa' ? 'M-Pesa' : ucfirst($exc->payment_method ?? 'Payment') }}
                                · {{ $ref }}
                            </span>
                            <a href="{{ route('payments.edit', $exc) }}" class="fin-exc-review-btn">
                                Review
                            </a>
                        </div>
                        <div>
                            <span class="fin-exc-amount">KES {{ number_format($exc->amount_cents / 100) }}</span>
                            <span class="fin-exc-time">{{ $timeAgo }}</span>
                        </div>
                    </div>
                @empty
                    <div style="padding:32px; text-align:center">
                        <div style="width:44px;height:44px;border-radius:12px;background:#ecfdf3;display:grid;place-items:center;margin:0 auto 12px;font-size:20px;color:#067647">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                        <p style="font-size:12px;font-weight:600;color:#067647;margin:0 0 4px">All clear</p>
                        <p style="font-size:11px;color:#98a2b3;margin:0">No exceptions requiring attention.</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- ═══ QUICK ACTIONS ═══════════════════════════════════════════════════ --}}
    <div class="fin-quick-actions">
        <a href="{{ route('finance.reconciliation') }}" class="fin-qa-card">
            <div class="fin-qa-icon fin-qa-icon-indigo"><i class="bi bi-check2-all"></i></div>
            <div>
                <span class="fin-qa-label">Run Reconciliation</span>
                <span class="fin-qa-sub">Match pending transactions</span>
            </div>
        </a>
        <a href="{{ route('finance.reports') }}" class="fin-qa-card">
            <div class="fin-qa-icon fin-qa-icon-blue"><i class="bi bi-bar-chart-line-fill"></i></div>
            <div>
                <span class="fin-qa-label">Download Report</span>
                <span class="fin-qa-sub">Collections &amp; repayments</span>
            </div>
        </a>
        <a href="{{ route('payments.index', ['view' => 'unmatched']) }}" class="fin-qa-card">
            <div class="fin-qa-icon fin-qa-icon-amber"><i class="bi bi-link-45deg"></i></div>
            <div>
                <span class="fin-qa-label">Match Payments</span>
                <span class="fin-qa-sub">Link unmatched receipts</span>
            </div>
        </a>
        <a href="{{ route('finance.reversals') }}" class="fin-qa-card">
            <div class="fin-qa-icon fin-qa-icon-green"><i class="bi bi-arrow-counterclockwise"></i></div>
            <div>
                <span class="fin-qa-label">View Reversals</span>
                <span class="fin-qa-sub">Audit reversal history</span>
            </div>
        </a>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    const trendData = @json($trendBuckets);

    // ── Collections trend chart ─────────────────────────────────────────────
    const trendEl = document.getElementById('finTrendChart');
    if (trendEl && trendData.labels?.length) {
        new Chart(trendEl, {
            type: 'line',
            data: {
                labels: trendData.labels,
                datasets: [
                    {
                        label: 'Collected',
                        data: trendData.collected,
                        borderColor: '#5b5ce2',
                        backgroundColor: (ctx) => {
                            const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 200);
                            g.addColorStop(0, 'rgba(91,92,226,.18)');
                            g.addColorStop(1, 'rgba(91,92,226,0)');
                            return g;
                        },
                        borderWidth: 2.5, tension: 0.42, pointRadius: 3,
                        pointHoverRadius: 5, pointBackgroundColor: '#fff',
                        pointBorderColor: '#5b5ce2', pointBorderWidth: 2,
                        fill: true,
                    },
                    {
                        label: 'Reconciled',
                        data: trendData.reconciled,
                        borderColor: '#12b76a',
                        backgroundColor: (ctx) => {
                            const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 200);
                            g.addColorStop(0, 'rgba(18,183,106,.12)');
                            g.addColorStop(1, 'rgba(18,183,106,0)');
                            return g;
                        },
                        borderWidth: 2, tension: 0.42, pointRadius: 0,
                        pointHoverRadius: 4, fill: true,
                    },
                ],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#fff', titleColor: '#101828', bodyColor: '#667085',
                        borderColor: '#eaecf0', borderWidth: 1,
                        padding: 10, titleFont: { weight: '700', size: 11 },
                        bodyFont: { size: 11 },
                        callbacks: {
                            label: (ctx) => ' KES ' + ctx.parsed.y.toLocaleString(),
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 }, color: '#98a2b3', maxTicksLimit: 7 },
                    },
                    y: {
                        grid: { color: '#f2f4f7' },
                        ticks: {
                            font: { size: 10 }, color: '#98a2b3',
                            callback: (v) => 'KES ' + (v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v),
                        },
                        border: { dash: [4, 4] },
                    },
                },
            },
        });
    }

    // ── Reconciliation donut ────────────────────────────────────────────────
    const donutEl = document.getElementById('finDonutChart');
    if (donutEl) {
        const matched    = {{ $matchedCount }};
        const pending    = {{ $pendingCount }};
        const exceptions = {{ $exceptionsCount }};
        const total      = matched + pending + exceptions;

        new Chart(donutEl, {
            type: 'doughnut',
            data: {
                labels: ['Reconciled', 'Pending', 'Exceptions'],
                datasets: [{
                    data: total > 0 ? [matched, pending, exceptions] : [1, 0, 0],
                    backgroundColor: ['#12b76a', '#f79009', '#f04438'],
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverOffset: 4,
                }],
            },
            options: {
                responsive: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#fff', titleColor: '#101828', bodyColor: '#667085',
                        borderColor: '#eaecf0', borderWidth: 1, padding: 10,
                    },
                },
            },
        });
    }
})();
</script>
@endpush
