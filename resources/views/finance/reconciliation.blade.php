@extends('layouts.app')

@section('title', 'Reconciliation')

@push('styles')
<style>
/* ═══════════════════════════════════════════════════════════════
   RECONCILIATION — Premium Fintech Operations UI
   Design system: dark navy + white + restrained indigo accent
   Typography scale: 11 / 12 / 13 / 16 / 22 / 32px
═══════════════════════════════════════════════════════════════ */

/* ── Page shell ─────────────────────────────────────────────── */
.rec-page { max-width: 1600px; margin: 0 auto; }

/* ── Page header ─────────────────────────────────────────────── */
.rec-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 26px var(--pg) 20px; gap: 16px; flex-wrap: wrap;
}
.rec-head-left { display: flex; align-items: flex-start; gap: 0; flex-direction: column; }
.rec-head-eyebrow {
    font-size: 10px; font-weight: 700; letter-spacing: .1em;
    text-transform: uppercase; color: var(--primary); margin-bottom: 5px;
    display: flex; align-items: center; gap: 6px;
}
.rec-head-sync-dot {
    width: 6px; height: 6px; border-radius: 50%; background: #12b76a;
    box-shadow: 0 0 0 3px rgba(18,183,106,.2); flex-shrink: 0;
    animation: rec-pulse 2.4s ease-in-out infinite;
}
@keyframes rec-pulse {
    0%,100% { box-shadow: 0 0 0 3px rgba(18,183,106,.2); }
    50%      { box-shadow: 0 0 0 5px rgba(18,183,106,.08); }
}
.rec-head h1 {
    font-size: 28px; font-weight: 760; color: #0d1320;
    letter-spacing: -.04em; margin: 0 0 5px; line-height: 1.1;
}
.rec-head-sub {
    font-size: 12.5px; color: #667085; margin: 0;
}
.rec-head-right {
    display: flex; align-items: center; gap: 8px; flex-shrink: 0; flex-wrap: wrap;
}

