@extends('layouts.app')

@section('title', 'M-Pesa Payment Reversals')

@push('styles')
<style>
/* ─────────────────────────────────────────────────────────
   PAGE SHELL
───────────────────────────────────────────────────────── */
.rev-page { max-width: 1520px; margin: 0 auto; }

/* ─────────────────────────────────────────────────────────
   BREADCRUMB
───────────────────────────────────────────────────────── */
.rev-breadcrumb {
    display: flex; align-items: center; gap: 6px;
    padding: 0 var(--pg) 14px;
    font-size: 11px; color: #98a2b3; font-weight: 500;
}
.rev-breadcrumb a {
    color: #667085; text-decoration: none; transition: color .14s;
}
.rev-breadcrumb a:hover { color: var(--primary); }
.rev-breadcrumb i { font-size: 9px; color: #c0c8d8; }
.rev-breadcrumb span { color: #344054; font-weight: 600; }

/* ─────────────────────────────────────────────────────────
   PAGE HEADER BAND
───────────────────────────────────────────────────────── */
.rev-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    padding: 0 var(--pg) 20px; gap: 16px;
}
.rev-header-left { display: flex; align-items: flex-start; gap: 14px; }
.rev-header-icon {
    width: 40px; height: 40px; border-radius: 11px; flex-shrink: 0;
    background: linear-gradient(135deg, #e0e1ff, #c7c8f6);
    color: var(--primary); font-size: 18px;
    display: grid; place-items: center;
    margin-top: 2px;
}
.rev-header h1 {
    font-size: 22px; font-weight: 760; color: #101828;
    letter-spacing: -.03em; margin: 0 0 4px; line-height: 1.2;
}
.rev-header p { font-size: 12px; color: #667085; margin: 0; }
.rev-header-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }

.rev-help-link {
    display: inline-flex; align-items: center; gap: 6px;
    height: 36px; padding: 0 14px;
    border: 1px solid var(--line); border-radius: 9px;
    background: #fff; color: #344054;
    font-size: 11.5px; font-weight: 600; text-decoration: none;
    transition: border-color .14s, color .14s, background .14s;
}
.rev-help-link:hover { border-color: #c7c8f6; color: var(--primary); background: #fafaff; }
.rev-help-link i { color: #98a2b3; font-size: 13px; }

.rev-cta-btn {
    display: inline-flex; align-items: center; gap: 7px;
    height: 36px; padding: 0 16px;
    background: var(--primary); border: 1px solid var(--primary);
    border-radius: 9px; color: #fff;
    font-size: 11.5px; font-weight: 650; text-decoration: none; cursor: pointer;
    transition: background .14s, box-shadow .14s;
    box-shadow: 0 1px 4px rgba(91,92,226,.25);
}
.rev-cta-btn:hover { background: #4a4bcf; box-shadow: 0 4px 14px rgba(91,92,226,.3); color: #fff; }

/* ─────────────────────────────────────────────────────────
   STAT CARDS — 4-up with sparklines
───────────────────────────────────────────────────────── */
.rev-stats {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 14px; padding: 0 var(--pg) 20px;
}
.rev-stat {
    background: #fff; border: 1px solid var(--line);
    border-radius: 16px; padding: 18px 20px 16px;
    box-shadow: 0 1px 4px rgba(16,24,40,.05);
    position: relative; overflow: hidden;
    transition: box-shadow .18s, transform .18s;
}
.rev-stat:hover { box-shadow: 0 6px 22px rgba(16,24,40,.09); transform: translateY(-1px); }
.rev-stat::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0;
    height: 3px; border-radius: 16px 16px 0 0;
}
.rev-stat-green::before  { background: linear-gradient(90deg, #10b981, #34d399); }
.rev-stat-blue::before   { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
.rev-stat-amber::before  { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.rev-stat-rose::before   { background: linear-gradient(90deg, #ef4444, #f87171); }

.rev-stat-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    margin-bottom: 14px;
}
.rev-stat-icon {
    width: 40px; height: 40px; border-radius: 11px;
    display: grid; place-items: center; font-size: 17px; flex-shrink: 0;
}
.rev-stat-green .rev-stat-icon  { background: #ecfdf5; color: #059669; }
.rev-stat-blue  .rev-stat-icon  { background: #eff6ff; color: #2563eb; }
.rev-stat-amber .rev-stat-icon  { background: #fffbeb; color: #d97706; }
.rev-stat-rose  .rev-stat-icon  { background: #fef2f2; color: #dc2626; }

.rev-stat-delta {
    display: inline-flex; align-items: center; gap: 3px;
    font-size: 10px; font-weight: 700; padding: 2px 7px;
    border-radius: 20px;
}
.rev-stat-delta-up   { background: #ecfdf5; color: #059669; }
.rev-stat-delta-down { background: #fef2f2; color: #dc2626; }
.rev-stat-delta-flat { background: #f5f6f8; color: #98a2b3; }

/* Sparkline bars */
.rev-sparkline {
    display: flex; align-items: flex-end; gap: 2px; height: 36px;
    margin-bottom: 10px;
}
.rev-spark-bar {
    flex: 1; border-radius: 3px 3px 0 0;
    background: #e9eafd;
    transition: background .15s;
}
.rev-stat-green  .rev-spark-bar { background: #bbf7d0; }
.rev-stat-green  .rev-spark-bar.active { background: #10b981; }
.rev-stat-blue   .rev-spark-bar { background: #bfdbfe; }
.rev-stat-blue   .rev-spark-bar.active { background: #3b82f6; }
.rev-stat-amber  .rev-spark-bar { background: #fde68a; }
.rev-stat-amber  .rev-spark-bar.active { background: #f59e0b; }
.rev-stat-rose   .rev-spark-bar { background: #fecaca; }
.rev-stat-rose   .rev-spark-bar.active { background: #ef4444; }

.rev-stat-label { font-size: 11px; font-weight: 500; color: #667085; margin-bottom: 4px; }
.rev-stat-value {
    font-size: 23px; font-weight: 760; color: #101828;
    letter-spacing: -.03em; line-height: 1.1;
}
.rev-stat-note { font-size: 10px; color: #98a2b3; margin-top: 5px; font-weight: 500; }
.rev-stat-note strong { color: #475467; }

/* ─────────────────────────────────────────────────────────
   MAIN LAYOUT  (table panel + sidebar)
───────────────────────────────────────────────────────── */
.rev-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 296px;
    gap: 16px;
    align-items: start;
    padding: 0 var(--pg);
}

/* ─────────────────────────────────────────────────────────
   TABLE PANEL
───────────────────────────────────────────────────────── */
.rev-panel {
    background: #fff; border: 1px solid var(--line);
    border-radius: 16px; overflow: hidden;
    box-shadow: 0 1px 4px rgba(16,24,40,.05);
}

/* Tab bar + date range row */
.rev-panel-nav {
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 20px; border-bottom: 1px solid var(--line);
    gap: 12px; flex-wrap: wrap;
}
.rev-tabs {
    display: flex; align-items: center; gap: 0; padding: 0;
    overflow-x: auto; scrollbar-width: none;
}
.rev-tabs::-webkit-scrollbar { display: none; }
.rev-tab {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 14px 16px 13px;
    font-size: 12px; font-weight: 600; color: #667085;
    text-decoration: none; white-space: nowrap;
    border-bottom: 2px solid transparent;
    transition: color .14s, border-color .14s;
    cursor: pointer; background: none; border-top: 0; border-left: 0; border-right: 0;
}
.rev-tab:hover { color: #344054; border-bottom-color: #e4e7ec; }
.rev-tab.active { color: var(--primary); border-bottom-color: var(--primary); }
.rev-tab-count {
    min-width: 20px; height: 18px; padding: 0 5px;
    border-radius: 20px; font-size: 10px; font-weight: 700;
    display: inline-flex; align-items: center; justify-content: center;
    background: #f0f2f5; color: #667085;
    transition: background .14s, color .14s;
}
.rev-tab.active .rev-tab-count { background: #e8e9ff; color: var(--primary); }

/* Date range picker row */
.rev-date-range {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 20px; border-bottom: 1px solid var(--line);
    justify-content: space-between; flex-wrap: wrap;
}
.rev-date-pill {
    display: inline-flex; align-items: center; gap: 8px;
    height: 34px; padding: 0 12px;
    border: 1px solid var(--line); border-radius: 8px; background: #fff;
    font-size: 11.5px; font-weight: 600; color: #344054; cursor: pointer;
    transition: border-color .14s;
}
.rev-date-pill:hover { border-color: #c7c8f6; }
.rev-date-pill i { color: #667085; font-size: 13px; }
.rev-date-nav {
    width: 30px; height: 30px; border-radius: 8px;
    border: 1px solid var(--line); background: #fff; color: #667085;
    display: grid; place-items: center; cursor: pointer;
    font-size: 12px; transition: border-color .14s, color .14s;
    text-decoration: none;
}
.rev-date-nav:hover { border-color: #c7c8f6; color: var(--primary); }
.rev-date-controls { display: flex; align-items: center; gap: 6px; }

/* Filter strip */
.rev-filters {
    display: flex; align-items: center; gap: 8px;
    padding: 12px 20px; border-bottom: 1px solid var(--line); flex-wrap: wrap;
}
.rev-search {
    flex: 1; max-width: 320px; height: 36px;
    display: flex; align-items: center; gap: 8px;
    border: 1px solid var(--line); border-radius: 9px;
    padding: 0 12px; background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
.rev-search:focus-within {
    border-color: #a5b4fc; box-shadow: 0 0 0 3px rgba(99,102,241,.09);
}
.rev-search i { color: #c0c8d8; font-size: 12px; flex-shrink: 0; }
.rev-search input {
    border: 0; outline: 0; min-width: 0; width: 100%;
    font-size: 11.5px; color: #344054; background: transparent;
}
.rev-select {
    height: 36px; border: 1px solid var(--line); border-radius: 9px;
    background: #fff; color: #475467; padding: 0 10px;
    font-size: 11.5px; cursor: pointer;
    transition: border-color .15s;
}
.rev-select:focus { border-color: #a5b4fc; outline: none; box-shadow: 0 0 0 3px rgba(99,102,241,.09); }
.rev-filter-btn {
    height: 36px; display: inline-flex; align-items: center; gap: 6px;
    border-radius: 9px; padding: 0 14px; font-size: 11.5px; font-weight: 650;
    cursor: pointer; border: 1px solid var(--primary);
    background: var(--primary); color: #fff; transition: background .14s;
}
.rev-filter-btn:hover { background: #4a4bcf; }
.rev-clear-btn {
    height: 36px; display: inline-flex; align-items: center; gap: 6px;
    border-radius: 9px; padding: 0 12px; font-size: 11.5px; font-weight: 600;
    cursor: pointer; border: 1px solid var(--line);
    background: #fff; color: #667085; text-decoration: none;
    transition: border-color .14s, color .14s;
}
.rev-clear-btn:hover { border-color: #c7c8f6; color: var(--primary); }

/* ─────────────────────────────────────────────────────────
   REVERSALS TABLE
───────────────────────────────────────────────────────── */
.rev-table-wrap { overflow-x: auto; }
.rev-table {
    width: 100%; border-collapse: collapse; min-width: 900px;
}
.rev-table thead tr { border-bottom: 2px solid var(--line); }
.rev-table th {
    padding: 10px 14px;
    font-size: 9.5px; font-weight: 700; letter-spacing: .07em;
    text-transform: uppercase; color: #98a2b3; text-align: left; white-space: nowrap;
}
.rev-table th:first-child { padding-left: 20px; }
.rev-table th:last-child  { padding-right: 20px; text-align: center; }
.rev-table tbody tr {
    border-bottom: 1px solid #f5f6f8;
    transition: background .12s;
    cursor: pointer;
}
.rev-table tbody tr:hover  { background: #fafaff; }
.rev-table tbody tr.active { background: #f3f3ff; }
.rev-table tbody tr:last-child { border-bottom: 0; }
.rev-table td {
    padding: 13px 14px; vertical-align: middle;
}
.rev-table td:first-child { padding-left: 20px; }
.rev-table td:last-child  { padding-right: 20px; }

/* Checkbox column */
.rev-table th.col-check, .rev-table td.col-check {
    width: 40px; padding-right: 4px;
}
.rev-chk {
    width: 16px; height: 16px; border-radius: 4px;
    border: 1.5px solid #d0d5dd; cursor: pointer; accent-color: var(--primary);
}

/* Date / Time cell */
.rev-cell-dt { display: flex; flex-direction: column; gap: 2px; }
.rev-cell-dt strong {
    font-size: 12px; font-weight: 650; color: #101828; display: block;
}
.rev-cell-dt small { font-size: 10px; color: #98a2b3; }

/* Borrower cell */
.rev-borrower { display: flex; align-items: center; gap: 10px; }
.rev-avatar {
    width: 32px; height: 32px; border-radius: 9px; flex-shrink: 0;
    background: linear-gradient(135deg, #e0e1ff, #c7c8f6);
    color: #4b4fc4; font-size: 11px; font-weight: 750;
    display: grid; place-items: center;
}
.rev-bname { font-size: 12px; font-weight: 650; color: #101828; display: block; line-height: 1.3; }
.rev-bref  { font-size: 10px; color: #98a2b3; display: block; margin-top: 1px; }

/* Receipt cell */
.rev-receipt { display: inline-flex; align-items: center; gap: 6px; }
.rev-receipt code {
    font-size: 11.5px; font-weight: 650; color: #344054;
    font-family: 'SF Mono', 'Fira Code', monospace; letter-spacing: .01em;
    background: #f5f6f8; padding: 2px 6px; border-radius: 5px;
}
.rev-copy-btn {
    width: 22px; height: 22px; border-radius: 5px;
    border: 1px solid var(--line); background: #fff;
    color: #98a2b3; display: grid; place-items: center;
    cursor: pointer; font-size: 11px; transition: all .14s;
}
.rev-copy-btn:hover { border-color: #c7c8f6; color: var(--primary); background: #f5f5ff; }

/* Amount */
.rev-amount {
    font-size: 13px; font-weight: 750; color: #101828;
    letter-spacing: -.015em; display: block;
    font-variant-numeric: tabular-nums;
}

/* Method badge */
.rev-method {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 9px; border-radius: 6px;
    font-size: 10.5px; font-weight: 650;
}
.rev-method-mpesa  { background: #ecfdf5; color: #027a48; }
.rev-method-bank   { background: #f4f0ff; color: #6941c6; }
.rev-method-cash   { background: #fffbeb; color: #b54708; }
.rev-method-credit { background: #eff6ff; color: #1d4ed8; }
.rev-method-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: currentColor; flex-shrink: 0;
}

/* Status pill */
.rev-status {
    display: inline-flex; align-items: center; gap: 5px;
    border-radius: 20px; padding: 4px 10px;
    font-size: 10px; font-weight: 700; letter-spacing: .02em; white-space: nowrap;
}
.rev-status::before {
    content: ''; width: 5px; height: 5px;
    border-radius: 50%; background: currentColor; flex-shrink: 0;
}
.rev-status-completed  { color: #067647; background: #ecfdf3; }
.rev-status-pending    { color: #b54708; background: #fffaeb; }
.rev-status-processing { color: #0369a1; background: #e0f2fe; }
.rev-status-failed     { color: #b42318; background: #fff1f0; }
.rev-status-reversed   { color: #6941c6; background: #f4f0ff; }

/* Reason */
.rev-reason {
    font-size: 10.5px; color: #475467; font-weight: 500;
    max-width: 140px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

/* Reversed-by */
.rev-by { display: flex; flex-direction: column; gap: 1px; }
.rev-by strong { font-size: 11px; font-weight: 650; color: #344054; display: block; }
.rev-by small  { font-size: 10px; color: #98a2b3; }

/* Action buttons */
.rev-actions { display: flex; align-items: center; gap: 6px; justify-content: center; }
.rev-view-btn {
    display: inline-flex; align-items: center; gap: 5px;
    height: 28px; padding: 0 10px;
    border: 1px solid var(--line); border-radius: 7px;
    background: #fff; color: #344054;
    font-size: 10.5px; font-weight: 650; text-decoration: none;
    transition: all .14s; cursor: pointer;
}
.rev-view-btn:hover { border-color: #c7c8f6; background: #f5f5ff; color: var(--primary); }
.rev-more-btn {
    width: 28px; height: 28px; border-radius: 7px;
    border: 1px solid var(--line); background: #fff;
    color: #98a2b3; display: grid; place-items: center;
    cursor: pointer; font-size: 13px; transition: all .14s;
    position: relative;
}
.rev-more-btn:hover { border-color: #c7c8f6; color: #344054; }

/* Dropdown menu */
.rev-dropdown {
    position: absolute; top: calc(100% + 4px); right: 0;
    min-width: 160px; background: #fff;
    border: 1px solid var(--line); border-radius: 10px;
    box-shadow: 0 8px 24px rgba(16,24,40,.12);
    z-index: 50; overflow: hidden;
    display: none;
}
.rev-dropdown.open { display: block; }
.rev-dropdown-item {
    display: flex; align-items: center; gap: 9px;
    padding: 9px 13px; font-size: 11.5px; font-weight: 600;
    color: #344054; cursor: pointer; transition: background .12s;
    text-decoration: none; border: 0; background: none; width: 100%;
    text-align: left;
}
.rev-dropdown-item:hover { background: #f9fafb; }
.rev-dropdown-item.danger { color: #b42318; }
.rev-dropdown-item.danger:hover { background: #fff1f0; }
.rev-dropdown-item i { font-size: 13px; color: #98a2b3; flex-shrink: 0; }
.rev-dropdown-item.danger i { color: #f04438; }
.rev-dropdown-divider { height: 1px; background: var(--line); margin: 4px 0; }

/* Empty state */
.rev-empty {
    text-align: center; padding: 56px 20px;
}
.rev-empty-icon {
    width: 56px; height: 56px; border-radius: 14px;
    background: linear-gradient(135deg, #f0f2f5, #eaecf0);
    display: grid; place-items: center; margin: 0 auto 16px;
    font-size: 24px; color: #c0c8d8;
}
.rev-empty strong { display: block; font-size: 13px; color: #344054; margin-bottom: 6px; }
.rev-empty small  { font-size: 11px; color: #98a2b3; }

/* Pagination row */
.rev-pagination {
    padding: 14px 20px; border-top: 1px solid var(--line);
    display: flex; align-items: center; justify-content: space-between;
}
.rev-pagination small { font-size: 10.5px; color: #98a2b3; font-weight: 500; }

/* ─────────────────────────────────────────────────────────
   SIDEBAR
───────────────────────────────────────────────────────── */
.rev-side { display: grid; gap: 14px; }

/* Reversal Details panel */
.rev-detail-panel {
    background: #fff; border: 1px solid var(--line);
    border-radius: 16px; overflow: hidden;
    box-shadow: 0 1px 4px rgba(16,24,40,.05);
}
.rev-detail-head {
    padding: 16px 18px 14px; border-bottom: 1px solid var(--line);
    display: flex; align-items: flex-start; justify-content: space-between; gap: 10px;
}
.rev-detail-head-left h2 {
    font-size: 13px; font-weight: 700; color: #0d1320; margin: 0 0 2px;
}
.rev-detail-head-left p { font-size: 10px; color: #98a2b3; margin: 0; }
.rev-detail-tag {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 9.5px; font-weight: 700; letter-spacing: .03em;
    padding: 3px 8px; border-radius: 20px;
    background: #f4f0ff; color: #6941c6; flex-shrink: 0;
}
.rev-detail-tag i { font-size: 10px; }

/* Empty placeholder */
.rev-detail-empty {
    padding: 28px 18px; text-align: center;
}
.rev-detail-empty-icon {
    width: 56px; height: 56px; border-radius: 14px;
    background: linear-gradient(135deg, #f5f6f8, #eef0f3);
    display: grid; place-items: center; margin: 0 auto 14px;
    font-size: 26px; color: #c0c8d8;
    border: 1.5px dashed #e4e7ec;
}
.rev-detail-empty strong {
    display: block; font-size: 12px; font-weight: 700; color: #344054; margin-bottom: 5px;
}
.rev-detail-empty p { font-size: 10.5px; color: #98a2b3; margin: 0; line-height: 1.6; }

/* Loaded detail content */
.rev-detail-body { padding: 0 18px 16px; }

/* Identity row */
.rev-detail-identity {
    display: flex; align-items: center; gap: 11px;
    padding: 16px 0 14px; border-bottom: 1px solid var(--line);
}
.rev-detail-avatar {
    width: 40px; height: 40px; border-radius: 11px; flex-shrink: 0;
    background: linear-gradient(135deg, #e0e1ff, #c7c8f6);
    color: #4b4fc4; font-size: 13px; font-weight: 750;
    display: grid; place-items: center;
}
.rev-detail-name { font-size: 13px; font-weight: 700; color: #0d1320; display: block; }
.rev-detail-sub  { font-size: 10.5px; color: #667085; display: block; margin-top: 2px; }
.rev-detail-identity .ml-auto { margin-left: auto; flex-shrink: 0; }

/* Meta grid */
.rev-detail-meta {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 13px; padding: 14px 0; border-bottom: 1px solid var(--line);
}
.rev-detail-meta-item label {
    font-size: 9px; font-weight: 700; letter-spacing: .08em;
    text-transform: uppercase; color: #98a2b3; display: block; margin-bottom: 3px;
}
.rev-detail-meta-item span {
    font-size: 11.5px; font-weight: 600; color: #0d1320; display: block;
}
.rev-detail-meta-item small { font-size: 10px; color: #667085; display: block; margin-top: 1px; }

/* Reason box */
.rev-detail-reason-box {
    margin: 14px 0 0;
    padding: 11px 13px; border-radius: 10px;
    background: #fffbeb; border: 1px solid #fde68a;
}
.rev-detail-reason-box label {
    font-size: 9px; font-weight: 700; letter-spacing: .08em;
    text-transform: uppercase; color: #92400e; display: block; margin-bottom: 4px;
}
.rev-detail-reason-box p {
    font-size: 11.5px; color: #78350f; margin: 0; line-height: 1.55;
}

/* Audit trail */
.rev-audit-trail { margin-top: 14px; }
.rev-audit-label {
    font-size: 10.5px; font-weight: 700; color: #0d1320;
    margin-bottom: 10px; display: flex; align-items: center; gap: 6px;
}
.rev-audit-label span { font-size: 9.5px; font-weight: 600; color: #98a2b3; margin-left: auto; }
.rev-audit-step {
    display: flex; align-items: flex-start; gap: 10px;
    padding-bottom: 12px; position: relative;
}
.rev-audit-step::before {
    content: ''; position: absolute; left: 14px; top: 26px;
    bottom: 0; width: 1px; background: #f0f2f5;
}
.rev-audit-step:last-child::before { display: none; }
.rev-audit-dot {
    width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
    display: grid; place-items: center; font-size: 11px;
    border: 2px solid #e4e7ec; background: #fff; color: #98a2b3;
    position: relative; z-index: 1;
}
.rev-audit-dot.done { background: #ecfdf3; border-color: #a9efc5; color: #067647; }
.rev-audit-dot.warn { background: #fffaeb; border-color: #fde68a; color: #b54708; }
.rev-audit-dot.fail { background: #fff1f0; border-color: #fca5a5; color: #b42318; }
.rev-audit-text strong { font-size: 11.5px; font-weight: 650; color: #344054; display: block; }
.rev-audit-text small  { font-size: 10px; color: #98a2b3; }

/* Detail footer */
.rev-detail-foot {
    padding: 13px 18px; border-top: 1px solid var(--line);
    display: flex; gap: 8px;
}
.rev-detail-foot-btn {
    flex: 1; height: 35px; border-radius: 8px;
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    font-size: 11px; font-weight: 650; cursor: pointer; text-decoration: none;
    border: 1px solid var(--line); background: #fff; color: #344054;
    transition: all .14s;
}
.rev-detail-foot-btn:hover { border-color: #c7c8f6; background: #f5f5ff; color: var(--primary); }
.rev-detail-foot-btn-primary {
    background: var(--primary); border-color: var(--primary); color: #fff;
}
.rev-detail-foot-btn-primary:hover { background: #4a4bcf; color: #fff; }

/* ─────────────────────────────────────────────────────────
   QUICK ACTIONS PANEL
───────────────────────────────────────────────────────── */
.rev-quick-panel {
    background: #fff; border: 1px solid var(--line);
    border-radius: 16px; padding: 18px;
    box-shadow: 0 1px 4px rgba(16,24,40,.05);
}
.rev-quick-head {
    display: flex; align-items: center; gap: 9px; margin-bottom: 14px;
}
.rev-quick-head-icon {
    width: 30px; height: 30px; border-radius: 8px;
    background: #eef4ff; color: #175cd3; font-size: 14px;
    display: grid; place-items: center; flex-shrink: 0;
}
.rev-quick-head h2 { font-size: 12.5px; font-weight: 700; color: #0d1320; margin: 0; }
.rev-quick-list { display: grid; gap: 7px; }
.rev-quick-item {
    display: flex; align-items: center; gap: 11px;
    padding: 11px 13px;
    border: 1px solid var(--line); border-radius: 10px;
    color: #344054; text-decoration: none;
    font-size: 11px; font-weight: 650;
    transition: all .14s; cursor: pointer;
    background: none; width: 100%; text-align: left;
}
.rev-quick-item:hover { border-color: #c7c8f6; background: #fafaff; color: var(--primary); }
.rev-quick-item:hover .rev-qi-icon { background: #e8e9ff; color: var(--primary); }
.rev-qi-icon {
    width: 30px; height: 30px; border-radius: 8px;
    background: #f5f6f8; color: #667085; font-size: 14px;
    display: grid; place-items: center; flex-shrink: 0;
    transition: background .14s, color .14s;
}
.rev-qi-chevron { margin-left: auto; color: #c0c8d8; font-size: 10px; flex-shrink: 0; }

/* ─────────────────────────────────────────────────────────
   ABOUT PANEL
───────────────────────────────────────────────────────── */
.rev-about-panel {
    background: linear-gradient(135deg, #fafaff, #f4f4fd);
    border: 1px solid #e8e9ff; border-radius: 16px; padding: 16px 18px;
}
.rev-about-panel-icon {
    width: 34px; height: 34px; border-radius: 9px;
    background: #e8e9ff; color: var(--primary); font-size: 16px;
    display: grid; place-items: center; margin-bottom: 10px;
}
.rev-about-panel h3 { font-size: 12px; font-weight: 700; color: #0d1320; margin: 0 0 6px; }
.rev-about-panel p  { font-size: 10.5px; color: #667085; margin: 0; line-height: 1.65; }
.rev-about-learn {
    display: inline-flex; align-items: center; gap: 5px;
    margin-top: 10px; font-size: 10.5px; font-weight: 650;
    color: var(--primary); text-decoration: none; transition: gap .14s;
}
.rev-about-learn:hover { gap: 7px; }

/* ─────────────────────────────────────────────────────────
   SKELETON LOADER
───────────────────────────────────────────────────────── */
.rev-skeleton { animation: rev-pulse 1.4s ease-in-out infinite; }
@keyframes rev-pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
.rev-skel-line { height: 11px; background: #f0f2f5; border-radius: 6px; margin-bottom: 9px; }

/* ─────────────────────────────────────────────────────────
   TOAST
───────────────────────────────────────────────────────── */
.rev-toast {
    position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%) translateY(12px);
    background: #0d1320; color: #fff; border-radius: 10px;
    padding: 10px 18px; font-size: 12px; font-weight: 600;
    display: flex; align-items: center; gap: 8px;
    box-shadow: 0 8px 24px rgba(10,16,30,.25);
    opacity: 0; pointer-events: none;
    transition: opacity .2s, transform .2s;
    z-index: 200; white-space: nowrap;
}
.rev-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
.rev-toast i { color: #86efac; font-size: 14px; }

/* ─────────────────────────────────────────────────────────
   RESPONSIVE
───────────────────────────────────────────────────────── */
@media (max-width: 1200px) {
    .rev-layout  { grid-template-columns: 1fr; }
    .rev-stats   { grid-template-columns: repeat(2, 1fr); }
    .rev-side    { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 760px) {
    .rev-stats   { grid-template-columns: 1fr 1fr; }
    .rev-side    { grid-template-columns: 1fr; }
    .rev-header  { flex-direction: column; }
    .rev-filters { padding-left: 14px; padding-right: 14px; }
    .rev-search  { max-width: none; flex-basis: 100%; order: -1; }
}
</style>
@endpush

@section('content')
@php
    $allCount       = $reversals->total();
    $completedCount = $statusCounts['completed'] ?? 0;
    $pendingCount   = $statusCounts['pending']   ?? 0;
    $failedCount    = $statusCounts['failed']    ?? 0;

    $activeTab = $filterStatus ?: 'all';

    $periodStart = now()->subDays(match($period) {
        'today' => 0,
        '7'     => 7,
        '90'    => 90,
        default => 30,
    })->startOfDay();
    $periodEnd = now();
@endphp

<div class="rev-page" id="revPage">

    {{-- ═══ BREADCRUMB ═══════════════════════════════════════════════════ --}}
    <nav class="rev-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('payments.index') }}"><i class="bi bi-house-door" style="font-size:11px"></i></a>
        <i class="bi bi-chevron-right"></i>
        <a href="{{ route('payments.index') }}">Payments</a>
        <i class="bi bi-chevron-right"></i>
        <span>Payment Reversals</span>
    </nav>

    {{-- ═══ PAGE HEADER ════════════════════════════════════════════════════ --}}
    <div class="rev-header">
        <div class="rev-header-left">
            <div class="rev-header-icon">
                <i class="bi bi-arrow-counterclockwise"></i>
            </div>
            <div>
                <h1>M-Pesa Payment Reversals</h1>
                <p>Review, process and track payments removed from active balances.</p>
            </div>
        </div>
        <div class="rev-header-actions">
            <a href="#" class="rev-help-link" data-bs-toggle="tooltip" title="How reversals work">
                <i class="bi bi-question-circle"></i> How reversals work?
            </a>
            <button class="rev-cta-btn" onclick="openRecordReversalModal()">
                <i class="bi bi-plus-lg"></i> Record Reversal
            </button>
        </div>
    </div>

    {{-- ═══ STAT CARDS ═══════════════════════════════════════════════════ --}}
    <div class="rev-stats">

        {{-- Total Reversed --}}
        <div class="rev-stat rev-stat-green">
            <div class="rev-stat-top">
                <div class="rev-stat-icon"><i class="bi bi-arrow-counterclockwise"></i></div>
                @if($stats['delta_pct'] > 0)
                    <span class="rev-stat-delta rev-stat-delta-up">
                        <i class="bi bi-arrow-up-short"></i>+{{ $stats['delta_pct'] }}%
                    </span>
                @elseif($stats['delta_pct'] < 0)
                    <span class="rev-stat-delta rev-stat-delta-down">
                        <i class="bi bi-arrow-down-short"></i>{{ $stats['delta_pct'] }}%
                    </span>
                @else
                    <span class="rev-stat-delta rev-stat-delta-flat">0%</span>
                @endif
            </div>
            <div class="rev-sparkline" aria-hidden="true">
                @foreach($stats['sparkline'] as $i => $h)
                    <div class="rev-spark-bar {{ $i === count($stats['sparkline'])-1 ? 'active' : '' }}" style="height:{{ $h }}%"></div>
                @endforeach
            </div>
            <div class="rev-stat-label">Total Reversed</div>
            <div class="rev-stat-value">KSh {{ number_format($stats['total_cents'] / 100) }}</div>
            <div class="rev-stat-note"><strong>{{ $allCount }}</strong> transactions</div>
        </div>

        {{-- Completed --}}
        <div class="rev-stat rev-stat-blue">
            <div class="rev-stat-top">
                <div class="rev-stat-icon"><i class="bi bi-check2-circle"></i></div>
                <span class="rev-stat-delta {{ $stats['completed_pct'] >= 100 ? 'rev-stat-delta-up' : 'rev-stat-delta-flat' }}">
                    {{ $stats['completed_pct'] }}%
                </span>
            </div>
            <div class="rev-sparkline" aria-hidden="true">
                @foreach($stats['completed_sparkline'] as $i => $h)
                    <div class="rev-spark-bar {{ $i === count($stats['completed_sparkline'])-1 ? 'active' : '' }}" style="height:{{ $h }}%"></div>
                @endforeach
            </div>
            <div class="rev-stat-label">Completed</div>
            <div class="rev-stat-value">KSh {{ number_format($stats['completed_cents'] / 100) }}</div>
            <div class="rev-stat-note"><strong>{{ $completedCount }}</strong> transactions</div>
        </div>

        {{-- Pending Review --}}
        <div class="rev-stat rev-stat-amber">
            <div class="rev-stat-top">
                <div class="rev-stat-icon"><i class="bi bi-clock-history"></i></div>
                <span class="rev-stat-delta rev-stat-delta-flat">
                    {{ $stats['total_cents'] > 0 ? round(($stats['pending_cents'] / $stats['total_cents']) * 100) : 0 }}%
                </span>
            </div>
            <div class="rev-sparkline" aria-hidden="true">
                @foreach([10,10,10,10,10,10,10] as $h)
                    <div class="rev-spark-bar" style="height:{{ $h }}%"></div>
                @endforeach
            </div>
            <div class="rev-stat-label">Pending Review</div>
            <div class="rev-stat-value">KSh {{ number_format($stats['pending_cents'] / 100) }}</div>
            <div class="rev-stat-note"><strong>{{ $pendingCount }}</strong> transactions</div>
        </div>

        {{-- Failed / Rejected --}}
        <div class="rev-stat rev-stat-rose">
            <div class="rev-stat-top">
                <div class="rev-stat-icon"><i class="bi bi-x-circle"></i></div>
                <span class="rev-stat-delta rev-stat-delta-flat">
                    {{ $stats['total_cents'] > 0 ? round(($stats['failed_cents'] / $stats['total_cents']) * 100) : 0 }}%
                </span>
            </div>
            <div class="rev-sparkline" aria-hidden="true">
                @foreach([10,10,10,10,10,10,10] as $h)
                    <div class="rev-spark-bar" style="height:{{ $h }}%"></div>
                @endforeach
            </div>
            <div class="rev-stat-label">Failed / Rejected</div>
            <div class="rev-stat-value">KSh {{ number_format($stats['failed_cents'] / 100) }}</div>
            <div class="rev-stat-note"><strong>{{ $failedCount }}</strong> transactions</div>
        </div>
    </div>

    {{-- ═══ TWO-COLUMN LAYOUT ═════════════════════════════════════════════ --}}
    <div class="rev-layout">

        {{-- ── MAIN TABLE PANEL ─────────────────────────────────────── --}}
        <section class="rev-panel">

            {{-- Status tabs + date range --}}
            <div class="rev-panel-nav">
                <nav class="rev-tabs" role="tablist" aria-label="Reversal status filters">
                    @php
                        $tabs = [
                            'all'        => ['label' => 'All',       'count' => $allCount],
                            'pending'    => ['label' => 'Pending',    'count' => $pendingCount],
                            'completed'  => ['label' => 'Completed',  'count' => $completedCount],
                            'failed'     => ['label' => 'Failed',     'count' => $failedCount],
                        ];
                    @endphp
                    @foreach($tabs as $tabKey => $tab)
                        <a href="{{ route('payments.reversals', array_merge(request()->except('status','page'), $tabKey !== 'all' ? ['status' => $tabKey] : [])) }}"
                           class="rev-tab {{ $activeTab === $tabKey ? 'active' : '' }}"
                           role="tab"
                           aria-selected="{{ $activeTab === $tabKey ? 'true' : 'false' }}">
                            {{ $tab['label'] }}
                            <span class="rev-tab-count">{{ $tab['count'] }}</span>
                        </a>
                    @endforeach
                </nav>
            </div>

            {{-- Date range row --}}
            <div class="rev-date-range">
                <div class="rev-date-controls">
                    <a href="{{ route('payments.reversals', array_merge(request()->except('period'), ['period' => $prevPeriod])) }}"
                       class="rev-date-nav" title="Previous period">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                    <div class="rev-date-pill">
                        <i class="bi bi-calendar3"></i>
                        {{ $periodStart->format('d M Y') }} – {{ $periodEnd->format('d M Y') }}
                    </div>
                    <a href="{{ route('payments.reversals', array_merge(request()->except('period'), ['period' => $nextPeriod])) }}"
                       class="rev-date-nav" title="Next period">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </div>
                <div style="display:flex;align-items:center;gap:8px">
                    @foreach(['today' => 'Today', '7' => '7d', '30' => '30d', '90' => '90d'] as $v => $l)
                        <a href="{{ route('payments.reversals', array_merge(request()->except('period'), ['period' => $v])) }}"
                           class="rev-date-nav {{ $period === $v ? 'active' : '' }}"
                           style="{{ $period === $v ? 'border-color:var(--primary);color:var(--primary);background:#f5f5ff' : '' }}">
                            <span style="font-size:9px;font-weight:700">{{ $l }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('payments.reversals') }}" class="rev-filters" id="revFilterForm">
                @if($filterStatus)
                    <input type="hidden" name="status" value="{{ $filterStatus }}">
                @endif
                <input type="hidden" name="period" value="{{ $period }}">
                <div class="rev-search">
                    <i class="bi bi-search"></i>
                    <input type="search" name="search" value="{{ $search }}"
                           placeholder="Search by borrower, phone, receipt or transaction ID..."
                           aria-label="Search reversals">
                </div>
                <select name="method" class="rev-select" aria-label="Filter by method">
                    <option value="">All methods</option>
                    @foreach(['mpesa' => 'M-Pesa', 'bank_transfer' => 'Bank transfer', 'cash' => 'Cash', 'credit_balance' => 'Credit balance'] as $val => $lbl)
                        <option value="{{ $val }}" @selected($filterMethod === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
                <select name="status_filter" class="rev-select" aria-label="Filter by status">
                    <option value="">All statuses</option>
                    @foreach(['reversed' => 'Reversed', 'pending' => 'Pending', 'failed' => 'Failed'] as $val => $lbl)
                        <option value="{{ $val }}" @selected($filterStatus === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
                <select name="user_filter" class="rev-select" aria-label="Filter by user">
                    <option value="">All users</option>
                    @foreach($adminUsers as $u)
                        <option value="{{ $u->id }}" @selected($filterUser == $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="rev-filter-btn">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                @if($search || $filterMethod || $filterStatus || $filterUser)
                    <a href="{{ route('payments.reversals', ['period' => $period]) }}" class="rev-clear-btn">
                        <i class="bi bi-x"></i> Clear
                    </a>
                @endif
            </form>

            {{-- Table --}}
            <div class="rev-table-wrap">
                <table class="rev-table" aria-label="Payment reversals">
                    <thead>
                        <tr>
                            <th class="col-check">
                                <input type="checkbox" class="rev-chk" id="checkAll" aria-label="Select all">
                            </th>
                            <th>Date &amp; Time</th>
                            <th>Borrower</th>
                            <th>Phone Number</th>
                            <th>Original Receipt</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Reason</th>
                            <th>Reversed By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($reversals as $payment)
                        @php
                            $borrower  = $payment->borrower ?? $payment->loan?->borrower;
                            $bName     = $borrower?->user?->name ?? 'Unmatched';
                            $bInitial  = strtoupper(substr($bName, 0, 1));
                            $bPhone    = $borrower?->phone_number ?? '—';
                            $loanRef   = $payment->loan ? 'BRW-' . str_pad($payment->loan->borrower_id ?? 0, 5, '0', STR_PAD_LEFT) : 'No loan';
                            $receipt   = $payment->mpesa_receipt_number ?: ($payment->external_reference ?: '—');
                            $ts        = $payment->reversed_at ?? $payment->received_at;

                            $methodKey   = $payment->payment_method ?? 'mpesa';
                            $methodLabel = match($methodKey) {
                                'mpesa'          => 'M-Pesa',
                                'bank_transfer'  => 'Bank transfer',
                                'cash'           => 'Cash',
                                'credit_balance' => 'Credit balance',
                                default          => ucfirst($methodKey),
                            };
                            $methodClass = match($methodKey) {
                                'mpesa'          => 'rev-method-mpesa',
                                'bank_transfer'  => 'rev-method-bank',
                                'cash'           => 'rev-method-cash',
                                'credit_balance' => 'rev-method-credit',
                                default          => 'rev-method-mpesa',
                            };

                            $revStatus = $payment->status;
                            $statusLabel = match($revStatus) {
                                'reversed'   => 'Completed',
                                'pending'    => 'Pending',
                                'failed'     => 'Failed',
                                'processing' => 'Processing',
                                default      => ucfirst($revStatus),
                            };
                            $statusClass = match($revStatus) {
                                'reversed'   => 'rev-status-completed',
                                'pending'    => 'rev-status-pending',
                                'failed'     => 'rev-status-failed',
                                'processing' => 'rev-status-processing',
                                default      => 'rev-status-reversed',
                            };

                            $reason    = $payment->reversal_reason ?: '—';
                            $reversedBy = 'System';   // will be enriched when audit log is available
                            $reversedAt = $payment->reversed_at;
                        @endphp
                        <tr class="rev-row"
                            data-payment-id="{{ $payment->id }}"
                            onclick="selectReversal({{ $payment->id }}, this)">
                            <td class="col-check" onclick="event.stopPropagation()">
                                <input type="checkbox" class="rev-chk row-chk" value="{{ $payment->id }}" aria-label="Select row">
                            </td>
                            <td>
                                <div class="rev-cell-dt">
                                    <strong>{{ optional($ts)->format('d M Y') ?? '—' }}</strong>
                                    <small>{{ optional($ts)->format('H:i A') ?? '' }}</small>
                                </div>
                            </td>
                            <td>
                                <div class="rev-borrower">
                                    <span class="rev-avatar">{{ $bInitial }}</span>
                                    <div>
                                        <span class="rev-bname">{{ $bName }}</span>
                                        <span class="rev-bref">{{ $loanRef }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span style="font-size:11.5px;color:#344054;font-weight:500;font-variant-numeric:tabular-nums">{{ $bPhone }}</span>
                            </td>
                            <td>
                                @if($receipt !== '—')
                                    <div class="rev-receipt">
                                        <code>{{ $receipt }}</code>
                                        <button class="rev-copy-btn"
                                                onclick="event.stopPropagation(); copyText('{{ $receipt }}', this)"
                                                title="Copy receipt number"
                                                aria-label="Copy receipt">
                                            <i class="bi bi-copy"></i>
                                        </button>
                                    </div>
                                @else
                                    <span style="font-size:11px;color:#98a2b3">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="rev-amount">KSh {{ number_format($payment->amount_cents / 100, 2) }}</span>
                            </td>
                            <td>
                                <span class="rev-method {{ $methodClass }}">
                                    <span class="rev-method-dot"></span>
                                    {{ $methodLabel }}
                                </span>
                            </td>
                            <td>
                                <span class="rev-status {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                            <td>
                                <span class="rev-reason" title="{{ $reason }}">{{ $reason }}</span>
                            </td>
                            <td>
                                <div class="rev-by">
                                    <strong>{{ $reversedBy }}</strong>
                                    <small>{{ optional($reversedAt)->format('d M Y') ?? optional($ts)->format('d M Y') ?? '' }}</small>
                                </div>
                            </td>
                            <td>
                                <div class="rev-actions">
                                    <button class="rev-view-btn"
                                            onclick="event.stopPropagation(); selectReversal({{ $payment->id }}, document.querySelector('[data-payment-id=\'{{ $payment->id }}\']'))"
                                            aria-label="View reversal details">
                                        <i class="bi bi-eye" style="font-size:11px"></i> View
                                    </button>
                                    <div style="position:relative">
                                        <button class="rev-more-btn"
                                                onclick="event.stopPropagation(); toggleDropdown({{ $payment->id }})"
                                                aria-label="More actions"
                                                aria-haspopup="true">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <div class="rev-dropdown" id="drop-{{ $payment->id }}" role="menu">
                                            <a href="{{ route('payments.edit', $payment) }}"
                                               class="rev-dropdown-item" role="menuitem"
                                               onclick="event.stopPropagation()">
                                                <i class="bi bi-pencil"></i> Edit payment
                                            </a>
                                            <a href="{{ route('payments.receipt', $payment) }}"
                                               class="rev-dropdown-item" role="menuitem"
                                               target="_blank" rel="noopener"
                                               onclick="event.stopPropagation()">
                                                <i class="bi bi-receipt"></i> View receipt
                                            </a>
                                            <div class="rev-dropdown-divider"></div>
                                            <form method="POST"
                                                  action="{{ route('payments.reverse', $payment) }}"
                                                  id="reverse-form-{{ $payment->id }}"
                                                  onsubmit="return confirmReverse(event, {{ $payment->id }})">
                                                @csrf
                                                <input type="hidden" name="reason" value="Manual re-reversal by administrator">
                                                <button type="submit" class="rev-dropdown-item danger" role="menuitem">
                                                    <i class="bi bi-arrow-counterclockwise"></i> Re-reverse
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11">
                                <div class="rev-empty">
                                    <div class="rev-empty-icon">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </div>
                                    <strong>No reversals found</strong>
                                    <small>
                                        @if($search || $filterMethod || $filterStatus)
                                            Try adjusting your filters or clearing the search.
                                        @else
                                            Reversed payments will appear here automatically.
                                        @endif
                                    </small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="rev-pagination">
                <small>Showing {{ $reversals->firstItem() ?? 0 }} to {{ $reversals->lastItem() ?? 0 }} of {{ number_format($reversals->total()) }} records</small>
                {{ $reversals->links() }}
            </div>
        </section>

        {{-- ── SIDEBAR ─────────────────────────────────────────────── --}}
        <aside class="rev-side">

            {{-- Reversal Details panel --}}
            <div class="rev-detail-panel">
                <div class="rev-detail-head">
                    <div class="rev-detail-head-left">
                        <h2>Reversal Details</h2>
                        <p>Select a reversal to view full details</p>
                    </div>
                    <span class="rev-detail-tag"><i class="bi bi-shield-check"></i> Audit</span>
                </div>

                {{-- Empty state (shown until a row is selected) --}}
                <div id="revDetailEmpty" class="rev-detail-empty">
                    <div class="rev-detail-empty-icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <strong>No reversal selected</strong>
                    <p>Choose a record from the list to see detailed information, audit trail and related transactions.</p>
                </div>

                {{-- Loaded content --}}
                <div id="revDetailContent" style="display:none">
                    <div class="rev-detail-body" id="revDetailBody"></div>
                    <div class="rev-detail-foot" id="revDetailFoot">
                        <a href="#" class="rev-detail-foot-btn" id="revEditBtn">
                            <i class="bi bi-pencil" style="font-size:11px"></i> Edit
                        </a>
                        <a href="#" class="rev-detail-foot-btn rev-detail-foot-btn-primary" id="revReceiptBtn" target="_blank" rel="noopener">
                            <i class="bi bi-receipt" style="font-size:11px"></i> Receipt
                        </a>
                    </div>
                </div>

                {{-- Skeleton (shown while loading) --}}
                <div id="revDetailSkeleton" style="display:none;padding:0 18px 16px">
                    <div class="rev-skeleton">
                        <div style="display:flex;align-items:center;gap:11px;padding:16px 0;border-bottom:1px solid var(--line)">
                            <div style="width:40px;height:40px;border-radius:11px;background:#f0f2f5;flex-shrink:0"></div>
                            <div style="flex:1">
                                <div class="rev-skel-line" style="width:65%"></div>
                                <div class="rev-skel-line" style="width:45%;margin-bottom:0"></div>
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:13px;padding:14px 0;border-bottom:1px solid var(--line)">
                            @for($i=0;$i<4;$i++)
                                <div>
                                    <div class="rev-skel-line" style="width:50%;height:8px"></div>
                                    <div class="rev-skel-line" style="margin-bottom:0"></div>
                                </div>
                            @endfor
                        </div>
                        <div style="margin-top:14px">
                            <div class="rev-skel-line" style="height:60px;border-radius:10px;margin-bottom:0"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="rev-quick-panel">
                <div class="rev-quick-head">
                    <div class="rev-quick-head-icon"><i class="bi bi-lightning-charge-fill"></i></div>
                    <h2>Quick Actions</h2>
                </div>
                <div class="rev-quick-list">
                    <button class="rev-quick-item" onclick="openRecordReversalModal()">
                        <span class="rev-qi-icon"><i class="bi bi-plus-circle"></i></span>
                        Record new reversal
                        <i class="bi bi-chevron-right rev-qi-chevron"></i>
                    </button>
                    <a class="rev-quick-item" href="{{ route('payments.index') }}">
                        <span class="rev-qi-icon"><i class="bi bi-upload"></i></span>
                        Import reversals
                        <i class="bi bi-chevron-right rev-qi-chevron"></i>
                    </a>
                    <a class="rev-quick-item" href="{{ route('payments.index', ['view' => 'reversals', 'export' => 1]) }}">
                        <span class="rev-qi-icon"><i class="bi bi-download"></i></span>
                        Export data
                        <i class="bi bi-chevron-right rev-qi-chevron"></i>
                    </a>
                    <a class="rev-quick-item" href="#">
                        <span class="rev-qi-icon"><i class="bi bi-gear"></i></span>
                        Reversal settings
                        <i class="bi bi-chevron-right rev-qi-chevron"></i>
                    </a>
                </div>
            </div>

            {{-- About M-Pesa Reversals --}}
            <div class="rev-about-panel">
                <div class="rev-about-panel-icon"><i class="bi bi-info-circle"></i></div>
                <h3>About M-Pesa Reversals</h3>
                <p>Payment reversals are transactions that remove previously posted payments from active loan balances. All reversals are audit-tracked and require proper authorization.</p>
                <a href="#" class="rev-about-learn">Learn more <i class="bi bi-arrow-right"></i></a>
            </div>
        </aside>
    </div>{{-- end .rev-layout --}}
</div>

{{-- Toast --}}
<div class="rev-toast" id="revToast" role="status" aria-live="polite">
    <i class="bi bi-check-circle-fill"></i>
    <span id="revToastMsg">Copied!</span>
</div>

{{-- Record Reversal Modal --}}
<div class="modal fade" id="recordReversalModal" tabindex="-1" aria-labelledby="recordReversalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
        <div class="modal-content" style="border-radius:16px;border:1px solid var(--line);overflow:hidden">
            <div style="padding:22px 24px 18px;border-bottom:1px solid var(--line);display:flex;align-items:flex-start;justify-content:space-between">
                <div>
                    <h5 style="font-size:15px;font-weight:750;color:#0d1320;margin:0 0 3px" id="recordReversalModalLabel">
                        <i class="bi bi-arrow-counterclockwise" style="color:var(--primary);margin-right:6px"></i>Record Reversal
                    </h5>
                    <p style="font-size:11px;color:#98a2b3;margin:0">Manually record a payment reversal with reason</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="margin-top:2px"></button>
            </div>
            <form method="POST" id="recordReversalForm" action="#">
                @csrf
                <div style="padding:22px 24px">
                    <div style="margin-bottom:16px">
                        <label style="font-size:10.5px;font-weight:700;color:#344054;display:block;margin-bottom:6px;letter-spacing:.03em;text-transform:uppercase">
                            Payment / Receipt Number
                        </label>
                        <input type="text" name="receipt" placeholder="e.g. QKF7RT3X9N"
                               class="form-control" style="font-size:12px;border-radius:9px"
                               required>
                    </div>
                    <div style="margin-bottom:16px">
                        <label style="font-size:10.5px;font-weight:700;color:#344054;display:block;margin-bottom:6px;letter-spacing:.03em;text-transform:uppercase">
                            Reversal Reason
                        </label>
                        <select name="reason" class="form-select" style="font-size:12px;border-radius:9px" required>
                            <option value="">Select reason…</option>
                            <option value="Duplicate payment">Duplicate payment</option>
                            <option value="Wrong allocation">Wrong allocation</option>
                            <option value="Customer request">Customer request</option>
                            <option value="Payment error">Payment error</option>
                            <option value="Loan closed">Loan closed</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div id="otherReasonWrap" style="display:none;margin-bottom:16px">
                        <label style="font-size:10.5px;font-weight:700;color:#344054;display:block;margin-bottom:6px;letter-spacing:.03em;text-transform:uppercase">
                            Custom Reason
                        </label>
                        <textarea name="custom_reason" rows="2" maxlength="500"
                                  class="form-control" style="font-size:12px;border-radius:9px"
                                  placeholder="Describe the reason for this reversal…"></textarea>
                    </div>
                </div>
                <div style="padding:14px 24px;border-top:1px solid var(--line);display:flex;gap:10px;background:#fafafa">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="flex:1">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center">
                        <i class="bi bi-arrow-counterclockwise"></i> Record Reversal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // ── State ─────────────────────────────────────────────────────────────
    let activeId   = null;
    let activeRow  = null;
    let loadingId  = null;

    const empty    = document.getElementById('revDetailEmpty');
    const content  = document.getElementById('revDetailContent');
    const skeleton = document.getElementById('revDetailSkeleton');
    const body     = document.getElementById('revDetailBody');
    const editBtn  = document.getElementById('revEditBtn');
    const rcptBtn  = document.getElementById('revReceiptBtn');

    // ── Select a reversal row ─────────────────────────────────────────────
    window.selectReversal = function (id, rowEl) {
        if (loadingId === id) return;

        // Deactivate previous
        if (activeRow) activeRow.classList.remove('active');
        if (activeId === id) {
            // Toggle off
            activeId = activeRow = null;
            showEmpty();
            return;
        }

        activeId  = id;
        activeRow = rowEl;
        if (rowEl) rowEl.classList.add('active');

        showSkeleton();

        loadingId = id;
        fetch(`/admin/payments/${id}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
        .then(r => { if (!r.ok) throw new Error('Request failed'); return r.json(); })
        .then(data => {
            if (loadingId !== id) return;   // stale — a newer click came in
            loadingId = null;
            renderDetail(data);
        })
        .catch(() => {
            loadingId = null;
            body.innerHTML = `
                <div style="padding:30px 0;text-align:center;color:#98a2b3;font-size:12px">
                    <i class="bi bi-exclamation-circle" style="font-size:28px;display:block;margin-bottom:10px;color:#f04438"></i>
                    Could not load reversal details.<br>
                    <button onclick="selectReversal(${id}, activeRow)" style="margin-top:10px;font-size:11px;color:var(--primary);background:none;border:0;cursor:pointer;font-weight:650;">Try again</button>
                </div>`;
            showContent();
        });
    };

    function showEmpty()    { empty.style.display=''; content.style.display='none'; skeleton.style.display='none'; }
    function showSkeleton() { empty.style.display='none'; content.style.display='none'; skeleton.style.display=''; }
    function showContent()  { empty.style.display='none'; content.style.display=''; skeleton.style.display='none'; }

    // ── Render detail ─────────────────────────────────────────────────────
    function renderDetail(d) {
        const statusClass = {
            posted: 'rev-status-completed', reversed: 'rev-status-completed',
            pending: 'rev-status-pending', processing: 'rev-status-processing',
            failed: 'rev-status-failed',
        }[d.status] || 'rev-status-reversed';

        const statusLabel = d.status === 'reversed' ? 'Completed' : capitalize(d.status);

        // Allocation rows
        const allocRows = (d.allocations || []).map(a => `
            <tr>
                <td>
                    <span style="display:inline-flex;align-items:center;gap:7px">
                        <span style="width:8px;height:8px;border-radius:50%;background:${a.color};display:inline-block;flex-shrink:0"></span>
                        ${a.type}
                    </span>
                </td>
                <td>${a.amount}</td>
                <td style="text-align:right">${a.percentage}%</td>
            </tr>`
        ).join('');

        // Reversal reason box
        const reasonHtml = d.reversal_reason ? `
            <div class="rev-detail-reason-box">
                <label>Reversal Reason</label>
                <p>${escHtml(d.reversal_reason)}</p>
            </div>` : '';

        // Audit timeline
        const auditSteps = [
            { icon: 'bi-arrow-down-left', label: 'Payment received', time: d.received_at, cls: 'done' },
            { icon: 'bi-check2-circle',   label: 'Payment posted',   time: d.posted_at || d.received_at, cls: 'done' },
            { icon: 'bi-arrow-counterclockwise', label: 'Reversal triggered', time: d.reversed_at, cls: d.reversed_at ? 'warn' : '' },
            { icon: 'bi-shield-check', label: 'Audit record created', time: d.reversed_at, cls: d.reversed_at ? 'done' : '' },
        ];

        const auditHtml = auditSteps.filter(s => s.time).map(s => `
            <div class="rev-audit-step">
                <div class="rev-audit-dot ${s.cls}"><i class="bi ${s.icon}"></i></div>
                <div class="rev-audit-text">
                    <strong>${s.label}</strong>
                    <small>${s.time || '—'}</small>
                </div>
            </div>`
        ).join('');

        body.innerHTML = `
            <div class="rev-detail-identity">
                <div class="rev-detail-avatar">${escHtml(d.initial || '?')}</div>
                <div>
                    <span class="rev-detail-name">${escHtml(d.name)}</span>
                    <span class="rev-detail-sub">${escHtml(d.loan_ref || 'No loan linked')}${d.loan_product ? ' · ' + escHtml(d.loan_product) : ''}</span>
                </div>
                <div class="ml-auto">
                    <span class="rev-status ${statusClass}">${statusLabel}</span>
                </div>
            </div>

            <div class="rev-detail-meta">
                <div class="rev-detail-meta-item">
                    <label>Receipt No.</label>
                    <span style="font-family:'SF Mono','Fira Code',monospace;font-size:11px">${escHtml(d.reference)}</span>
                </div>
                <div class="rev-detail-meta-item">
                    <label>Method</label>
                    <span>${escHtml(d.method)}</span>
                </div>
                <div class="rev-detail-meta-item">
                    <label>Received</label>
                    <span>${d.received_at || '—'}</span>
                </div>
                <div class="rev-detail-meta-item">
                    <label>Reversed</label>
                    <span>${d.reversed_at || '—'}</span>
                </div>
                <div class="rev-detail-meta-item">
                    <label>Amount</label>
                    <span style="font-weight:750;font-size:13px;color:#0d1320">KSh ${fmtMoney(d.amount_cents)}</span>
                </div>
                <div class="rev-detail-meta-item">
                    <label>Reversed By</label>
                    <span>${escHtml(d.processed_by || 'System')}</span>
                </div>
            </div>

            ${reasonHtml}

            ${allocRows ? `
            <div class="rev-audit-label" style="margin-top:16px">
                Allocation breakdown
                <span>Original waterfall</span>
            </div>
            <table style="width:100%;border-collapse:collapse;border-radius:10px;overflow:hidden;border:1px solid var(--line)">
                <thead>
                    <tr style="background:#f9fafb">
                        <th style="padding:7px 11px;font-size:9px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#98a2b3;text-align:left;border-bottom:1px solid var(--line)">Type</th>
                        <th style="padding:7px 11px;font-size:9px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#98a2b3;text-align:left;border-bottom:1px solid var(--line)">Amount</th>
                        <th style="padding:7px 11px;font-size:9px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#98a2b3;text-align:right;border-bottom:1px solid var(--line)">%</th>
                    </tr>
                </thead>
                <tbody>${allocRows}</tbody>
                <tfoot>
                    <tr style="background:#f9fafb;border-top:2px solid var(--line)">
                        <td style="padding:8px 11px;font-size:11px;font-weight:750;color:#0d1320">Total</td>
                        <td style="padding:8px 11px;font-size:11px;font-weight:750;color:var(--primary)">${d.total_allocated}</td>
                        <td style="padding:8px 11px;text-align:right;font-size:11px">100%</td>
                    </tr>
                </tfoot>
            </table>` : ''}

            ${auditHtml ? `
            <div class="rev-audit-trail">
                <div class="rev-audit-label">Audit trail <span>Timeline</span></div>
                ${auditHtml}
            </div>` : ''}
        `;

        editBtn.href = d.edit_url || '#';
        rcptBtn.href = d.receipt_url || '#';
        showContent();
    }

    // ── Helpers ───────────────────────────────────────────────────────────
    function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }
    function fmtMoney(c)   { return Number(c / 100).toLocaleString('en-KE', {minimumFractionDigits:2}); }
    function escHtml(s)    {
        if (s == null) return '';
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Copy to clipboard ─────────────────────────────────────────────────
    window.copyText = function (text, btn) {
        navigator.clipboard.writeText(text).then(() => {
            btn.innerHTML = '<i class="bi bi-check2"></i>';
            setTimeout(() => { btn.innerHTML = '<i class="bi bi-copy"></i>'; }, 1600);
            showToast('Receipt number copied!');
        }).catch(() => { showToast('Copy failed'); });
    };

    function showToast(msg) {
        const toast = document.getElementById('revToast');
        document.getElementById('revToastMsg').textContent = msg;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 2400);
    }

    // ── Dropdown menus ────────────────────────────────────────────────────
    window.toggleDropdown = function (id) {
        const all = document.querySelectorAll('.rev-dropdown');
        const drop = document.getElementById(`drop-${id}`);
        all.forEach(d => { if (d !== drop) d.classList.remove('open'); });
        drop.classList.toggle('open');
    };
    document.addEventListener('click', () => {
        document.querySelectorAll('.rev-dropdown').forEach(d => d.classList.remove('open'));
    });

    // ── Confirm re-reverse ────────────────────────────────────────────────
    window.confirmReverse = function (e, id) {
        if (!confirm('Re-reverse this payment? This action is logged to the audit trail.')) {
            e.preventDefault();
            return false;
        }
        return true;
    };

    // ── Select all checkbox ───────────────────────────────────────────────
    const checkAll = document.getElementById('checkAll');
    if (checkAll) {
        checkAll.addEventListener('change', () => {
            document.querySelectorAll('.row-chk').forEach(c => { c.checked = checkAll.checked; });
        });
    }

    // ── Record reversal modal trigger ─────────────────────────────────────
    window.openRecordReversalModal = function () {
        const modal = document.getElementById('recordReversalModal');
        if (modal && typeof bootstrap !== 'undefined') {
            bootstrap.Modal.getOrCreateInstance(modal).show();
        }
    };

    // Show/hide custom reason textarea
    const reasonSelect = document.querySelector('#recordReversalForm select[name="reason"]');
    if (reasonSelect) {
        reasonSelect.addEventListener('change', () => {
            const wrap = document.getElementById('otherReasonWrap');
            if (wrap) wrap.style.display = reasonSelect.value === 'Other' ? '' : 'none';
        });
    }

    // ── ESC key closes dropdowns ──────────────────────────────────────────
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.rev-dropdown').forEach(d => d.classList.remove('open'));
        }
    });

})();
</script>
@endpush
