@extends('layouts.app')

@section('title', 'M-Pesa STK Payments')

@push('styles')
<style>
/* ── Page shell ─────────────────────────────────────────────────────────── */
.stk-page { max-width: 1520px; margin: 0 auto; }

/* ── Breadcrumb ─────────────────────────────────────────────────────────── */
.stk-breadcrumb {
    display: flex; align-items: center; gap: 6px;
    font-size: 11px; color: #98a2b3; margin-bottom: 14px;
}
.stk-breadcrumb a { color: #667085; text-decoration: none; }
.stk-breadcrumb a:hover { color: var(--primary); }
.stk-breadcrumb i { font-size: 9px; }

/* ── Page header ─────────────────────────────────────────────────────────── */
.stk-header {
    display: flex; align-items: center;
    justify-content: space-between; gap: 16px; margin-bottom: 22px;
}
.stk-header h1 {
    margin: 0 0 5px; font-size: 26px; font-weight: 760;
    letter-spacing: -.03em; color: #0d1320; display: flex;
    align-items: center; gap: 10px;
}
.stk-live-badge {
    display: inline-flex; align-items: center; gap: 5px;
    background: #ecfdf3; color: #067647; font-size: 10.5px;
    font-weight: 700; padding: 4px 10px; border-radius: 20px;
    letter-spacing: 0; vertical-align: middle; line-height: 1;
}
.stk-live-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: #12b76a; box-shadow: 0 0 0 3px rgba(18,183,106,.25);
}
.stk-header p { margin: 0; color: #98a2b3; font-size: 11.5px; }

/* ── Header actions ─────────────────────────────────────────────────────── */
.stk-header-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.stk-date-btn {
    height: 38px; display: inline-flex; align-items: center; gap: 7px;
    border: 1px solid var(--line); border-radius: 9px; background: #fff;
    padding: 0 13px; font-size: 11px; font-weight: 600; color: #475467;
    cursor: pointer; text-decoration: none; transition: border-color .15s;
}
.stk-date-btn:hover { border-color: #c7c8f6; color: var(--primary); }
.stk-date-btn .date-label { font-weight: 700; color: #101828; font-size: 11.5px; }
.stk-date-btn .date-sub { font-size: 9.5px; color: #98a2b3; display: block; }

.stk-sync-wrap { display: flex; align-items: stretch; }
.stk-sync-btn {
    height: 38px; display: inline-flex; align-items: center; gap: 7px;
    background: var(--primary); color: #fff; border: 1px solid var(--primary);
    border-right: 0; border-radius: 9px 0 0 9px; padding: 0 15px;
    font-size: 11.5px; font-weight: 700; cursor: pointer;
    transition: background .15s; white-space: nowrap;
}
.stk-sync-btn:hover { background: #4a4bcf; border-color: #4a4bcf; }
.stk-sync-caret {
    height: 38px; width: 34px; display: inline-flex; align-items: center;
    justify-content: center; background: #4a4bcf; color: #fff;
    border: 1px solid #4a4bcf; border-left: 1px solid rgba(255,255,255,.2);
    border-radius: 0 9px 9px 0; cursor: pointer;
    transition: background .15s;
}
.stk-sync-caret:hover { background: #3e3fbc; }

/* ── Stat cards ─────────────────────────────────────────────────────────── */
.stk-stat-grid {
    display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px; margin-bottom: 20px;
}
.stk-stat {
    background: #fff; border: 1px solid var(--line); border-radius: 14px;
    padding: 18px 20px 16px; position: relative; overflow: hidden;
}
.stk-stat::after {
    content: ''; position: absolute; right: -28px; top: -36px;
    width: 110px; height: 110px; border-radius: 50%;
    background: var(--stat-wash, #f5f6f8); opacity: .7; pointer-events: none;
}
.stk-stat-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    position: relative; z-index: 1;
}
.stk-stat-icon {
    width: 36px; height: 36px; border-radius: 10px; display: grid;
    place-items: center; background: var(--stat-wash, #f5f6f8);
    color: var(--stat-tone, #667085); font-size: 16px; flex-shrink: 0;
}
.stk-stat-badge {
    font-size: 10px; font-weight: 700; color: var(--stat-tone);
    display: flex; align-items: center; gap: 3px;
}
.stk-stat-label { color: #667085; font-size: 11px; margin-top: 18px; }
.stk-stat-value {
    font-size: 24px; font-weight: 760; letter-spacing: -.03em;
    color: #0d1320; margin-top: 2px;
}
.stk-stat-note { font-size: 10.5px; color: #98a2b3; margin-top: 5px; }

/* Stat sparkline placeholder */
.stk-sparkline {
    position: absolute; bottom: 14px; right: 14px; z-index: 1;
    pointer-events: none;
}

/* Colour variants */
.stk-stat-green { --stat-tone: #079455; --stat-wash: #e9faf2; }
.stk-stat-blue  { --stat-tone: #175cd3; --stat-wash: #edf4ff; }
.stk-stat-amber { --stat-tone: #dc6803; --stat-wash: #fff5e8; }
.stk-stat-rose  { --stat-tone: #d92d20; --stat-wash: #fff0ee; }

/* ── Tabs ───────────────────────────────────────────────────────────────── */
.stk-tabs {
    display: flex; gap: 0; padding: 0 22px;
    border-bottom: 2px solid var(--line); margin-bottom: 0;
}
.stk-tab {
    padding: 11px 14px; color: #667085; font-size: 11px; font-weight: 650;
    text-decoration: none; border-bottom: 2px solid transparent;
    margin-bottom: -2px; transition: color .15s, border-color .15s;
    display: inline-flex; align-items: center; gap: 7px; white-space: nowrap;
}
.stk-tab:hover { color: var(--primary); }
.stk-tab.active { color: var(--primary); border-bottom-color: var(--primary); }
.stk-tab-count {
    min-width: 18px; height: 18px; border-radius: 9px; padding: 0 5px;
    font-size: 9.5px; font-weight: 750; display: grid; place-items: center;
    background: #f0f1f5; color: #667085; transition: background .15s, color .15s;
}
.stk-tab.active .stk-tab-count { background: #efefff; color: var(--primary); }
.stk-tab-count.has-value { background: #fff0ee; color: #d92d20; }

/* ── Filter bar ─────────────────────────────────────────────────────────── */
.stk-filter-bar {
    display: flex; align-items: center; gap: 8px; padding: 14px 22px;
    flex-wrap: wrap;
}
.stk-search-box {
    height: 36px; flex: 1; min-width: 220px; max-width: 340px;
    display: flex; align-items: center; gap: 8px;
    border: 1px solid var(--line); border-radius: 9px; padding: 0 11px;
    background: #fff; transition: border-color .15s, box-shadow .15s;
}
.stk-search-box:focus-within {
    border-color: #a5b4fc; box-shadow: 0 0 0 3px rgba(99,102,241,.1);
}
.stk-search-box i { color: #b0bbc8; font-size: 13px; flex-shrink: 0; }
.stk-search-box input {
    border: 0; outline: 0; min-width: 0; width: 100%;
    font-size: 11.5px; color: #344054; background: transparent;
}
.stk-select {
    height: 36px; border: 1px solid var(--line); border-radius: 9px;
    background: #fff; color: #475467; padding: 0 10px; font-size: 11.5px;
    cursor: pointer; transition: border-color .15s;
}
.stk-select:focus {
    border-color: #a5b4fc; outline: none; box-shadow: 0 0 0 3px rgba(99,102,241,.1);
}
.stk-date-range {
    display: flex; align-items: center; gap: 0;
    border: 1px solid var(--line); border-radius: 9px; overflow: hidden; height: 36px;
}
.stk-date-input {
    height: 100%; border: 0; outline: 0; background: #fff; color: #475467;
    padding: 0 10px; font-size: 11.5px; width: 110px;
}
.stk-date-input:first-child { border-right: 1px solid var(--line); }
.stk-date-sep {
    padding: 0 7px; color: #98a2b3; font-size: 11px; background: #fff;
    border-right: 1px solid var(--line); height: 100%;
    display: flex; align-items: center;
}
.stk-date-icon {
    width: 34px; height: 100%; background: #f9fafb; display: flex;
    align-items: center; justify-content: center; color: #98a2b3; font-size: 13px;
}
.stk-filter-btn {
    height: 36px; display: inline-flex; align-items: center; gap: 6px;
    border-radius: 9px; padding: 0 14px; font-size: 11.5px; font-weight: 700;
    background: var(--primary); color: #fff; border: 1px solid var(--primary);
    cursor: pointer; transition: background .15s;
}
.stk-filter-btn:hover { background: #4a4bcf; }
.stk-export-btn {
    height: 36px; display: inline-flex; align-items: center; gap: 6px;
    border-radius: 9px; padding: 0 13px; font-size: 11.5px; font-weight: 650;
    background: #fff; color: #475467; border: 1px solid var(--line);
    cursor: pointer; transition: border-color .15s, color .15s; text-decoration: none;
}
.stk-export-btn:hover { border-color: #c7c8f6; color: var(--primary); }
.stk-more-btn {
    height: 36px; width: 36px; display: inline-flex; align-items: center;
    justify-content: center; border-radius: 9px; background: #fff;
    color: #667085; border: 1px solid var(--line); cursor: pointer;
    transition: border-color .15s, color .15s;
}
.stk-more-btn:hover { border-color: #c7c8f6; color: var(--primary); }

/* ── Table ──────────────────────────────────────────────────────────────── */
.stk-table-wrap { overflow-x: auto; }
.stk-table { width: 100%; border-collapse: collapse; min-width: 820px; }
.stk-table thead tr { border-bottom: 2px solid var(--line); }
.stk-table th {
    padding: 10px 14px; font-size: 9.5px; font-weight: 700;
    letter-spacing: .07em; text-transform: uppercase; color: #98a2b3;
    text-align: left; white-space: nowrap;
}
.stk-table th:first-child { padding-left: 22px; width: 42px; }
.stk-table th:last-child  { padding-right: 22px; }
.stk-table td { padding: 12px 14px; border-bottom: 1px solid #f5f6f8; vertical-align: middle; }
.stk-table td:first-child { padding-left: 22px; }
.stk-table td:last-child  { padding-right: 22px; }
.stk-table tbody tr { transition: background .12s; }
.stk-table tbody tr:hover { background: #fafbff; cursor: pointer; }
.stk-table tbody tr:last-child td { border-bottom: 0; }

/* Checkbox col */
.stk-checkbox {
    width: 16px; height: 16px; border: 1.5px solid #d0d5dd; border-radius: 4px;
    cursor: pointer; accent-color: var(--primary);
}

/* Table cells */
.stk-cell-dt { display: block; font-size: 11.5px; font-weight: 650; color: #101828; }
.stk-cell-sub { display: block; font-size: 10px; color: #98a2b3; margin-top: 2px; }
.stk-cell-ref { font-size: 11.5px; font-weight: 650; color: #344054; display: block; }
.stk-cell-phone { font-size: 11.5px; color: #344054; font-variant-numeric: tabular-nums; }
.stk-person { display: flex; align-items: center; gap: 9px; }
.stk-avatar {
    width: 30px; height: 30px; border-radius: 9px; flex-shrink: 0;
    background: linear-gradient(135deg, #e0e1ff, #c7c8f6);
    color: #4b4fc4; font-size: 11px; font-weight: 750;
    display: grid; place-items: center;
}
.stk-avatar-unknown { background: linear-gradient(135deg, #f0f2f5, #e4e7ec); color: #667085; }
.stk-p-name { display: block; font-size: 11.5px; font-weight: 650; color: #101828; }
.stk-p-sub  { display: block; font-size: 10px; color: #98a2b3; margin-top: 2px; }
.stk-loan   { font-size: 11.5px; color: #344054; font-weight: 600; }
.stk-amount { font-size: 12.5px; font-weight: 750; color: #0d1320; }

/* Status pill */
.stk-status {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 9px; border-radius: 20px; font-size: 9.5px; font-weight: 700;
    letter-spacing: .02em; white-space: nowrap;
}
.stk-status::before {
    content: ''; width: 5px; height: 5px; border-radius: 50%;
    background: currentColor; flex-shrink: 0;
}
.stk-s-success   { color: #067647; background: #ecfdf3; }
.stk-s-pending   { color: #b54708; background: #fffaeb; }
.stk-s-failed    { color: #b42318; background: #fff1f0; }
.stk-s-reversed  { color: #6941c6; background: #f9f5ff; }
.stk-s-submitted { color: #344054; background: #f5f6f8; }

/* Row action */
.stk-row-btn {
    width: 30px; height: 30px; border-radius: 8px; display: grid; place-items: center;
    border: 1px solid var(--line); background: #fff; color: #98a2b3;
    cursor: pointer; transition: all .15s; text-decoration: none; font-size: 12px;
}
.stk-row-btn:hover { border-color: #c7c8f6; background: #f5f5ff; color: var(--primary); }

/* ── Empty state ────────────────────────────────────────────────────────── */
.stk-empty {
    text-align: center; padding: 56px 24px;
}
.stk-empty-icon {
    width: 64px; height: 64px; margin: 0 auto 18px;
    background: #f5f6f8; border-radius: 16px;
    display: grid; place-items: center;
}
.stk-empty-icon svg { opacity: .45; }
.stk-empty strong {
    display: block; font-size: 14px; font-weight: 700;
    color: #344054; margin-bottom: 7px;
}
.stk-empty p {
    font-size: 11.5px; color: #98a2b3; line-height: 1.7;
    max-width: 360px; margin: 0 auto 20px;
}
.stk-empty-sync-btn {
    display: inline-flex; align-items: center; gap: 7px;
    height: 38px; padding: 0 18px; border-radius: 9px;
    background: var(--primary); color: #fff; font-size: 12px; font-weight: 700;
    border: 0; cursor: pointer; text-decoration: none;
    transition: background .15s;
}
.stk-empty-sync-btn:hover { background: #4a4bcf; color: #fff; }

/* ── Pagination ─────────────────────────────────────────────────────────── */
.stk-pagination { padding: 14px 22px; border-top: 1px solid var(--line); }

/* ── Toast / flash ──────────────────────────────────────────────────────── */
.stk-toast {
    display: flex; align-items: center; gap: 9px;
    background: #f6fef9; border: 1px solid #a9efc5; border-radius: 9px;
    padding: 11px 14px; font-size: 11.5px; color: #067647; margin-bottom: 16px;
}
.stk-toast i { font-size: 14px; flex-shrink: 0; }

/* ── Integration footer bar ─────────────────────────────────────────────── */
.stk-integration-bar {
    display: flex; align-items: center; justify-content: space-between;
    gap: 16px; padding: 16px 22px; margin-top: 16px;
    flex-wrap: wrap;
}
.stk-int-brand { display: flex; align-items: center; gap: 12px; }
.stk-int-icon {
    width: 38px; height: 38px; border-radius: 10px; display: grid;
    place-items: center; background: #e9faf2; color: #079455; font-size: 17px; flex-shrink: 0;
}
.stk-int-name { font-size: 12px; font-weight: 700; color: #101828; }
.stk-int-sub  { font-size: 10px; color: #98a2b3; display: block; margin-top: 2px; }
.stk-int-features { display: flex; gap: 28px; align-items: center; }
.stk-int-feature { display: flex; align-items: center; gap: 7px; font-size: 10.5px; color: #667085; }
.stk-int-feature i { font-size: 14px; }
.stk-int-feature strong { display: block; color: #344054; font-size: 11px; font-weight: 700; }
.stk-int-feature span  { display: block; color: #98a2b3; font-size: 10px; }
.stk-int-settings {
    display: inline-flex; align-items: center; gap: 6px;
    height: 34px; padding: 0 13px; border-radius: 8px;
    border: 1px solid var(--line); background: #fff; color: #475467;
    font-size: 11px; font-weight: 650; text-decoration: none; cursor: pointer;
    transition: border-color .15s, color .15s;
}
.stk-int-settings:hover { border-color: #c7c8f6; color: var(--primary); }

/* ── Detail drawer ──────────────────────────────────────────────────────── */
.stk-overlay {
    position: fixed; inset: 0; background: rgba(10,16,30,.35); z-index: 150;
    opacity: 0; pointer-events: none; transition: opacity .22s ease;
    backdrop-filter: blur(2px);
}
.stk-overlay.open { opacity: 1; pointer-events: all; }
.stk-drawer {
    position: fixed; top: 0; right: 0; bottom: 0; width: 420px;
    background: #fff; z-index: 151; display: flex; flex-direction: column;
    box-shadow: -8px 0 40px rgba(10,16,30,.12);
    transform: translateX(100%); transition: transform .25s cubic-bezier(.22,1,.36,1);
}
.stk-drawer.open { transform: translateX(0); }
.stk-drawer-head {
    padding: 20px 22px 16px; border-bottom: 1px solid var(--line); flex-shrink: 0;
    display: flex; align-items: flex-start; justify-content: space-between;
}
.stk-drawer-title { font-size: 14px; font-weight: 750; color: #0d1320; margin: 0; }
.stk-drawer-sub   { font-size: 10.5px; color: #98a2b3; margin: 3px 0 0; }
.stk-drawer-close {
    width: 28px; height: 28px; border-radius: 8px; border: 1px solid var(--line);
    background: #fff; display: grid; place-items: center; cursor: pointer;
    color: #667085; font-size: 15px; transition: all .15s; flex-shrink: 0;
}
.stk-drawer-close:hover { border-color: #f04438; color: #f04438; background: #fff1f0; }
.stk-drawer-body {
    flex: 1; overflow-y: auto; padding: 0 22px 24px;
    scrollbar-width: thin; scrollbar-color: #e4e7ec transparent;
}
.stk-drawer-body::-webkit-scrollbar { width: 4px; }
.stk-drawer-body::-webkit-scrollbar-thumb { background: #e4e7ec; border-radius: 4px; }
.stk-drawer-foot {
    padding: 14px 20px; border-top: 1px solid var(--line);
    display: flex; gap: 10px; flex-shrink: 0;
}
.stk-drawer-foot-btn {
    flex: 1; height: 38px; border-radius: 9px; font-size: 12px; font-weight: 650;
    display: inline-flex; align-items: center; justify-content: center; gap: 7px;
    cursor: pointer; transition: all .15s; text-decoration: none;
    border: 1px solid var(--line); background: #fff; color: #344054;
}
.stk-drawer-foot-btn:hover { border-color: #c7c8f6; background: #f5f5ff; color: var(--primary); }
.stk-drawer-foot-btn-primary {
    background: var(--primary); border-color: var(--primary); color: #fff;
}
.stk-drawer-foot-btn-primary:hover { background: #4a4bcf; border-color: #4a4bcf; color: #fff; }

/* Drawer inner sections */
.stk-d-identity { display: flex; align-items: center; gap: 12px; padding: 18px 0 16px; border-bottom: 1px solid var(--line); }
.stk-d-avatar { width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center; background: linear-gradient(135deg,#e0e1ff,#c7c8f6); color: #4b4fc4; font-size: 15px; font-weight: 750; flex-shrink: 0; }
.stk-d-name { font-size: 14px; font-weight: 700; color: #0d1320; display: block; }
.stk-d-phone { font-size: 11px; color: #667085; display: block; margin-top: 3px; }
.stk-d-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; padding: 18px 0 16px; border-bottom: 1px solid var(--line); }
.stk-d-meta label { font-size: 9px; letter-spacing: .08em; text-transform: uppercase; color: #98a2b3; font-weight: 700; display: block; margin-bottom: 4px; }
.stk-d-meta span  { font-size: 12px; font-weight: 650; color: #0d1320; display: block; }
.stk-d-meta small { font-size: 10px; color: #667085; display: block; margin-top: 2px; }
.stk-d-section    { font-size: 11px; font-weight: 700; color: #0d1320; margin: 18px 0 10px; }
.stk-d-loan-card  { display: flex; align-items: center; gap: 12px; padding: 13px 14px; border: 1px solid var(--line); border-radius: 11px; background: #fafbff; text-decoration: none; transition: border-color .15s, background .15s; }
.stk-d-loan-card:hover { border-color: #c7c8f6; background: #f5f5ff; }
.stk-d-loan-icon  { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg,#eef4ff,#dde9ff); color: #175cd3; font-size: 15px; display: grid; place-items: center; flex-shrink: 0; }
.stk-d-loan-name  { font-size: 12px; font-weight: 650; color: #0d1320; display: block; }
.stk-d-loan-prod  { font-size: 10px; color: #667085; display: block; margin-top: 2px; }

/* Skel */
.stk-skel { animation: stkPulse 1.4s ease-in-out infinite; }
@keyframes stkPulse { 0%,100%{opacity:1} 50%{opacity:.4} }
.stk-skel-line { height: 11px; background: #f0f2f5; border-radius: 6px; margin-bottom: 10px; }

/* ── Responsive ─────────────────────────────────────────────────────────── */
@media(max-width: 900px) {
    .stk-stat-grid { grid-template-columns: repeat(2, minmax(0,1fr)); }
    .stk-int-features { display: none; }
}
@media(max-width: 640px) {
    .stk-header { flex-direction: column; align-items: flex-start; }
    .stk-header-actions { width: 100%; }
    .stk-filter-bar { padding: 12px 14px; }
    .stk-search-box { max-width: none; }
    .stk-drawer { width: 100%; }
    .stk-integration-bar { flex-direction: column; align-items: flex-start; }
}
</style>
@endpush

@section('content')
<div class="stk-page">

    {{-- Breadcrumb --}}
    <div class="stk-breadcrumb">
        <a href="{{ route('payments.index') }}">Payments</a>
        <i class="bi bi-chevron-right"></i>
        <span>M-Pesa STK</span>
    </div>

    {{-- Flash message --}}
    @if(session('sync_message'))
        <div class="stk-toast">
            <i class="bi bi-check-circle-fill"></i>
            {{ session('sync_message') }}
        </div>
    @endif

    {{-- ═══ PAGE HEADER ═══════════════════════════════════════════════════ --}}
    <header class="stk-header">
        <div>
            <h1>
                M-Pesa STK Payments
                <span class="stk-live-badge">
                    <span class="stk-live-dot"></span>
                    Live
                </span>
            </h1>
            <p>Track, verify and allocate M-Pesa STK payments to the right borrowers and loans.</p>
        </div>

        <div class="stk-header-actions">
            {{-- Date picker button --}}
            <button class="stk-date-btn" type="button">
                <i class="bi bi-calendar3" style="color:#98a2b3;font-size:13px"></i>
                <div style="text-align:left">
                    <span class="date-label">Today</span>
                    <span class="date-sub">{{ now()->format('D, d M Y') }}</span>
                </div>
                <i class="bi bi-chevron-down" style="font-size:10px;color:#98a2b3;margin-left:4px"></i>
            </button>

            {{-- Sync M-Pesa split button --}}
            <div class="stk-sync-wrap">
                <form method="POST" action="{{ route('mpesa.stk.sync') }}" id="syncForm" style="display:contents">
                    @csrf
                    <button class="stk-sync-btn" type="submit">
                        <i class="bi bi-arrow-clockwise" style="font-size:13px"></i>
                        Sync M-Pesa
                    </button>
                </form>
                <button class="stk-sync-caret" type="button" aria-label="More sync options">
                    <i class="bi bi-chevron-down" style="font-size:10px"></i>
                </button>
            </div>
        </div>
    </header>

    {{-- ═══ STAT CARDS ════════════════════════════════════════════════════ --}}
    <div class="stk-stat-grid">

        {{-- Received Today --}}
        <div class="stk-stat stk-stat-green">
            <div class="stk-stat-top">
                <span class="stk-stat-icon"><i class="bi bi-wallet2"></i></span>
                @if($receivedGrowth !== null)
                    <span class="stk-stat-badge" style="color:{{ $receivedGrowth >= 0 ? '#079455' : '#d92d20' }}">
                        <i class="bi bi-arrow-{{ $receivedGrowth >= 0 ? 'up' : 'down' }}" style="font-size:9px"></i>
                        {{ abs($receivedGrowth) }}%
                    </span>
                @endif
            </div>
            <div class="stk-stat-label">Received Today</div>
            <div class="stk-stat-value">KSh {{ number_format($receivedToday / 100) }}</div>
            <div class="stk-stat-note">{{ $receivedTodayCount }} successful payment{{ $receivedTodayCount !== 1 ? 's' : '' }}</div>
            <svg class="stk-sparkline" width="80" height="28" viewBox="0 0 80 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                <polyline points="0,22 16,18 32,14 48,10 64,7 80,4" stroke="#079455" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none" opacity=".55"/>
            </svg>
        </div>

        {{-- Successful --}}
        <div class="stk-stat stk-stat-blue">
            <div class="stk-stat-top">
                <span class="stk-stat-icon"><i class="bi bi-check-circle"></i></span>
                <span class="stk-stat-badge" style="color:#175cd3">{{ $successRate }}%</span>
            </div>
            <div class="stk-stat-label">Successful</div>
            <div class="stk-stat-value">KSh {{ number_format($successfulToday / 100) }}</div>
            <div class="stk-stat-note">{{ $successfulTodayCount }} transaction{{ $successfulTodayCount !== 1 ? 's' : '' }}</div>
            <svg class="stk-sparkline" width="80" height="28" viewBox="0 0 80 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                <polyline points="0,14 13,10 26,16 39,8 52,12 65,6 80,9" stroke="#175cd3" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none" opacity=".5"/>
            </svg>
        </div>

        {{-- Pending Confirmation --}}
        <div class="stk-stat stk-stat-amber">
            <div class="stk-stat-top">
                <span class="stk-stat-icon"><i class="bi bi-clock"></i></span>
                <span class="stk-stat-badge" style="color:#dc6803">{{ $pendingCount > 0 ? round(($pendingCount / max($receivedTodayCount,1)) * 100) : 0 }}%</span>
            </div>
            <div class="stk-stat-label">Pending Confirmation</div>
            <div class="stk-stat-value">KSh {{ number_format($pendingAmount / 100) }}</div>
            <div class="stk-stat-note">{{ $pendingCount }} transaction{{ $pendingCount !== 1 ? 's' : '' }}</div>
            <svg class="stk-sparkline" width="80" height="28" viewBox="0 0 80 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                <polyline points="0,14 20,14 40,14 60,14 80,14" stroke="#dc6803" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none" opacity=".4"/>
            </svg>
        </div>

        {{-- Failed / Reversed --}}
        <div class="stk-stat stk-stat-rose">
            <div class="stk-stat-top">
                <span class="stk-stat-icon"><i class="bi bi-exclamation-triangle"></i></span>
                <span class="stk-stat-badge" style="color:#d92d20">{{ $failedRate }}%</span>
            </div>
            <div class="stk-stat-label">Failed / Reversed</div>
            <div class="stk-stat-value">KSh {{ number_format($failedAmount / 100) }}</div>
            <div class="stk-stat-note">{{ $failedCount }} transaction{{ $failedCount !== 1 ? 's' : '' }}</div>
            <svg class="stk-sparkline" width="80" height="28" viewBox="0 0 80 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                <polyline points="0,8 20,12 40,10 60,16 80,14" stroke="#d92d20" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none" opacity=".45"/>
            </svg>
        </div>
    </div>

    {{-- ═══ MAIN PANEL ════════════════════════════════════════════════════ --}}
    <section class="panel" style="padding:0;overflow:hidden">

        {{-- Tabs --}}
        <nav class="stk-tabs" aria-label="Filter by status">
            @php
                $tabs = [
                    'all'        => 'All Payments',
                    'successful' => 'Successful',
                    'pending'    => 'Pending',
                    'failed'     => 'Failed',
                    'reversed'   => 'Reversed',
                ];
            @endphp
            @foreach($tabs as $key => $label)
                <a class="stk-tab {{ $tab === $key ? 'active' : '' }}"
                   href="{{ route('mpesa.stk.index', array_merge(request()->except(['page']), ['tab' => $key])) }}">
                    {{ $label }}
                    <span class="stk-tab-count {{ $key === 'failed' && $tabCounts['failed'] > 0 ? 'has-value' : '' }}">
                        {{ $tabCounts[$key] }}
                    </span>
                </a>
            @endforeach
        </nav>

        {{-- Filter bar --}}
        <form method="GET" action="{{ route('mpesa.stk.index') }}" class="stk-filter-bar" id="stkFilterForm">
            <input type="hidden" name="tab" value="{{ $tab }}">

            {{-- Search --}}
            <label class="stk-search-box">
                <i class="bi bi-search"></i>
                <input type="search" name="search" value="{{ $search }}"
                       placeholder="Search by name, phone number, transaction ID or loan ID...">
            </label>

            {{-- Borrower dropdown --}}
            <select class="stk-select" name="borrower_id" aria-label="Filter by borrower">
                <option value="">All Borrowers</option>
                @foreach($borrowers as $b)
                    <option value="{{ $b->id }}" @selected($borrowerId == $b->id)>
                        {{ $b->user?->name ?? 'Borrower #'.$b->id }}
                    </option>
                @endforeach
            </select>

            {{-- Status dropdown --}}
            <select class="stk-select" name="status" aria-label="Filter by status">
                <option value="">All Statuses</option>
                @foreach(['success' => 'Successful', 'processing' => 'Pending', 'submitted' => 'Submitted', 'failed' => 'Failed', 'reversed' => 'Reversed'] as $v => $l)
                    <option value="{{ $v }}" @selected($statusFilter === $v)>{{ $l }}</option>
                @endforeach
            </select>

            {{-- Date range --}}
            <div class="stk-date-range">
                <input class="stk-date-input" type="date" name="from" value="{{ $dateFrom }}" aria-label="From date" placeholder="From">
                <span class="stk-date-sep">→</span>
                <input class="stk-date-input" type="date" name="to" value="{{ $dateTo }}" aria-label="To date" placeholder="To">
                <div class="stk-date-icon"><i class="bi bi-calendar3"></i></div>
            </div>

            {{-- Submit --}}
            <button class="stk-filter-btn" type="submit">
                <i class="bi bi-funnel-fill" style="font-size:11px"></i>
                Filter
            </button>

            {{-- Export --}}
            <a class="stk-export-btn" href="{{ route('mpesa.stk.index', array_merge(request()->all(), ['export' => 1])) }}" title="Export CSV">
                <i class="bi bi-download" style="font-size:12px"></i>
                Export
            </a>

            {{-- More --}}
            <button class="stk-more-btn" type="button" title="More options">
                <i class="bi bi-three-dots" style="font-size:14px"></i>
            </button>
        </form>

        {{-- Table --}}
        <div class="stk-table-wrap">
            <table class="stk-table" aria-label="M-Pesa STK transactions">
                <thead>
                    <tr>
                        <th><input type="checkbox" class="stk-checkbox" aria-label="Select all"></th>
                        <th>Date &amp; Time</th>
                        <th>M-Pesa Ref</th>
                        <th>Phone Number</th>
                        <th>Borrower</th>
                        <th>Loan</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($transactions as $tx)
                    @php
                        $name   = $tx->borrower?->user?->name ?? 'Unknown borrower';
                        $init   = strtoupper(substr($name, 0, 1));
                        $known  = $tx->borrower_id !== null;
                        $phone  = $tx->phone_number ?: '—';
                        // Format phone: 254712345678 → 0712 345 678
                        if (strlen(preg_replace('/\D/','',$phone)) === 12 && str_starts_with($phone, '254')) {
                            $local = '0'.substr(preg_replace('/\D/','',$phone), 3);
                            $phone = substr($local,0,4).' '.substr($local,4,3).' '.substr($local,7);
                        }
                        $statusMap = [
                            'success'    => ['stk-s-success',   'Successful'],
                            'completed'  => ['stk-s-success',   'Successful'],
                            'processing' => ['stk-s-pending',   'Pending'],
                            'submitted'  => ['stk-s-submitted', 'Submitted'],
                            'failed'     => ['stk-s-failed',    'Failed'],
                            'reversed'   => ['stk-s-reversed',  'Reversed'],
                        ];
                        [$statusCls, $statusLabel] = $statusMap[$tx->status] ?? ['stk-s-submitted', ucfirst($tx->status)];
                        $loanRef = $tx->loan ? 'Loan #'.str_pad($tx->loan->id, 7, '0', STR_PAD_LEFT) : '—';
                    @endphp
                    <tr onclick="openStkDetail({{ $tx->id }})" data-tx-id="{{ $tx->id }}">
                        <td onclick="event.stopPropagation()">
                            <input type="checkbox" class="stk-checkbox" aria-label="Select row">
                        </td>
                        <td>
                            <span class="stk-cell-dt">{{ $tx->received_at?->format('d M Y') ?? '—' }}</span>
                            <span class="stk-cell-sub">{{ $tx->received_at?->format('H:i') ?? '' }}</span>
                        </td>
                        <td>
                            <span class="stk-cell-ref">{{ $tx->mpesa_receipt_number ?: 'Awaiting...' }}</span>
                            <span class="stk-cell-sub">{{ Str::limit($tx->checkout_request_id ?? '', 22, '…') }}</span>
                        </td>
                        <td>
                            <span class="stk-cell-phone">{{ $phone }}</span>
                        </td>
                        <td>
                            <div class="stk-person">
                                <span class="stk-avatar {{ $known ? '' : 'stk-avatar-unknown' }}">{{ $init }}</span>
                                <div>
                                    <span class="stk-p-name">{{ $name }}</span>
                                    <span class="stk-p-sub">
                                        {{ $tx->account_reference ?: '—' }}
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="stk-loan">{{ $loanRef }}</span>
                        </td>
                        <td>
                            <span class="stk-amount">KSh {{ number_format(($tx->amount_cents ?? 0) / 100, 2) }}</span>
                        </td>
                        <td>
                            <span class="stk-status {{ $statusCls }}">{{ $statusLabel }}</span>
                        </td>
                        <td onclick="event.stopPropagation()">
                            <div style="display:flex;gap:5px;align-items:center">
                                <button class="stk-row-btn"
                                        onclick="openStkDetail({{ $tx->id }})"
                                        title="View details"
                                        aria-label="View transaction">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <a class="stk-row-btn"
                                   href="{{ route('mpesa.stk.edit', $tx) }}"
                                   title="Edit"
                                   aria-label="Edit transaction">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="padding:0;border:0">
                            <div class="stk-empty">
                                <div class="stk-empty-icon">
                                    <svg width="34" height="34" viewBox="0 0 34 34" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="4" y="7" width="18" height="22" rx="3" stroke="#667085" stroke-width="1.8" fill="none"/>
                                        <line x1="8" y1="13" x2="18" y2="13" stroke="#667085" stroke-width="1.6" stroke-linecap="round"/>
                                        <line x1="8" y1="17" x2="15" y2="17" stroke="#667085" stroke-width="1.6" stroke-linecap="round"/>
                                        <circle cx="25" cy="25" r="7" stroke="#5b5ce2" stroke-width="1.8" fill="none"/>
                                        <line x1="22.5" y1="25" x2="27.5" y2="25" stroke="#5b5ce2" stroke-width="1.6" stroke-linecap="round"/>
                                        <line x1="23" y1="22.5" x2="26.5" y2="25" stroke="#5b5ce2" stroke-width="1.6" stroke-linecap="round"/>
                                        <line x1="23" y1="27.5" x2="26.5" y2="25" stroke="#5b5ce2" stroke-width="1.6" stroke-linecap="round"/>
                                    </svg>
                                </div>
                                <strong>No M-Pesa payments yet</strong>
                                <p>
                                    Payments received via M-Pesa STK will appear here automatically.<br>
                                    Click "Sync M-Pesa" to fetch the latest transactions.
                                </p>
                                <form method="POST" action="{{ route('mpesa.stk.sync') }}" style="display:inline">
                                    @csrf
                                    <button class="stk-empty-sync-btn" type="submit">
                                        <i class="bi bi-arrow-clockwise" style="font-size:13px"></i>
                                        Sync M-Pesa
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($transactions->hasPages())
            <div class="stk-pagination">
                {{ $transactions->links() }}
            </div>
        @endif
    </section>

    {{-- ═══ INTEGRATION FOOTER BAR ════════════════════════════════════════ --}}
    <section class="panel stk-integration-bar">
        <div class="stk-int-brand">
            <div class="stk-int-icon"><i class="bi bi-shield-check-fill"></i></div>
            <div>
                <span class="stk-int-name">M-Pesa STK Integration</span>
                <span class="stk-int-sub">Secure. Reliable. Automatic.</span>
            </div>
        </div>

        <div class="stk-int-features">
            <div class="stk-int-feature">
                <i class="bi bi-lightning-charge-fill" style="color:#5b5ce2"></i>
                <div>
                    <strong>Real-time Sync</strong>
                    <span>Automatic updates</span>
                </div>
            </div>
            <div class="stk-int-feature">
                <i class="bi bi-shield-check" style="color:#12b76a"></i>
                <div>
                    <strong>Secure &amp; Encrypted</strong>
                    <span>Bank-grade security</span>
                </div>
            </div>
            <div class="stk-int-feature">
                <i class="bi bi-phone" style="color:#5b5ce2"></i>
                <div>
                    <strong>Instant Notifications</strong>
                    <span>Get notified immediately</span>
                </div>
            </div>
        </div>

        <a href="#" class="stk-int-settings">
            <i class="bi bi-gear" style="font-size:12px"></i>
            View Integration Settings
        </a>
    </section>

</div>{{-- .stk-page --}}

{{-- ═══ DETAIL DRAWER ══════════════════════════════════════════════════════ --}}
<div class="stk-overlay" id="stkOverlay" onclick="closeStkDetail()"></div>
<div class="stk-drawer" id="stkDrawer" role="dialog" aria-modal="true" aria-labelledby="stkDrawerTitle">
    <div class="stk-drawer-head">
        <div>
            <h2 class="stk-drawer-title" id="stkDrawerTitle">Transaction Details</h2>
            <p class="stk-drawer-sub">Full breakdown for this STK Push transaction.</p>
        </div>
        <button class="stk-drawer-close" onclick="closeStkDetail()" aria-label="Close details">
            <i class="bi bi-x"></i>
        </button>
    </div>
    <div class="stk-drawer-body" id="stkDrawerBody"></div>
    <div class="stk-drawer-foot" id="stkDrawerFoot" style="display:none">
        <a href="#" class="stk-drawer-foot-btn" id="stkEditBtn">
            <i class="bi bi-pencil" style="font-size:11px"></i> Edit
        </a>
        <a href="#" class="stk-drawer-foot-btn stk-drawer-foot-btn-primary" id="stkViewLoanBtn">
            <i class="bi bi-bank2" style="font-size:11px"></i> View Loan
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const overlay  = document.getElementById('stkOverlay');
    const drawer   = document.getElementById('stkDrawer');
    const body     = document.getElementById('stkDrawerBody');
    const foot     = document.getElementById('stkDrawerFoot');
    const editBtn  = document.getElementById('stkEditBtn');
    const loanBtn  = document.getElementById('stkViewLoanBtn');

    let activeRow = null;

    window.openStkDetail = function (id) {
        // Highlight row
        document.querySelectorAll('[data-tx-id]').forEach(r => r.style.background = '');
        const row = document.querySelector(`[data-tx-id="${id}"]`);
        if (row) { row.style.background = '#f5f5ff'; activeRow = row; }

        overlay.classList.add('open');
        drawer.classList.add('open');
        foot.style.display = 'none';
        body.innerHTML = skeletonHTML();

        fetch(`/admin/mpesa/stk/${id}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => { if (!r.ok) throw new Error('Failed'); return r.json(); })
        .then(d => {
            body.innerHTML = renderDetail(d);
            editBtn.href  = `/admin/mpesa/stk/${id}/edit`;
            loanBtn.href  = d.loan_id ? `/applications/${d.loan_id}` : '#';
            if (!d.loan_id) { loanBtn.style.display = 'none'; } else { loanBtn.style.display = ''; }
            foot.style.display = 'flex';
        })
        .catch(() => {
            body.innerHTML = `<div style="padding:30px 0;text-align:center;color:#98a2b3;font-size:12px">
                <i class="bi bi-exclamation-circle" style="font-size:28px;display:block;margin-bottom:10px"></i>
                Could not load transaction details.
            </div>`;
        });
    };

    window.closeStkDetail = function () {
        overlay.classList.remove('open');
        drawer.classList.remove('open');
        if (activeRow) { activeRow.style.background = ''; activeRow = null; }
    };

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeStkDetail(); });

    /* ── Skeleton ── */
    function skeletonHTML() {
        return `<div class="stk-skel" style="padding-top:18px">
            <div style="display:flex;align-items:center;gap:12px;padding-bottom:18px;border-bottom:1px solid var(--line)">
                <div style="width:44px;height:44px;border-radius:12px;background:#f0f2f5;flex-shrink:0"></div>
                <div style="flex:1">
                    <div class="stk-skel-line" style="width:55%"></div>
                    <div class="stk-skel-line" style="width:35%;margin-bottom:0"></div>
                </div>
            </div>
            <div style="padding:18px 0 16px;border-bottom:1px solid var(--line)">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    ${[1,2,3,4].map(() => `<div>
                        <div class="stk-skel-line" style="width:45%;height:8px"></div>
                        <div class="stk-skel-line" style="width:80%;margin-bottom:0"></div>
                    </div>`).join('')}
                </div>
            </div>
            <div style="margin-top:20px">
                <div class="stk-skel-line" style="width:40%;height:9px"></div>
                <div style="height:72px;background:#f9fafb;border-radius:11px;border:1px solid var(--line)"></div>
            </div>
        </div>`;
    }

    /* ── Detail render ── */
    function renderDetail(d) {
        const statusColors = {
            success:   ['#067647','#ecfdf3'],
            completed: ['#067647','#ecfdf3'],
            processing:['#b54708','#fffaeb'],
            submitted: ['#344054','#f5f6f8'],
            failed:    ['#b42318','#fff1f0'],
            reversed:  ['#6941c6','#f9f5ff'],
        };
        const [sColor, sBg] = statusColors[d.status] || ['#344054','#f5f6f8'];
        const statusLabel = { success:'Successful', completed:'Successful', processing:'Pending', submitted:'Submitted', failed:'Failed', reversed:'Reversed' }[d.status] || d.status;

        const pill = `<span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:10px;font-weight:700;color:${sColor};background:${sBg}">
            <span style="width:5px;height:5px;border-radius:50%;background:${sColor};display:inline-block"></span>
            ${statusLabel}
        </span>`;

        const amountKsh = `KSh ${Number((d.amount_cents||0)/100).toLocaleString('en-KE',{minimumFractionDigits:2})}`;

        const loanCard = d.loan_ref ? `
            <div class="stk-d-section">Related loan</div>
            <a href="/applications/${d.loan_id}" class="stk-d-loan-card" onclick="event.stopPropagation()">
                <div class="stk-d-loan-icon"><i class="bi bi-bank2"></i></div>
                <div>
                    <span class="stk-d-loan-name">${d.loan_ref}</span>
                    <span class="stk-d-loan-prod">${d.loan_product || 'Loan product'}</span>
                </div>
                <i class="bi bi-chevron-right" style="margin-left:auto;color:#98a2b3;font-size:11px"></i>
            </a>` : '';

        const paymentSection = d.has_payment ? `
            <div class="stk-d-section" style="margin-top:20px">Payment record</div>
            <div style="padding:12px 14px;border:1px solid #d1fadf;border-radius:10px;background:#f6fef9;font-size:11px;color:#067647">
                <i class="bi bi-check-circle-fill" style="margin-right:6px"></i>
                Posted as loan payment · ${`KSh ${Number((d.payment_amount||0)/100).toLocaleString('en-KE',{minimumFractionDigits:2})}`}
                ${d.unapplied_cents > 0 ? `<span style="color:#dc6803;font-size:10px;display:block;margin-top:4px">KSh ${Number(d.unapplied_cents/100).toLocaleString('en-KE',{minimumFractionDigits:2})} unapplied balance</span>` : ''}
            </div>` : '';

        const resultSection = (d.result_code !== '—' && d.result_code !== null) ? `
            <div class="stk-d-section" style="margin-top:20px">Daraja result</div>
            <div style="padding:12px 14px;border:1px solid var(--line);border-radius:10px;background:#f9fafb;font-size:11px;color:#344054">
                <div style="display:flex;justify-content:space-between;margin-bottom:6px">
                    <span style="color:#98a2b3;font-size:10px">Result code</span>
                    <strong>${d.result_code}</strong>
                </div>
                <div style="font-size:10.5px;color:#667085;line-height:1.5">${d.result_desc || '—'}</div>
            </div>` : '';

        return `
        <div class="stk-d-identity">
            <div class="stk-d-avatar">${d.initial || '?'}</div>
            <div style="flex:1;min-width:0">
                <span class="stk-d-name">${d.name}</span>
                <span class="stk-d-phone"><i class="bi bi-phone" style="font-size:10px;margin-right:4px"></i>${d.phone}</span>
            </div>
            <div style="flex-shrink:0">${pill}</div>
        </div>

        <div class="stk-d-meta">
            <div>
                <label>Amount</label>
                <span style="font-size:15px;font-weight:760;letter-spacing:-.02em">${amountKsh}</span>
            </div>
            <div>
                <label>M-Pesa Ref</label>
                <span style="font-family:monospace;font-size:12px">${d.mpesa_ref}</span>
            </div>
            <div>
                <label>Received</label>
                <span>${d.received_at}</span>
            </div>
            <div>
                <label>Transacted</label>
                <span>${d.transacted_at}</span>
            </div>
            <div>
                <label>Account Ref</label>
                <span>${d.account_ref}</span>
            </div>
            <div>
                <label>Checkout ID</label>
                <span style="font-size:10px;word-break:break-all">${d.checkout_id}</span>
            </div>
        </div>

        ${loanCard}
        ${paymentSection}
        ${resultSection}
        `;
    }
})();
</script>
@endpush