/* Filter selects */
.rec-select {
    height: 34px; border: 1px solid var(--line); border-radius: 8px;
    background: #fff; color: #344054; padding: 0 28px 0 11px;
    font-size: 11.5px; font-weight: 600; cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%2398a2b3'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 10px center;
    transition: border-color .14s, box-shadow .14s;
}
.rec-select:focus { outline: none; border-color: #a5b4fc; box-shadow: 0 0 0 3px rgba(91,92,226,.1); }

/* Run button */
.rec-run-btn {
    height: 34px; padding: 0 16px; border-radius: 8px;
    background: var(--primary); border: 1px solid var(--primary);
    color: #fff; font-size: 11.5px; font-weight: 650; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px;
    transition: background .14s, box-shadow .14s;
    box-shadow: 0 1px 4px rgba(91,92,226,.22);
    white-space: nowrap;
}
.rec-run-btn:hover { background: #4a4bcf; box-shadow: 0 4px 14px rgba(91,92,226,.28); }

/* Divider line in header */
.rec-head-divider {
    height: 24px; width: 1px; background: var(--line); margin: 0 2px;
}

/* ── KPI strip ───────────────────────────────────────────────── */
.rec-kpi-strip {
    display: grid; grid-template-columns: repeat(5, 1fr);
    gap: 1px; margin: 0 var(--pg) 22px;
    background: var(--line); border: 1px solid var(--line);
    border-radius: 14px; overflow: hidden;
    box-shadow: 0 1px 4px rgba(16,24,40,.05);
}
.rec-kpi {
    background: #fff; padding: 18px 22px 16px;
    display: flex; flex-direction: column; gap: 0;
    position: relative; cursor: default;
    transition: background .15s;
}
.rec-kpi:hover { background: #fafbff; }
.rec-kpi-accent {
    position: absolute; top: 0; left: 22px; right: 22px;
    height: 2px; border-radius: 0 0 3px 3px;
}
.rec-kpi-accent-green  { background: linear-gradient(90deg, #10b981, #34d399); }
.rec-kpi-accent-amber  { background: linear-gradient(90deg, #f79009, #fbbf24); }
.rec-kpi-accent-red    { background: linear-gradient(90deg, #f04438, #f87171); }
.rec-kpi-accent-purple { background: linear-gradient(90deg, #7c3aed, #9e77ed); }

.rec-kpi-label {
    font-size: 9.5px; font-weight: 700; letter-spacing: .1em;
    text-transform: uppercase; color: #98a2b3; margin-bottom: 10px;
    margin-top: 4px;
}
.rec-kpi-value {
    font-size: 30px; font-weight: 760; color: #0d1320;
    letter-spacing: -.05em; line-height: 1; margin-bottom: 6px;
    font-variant-numeric: tabular-nums;
}
.rec-kpi-value-sm {
    font-size: 22px; font-weight: 760; color: #0d1320;
    letter-spacing: -.04em; line-height: 1; margin-bottom: 6px;
    font-variant-numeric: tabular-nums;
}
.rec-kpi-rate {
    font-size: 12px; font-weight: 650; color: #344054; margin-bottom: 4px;
}
.rec-kpi-note {
    font-size: 10.5px; color: #98a2b3;
}
.rec-kpi-delta {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 10.5px; font-weight: 700; margin-top: 2px;
}
.rec-kpi-delta-up   { color: #059669; }
.rec-kpi-delta-down { color: #dc2626; }
.rec-kpi-delta-warn { color: #d97706; }

/* Thin rate bar */
.rec-kpi-bar-wrap {
    height: 3px; background: #f0f2f5; border-radius: 10px;
    overflow: hidden; margin-top: 10px;
}
.rec-kpi-bar {
    height: 100%; border-radius: 10px;
    transition: width .8s cubic-bezier(.4,0,.2,1);
}
.rec-kpi-bar-green  { background: linear-gradient(90deg, #10b981, #34d399); }
.rec-kpi-bar-amber  { background: linear-gradient(90deg, #f79009, #fbbf24); }
.rec-kpi-bar-red    { background: linear-gradient(90deg, #f04438, #f87171); }

/* ── Status badge row (compact inline pills) ─────────────────── */
.rec-status-strip {
    display: flex; align-items: center; gap: 6px;
    padding: 0 var(--pg); margin-bottom: 18px;
    flex-wrap: wrap;
}
.rec-status-pill {
    display: inline-flex; align-items: center; gap: 5px;
    height: 26px; padding: 0 10px; border-radius: 20px;
    font-size: 10.5px; font-weight: 650; white-space: nowrap;
    border: 1px solid; cursor: pointer; text-decoration: none;
    transition: box-shadow .14s, filter .14s;
}
.rec-status-pill:hover { filter: brightness(.97); box-shadow: 0 2px 8px rgba(16,24,40,.08); }
.rec-status-pill-all    { background: #f5f5ff; border-color: #c7c8f6; color: var(--primary); }
.rec-status-pill-green  { background: #ecfdf3; border-color: #a9efc5; color: #067647; }
.rec-status-pill-amber  { background: #fffaeb; border-color: #fde68a; color: #b54708; }
.rec-status-pill-red    { background: #fef3f2; border-color: #fecdca; color: #b42318; }
.rec-status-pill.active { box-shadow: 0 0 0 2px currentColor; }
.rec-status-pill i { font-size: 10px; }

/* ── Main workspace ──────────────────────────────────────────── */
.rec-workspace {
    display: grid; grid-template-columns: 1fr 300px;
    gap: 16px; padding: 0 var(--pg); margin-bottom: 32px;
    align-items: start;
}

/* ── Operations panel (table) ────────────────────────────────── */
.rec-panel {
    background: #fff; border: 1px solid var(--line);
    border-radius: 14px; overflow: hidden;
    box-shadow: 0 1px 4px rgba(16,24,40,.04);
}

/* Panel header row */
.rec-panel-head {
    display: flex; align-items: center; gap: 10px;
    padding: 14px 18px; border-bottom: 1px solid var(--line);
    flex-wrap: wrap;
}
.rec-panel-title {
    font-size: 12px; font-weight: 700; color: #0d1320;
    margin-right: auto; display: flex; align-items: center; gap: 7px;
}
.rec-panel-title i { color: #98a2b3; font-size: 13px; }

/* Search */
.rec-search {
    height: 32px; width: 260px;
    display: flex; align-items: center; gap: 7px;
    border: 1px solid var(--line); border-radius: 8px;
    padding: 0 10px; background: #f7f8fa;
    transition: border-color .14s, background .14s, box-shadow .14s;
}
.rec-search:focus-within {
    background: #fff; border-color: #a5b4fc;
    box-shadow: 0 0 0 3px rgba(91,92,226,.08);
}
.rec-search i { color: #c0c8d8; font-size: 12px; flex-shrink: 0; }
.rec-search input {
    border: 0; outline: 0; min-width: 0; width: 100%; background: transparent;
    font-size: 11.5px; color: #344054;
}
.rec-search input::placeholder { color: #b2bcc8; }

.rec-filter-apply {
    height: 32px; padding: 0 13px; border-radius: 8px;
    border: 1px solid var(--line); background: #fff;
    color: #344054; font-size: 11.5px; font-weight: 600;
    cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
    transition: border-color .14s, background .14s;
}
.rec-filter-apply:hover { border-color: #c7c8f6; background: #f5f5ff; color: var(--primary); }

.rec-clear-link {
    height: 32px; padding: 0 10px; border-radius: 8px;
    border: 1px solid transparent; background: transparent;
    color: #98a2b3; font-size: 11.5px;
    cursor: pointer; display: inline-flex; align-items: center; gap: 5px;
    text-decoration: none; transition: color .14s;
}
.rec-clear-link:hover { color: #f04438; }

.rec-records-count {
    font-size: 10px; font-weight: 650; color: #98a2b3;
    white-space: nowrap; margin-left: auto;
    background: #f5f6f8; border: 1px solid var(--line);
    border-radius: 20px; padding: 3px 9px;
}

/* ── Table ───────────────────────────────────────────────────── */
.rec-table { width: 100%; border-collapse: collapse; min-width: 680px; }
.rec-table thead tr { border-bottom: 1px solid var(--line); background: #fafafa; }
.rec-table th {
    padding: 9px 14px; font-size: 9px; font-weight: 700;
    letter-spacing: .08em; text-transform: uppercase;
    color: #b2bcc8; text-align: left; white-space: nowrap;
}
.rec-table th:first-child { padding-left: 20px; }
.rec-table th:last-child  { padding-right: 20px; }
.rec-table tbody tr {
    border-bottom: 1px solid #f5f6f8; cursor: pointer;
    transition: background .1s;
}
.rec-table tbody tr:last-child { border-bottom: none; }
.rec-table tbody tr:hover { background: #fbfbff; }
.rec-table td { padding: 11px 14px; vertical-align: middle; }
.rec-table td:first-child { padding-left: 20px; }
.rec-table td:last-child  { padding-right: 20px; }

/* Table cell components */
.rec-cell-ref {
    font-size: 11px; font-weight: 650; color: #0d1320;
    font-family: 'SF Mono', 'Fira Code', monospace;
    letter-spacing: .01em; display: block;
}
.rec-cell-sub {
    font-size: 10px; color: #b2bcc8; display: block; margin-top: 2px;
}
.rec-cell-name {
    font-size: 12px; font-weight: 650; color: #0d1320; display: block;
}
.rec-cell-loan {
    font-size: 10px; color: #b2bcc8; display: block; margin-top: 2px;
}
.rec-cell-amount {
    font-size: 12.5px; font-weight: 750; color: #0d1320; display: block;
    letter-spacing: -.02em; font-variant-numeric: tabular-nums;
}
.rec-cell-unapplied {
    font-size: 10px; color: #d97706; font-weight: 600;
    display: block; margin-top: 2px;
}
.rec-cell-diff-pos {
    font-size: 11px; font-weight: 700; color: #d92d20;
    font-variant-numeric: tabular-nums;
}
.rec-cell-diff-none { font-size: 11px; color: #d0d5dd; }

/* Status chips — compact pill */
.rec-chip {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 8px; border-radius: 20px;
    font-size: 9.5px; font-weight: 700; white-space: nowrap;
}
.rec-chip-green  { background: #ecfdf3; color: #067647; }
.rec-chip-amber  { background: #fffaeb; color: #b54708; }
.rec-chip-red    { background: #fef3f2; color: #b42318; }
.rec-chip-grey   { background: #f2f4f7; color: #667085; }
.rec-chip-purple { background: #f4f3ff; color: #5b21b6; }
.rec-chip i { font-size: 9px; }

/* Action button */
.rec-row-action {
    width: 28px; height: 28px; border-radius: 7px;
    border: 1px solid var(--line); background: #fff; color: #98a2b3;
    display: grid; place-items: center; cursor: pointer;
    text-decoration: none; font-size: 11px;
    transition: border-color .14s, color .14s, background .14s;
}
.rec-row-action:hover { border-color: #c7c8f6; background: #f5f5ff; color: var(--primary); }

/* Pagination */
.rec-pagination {
    padding: 12px 18px; border-top: 1px solid var(--line);
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px;
}

/* Empty state */
.rec-empty {
    text-align: center; padding: 52px 20px;
}
.rec-empty-icon {
    width: 48px; height: 48px; border-radius: 12px;
    background: #f5f6f8; display: grid; place-items: center;
    margin: 0 auto 14px; font-size: 20px; color: #c0c8d8;
}
.rec-empty strong { display: block; font-size: 12.5px; color: #344054; margin-bottom: 5px; }
.rec-empty small  { font-size: 11px; color: #98a2b3; }

/* ── Exceptions side panel ───────────────────────────────────── */
.rec-exc-panel {
    background: #fff; border: 1px solid var(--line);
    border-radius: 14px; overflow: hidden;
    box-shadow: 0 1px 4px rgba(16,24,40,.04);
    display: flex; flex-direction: column;
}
.rec-exc-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 16px 13px; border-bottom: 1px solid var(--line);
}
.rec-exc-head-left { display: flex; align-items: center; gap: 8px; }
.rec-exc-head-icon {
    width: 28px; height: 28px; border-radius: 8px;
    background: #fef3f2; color: #b42318; font-size: 13px;
    display: grid; place-items: center; flex-shrink: 0;
}
.rec-exc-head-title { font-size: 12px; font-weight: 700; color: #0d1320; display: block; }
.rec-exc-head-count { font-size: 10px; color: #98a2b3; display: block; margin-top: 1px; }
.rec-exc-view-all {
    font-size: 10.5px; font-weight: 650; color: var(--primary);
    text-decoration: none; display: inline-flex; align-items: center; gap: 3px;
    opacity: .9; transition: opacity .14s;
}
.rec-exc-view-all:hover { opacity: 1; }

.rec-exc-list { flex: 1; overflow-y: auto; }
.rec-exc-item {
    padding: 12px 16px; border-bottom: 1px solid #f5f6f8;
    cursor: pointer; transition: background .12s;
}
.rec-exc-item:last-child { border-bottom: none; }
.rec-exc-item:hover { background: #fffbf5; }

.rec-exc-row1 { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 8px; }
.rec-exc-icon {
    width: 28px; height: 28px; border-radius: 8px;
    display: grid; place-items: center; font-size: 12px; flex-shrink: 0;
}
.rec-exc-icon-orange { background: #fff7ed; color: #c2410c; }
.rec-exc-icon-red    { background: #fff0ee; color: #b42318; }
.rec-exc-reason { font-size: 11px; font-weight: 650; color: #0d1320; display: block; }
.rec-exc-meta   { font-size: 10px; color: #b2bcc8; display: block; margin-top: 2px; }
.rec-exc-row2   { display: flex; align-items: center; justify-content: space-between; }
.rec-exc-amount { font-size: 12px; font-weight: 750; color: #0d1320; }
.rec-exc-time   { font-size: 10px; color: #c0c8d8; }
.rec-exc-review-btn {
    height: 24px; padding: 0 9px; border-radius: 6px;
    border: 1px solid var(--line); background: #fff; color: #475467;
    font-size: 10px; font-weight: 650; cursor: pointer;
    text-decoration: none; display: inline-flex; align-items: center;
    transition: border-color .14s, color .14s;
}
.rec-exc-review-btn:hover { border-color: #c7c8f6; color: var(--primary); }

/* Empty exceptions */
.rec-exc-empty {
    padding: 32px 16px; text-align: center;
}
.rec-exc-empty-icon {
    width: 40px; height: 40px; border-radius: 11px;
    background: #ecfdf3; display: grid; place-items: center;
    margin: 0 auto 11px; font-size: 18px; color: #067647;
}
.rec-exc-empty p { margin: 0; font-size: 11px; }
.rec-exc-empty .rec-exc-empty-title {
    font-weight: 700; color: #059669; margin-bottom: 3px;
}
.rec-exc-empty .rec-exc-empty-sub { color: #98a2b3; }

/* ── Detail drawer ───────────────────────────────────────────── */
.rec-overlay {
    position: fixed; inset: 0;
    background: rgba(10,16,30,.32); z-index: 150;
    opacity: 0; pointer-events: none;
    transition: opacity .22s ease;
    backdrop-filter: blur(2px);
}
.rec-overlay.open { opacity: 1; pointer-events: all; }

.rec-drawer {
    position: fixed; top: 0; right: 0; bottom: 0; width: 440px;
    background: #fff; z-index: 151;
    display: flex; flex-direction: column;
    box-shadow: -8px 0 48px rgba(10,16,30,.14);
    transform: translateX(100%);
    transition: transform .26s cubic-bezier(.22,1,.36,1);
    border-left: 1px solid var(--line);
}
.rec-drawer.open { transform: translateX(0); }

/* Drawer head */
.rec-dr-head {
    padding: 18px 20px 14px; border-bottom: 1px solid var(--line);
    flex-shrink: 0; display: flex; align-items: center; gap: 12px;
}
.rec-dr-head-info { flex: 1; min-width: 0; }
.rec-dr-head-title {
    font-size: 13px; font-weight: 750; color: #0d1320;
    margin: 0 0 3px; letter-spacing: -.01em;
}
.rec-dr-head-sub { font-size: 10.5px; color: #98a2b3; font-family: monospace; }
.rec-dr-close {
    width: 28px; height: 28px; border-radius: 7px;
    border: 1px solid var(--line); background: #fff;
    display: grid; place-items: center; cursor: pointer;
    color: #98a2b3; font-size: 14px; flex-shrink: 0;
    transition: border-color .14s, color .14s, background .14s;
}
.rec-dr-close:hover { border-color: #fecdca; background: #fff1f0; color: #f04438; }

/* Drawer body */
.rec-dr-body {
    flex: 1; overflow-y: auto; padding: 0;
    scrollbar-width: thin; scrollbar-color: #e4e7ec transparent;
}
.rec-dr-body::-webkit-scrollbar { width: 3px; }
.rec-dr-body::-webkit-scrollbar-thumb { background: #e4e7ec; border-radius: 3px; }

/* Amount hero */
.rec-dr-hero {
    background: linear-gradient(135deg, #0d1320 0%, #111827 100%);
    padding: 22px 22px 20px; text-align: center;
}
.rec-dr-hero-amount {
    font-size: 36px; font-weight: 760; color: #fff;
    letter-spacing: -.06em; display: block; margin-bottom: 6px;
    font-variant-numeric: tabular-nums;
}
.rec-dr-hero-ref {
    font-size: 11px; color: #4b5563; font-family: monospace; display: block;
    margin-bottom: 12px;
}
.rec-dr-hero-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 11px; border-radius: 20px;
    font-size: 10px; font-weight: 700;
}
.rec-dr-hero-badge-green  { background: rgba(16,185,129,.2); color: #34d399; }
.rec-dr-hero-badge-amber  { background: rgba(251,191,36,.2);  color: #fbbf24; }
.rec-dr-hero-badge-red    { background: rgba(239,68,68,.2);   color: #f87171; }
.rec-dr-hero-badge-grey   { background: rgba(156,163,175,.2); color: #9ca3af; }

/* Sections */
.rec-dr-section-wrap {
    padding: 16px 20px; border-bottom: 1px solid #f5f6f8;
}
.rec-dr-section-wrap:last-child { border-bottom: none; }
.rec-dr-section-title {
    font-size: 9.5px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .09em; color: #b2bcc8; margin: 0 0 12px;
    display: flex; align-items: center; gap: 6px;
}
.rec-dr-section-title::after {
    content: ''; flex: 1; height: 1px; background: #f5f6f8;
}

/* Identity block */
.rec-dr-identity {
    display: flex; align-items: center; gap: 11px;
}
.rec-dr-avatar {
    width: 40px; height: 40px; border-radius: 11px; flex-shrink: 0;
    background: linear-gradient(135deg, #e0e1ff, #c7c8f6);
    color: #4b4fc4; font-size: 14px; font-weight: 750;
    display: grid; place-items: center;
}
.rec-dr-id-name  { font-size: 13px; font-weight: 700; color: #0d1320; display: block; }
.rec-dr-id-loan  { font-size: 10.5px; color: #98a2b3; display: block; margin-top: 3px; }

/* Meta grid */
.rec-dr-meta-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
}
.rec-dr-meta-item label {
    font-size: 9.5px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .07em; color: #b2bcc8; display: block; margin-bottom: 3px;
}
.rec-dr-meta-item span {
    font-size: 12px; font-weight: 600; color: #0d1320; display: block;
}

/* Comparison block */
.rec-dr-compare-grid {
    display: grid; grid-template-columns: 1fr 32px 1fr;
    gap: 10px; align-items: center;
}
.rec-dr-compare-cell { text-align: center; }
.rec-dr-compare-cell label {
    font-size: 9px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .08em; color: #b2bcc8; display: block; margin-bottom: 6px;
}
.rec-dr-compare-value {
    font-size: 17px; font-weight: 760; color: #0d1320;
    letter-spacing: -.03em; display: block;
    font-variant-numeric: tabular-nums;
}
.rec-dr-compare-note { font-size: 9px; color: #b2bcc8; display: block; margin-top: 3px; }
.rec-dr-vs {
    width: 30px; height: 30px; border-radius: 50%;
    background: #f5f6f8; border: 1px solid var(--line);
    display: grid; place-items: center;
    font-size: 9px; font-weight: 700; color: #98a2b3;
}
.rec-dr-diff-result {
    margin-top: 12px; text-align: center;
}
.rec-dr-diff-pill {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 12px; border-radius: 20px;
    font-size: 10.5px; font-weight: 700;
}
.rec-dr-diff-pill-ok   { background: #ecfdf3; color: #067647; }
.rec-dr-diff-pill-warn { background: #fef3f2; color: #b42318; }

/* Timeline */
.rec-dr-timeline { display: flex; flex-direction: column; }
.rec-dr-tl-item {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 6px 0; position: relative;
}
.rec-dr-tl-item:not(:last-child)::after {
    content: ''; position: absolute; left: 9px; top: 26px;
    width: 1px; height: calc(100% - 4px);
    background: #f0f2f5;
}
.rec-dr-tl-dot {
    width: 20px; height: 20px; border-radius: 50%;
    flex-shrink: 0; border: 2px solid; display: grid;
    place-items: center; font-size: 9px; background: #fff;
}
.rec-dr-tl-dot-done   { border-color: #12b76a; color: #12b76a; }
.rec-dr-tl-dot-warn   { border-color: #f79009; color: #f79009; }
.rec-dr-tl-dot-active { border-color: var(--primary); color: var(--primary); }
.rec-dr-tl-dot-grey   { border-color: #e4e7ec; color: #d0d5dd; }
.rec-dr-tl-content { flex: 1; }
.rec-dr-tl-label  { font-size: 11.5px; font-weight: 600; color: #344054; }
.rec-dr-tl-time   { font-size: 10px; color: #b2bcc8; margin-top: 2px; display: block; }

/* Drawer footer */
.rec-dr-foot {
    padding: 14px 20px; border-top: 1px solid var(--line);
    display: flex; gap: 8px; flex-shrink: 0; background: #fff;
}
.rec-dr-btn {
    flex: 1; height: 36px; border-radius: 8px;
    font-size: 11.5px; font-weight: 650;
    display: inline-flex; align-items: center; justify-content: center;
    gap: 6px; cursor: pointer; text-decoration: none;
    border: 1px solid var(--line); background: #fff; color: #344054;
    transition: border-color .14s, background .14s, color .14s;
}
.rec-dr-btn:hover { border-color: #c7c8f6; background: #f5f5ff; color: var(--primary); }
.rec-dr-btn-primary {
    background: var(--primary); border-color: var(--primary); color: #fff;
}
.rec-dr-btn-primary:hover {
    background: #4a4bcf; border-color: #4a4bcf; color: #fff;
}

/* ── Responsive ──────────────────────────────────────────────── */
@media (max-width: 1280px) {
    .rec-kpi-strip { grid-template-columns: repeat(3, 1fr); }
    .rec-workspace { grid-template-columns: 1fr; }
    .rec-exc-panel { display: none; }
}
@media (max-width: 720px) {
    .rec-kpi-strip { grid-template-columns: 1fr 1fr; }
    .rec-kpi-value { font-size: 24px; }
    .rec-drawer { width: 100%; border-left: none; }
    .rec-head h1 { font-size: 22px; }
}
@media (max-width: 480px) {
    .rec-head-right { flex-wrap: wrap; }
    .rec-kpi-strip { grid-template-columns: 1fr 1fr; gap: 1px; }
}
</style>
@endpush

@section('content')
<div class="rec-page">

{{-- ═══ PAGE HEADER ══════════════════════════════════════════════════════════ --}}
<div class="rec-head">
    <div class="rec-head-left">
        <div class="rec-head-eyebrow">
            <span class="rec-head-sync-dot"></span>
            Finance Operations · Synced {{ now()->format('H:i') }}
        </div>
        <h1>Reconciliation</h1>
        <p class="rec-head-sub">Match and verify financial transactions against external records.</p>
    </div>
    <div class="rec-head-right">
        <form method="GET" id="recHeaderForm" style="display:contents">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <select name="period" class="rec-select"
                    onchange="document.getElementById('recHeaderForm').submit()">
                @foreach(['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', '7' => 'Last 7 Days'] as $p => $l)
                    <option value="{{ $p }}" @selected($period === $p)>{{ $l }}</option>
                @endforeach
            </select>
            <select name="method" class="rec-select"
                    onchange="document.getElementById('recHeaderForm').submit()">
                <option value="">All Channels</option>
                <option value="mpesa"        @selected($method === 'mpesa')>M-Pesa</option>
                <option value="bank_transfer" @selected($method === 'bank_transfer')>Bank Transfer</option>
                <option value="cash"          @selected($method === 'cash')>Cash</option>
            </select>
        </form>
        <div class="rec-head-divider"></div>
        <button class="rec-run-btn">
            <i class="bi bi-arrow-repeat"></i> Run Reconciliation
        </button>
    </div>
</div>

{{-- ═══ KPI STRIP ═══════════════════════════════════════════════════════════ --}}
@php
    $pendingPct    = $totalTx > 0 ? round(($pending    / $totalTx) * 100, 1) : 0;
    $exceptionsPct = $totalTx > 0 ? round(($exceptions / $totalTx) * 100, 1) : 0;
@endphp
<div class="rec-kpi-strip">

    {{-- Matched --}}
    <div class="rec-kpi">
        <div class="rec-kpi-accent rec-kpi-accent-green"></div>
        <span class="rec-kpi-label">Matched</span>
        <span class="rec-kpi-value">{{ number_format($matched) }}</span>
        <div class="rec-kpi-rate">{{ $recoRate }}% of total transactions</div>
        @if($recoRate >= 90)
            <span class="rec-kpi-delta rec-kpi-delta-up">
                <i class="bi bi-check-circle-fill"></i> Above 90% target
            </span>
        @elseif($recoRate >= 75)
            <span class="rec-kpi-delta rec-kpi-delta-warn">
                <i class="bi bi-dash-circle"></i> Below 90% target
            </span>
        @else
            <span class="rec-kpi-delta rec-kpi-delta-down">
                <i class="bi bi-exclamation-circle-fill"></i> Needs attention
            </span>
        @endif
        <div class="rec-kpi-bar-wrap" style="margin-top:10px">
            <div class="rec-kpi-bar rec-kpi-bar-green" style="width:{{ $recoRate }}%"></div>
        </div>
    </div>

    {{-- Pending --}}
    <div class="rec-kpi">
        <div class="rec-kpi-accent rec-kpi-accent-amber"></div>
        <span class="rec-kpi-label">Pending</span>
        <span class="rec-kpi-value">{{ number_format($pending) }}</span>
        <div class="rec-kpi-rate">{{ $pendingPct }}% of total</div>
        <span class="rec-kpi-note">Awaiting match &amp; posting</span>
        <div class="rec-kpi-bar-wrap" style="margin-top:10px">
            <div class="rec-kpi-bar rec-kpi-bar-amber" style="width:{{ min($pendingPct, 100) }}%"></div>
        </div>
    </div>

    {{-- Exceptions --}}
    <div class="rec-kpi">
        <div class="rec-kpi-accent rec-kpi-accent-red"></div>
        <span class="rec-kpi-label">Exceptions</span>
        <span class="rec-kpi-value">{{ number_format($exceptions) }}</span>
        <div class="rec-kpi-rate">{{ $exceptionsPct }}% of total</div>
        @if($exceptions > 0)
            <span class="rec-kpi-delta rec-kpi-delta-down">
                <i class="bi bi-arrow-up-short"></i> {{ $exceptions }} require{{ $exceptions === 1 ? 's' : '' }} action
            </span>
        @else
            <span class="rec-kpi-delta rec-kpi-delta-up">
                <i class="bi bi-check2"></i> No exceptions
            </span>
        @endif
        <div class="rec-kpi-bar-wrap" style="margin-top:10px">
            <div class="rec-kpi-bar rec-kpi-bar-red" style="width:{{ min($exceptionsPct, 100) }}%"></div>
        </div>
    </div>

    {{-- Difference --}}
    <div class="rec-kpi">
        <div class="rec-kpi-accent rec-kpi-accent-purple"></div>
        <span class="rec-kpi-label">Difference</span>
        @if($differenceCents > 0)
            <span class="rec-kpi-value-sm">KES {{ number_format($differenceCents / 100) }}</span>
            <div class="rec-kpi-rate" style="color:#b42318">
                Unapplied balance
            </div>
            <span class="rec-kpi-delta rec-kpi-delta-down">
                <i class="bi bi-exclamation-triangle-fill"></i> Requires investigation
            </span>
        @else
            <span class="rec-kpi-value-sm" style="color:#059669">KES 0</span>
            <div class="rec-kpi-rate" style="color:#059669">No unapplied balance</div>
            <span class="rec-kpi-delta rec-kpi-delta-up">
                <i class="bi bi-check2-circle"></i> Fully reconciled
            </span>
        @endif
    </div>

    {{-- M-Pesa Account Balance --}}
    <div class="rec-kpi" style="background:linear-gradient(160deg,#f0fdf9 0%,#fff 55%)">
        <div class="rec-kpi-accent" style="background:linear-gradient(90deg,#10b981,#34d399)"></div>
        <span class="rec-kpi-label" style="color:#059669">M-Pesa Balance</span>
        @if(isset($mpesaBalance) && $mpesaBalance !== null)
            <span class="rec-kpi-value-sm" style="color:#0d7459">
                KES {{ number_format($mpesaBalance / 100) }}
            </span>
            <div class="rec-kpi-rate" style="color:#059669">Paybill working account</div>
            <span class="rec-kpi-note">
                @if(isset($mpesaBalanceAt) && $mpesaBalanceAt)
                    <i class="bi bi-clock" style="margin-right:3px"></i>
                    {{ $mpesaBalanceAt->diffForHumans() }}
                @else
                    At last C2B transaction
                @endif
            </span>
            <span class="rec-kpi-delta rec-kpi-delta-up" style="margin-top:6px">
                <i class="bi bi-phone-fill"></i> Live balance
            </span>
        @else
            <span class="rec-kpi-value-sm" style="color:#98a2b3">—</span>
            <div class="rec-kpi-rate" style="color:#98a2b3">No C2B data yet</div>
            <span class="rec-kpi-note">Populated on next M-Pesa C2B payment</span>
        @endif
    </div>

</div>

{{-- ═══ TAB / STATUS STRIP ═════════════════════════════════════════════════ --}}
<div class="rec-status-strip">
    @php
        $tabDefs = [
            'all'        => ['label' => 'All Transactions', 'count' => $totalTx,    'cls' => 'rec-status-pill-all',   'icon' => 'bi-list-ul'],
            'matched'    => ['label' => 'Matched',          'count' => $matched,    'cls' => 'rec-status-pill-green', 'icon' => 'bi-check2-circle'],
            'pending'    => ['label' => 'Pending',          'count' => $pending,    'cls' => 'rec-status-pill-amber', 'icon' => 'bi-hourglass-split'],
            'exceptions' => ['label' => 'Exceptions',       'count' => $exceptions, 'cls' => 'rec-status-pill-red',   'icon' => 'bi-exclamation-triangle'],
        ];
    @endphp
    @foreach($tabDefs as $key => $meta)
        <a href="{{ route('finance.reconciliation', array_merge(request()->query(), ['tab' => $key])) }}"
           class="rec-status-pill {{ $meta['cls'] }} {{ $tab === $key ? 'active' : '' }}">
            <i class="bi {{ $meta['icon'] }}"></i>
            {{ $meta['label'] }}
            <strong style="font-size:10px; opacity:.8">{{ number_format($meta['count']) }}</strong>
        </a>
    @endforeach
</div>

{{-- ═══ WORKSPACE ══════════════════════════════════════════════════════════ --}}
<div class="rec-workspace">

    {{-- Main panel --}}
    <div class="rec-panel">

        {{-- Panel header: search + filters --}}
        <form method="GET" id="recOpsForm">
            <input type="hidden" name="period" value="{{ $period }}">
            <input type="hidden" name="tab"    value="{{ $tab }}">
            <input type="hidden" name="method" value="{{ $method }}">
            <div class="rec-panel-head">
                <span class="rec-panel-title">
                    <i class="bi bi-table"></i>
                    Transaction Reconciliation
                </span>
                <div class="rec-search">
                    <i class="bi bi-search"></i>
                    <input type="search" name="search" value="{{ $search }}"
                           placeholder="Search receipt, reference, borrower…">
                </div>
                <button class="rec-filter-apply" type="submit">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                @if($search)
                    <a href="{{ route('finance.reconciliation', array_merge(request()->except('search'), ['tab' => $tab])) }}"
                       class="rec-clear-link">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                @endif
                <span class="rec-records-count">{{ number_format($transactions->total()) }} records</span>
            </div>
        </form>

        {{-- Table --}}
        <div style="overflow-x:auto">
            <table class="rec-table">
                <thead>
                    <tr>
                        <th>Receipt / Ref</th>
                        <th>Borrower</th>
                        <th>Internal</th>
                        <th>External</th>
                        <th>Difference</th>
                        <th>Status</th>
                        <th style="width:40px"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($transactions as $tx)
                    @php
                        $borrower = $tx->borrower ?? $tx->loan?->borrower;
                        $name     = $borrower?->user?->name ?? 'Unmatched';
                        $initial  = strtoupper(substr($name, 0, 1));
                        $loanRef  = $tx->loan
                            ? 'Loan #' . str_pad($tx->loan->id, 7, '0', STR_PAD_LEFT)
                            : 'No loan linked';
                        $ref      = $tx->mpesa_receipt_number
                                    ?: $tx->external_reference
                                    ?: ('TXN-' . str_pad($tx->id, 6, '0', STR_PAD_LEFT));
                        $extAmt   = $tx->mpesaTransaction?->amount
                                    ? ($tx->mpesaTransaction->amount * 100)
                                    : null;
                        $diff     = $extAmt !== null ? abs($tx->amount_cents - $extAmt) : null;

                        $chip = match(true) {
                            $tx->loan_id === null && $tx->status !== 'reversed'
                                                => ['cls' => 'rec-chip-red',   'icon' => 'bi-question-circle', 'label' => 'Unmatched'],
                            $tx->status === 'posted'
                                                => ['cls' => 'rec-chip-green', 'icon' => 'bi-check2',          'label' => 'Matched'],
                            in_array($tx->status, ['pending','processing'])
                                                => ['cls' => 'rec-chip-amber', 'icon' => 'bi-hourglass-split', 'label' => 'Pending'],
                            $tx->status === 'failed'
                                                => ['cls' => 'rec-chip-red',   'icon' => 'bi-x-circle',        'label' => 'Exception'],
                            $tx->status === 'reversed'
                                                => ['cls' => 'rec-chip-grey',  'icon' => 'bi-arrow-counterclockwise', 'label' => 'Reversed'],
                            default             => ['cls' => 'rec-chip-grey',  'icon' => 'bi-circle',          'label' => ucfirst($tx->status)],
                        };

                        $drawerData = [
                            'name'        => $name,
                            'initial'     => $initial,
                            'ref'         => $ref,
                            'loanRef'     => $loanRef,
                            'method'      => ucfirst(str_replace('_', ' ', $tx->payment_method ?? '')),
                            'amountCents' => $tx->amount_cents,
                            'unapplied'   => $tx->unapplied_cents,
                            'extAmt'      => $extAmt,
                            'diff'        => $diff,
                            'status'      => $tx->status,
                            'statusLabel' => $chip['label'],
                            'receivedAt'  => $tx->received_at?->format('d M Y H:i') ?? '—',
                            'postedAt'    => $tx->posted_at?->format('d M Y H:i') ?? '—',
                            'editUrl'     => route('payments.edit', $tx),
                            'mpesaBalance'   => $tx->mpesaTransaction?->org_account_balance_cents,
                            'mpesaBalanceAt' => $tx->mpesaTransaction?->transacted_at?->format('d M Y H:i')
                                            ?? $tx->mpesaTransaction?->received_at?->format('d M Y H:i'),
                            'phoneNumber'    => $tx->mpesaTransaction?->phone_number,
                        ];
                    @endphp
                    <tr onclick="openRecDrawer({{ $tx->id }}, {{ json_encode($drawerData) }})">
                        <td>
                            <span class="rec-cell-ref">{{ Str::limit($ref, 16) }}</span>
                            <span class="rec-cell-sub">
                                {{ ucfirst(str_replace('_', ' ', $tx->payment_method ?? '—')) }}
                                &middot;
                                {{ $tx->received_at?->format('d M Y') ?? '—' }}
                            </span>
                        </td>
                        <td>
                            <span class="rec-cell-name">{{ $name }}</span>
                            <span class="rec-cell-loan">{{ $loanRef }}</span>
                        </td>
                        <td>
                            <span class="rec-cell-amount">KES {{ number_format($tx->amount_cents / 100) }}</span>
                            @if($tx->unapplied_cents > 0)
                                <span class="rec-cell-unapplied">
                                    KES {{ number_format($tx->unapplied_cents / 100) }} unapplied
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($extAmt !== null)
                                <span class="rec-cell-amount">KES {{ number_format($extAmt / 100) }}</span>
                            @else
                                <span style="font-size:11px;color:#d0d5dd">—</span>
                            @endif
                        </td>
                        <td>
                            @if($diff !== null && $diff > 0)
                                <span class="rec-cell-diff-pos">
                                    &minus;KES {{ number_format($diff / 100) }}
                                </span>
                            @else
                                <span class="rec-cell-diff-none">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="rec-chip {{ $chip['cls'] }}">
                                <i class="bi {{ $chip['icon'] }}"></i>
                                {{ $chip['label'] }}
                            </span>
                        </td>
                        <td onclick="event.stopPropagation()">
                            <a href="{{ route('payments.edit', $tx) }}"
                               class="rec-row-action" title="Investigate">
                                <i class="bi bi-arrow-up-right"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="rec-empty">
                                <div class="rec-empty-icon">
                                    <i class="bi bi-check2-all"></i>
                                </div>
                                <strong>No transactions found</strong>
                                <small>Adjust the period, channel, or search filters.</small>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($transactions->hasPages())
            <div class="rec-pagination">
                <span style="font-size:10.5px;color:#98a2b3">
                    Page {{ $transactions->currentPage() }} of {{ $transactions->lastPage() }}
                    &middot; {{ number_format($transactions->total()) }} total
                </span>
                {{ $transactions->links() }}
            </div>
        @endif

    </div>

    {{-- Exceptions side panel --}}
    <div class="rec-exc-panel">
        <div class="rec-exc-head">
            <div class="rec-exc-head-left">
                <div class="rec-exc-head-icon">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div>
                    <span class="rec-exc-head-title">Open Exceptions</span>
                    <span class="rec-exc-head-count">{{ number_format($exceptions) }} requiring action</span>
                </div>
            </div>
            <a href="{{ route('finance.reconciliation', ['tab' => 'exceptions', 'period' => $period]) }}"
               class="rec-exc-view-all">
                All <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <div class="rec-exc-list">
            @forelse($openExceptions as $exc)
                @php
                    $eBor    = $exc->borrower ?? $exc->loan?->borrower;
                    $eName   = $eBor?->user?->name ?? 'Unknown client';
                    $eRef    = $exc->mpesa_receipt_number
                               ?: $exc->external_reference
                               ?: ('PAY-' . str_pad($exc->id, 5, '0', STR_PAD_LEFT));
                    $eReason = match(true) {
                        $exc->status === 'failed' => 'Failed payment',
                        $exc->loan_id === null    => 'No loan linked',
                        $exc->unapplied_cents > 0 => 'Amount mismatch',
                        default                   => 'Payment exception',
                    };
                    $eIconCls = $exc->status === 'failed' ? 'rec-exc-icon-red' : 'rec-exc-icon-orange';
                    $eIconI   = $exc->status === 'failed' ? 'bi-x-circle-fill' : 'bi-exclamation-triangle-fill';
                    $eDrawer = [
                        'name'        => $eName,
                        'initial'     => strtoupper(substr($eName, 0, 1)),
                        'ref'         => $eRef,
                        'loanRef'     => $exc->loan ? 'Loan #'.str_pad($exc->loan->id, 7, '0', STR_PAD_LEFT) : 'No loan linked',
                        'method'      => ucfirst(str_replace('_', ' ', $exc->payment_method ?? '')),
                        'amountCents' => $exc->amount_cents,
                        'unapplied'   => $exc->unapplied_cents,
                        'extAmt'      => null,
                        'diff'        => null,
                        'status'      => $exc->status,
                        'statusLabel' => $eReason,
                        'receivedAt'  => $exc->received_at?->format('d M Y H:i') ?? '—',
                        'postedAt'    => $exc->posted_at?->format('d M Y H:i') ?? '—',
                        'editUrl'     => route('payments.edit', $exc),
                    ];
                @endphp
                <div class="rec-exc-item"
                     onclick="openRecDrawer({{ $exc->id }}, {{ json_encode($eDrawer) }})">
                    <div class="rec-exc-row1">
                        <div class="rec-exc-icon {{ $eIconCls }}">
                            <i class="bi {{ $eIconI }}"></i>
                        </div>
                        <div>
                            <span class="rec-exc-reason">{{ $eReason }}</span>
                            <span class="rec-exc-meta">
                                {{ $exc->payment_method === 'mpesa' ? 'M-Pesa' : ucfirst($exc->payment_method ?? 'Payment') }}
                                &middot; {{ Str::limit($eRef, 14) }}
                            </span>
                        </div>
                    </div>
                    <div class="rec-exc-row2">
                        <div>
                            <div class="rec-exc-amount">KES {{ number_format($exc->amount_cents / 100) }}</div>
                            <div class="rec-exc-time">{{ $exc->received_at?->diffForHumans() ?? '—' }}</div>
                        </div>
                        <a href="{{ route('payments.edit', $exc) }}" class="rec-exc-review-btn"
                           onclick="event.stopPropagation()">
                            Review
                        </a>
                    </div>
                </div>
            @empty
                <div class="rec-exc-empty">
                    <div class="rec-exc-empty-icon">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <p class="rec-exc-empty-title">All clear</p>
                    <p class="rec-exc-empty-sub">No open exceptions for this period.</p>
                </div>
            @endforelse
        </div>
    </div>

</div>{{-- /rec-workspace --}}

</div>{{-- /rec-page --}}

{{-- ═══ DETAIL DRAWER ═══════════════════════════════════════════════════════ --}}
<div class="rec-overlay" id="recOverlay" onclick="closeRecDrawer()"></div>
<aside class="rec-drawer" id="recDrawer" role="complementary" aria-label="Transaction details">

    <div class="rec-dr-head">
        <div class="rec-dr-head-info">
            <h2 class="rec-dr-head-title" id="recDrTitle">Transaction Details</h2>
            <span class="rec-dr-head-sub" id="recDrSub">—</span>
        </div>
        <button class="rec-dr-close" onclick="closeRecDrawer()" aria-label="Close drawer">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="rec-dr-body" id="recDrBody">
        <div style="padding:48px 20px;text-align:center;color:#b2bcc8;font-size:12px">
            <i class="bi bi-arrow-left" style="font-size:20px;display:block;margin-bottom:10px;opacity:.4"></i>
            Select a row to view transaction details.
        </div>
    </div>

    <div class="rec-dr-foot" id="recDrFoot" style="display:none">
        <a href="#" id="recDrInvestBtn" class="rec-dr-btn">
            <i class="bi bi-arrow-up-right"></i> Investigate
        </a>
        <a href="#" id="recDrResolveBtn" class="rec-dr-btn rec-dr-btn-primary">
            <i class="bi bi-check2-circle"></i> Mark Resolved
        </a>
    </div>

</aside>

@endsection

@push('scripts')
<script>
(function () {

    const fmt = (cents) => 'KES\u00a0' + Math.round(cents / 100).toLocaleString('en-KE');

    const statusConfig = {
        posted:     { badgeCls: 'rec-dr-hero-badge-green',  icon: 'bi-check2-circle',          label: 'Matched' },
        pending:    { badgeCls: 'rec-dr-hero-badge-amber',  icon: 'bi-hourglass-split',         label: 'Pending' },
        processing: { badgeCls: 'rec-dr-hero-badge-amber',  icon: 'bi-arrow-repeat',            label: 'Processing' },
        failed:     { badgeCls: 'rec-dr-hero-badge-red',    icon: 'bi-x-circle',                label: 'Exception' },
        reversed:   { badgeCls: 'rec-dr-hero-badge-grey',   icon: 'bi-arrow-counterclockwise',  label: 'Reversed' },
    };

    function buildDrawer(data) {
        const st  = statusConfig[data.status] || { badgeCls: 'rec-dr-hero-badge-grey', icon: 'bi-circle', label: data.statusLabel || data.status };
        const ext = data.extAmt;
        const hasDiff = data.diff && data.diff > 0;

        const extHtml = ext !== null
            ? `<span class="rec-dr-compare-value">${fmt(ext)}</span><span class="rec-dr-compare-note">M-Pesa received</span>`
            : `<span class="rec-dr-compare-value" style="font-size:13px;color:#b2bcc8">Not linked</span><span class="rec-dr-compare-note">No external record</span>`;

        const diffPill = hasDiff
            ? `<span class="rec-dr-diff-pill rec-dr-diff-pill-warn"><i class="bi bi-exclamation-triangle"></i> KES\u00a0${(data.diff / 100).toLocaleString('en-KE')} mismatch</span>`
            : ext !== null
                ? `<span class="rec-dr-diff-pill rec-dr-diff-pill-ok"><i class="bi bi-check2-circle"></i> Amounts match</span>`
                : `<span class="rec-dr-diff-pill" style="background:#f5f6f8;color:#98a2b3"><i class="bi bi-dash"></i> No external record</span>`;

        const tl2Cls   = data.status === 'posted' ? 'rec-dr-tl-dot-done'   : 'rec-dr-tl-dot-warn';
        const tl2Icon  = data.status === 'posted' ? 'bi-check'             : 'bi-dash';
        const tl2Label = data.status === 'posted' ? 'Reconciliation matched' : 'Awaiting reconciliation';
        const tl2Time  = data.postedAt || '—';

        const tl3 = data.status !== 'posted'
            ? `<div class="rec-dr-tl-item">
                <div class="rec-dr-tl-dot rec-dr-tl-dot-grey"><i class="bi bi-circle"></i></div>
                <div class="rec-dr-tl-content">
                    <span class="rec-dr-tl-label" style="color:#b2bcc8">Pending resolution</span>
                    <span class="rec-dr-tl-time">—</span>
                </div>
              </div>`
            : '';

        return `
<div class="rec-dr-hero">
    <span class="rec-dr-hero-amount">${fmt(data.amountCents)}</span>
    <span class="rec-dr-hero-ref">${data.ref}</span>
    <span class="rec-dr-hero-badge ${st.badgeCls}">
        <i class="bi ${st.icon}"></i> ${data.statusLabel || st.label}
    </span>
</div>

<div class="rec-dr-section-wrap">
    <div class="rec-dr-section-title">Client</div>
    <div class="rec-dr-identity">
        <div class="rec-dr-avatar">${data.initial}</div>
        <div>
            <span class="rec-dr-id-name">${data.name}</span>
            <span class="rec-dr-id-loan">${data.loanRef}</span>
        </div>
    </div>
</div>

<div class="rec-dr-section-wrap">
    <div class="rec-dr-section-title">Transaction Details</div>
    <div class="rec-dr-meta-grid">
        <div class="rec-dr-meta-item">
            <label>Channel</label>
            <span>${data.method || '—'}</span>
        </div>
        <div class="rec-dr-meta-item">
            <label>Amount</label>
            <span>${fmt(data.amountCents)}</span>
        </div>
        <div class="rec-dr-meta-item">
            <label>Received</label>
            <span>${data.receivedAt}</span>
        </div>
        <div class="rec-dr-meta-item">
            <label>Posted</label>
            <span>${data.postedAt || '—'}</span>
        </div>
        ${data.unapplied > 0 ? `
        <div class="rec-dr-meta-item" style="grid-column:1/-1">
            <label>Unapplied</label>
            <span style="color:#d97706">${fmt(data.unapplied)} unapplied balance</span>
        </div>` : ''}
    </div>
</div>

${data.mpesaBalance ? `
<div class="rec-dr-section-wrap" style="background:#f0fdf9;border:1px solid #a7f3d0;border-radius:12px;padding:14px 16px;margin-bottom:2px">
    <div class="rec-dr-section-title" style="color:#0d7459;margin-bottom:10px">
        <i class="bi bi-phone-fill" style="margin-right:6px"></i> M-Pesa Account Balance
    </div>
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
        <div>
            <span style="font-size:22px;font-weight:760;color:#0d7459;letter-spacing:-.03em">
                KES ${Math.round(data.mpesaBalance / 100).toLocaleString()}
            </span>
            <span style="display:block;font-size:10px;color:#059669;margin-top:3px">
                Paybill working-account balance at time of this transaction
            </span>
        </div>
        <div style="text-align:right">
            <span style="font-size:10px;color:#6ee7b7;background:#065f46;padding:3px 9px;border-radius:20px;font-weight:650">
                <i class="bi bi-clock" style="margin-right:3px"></i>
                ${data.mpesaBalanceAt || 'at transaction time'}
            </span>
            ${data.phoneNumber ? `<span style="display:block;font-size:10px;color:#047857;margin-top:5px"><i class="bi bi-telephone-fill" style="margin-right:4px"></i>${data.phoneNumber}</span>` : ''}
        </div>
    </div>
</div>
` : ''}

<div class="rec-dr-section-wrap">
    <div class="rec-dr-section-title">Comparison</div>
    <div class="rec-dr-compare-grid">
        <div class="rec-dr-compare-cell">
            <label>Internal</label>
            <span class="rec-dr-compare-value">${fmt(data.amountCents)}</span>
            <span class="rec-dr-compare-note">Expected amount</span>
        </div>
        <div class="rec-dr-vs">VS</div>
        <div class="rec-dr-compare-cell">
            <label>External</label>
            ${extHtml}
        </div>
    </div>
    <div class="rec-dr-diff-result">${diffPill}</div>
</div>

<div class="rec-dr-section-wrap">
    <div class="rec-dr-section-title">Timeline</div>
    <div class="rec-dr-timeline">
        <div class="rec-dr-tl-item">
            <div class="rec-dr-tl-dot rec-dr-tl-dot-done"><i class="bi bi-check"></i></div>
            <div class="rec-dr-tl-content">
                <span class="rec-dr-tl-label">Payment received</span>
                <span class="rec-dr-tl-time">${data.receivedAt}</span>
            </div>
        </div>
        <div class="rec-dr-tl-item">
            <div class="rec-dr-tl-dot ${tl2Cls}"><i class="bi ${tl2Icon}"></i></div>
            <div class="rec-dr-tl-content">
                <span class="rec-dr-tl-label">${tl2Label}</span>
                <span class="rec-dr-tl-time">${tl2Time}</span>
            </div>
        </div>
        ${tl3}
    </div>
</div>`;
    }

    window.openRecDrawer = function (id, data) {
        const overlay     = document.getElementById('recOverlay');
        const drawer      = document.getElementById('recDrawer');
        const body        = document.getElementById('recDrBody');
        const foot        = document.getElementById('recDrFoot');
        const title       = document.getElementById('recDrTitle');
        const sub         = document.getElementById('recDrSub');
        const investigBtn = document.getElementById('recDrInvestBtn');
        const resolveBtn  = document.getElementById('recDrResolveBtn');

        title.textContent = 'Transaction ' + data.ref;
        sub.textContent   = (data.method || 'Payment') + ' · ' + data.receivedAt;

        if (data.editUrl) {
            investigBtn.href = data.editUrl;
            resolveBtn.href  = data.editUrl;
        }

        body.innerHTML    = buildDrawer(data);
        foot.style.display = 'flex';

        overlay.classList.add('open');
        drawer.classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    window.closeRecDrawer = function () {
        document.getElementById('recOverlay').classList.remove('open');
        document.getElementById('recDrawer').classList.remove('open');
        document.body.style.overflow = '';
    };

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') window.closeRecDrawer();
    });

}());
</script>
@endpush
