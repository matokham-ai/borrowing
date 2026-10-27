@extends('layouts.app')

@section('title', 'Financial Reports')

@push('styles')
<style>
/* ─── Page shell ──────────────────────────────────────────────────────────── */
.rpt-page { max-width: 1560px; margin: 0 auto; }

/* ─── Header ──────────────────────────────────────────────────────────────── */
.rpt-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    padding: 0 var(--pg) 22px; gap: 16px; flex-wrap: wrap;
}
.rpt-header-left { display: flex; align-items: flex-start; gap: 14px; }
.rpt-header-icon {
    width: 44px; height: 44px; border-radius: 13px; flex-shrink: 0;
    background: linear-gradient(135deg, #eef4ff, #dde9ff);
    color: #175cd3; font-size: 20px;
    display: grid; place-items: center; margin-top: 3px;
}
.rpt-header h1 {
    font-size: 24px; font-weight: 760; color: #101828;
    letter-spacing: -.035em; margin: 0 0 4px; line-height: 1.15;
}
.rpt-header p { font-size: 12px; color: #667085; margin: 0; }
.rpt-header-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; margin-top: 4px; }

/* Period selector */
.rpt-period-tabs {
    display: flex; align-items: center;
    background: #f2f4f7; border-radius: 9px; padding: 3px; gap: 2px;
}
.rpt-period-tab {
    height: 30px; padding: 0 12px; border: none; background: transparent;
    font-size: 11px; font-weight: 600; color: #667085; border-radius: 7px;
    cursor: pointer; text-decoration: none; display: inline-flex; align-items: center;
    transition: background .15s, color .15s, box-shadow .15s;
}
.rpt-period-tab.active {
    background: #fff; color: var(--primary);
    box-shadow: 0 1px 4px rgba(16,24,40,.1);
}
.rpt-period-tab:hover:not(.active) { background: rgba(255,255,255,.6); color: #344054; }

.rpt-export-btn {
    height: 36px; padding: 0 16px; border-radius: 9px;
    background: var(--primary); border: 1px solid var(--primary);
    color: #fff; font-size: 11.5px; font-weight: 650; cursor: pointer;
    display: inline-flex; align-items: center; gap: 7px;
    transition: background .14s, box-shadow .14s;
    box-shadow: 0 1px 4px rgba(91,92,226,.25);
}
.rpt-export-btn:hover { background: #4a4bcf; box-shadow: 0 4px 14px rgba(91,92,226,.3); }

/* ─── Tab nav ─────────────────────────────────────────────────────────────── */
.rpt-tab-nav {
    display: flex; align-items: center; gap: 2px;
    padding: 0 var(--pg) 18px; overflow-x: auto;
    scrollbar-width: none; -webkit-overflow-scrolling: touch;
    border-bottom: 1px solid var(--line); margin-bottom: 22px;
}
.rpt-tab-nav::-webkit-scrollbar { display: none; }
.rpt-tab-btn {
    height: 36px; padding: 0 14px; border: none; background: transparent;
    font-size: 12px; font-weight: 600; color: #667085; border-radius: 8px;
    cursor: pointer; text-decoration: none; display: inline-flex; align-items: center;
    gap: 6px; white-space: nowrap; transition: background .14s, color .14s;
    position: relative;
}
.rpt-tab-btn::after {
    content: ''; position: absolute; bottom: -18px; left: 0; right: 0; height: 2px;
    border-radius: 2px; background: transparent; transition: background .15s;
}
.rpt-tab-btn.active { color: var(--primary); background: #f5f5ff; }
.rpt-tab-btn.active::after { background: var(--primary); }
.rpt-tab-btn:hover:not(.active) { background: #f5f6f8; color: #344054; }

/* ─── KPI grid ────────────────────────────────────────────────────────────── */
.rpt-kpi-grid {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 16px; padding: 0 var(--pg); margin-bottom: 22px;
}
.rpt-kpi-card {
    background: #fff; border: 1px solid var(--line); border-radius: 16px;
    padding: 22px 22px 18px; position: relative; overflow: hidden;
    box-shadow: 0 1px 3px rgba(16,24,40,.04);
    transition: box-shadow .18s, transform .18s;
}
.rpt-kpi-card:hover { box-shadow: 0 6px 20px rgba(16,24,40,.08); transform: translateY(-1px); }
.rpt-kpi-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0;
    height: 3px; border-radius: 16px 16px 0 0;
}
.rpt-kpi-card-blue::before   { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
.rpt-kpi-card-green::before  { background: linear-gradient(90deg, #10b981, #34d399); }
.rpt-kpi-card-red::before    { background: linear-gradient(90deg, #ef4444, #f87171); }
.rpt-kpi-card-indigo::before { background: linear-gradient(90deg, #6366f1, #8b5cf6); }

.rpt-kpi-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    margin-bottom: 14px;
}
.rpt-kpi-icon {
    width: 40px; height: 40px; border-radius: 11px;
    display: grid; place-items: center; font-size: 18px; flex-shrink: 0;
}
.rpt-kpi-icon-blue   { background: #eff6ff; color: #2563eb; }
.rpt-kpi-icon-green  { background: #ecfdf5; color: #059669; }
.rpt-kpi-icon-red    { background: #fef2f2; color: #dc2626; }
.rpt-kpi-icon-indigo { background: #eeefff; color: #5b5ce2; }

.rpt-delta {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 10.5px; font-weight: 700; padding: 3px 8px;
    border-radius: 20px;
}
.rpt-delta-up   { background: #ecfdf3; color: #067647; }
.rpt-delta-down { background: #fef3f2; color: #b42318; }
.rpt-delta-neu  { background: #f2f4f7; color: #667085; }

.rpt-kpi-label { font-size: 11px; font-weight: 500; color: #667085; margin-bottom: 5px; }
.rpt-kpi-value {
    font-size: 26px; font-weight: 760; color: #101828;
    letter-spacing: -.04em; line-height: 1; margin-bottom: 4px;
}
.rpt-kpi-sub { font-size: 10.5px; color: #98a2b3; }
.rpt-kpi-sub strong { color: #475467; font-weight: 650; }

/* Reconciliation rate card — special layout */
.rpt-reco-bar-wrap {
    height: 8px; background: #f0f2f5; border-radius: 10px;
    overflow: hidden; margin: 12px 0 6px;
}
.rpt-reco-bar {
    height: 100%; border-radius: 10px;
    background: linear-gradient(90deg, #6366f1, #8b5cf6);
    transition: width .6s ease;
}
.rpt-reco-stats {
    display: flex; justify-content: space-between;
    font-size: 10px; color: #98a2b3; margin-top: 4px;
}
.rpt-reco-stats strong { color: #344054; font-weight: 650; }

/* ─── Charts row ──────────────────────────────────────────────────────────── */
.rpt-charts-row {
    display: grid; grid-template-columns: 1fr 320px;
    gap: 16px; padding: 0 var(--pg); margin-bottom: 22px;
}
.rpt-chart-panel {
    background: #fff; border: 1px solid var(--line); border-radius: 16px;
    padding: 22px 22px 18px;
    box-shadow: 0 1px 3px rgba(16,24,40,.04);
}
.rpt-panel-head {
    display: flex; align-items: flex-start; justify-content: space-between;
    margin-bottom: 18px; gap: 12px;
}
.rpt-panel-head h2 { font-size: 13px; font-weight: 700; color: #101828; margin: 0; }
.rpt-panel-head p { font-size: 10.5px; color: #98a2b3; margin: 4px 0 0; }
.rpt-chart-legend { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
.rpt-legend-item {
    display: flex; align-items: center; gap: 6px;
    font-size: 10.5px; color: #475467; font-weight: 500;
}
.rpt-legend-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.rpt-legend-line {
    width: 18px; height: 2.5px; border-radius: 2px; flex-shrink: 0;
}
.rpt-chart-wrap { height: 230px; position: relative; }

/* ─── Channel breakdown panel ─────────────────────────────────────────────── */
.rpt-channel-total {
    font-size: 22px; font-weight: 760; color: #101828;
    letter-spacing: -.04em; margin-bottom: 4px;
}
.rpt-channel-sub { font-size: 10.5px; color: #98a2b3; margin-bottom: 20px; }
.rpt-channel-list { display: flex; flex-direction: column; gap: 16px; }
.rpt-channel-row {}
.rpt-channel-top {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 6px;
}
.rpt-channel-name {
    display: flex; align-items: center; gap: 8px;
    font-size: 12px; font-weight: 650; color: #344054;
}
.rpt-channel-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
.rpt-channel-pct { font-size: 12px; font-weight: 750; color: #101828; }
.rpt-channel-track {
    height: 8px; border-radius: 10px; background: #f0f2f5; overflow: hidden;
    margin-bottom: 4px;
}
.rpt-channel-track span {
    display: block; height: 100%; border-radius: inherit;
    transition: width .5s cubic-bezier(.22,1,.36,1);
}
.rpt-channel-meta { font-size: 10px; color: #98a2b3; }

/* ─── Second charts row — collections by channel donut + stats table ───────── */
.rpt-lower-row {
    display: grid; grid-template-columns: 260px 1fr;
    gap: 16px; padding: 0 var(--pg); margin-bottom: 22px;
}
.rpt-donut-wrap {
    position: relative; height: 180px;
    display: flex; align-items: center; justify-content: center;
    margin: 8px 0 16px;
}
.rpt-donut-center {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -50%); text-align: center;
}
.rpt-donut-center strong {
    display: block; font-size: 18px; font-weight: 760;
    color: #101828; letter-spacing: -.03em;
}
.rpt-donut-center span {
    font-size: 9px; color: #98a2b3; font-weight: 600;
    text-transform: uppercase; letter-spacing: .07em;
}
.rpt-donut-legend { display: flex; flex-direction: column; gap: 8px; }
.rpt-donut-leg { display: flex; align-items: center; gap: 8px; font-size: 11px; color: #475467; }
.rpt-donut-leg-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.rpt-donut-leg strong { margin-left: auto; color: #101828; font-weight: 700; }

/* ─── Stats summary table ─────────────────────────────────────────────────── */
.rpt-summary-table { width: 100%; border-collapse: collapse; }
.rpt-summary-table thead tr {
    background: #f9fafb; border-bottom: 2px solid var(--line);
}
.rpt-summary-table th {
    padding: 11px 16px; font-size: 10px; font-weight: 700;
    letter-spacing: .07em; text-transform: uppercase;
    color: #98a2b3; text-align: left; white-space: nowrap;
}
.rpt-summary-table th:not(:first-child) { text-align: right; }
.rpt-summary-table td {
    padding: 14px 16px; font-size: 12px; color: #344054;
    border-bottom: 1px solid #f5f6f8; vertical-align: middle;
}
.rpt-summary-table td:not(:first-child) { text-align: right; font-variant-numeric: tabular-nums; }
.rpt-summary-table tbody tr:last-child td { border-bottom: none; }
.rpt-summary-table tbody tr:hover { background: #fafbff; }
.rpt-summary-table tfoot tr { background: #f9fafb; border-top: 2px solid var(--line); }
.rpt-summary-table tfoot td {
    padding: 12px 16px; font-size: 12px; font-weight: 750;
    color: #101828; border-bottom: none;
}
.rpt-summary-table tfoot td:not(:first-child) { text-align: right; color: var(--primary); }

.rpt-channel-badge {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 11.5px; font-weight: 650; color: #344054;
}

/* ─── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 1200px) {
    .rpt-kpi-grid { grid-template-columns: repeat(2, 1fr); }
    .rpt-charts-row { grid-template-columns: 1fr; }
    .rpt-lower-row { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .rpt-kpi-grid { grid-template-columns: 1fr; }
    .rpt-tab-nav { padding: 0 16px 16px; }
    .rpt-kpi-value { font-size: 22px; }
}
</style>
@endpush

@section('content')
<div class="rpt-page">

    {{-- ═══ PAGE HEADER ════════════════════════════════════════════════════════ --}}
    <div class="rpt-header">
        <div class="rpt-header-left">
            <div class="rpt-header-icon"><i class="bi bi-bar-chart-line-fill"></i></div>
            <div>
                <div class="eyebrow" style="margin-bottom:5px">FINANCIAL INTELLIGENCE</div>
                <h1>Financial Reports</h1>
                <p>Understand collections, repayments and financial performance</p>
            </div>
        </div>
        <div class="rpt-header-actions">
            <div class="rpt-period-tabs">
                @foreach(['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', '90' => 'Last 90 Days'] as $p => $label)
                    <a href="{{ route('finance.reports', ['period' => $p, 'tab' => $tab]) }}"
                       class="rpt-period-tab {{ $period === $p ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
            <button class="rpt-export-btn" onclick="window.print()">
                <i class="bi bi-download"></i> Export
            </button>
        </div>
    </div>

    {{-- ═══ TAB NAVIGATION ════════════════════════════════════════════════════ --}}
    <div class="rpt-tab-nav">
        @php
            $tabs = [
                'overview'     => ['label' => 'Overview',     'icon' => 'bi-grid'],
                'collections'  => ['label' => 'Collections',  'icon' => 'bi-arrow-down-left-circle'],
                'repayments'   => ['label' => 'Repayments',   'icon' => 'bi-check2-circle'],
                'mpesa'        => ['label' => 'M-Pesa',       'icon' => 'bi-phone'],
                'bank'         => ['label' => 'Bank',         'icon' => 'bi-bank2'],
                'reversals'    => ['label' => 'Reversals',    'icon' => 'bi-arrow-counterclockwise'],
                'exceptions'   => ['label' => 'Exceptions',   'icon' => 'bi-exclamation-circle'],
            ];
        @endphp
        @foreach($tabs as $key => $meta)
            <a href="{{ route('finance.reports', ['period' => $period, 'tab' => $key]) }}"
               class="rpt-tab-btn {{ $tab === $key ? 'active' : '' }}">
                <i class="bi {{ $meta['icon'] }}"></i>
                {{ $meta['label'] }}
            </a>
        @endforeach
    </div>

    {{-- ═══ KPI CARDS ══════════════════════════════════════════════════════════ --}}
    <div class="rpt-kpi-grid">

        {{-- Collections --}}
        <div class="rpt-kpi-card rpt-kpi-card-blue">
            <div class="rpt-kpi-top">
                <div class="rpt-kpi-icon rpt-kpi-icon-blue"><i class="bi bi-arrow-down-left-circle-fill"></i></div>
                @if($collDelta !== null)
                    <span class="rpt-delta {{ $collDelta >= 0 ? 'rpt-delta-up' : 'rpt-delta-down' }}">
                        <i class="bi bi-arrow-{{ $collDelta >= 0 ? 'up' : 'down' }}-right"></i>
                        {{ abs($collDelta) }}%
                    </span>
                @else
                    <span class="rpt-delta rpt-delta-neu">—</span>
                @endif
            </div>
            <div class="rpt-kpi-label">Collections</div>
            <div class="rpt-kpi-value">KES {{ number_format($collections / 100) }}</div>
            <div class="rpt-kpi-sub">vs. previous period</div>
        </div>

        {{-- Repayments --}}
        <div class="rpt-kpi-card rpt-kpi-card-green">
            <div class="rpt-kpi-top">
                <div class="rpt-kpi-icon rpt-kpi-icon-green"><i class="bi bi-check2-circle"></i></div>
                @if($repDelta !== null)
                    <span class="rpt-delta {{ $repDelta >= 0 ? 'rpt-delta-up' : 'rpt-delta-down' }}">
                        <i class="bi bi-arrow-{{ $repDelta >= 0 ? 'up' : 'down' }}-right"></i>
                        {{ abs($repDelta) }}%
                    </span>
                @else
                    <span class="rpt-delta rpt-delta-neu">—</span>
                @endif
            </div>
            <div class="rpt-kpi-label">Repayments</div>
            <div class="rpt-kpi-value">KES {{ number_format($repayments / 100) }}</div>
            <div class="rpt-kpi-sub">Posted &amp; applied to loans</div>
        </div>

        {{-- Reversals --}}
        <div class="rpt-kpi-card rpt-kpi-card-red">
            <div class="rpt-kpi-top">
                <div class="rpt-kpi-icon rpt-kpi-icon-red"><i class="bi bi-arrow-counterclockwise"></i></div>
                @if($revDelta !== null)
                    <span class="rpt-delta {{ $revDelta <= 0 ? 'rpt-delta-up' : 'rpt-delta-down' }}">
                        <i class="bi bi-arrow-{{ $revDelta <= 0 ? 'down' : 'up' }}-right"></i>
                        {{ abs($revDelta) }}%
                    </span>
                @else
                    <span class="rpt-delta rpt-delta-neu">—</span>
                @endif
            </div>
            <div class="rpt-kpi-label">Reversals</div>
            <div class="rpt-kpi-value">KES {{ number_format($reversals / 100) }}</div>
            <div class="rpt-kpi-sub"><strong>{{ number_format($reversalsCnt) }}</strong> transactions reversed</div>
        </div>

        {{-- Reconciliation Rate --}}
        <div class="rpt-kpi-card rpt-kpi-card-indigo">
            <div class="rpt-kpi-top">
                <div class="rpt-kpi-icon rpt-kpi-icon-indigo"><i class="bi bi-shield-check"></i></div>
                <span class="rpt-delta {{ $recoRate >= 90 ? 'rpt-delta-up' : ($recoRate >= 75 ? 'rpt-delta-neu' : 'rpt-delta-down') }}">
                    {{ $recoRate >= 90 ? 'Healthy' : ($recoRate >= 75 ? 'Fair' : 'Needs review') }}
                </span>
            </div>
            <div class="rpt-kpi-label">Reconciliation Rate</div>
            <div class="rpt-kpi-value">{{ $recoRate }}%</div>
            <div class="rpt-reco-bar-wrap">
                <div class="rpt-reco-bar" style="width:{{ $recoRate }}%"></div>
            </div>
            <div class="rpt-reco-stats">
                <span><strong>{{ number_format($matched) }}</strong> matched</span>
                <span><strong>{{ number_format($pending) }}</strong> pending review</span>
            </div>
        </div>

    </div>

    {{-- ═══ MAIN CHART + CHANNEL BREAKDOWN ═══════════════════════════════════ --}}
    <div class="rpt-charts-row">

        {{-- 12-month performance chart --}}
        <div class="rpt-chart-panel">
            <div class="rpt-panel-head">
                <div>
                    <h2>Financial Performance</h2>
                    <p>12-month rolling collections, repayments &amp; reversals</p>
                </div>
                <div class="rpt-chart-legend">
                    <span class="rpt-legend-item">
                        <span class="rpt-legend-line" style="background:#5b5ce2"></span> Collections
                    </span>
                    <span class="rpt-legend-item">
                        <span class="rpt-legend-line" style="background:#12b76a"></span> Repayments
                    </span>
                    <span class="rpt-legend-item">
                        <span class="rpt-legend-line" style="background:#f04438; border-top: 2px dashed #f04438; height:0; width:18px"></span> Reversals
                    </span>
                </div>
            </div>
            <div class="rpt-chart-wrap">
                <canvas id="rptPerformanceChart"></canvas>
            </div>
        </div>

        {{-- Collections by Channel --}}
        <div class="rpt-chart-panel">
            <div class="rpt-panel-head">
                <div>
                    <h2>Collections by Channel</h2>
                    <p>Payment method breakdown</p>
                </div>
            </div>
            <div class="rpt-channel-total">KES {{ number_format($collections / 100) }}</div>
            <div class="rpt-channel-sub">Total collected · {{ ucfirst($period) }}</div>
            <div class="rpt-channel-list">
                @forelse($channelRows as $ch)
                    <div class="rpt-channel-row">
                        <div class="rpt-channel-top">
                            <span class="rpt-channel-name">
                                <span class="rpt-channel-dot" style="background:{{ $ch['color'] }}"></span>
                                {{ $ch['label'] }}
                            </span>
                            <span class="rpt-channel-pct">{{ $ch['pct'] }}%</span>
                        </div>
                        <div class="rpt-channel-track">
                            <span style="width:{{ $ch['pct'] }}%; background:{{ $ch['color'] }}"></span>
                        </div>
                        <div class="rpt-channel-meta">
                            KES {{ number_format($ch['cents'] / 100) }}
                            &nbsp;·&nbsp; {{ number_format($ch['count']) }} transactions
                        </div>
                    </div>
                @empty
                    <p style="font-size:11px; color:#98a2b3; text-align:center; padding:16px 0">No data for this period</p>
                @endforelse
            </div>
        </div>

    </div>

    {{-- ═══ LOWER ROW — donut + summary table ════════════════════════════════ --}}
    <div class="rpt-lower-row">

        {{-- Channel donut --}}
        <div class="rpt-chart-panel">
            <div class="rpt-panel-head">
                <div>
                    <h2>Channel Mix</h2>
                    <p>Share of collections</p>
                </div>
            </div>
            <div class="rpt-donut-wrap">
                <canvas id="rptChannelDonut" width="180" height="180"></canvas>
                <div class="rpt-donut-center">
                    <strong>{{ $channelRows->count() }}</strong>
                    <span>Channels</span>
                </div>
            </div>
            <div class="rpt-donut-legend">
                @foreach($channelRows as $ch)
                    <div class="rpt-donut-leg">
                        <span class="rpt-donut-leg-dot" style="background:{{ $ch['color'] }}"></span>
                        {{ $ch['label'] }}
                        <strong>{{ $ch['pct'] }}%</strong>
                    </div>
                @endforeach
                @if($channelRows->isEmpty())
                    <p style="font-size:11px; color:#98a2b3; text-align:center">No data</p>
                @endif
            </div>
        </div>

        {{-- Summary table --}}
        <div class="rpt-chart-panel" style="padding:0; overflow:hidden;">
            <div class="rpt-panel-head" style="padding:20px 22px 16px; border-bottom:1px solid var(--line); margin-bottom:0">
                <div>
                    <h2>Channel Summary</h2>
                    <p>Volume &amp; value by payment method</p>
                </div>
            </div>
            <div style="overflow-x:auto">
                <table class="rpt-summary-table">
                    <thead>
                        <tr>
                            <th>Channel</th>
                            <th>Transactions</th>
                            <th>Volume (KES)</th>
                            <th>Share</th>
                            <th>Reconciled</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($channelRows as $ch)
                            <tr>
                                <td>
                                    <span class="rpt-channel-badge">
                                        <span class="rpt-channel-dot" style="background:{{ $ch['color'] }}"></span>
                                        {{ $ch['label'] }}
                                    </span>
                                </td>
                                <td>{{ number_format($ch['count']) }}</td>
                                <td><strong>{{ number_format($ch['cents'] / 100) }}</strong></td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px;justify-content:flex-end">
                                        <div style="width:60px;height:5px;background:#f0f2f5;border-radius:10px;overflow:hidden;flex-shrink:0">
                                            <div style="width:{{ $ch['pct'] }}%;height:100%;background:{{ $ch['color'] }};border-radius:10px"></div>
                                        </div>
                                        {{ $ch['pct'] }}%
                                    </div>
                                </td>
                                <td style="color:#067647; font-weight:650">{{ $recoRate }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align:center; color:#98a2b3; padding:32px; font-size:11.5px">
                                    No transactions in this period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($channelRows->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td>Total</td>
                            <td>{{ number_format($channelRows->sum('count')) }}</td>
                            <td>{{ number_format($collections / 100) }}</td>
                            <td style="text-align:right">100%</td>
                            <td style="color:#067647">{{ $recoRate }}%</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>

    </div>

    {{-- ═══ DAILY TREND SPARKLINE ROW ═══════════════════════════════════════ --}}
    <div style="padding: 0 var(--pg); margin-bottom: 28px;">
        <div class="rpt-chart-panel">
            <div class="rpt-panel-head">
                <div>
                    <h2>Daily Collections · Last 30 Days</h2>
                    <p>Daily collected vs reconciled amounts</p>
                </div>
                <div class="rpt-chart-legend">
                    <span class="rpt-legend-item">
                        <span class="rpt-legend-line" style="background:#5b5ce2"></span> Collected
                    </span>
                    <span class="rpt-legend-item">
                        <span class="rpt-legend-line" style="background:#12b76a"></span> Reconciled
                    </span>
                </div>
            </div>
            <div style="height:180px; position:relative;">
                <canvas id="rptDailyChart"></canvas>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    const monthly = @json($monthlyTrend);
    const daily   = @json($dailyTrend);

    const tooltipDefaults = {
        backgroundColor: '#fff', titleColor: '#101828', bodyColor: '#667085',
        borderColor: '#eaecf0', borderWidth: 1, padding: 10,
        titleFont: { weight: '700', size: 11 }, bodyFont: { size: 11 },
    };

    // ── 12-month performance bar + line chart ───────────────────────────────
    const perfEl = document.getElementById('rptPerformanceChart');
    if (perfEl && monthly.labels?.length) {
        new Chart(perfEl, {
            type: 'bar',
            data: {
                labels: monthly.labels,
                datasets: [
                    {
                        label: 'Collections',
                        data: monthly.collected,
                        backgroundColor: 'rgba(91,92,226,.18)',
                        borderColor: '#5b5ce2',
                        borderWidth: 1.5,
                        borderRadius: 5,
                        order: 2,
                    },
                    {
                        label: 'Repayments',
                        data: monthly.reconciled,
                        backgroundColor: 'rgba(18,183,106,.15)',
                        borderColor: '#12b76a',
                        borderWidth: 1.5,
                        borderRadius: 5,
                        order: 3,
                    },
                    {
                        label: 'Reversals',
                        data: monthly.reversed,
                        type: 'line',
                        borderColor: '#f04438',
                        borderWidth: 2,
                        borderDash: [5, 4],
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#f04438',
                        pointBorderWidth: 2,
                        fill: false,
                        order: 1,
                    },
                ],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        ...tooltipDefaults,
                        callbacks: { label: (ctx) => ' KES ' + ctx.parsed.y.toLocaleString() },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 }, color: '#98a2b3' },
                    },
                    y: {
                        grid: { color: '#f2f4f7' },
                        border: { dash: [4, 4] },
                        ticks: {
                            font: { size: 10 }, color: '#98a2b3',
                            callback: (v) => 'KES ' + (v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v),
                        },
                    },
                },
            },
        });
    }

    // ── Channel donut ───────────────────────────────────────────────────────
    const donutEl = document.getElementById('rptChannelDonut');
    if (donutEl) {
        const channelRows = @json($channelRows);
        const data   = channelRows.map(r => r.cents);
        const colors = channelRows.map(r => r.color);
        const labels = channelRows.map(r => r.label);

        new Chart(donutEl, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data: data.length ? data : [1],
                    backgroundColor: colors.length ? colors : ['#f0f2f5'],
                    borderWidth: 3, borderColor: '#fff', hoverOffset: 4,
                }],
            },
            options: {
                responsive: false, cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        ...tooltipDefaults,
                        callbacks: {
                            label: (ctx) => ' KES ' + (ctx.parsed / 100).toLocaleString(),
                        },
                    },
                },
            },
        });
    }

    // ── Daily bar chart ─────────────────────────────────────────────────────
    const dailyEl = document.getElementById('rptDailyChart');
    if (dailyEl && daily.labels?.length) {
        new Chart(dailyEl, {
            type: 'bar',
            data: {
                labels: daily.labels,
                datasets: [
                    {
                        label: 'Collected',
                        data: daily.collected,
                        backgroundColor: 'rgba(91,92,226,.2)',
                        borderColor: '#5b5ce2',
                        borderWidth: 1.5,
                        borderRadius: 4,
                    },
                    {
                        label: 'Reconciled',
                        data: daily.reconciled,
                        type: 'line',
                        borderColor: '#12b76a',
                        borderWidth: 2,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        fill: false,
                    },
                ],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        ...tooltipDefaults,
                        callbacks: { label: (ctx) => ' KES ' + ctx.parsed.y.toLocaleString() },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 9 }, color: '#98a2b3',
                            maxTicksLimit: 10,
                        },
                    },
                    y: {
                        grid: { color: '#f2f4f7' },
                        border: { dash: [4, 4] },
                        ticks: {
                            font: { size: 10 }, color: '#98a2b3',
                            callback: (v) => v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v,
                        },
                    },
                },
            },
        });
    }
})();
</script>
@endpush
