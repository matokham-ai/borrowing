@extends('layouts.app')

@section('title', 'Daraja API Operations')

@push('styles')
<style>
/* ─── Page shell ─────────────────────────────────────────────────────────── */
.ops-page { max-width: 1480px; margin: 0 auto; }

/* ─── Environment badge ──────────────────────────────────────────────────── */
.ops-env-badge {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 5px 13px; border-radius: 20px; font-size: 10.5px; font-weight: 700;
    letter-spacing: .05em; text-transform: uppercase;
}
.ops-env-badge::before {
    content: ''; width: 7px; height: 7px; border-radius: 50%;
    background: currentColor; flex-shrink: 0;
}
.ops-env-production { background: #fffaeb; color: #b54708; border: 1px solid #fedf89; }
.ops-env-sandbox    { background: #f0f2f5; color: #475467; border: 1px solid #e4e7ec; }

/* ─── Two-column layout ──────────────────────────────────────────────────── */
.ops-layout {
    display: grid;
    grid-template-columns: 420px minmax(0, 1fr);
    gap: 18px;
    align-items: start;
}

/* ─── Panel head ─────────────────────────────────────────────────────────── */
.ops-panel-head {
    display: flex; align-items: flex-start; justify-content: space-between;
    padding: 20px 22px 0;
}
.ops-panel-head h2 { font-size: 13px; font-weight: 700; margin: 0; color: #0d1320; }
.ops-panel-head p  { font-size: 10.5px; color: #98a2b3; margin: 3px 0 0; font-weight: 500; }

/* ─── Form card ──────────────────────────────────────────────────────────── */
.ops-form-body { padding: 20px 22px 22px; }

.ops-field-label {
    display: flex; align-items: center; justify-content: space-between;
    font-size: 11px; font-weight: 650; color: #344054; margin-bottom: 7px;
}
.ops-field-hint { font-size: 10px; color: #98a2b3; font-weight: 500; }

.ops-select {
    width: 100%; height: 40px; border: 1px solid var(--line); border-radius: 9px;
    background: #fff; color: #0d1320; padding: 0 32px 0 12px; font-size: 12px;
    font-weight: 500; cursor: pointer;
    transition: border-color .15s, box-shadow .15s;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%2398a2b3' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
}
.ops-select:focus { border-color: #a5b4fc; outline: none; box-shadow: 0 0 0 3px rgba(99,102,241,.1); }

.ops-chips-bar { display: flex; flex-wrap: wrap; gap: 6px; min-height: 24px; margin: 10px 0 6px; }
.ops-chip {
    display: inline-flex; align-items: center; gap: 5px;
    border-radius: 6px; padding: 3px 9px; font-size: 10px; font-weight: 650;
}
.ops-chip-req { background: #eef4ff; color: #175cd3; border: 1px solid #b2ccff; }
.ops-chip-sys { background: #f9fafb; color: #667085; border: 1px solid #e4e7ec; }
.ops-chip i   { font-size: 9px; }

.ops-textarea {
    width: 100%; border: 1px solid var(--line); border-radius: 9px;
    background: #fff; color: #0d1320; padding: 11px 13px;
    font-size: 11.5px; font-family: 'JetBrains Mono', 'Cascadia Code', ui-monospace, monospace;
    resize: vertical; min-height: 210px; line-height: 1.65;
    transition: border-color .15s, box-shadow .15s;
}
.ops-textarea:focus        { border-color: #a5b4fc; outline: none; box-shadow: 0 0 0 3px rgba(99,102,241,.1); }
.ops-textarea.is-invalid   { border-color: #f04438; }

.ops-error-msg {
    display: flex; align-items: flex-start; gap: 7px;
    background: #fff1f0; border: 1px solid #fecdca; border-radius: 8px;
    padding: 10px 12px; font-size: 11px; color: #b42318; margin-top: 8px; line-height: 1.55;
}
.ops-error-msg i { font-size: 13px; flex-shrink: 0; margin-top: 1px; }

.ops-caution {
    display: flex; align-items: flex-start; gap: 11px;
    background: #fffaeb; border: 1px solid #fedf89; border-radius: 10px;
    padding: 13px 16px; font-size: 11px; color: #7a2e0e; line-height: 1.6; margin-top: 14px;
}
.ops-caution i    { font-size: 15px; color: #dc6803; flex-shrink: 0; margin-top: 1px; }
.ops-caution code { background: #fef0c7; border-radius: 4px; padding: 1px 5px; font-size: 10.5px; font-family: ui-monospace, monospace; color: #7a2e0e; }

.ops-form-footer {
    display: flex; align-items: center; gap: 10px;
    padding: 16px 22px; border-top: 1px solid var(--line); background: #fafbff;
}
.ops-template-btn {
    height: 38px; display: inline-flex; align-items: center; gap: 7px;
    border: 1px solid var(--line); border-radius: 9px; background: #fff;
    color: #475467; font-size: 11.5px; font-weight: 650; cursor: pointer;
    padding: 0 14px; transition: all .15s;
}
.ops-template-btn:hover { border-color: #c7c8f6; background: #f5f5ff; color: var(--primary); }
.ops-submit-btn {
    flex: 1; height: 38px; display: inline-flex; align-items: center;
    justify-content: center; gap: 8px;
    background: var(--primary); border: 1px solid var(--primary); border-radius: 9px;
    color: #fff; font-size: 12px; font-weight: 650; cursor: pointer; transition: all .15s;
}
.ops-submit-btn:hover { background: #4a4bcf; border-color: #4a4bcf; }

/* ─── Flash messages ─────────────────────────────────────────────────────── */
.ops-flash {
    display: flex; align-items: flex-start; gap: 10px;
    border-radius: 10px; padding: 13px 16px; font-size: 11.5px;
    line-height: 1.6; margin-bottom: 16px;
}
.ops-flash i             { font-size: 15px; flex-shrink: 0; margin-top: 1px; }
.ops-flash-success       { background: #ecfdf3; border: 1px solid #a9efc5; color: #065f46; }
.ops-flash-error         { background: #fff1f0; border: 1px solid #fecdca; color: #7f1d1d; }
.ops-response-block      { background: #f8faff; border: 1px solid #dde9ff; border-radius: 10px; padding: 13px 16px; margin-bottom: 16px; }
.ops-response-block strong { font-size: 11px; font-weight: 700; color: #175cd3; display: block; margin-bottom: 8px; }
.ops-response-block pre  { margin: 0; font-size: 10.5px; font-family: ui-monospace, monospace; color: #344054; white-space: pre-wrap; word-break: break-word; max-height: 180px; overflow-y: auto; }

/* ─── Operations table ───────────────────────────────────────────────────── */
.ops-table-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 20px 22px 16px;
}
.ops-total-badge {
    background: #f5f6f8; border: 1px solid var(--line); border-radius: 20px;
    padding: 4px 11px; font-size: 10px; font-weight: 650; color: #667085;
}
.ops-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.ops-table colgroup col { }
.ops-table thead tr { border-bottom: 2px solid var(--line); }
.ops-table th {
    padding: 10px 14px; font-size: 9.5px; font-weight: 700;
    letter-spacing: .07em; text-transform: uppercase; color: #98a2b3;
    text-align: left; white-space: nowrap; overflow: hidden;
}
.ops-table th:first-child { padding-left: 22px; }
.ops-table th:last-child  { padding-right: 22px; text-align: right; }
.ops-table tbody tr { border-bottom: 1px solid #f5f6f8; transition: background .12s; cursor: pointer; }
.ops-table tbody tr:hover { background: #fafbff; }
.ops-table tbody tr:last-child { border-bottom: 0; }
.ops-table td { padding: 14px 14px; vertical-align: middle; overflow: hidden; }
.ops-table td:first-child { padding-left: 22px; }
.ops-table td:last-child  { padding-right: 22px; text-align: right; }

/* ─── Table cell atoms ───────────────────────────────────────────────────── */
.ops-ts       { font-size: 11px; font-weight: 500; color: #344054; display: block; white-space: nowrap; }
.ops-ts-s     { font-size: 10px; color: #98a2b3; display: block; margin-top: 2px; }
.ops-product-cell { display: flex; align-items: center; gap: 9px; }
.ops-product-icon { width: 30px; height: 30px; border-radius: 8px; background: #eef4ff; color: #175cd3; font-size: 13px; display: grid; place-items: center; flex-shrink: 0; }
.ops-product-name { font-size: 11.5px; font-weight: 650; color: #0d1320; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ops-product-path { font-size: 10px; color: #98a2b3; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.ops-env-pill { display: inline-block; border-radius: 6px; padding: 3px 8px; font-size: 9.5px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; white-space: nowrap; }
.ops-env-pill-prod { background: #fffaeb; color: #b54708; }
.ops-env-pill-sand { background: #f0f2f5; color: #475467; }

.ops-status-pill { display: inline-flex; align-items: center; gap: 5px; border-radius: 20px; padding: 4px 10px; font-size: 9.5px; font-weight: 700; letter-spacing: .02em; white-space: nowrap; }
.ops-status-pill::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: currentColor; flex-shrink: 0; }
.ops-status-completed  { color: #067647; background: #ecfdf3; }
.ops-status-failed,
.ops-status-timeout    { color: #b42318; background: #fff1f0; }
.ops-status-submitted,
.ops-status-processing,
.ops-status-queued     { color: #b54708; background: #fffaeb; }

.ops-ref { font-size: 11px; font-weight: 650; color: #344054; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-variant-numeric: tabular-nums; }
.ops-ref-sub   { font-size: 10px; color: #98a2b3; display: block; margin-top: 2px; }
.ops-ref-error { font-size: 10.5px; color: #b42318; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.ops-detail-btn {
    width: 30px; height: 30px; border-radius: 8px; display: inline-grid; place-items: center;
    border: 1px solid var(--line); background: #fff; color: #98a2b3;
    cursor: pointer; transition: all .15s; font-size: 13px;
}
.ops-detail-btn:hover { border-color: #c7c8f6; background: #f5f5ff; color: var(--primary); }

/* ─── Empty state ────────────────────────────────────────────────────────── */
.ops-empty { display: flex; flex-direction: column; align-items: center; padding: 64px 24px; text-align: center; }
.ops-empty-icon { width: 52px; height: 52px; border-radius: 14px; background: #f5f6f8; color: #c0c8d8; font-size: 22px; display: grid; place-items: center; margin-bottom: 16px; }
.ops-empty strong { font-size: 13px; font-weight: 700; color: #344054; display: block; margin-bottom: 6px; }
.ops-empty small  { font-size: 11px; color: #98a2b3; }

/* ─── Pagination ─────────────────────────────────────────────────────────── */
.ops-pagination { padding: 14px 22px; border-top: 1px solid var(--line); }

/* ─── Detail drawer ──────────────────────────────────────────────────────── */
.ops-overlay {
    position: fixed; inset: 0; background: rgba(10,16,30,.35); z-index: 150;
    opacity: 0; pointer-events: none; transition: opacity .22s ease;
    backdrop-filter: blur(2px);
}
.ops-overlay.open { opacity: 1; pointer-events: all; }

.ops-drawer {
    position: fixed; top: 0; right: 0; bottom: 0; width: 460px; background: #fff;
    z-index: 151; display: flex; flex-direction: column;
    box-shadow: -8px 0 40px rgba(10,16,30,.12);
    transform: translateX(100%); transition: transform .26s cubic-bezier(.22,1,.36,1);
}
.ops-drawer.open { transform: translateX(0); }

.ops-drawer-head {
    padding: 20px 22px 16px; border-bottom: 1px solid var(--line); flex-shrink: 0;
    display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
}
.ops-drawer-title { font-size: 14px; font-weight: 750; color: #0d1320; margin: 0; }
.ops-drawer-sub   { font-size: 10.5px; color: #98a2b3; margin: 3px 0 0; display: block; }
.ops-drawer-close {
    width: 28px; height: 28px; border-radius: 8px; border: 1px solid var(--line);
    background: #fff; display: grid; place-items: center; cursor: pointer;
    color: #667085; font-size: 15px; transition: all .15s; flex-shrink: 0;
}
.ops-drawer-close:hover { border-color: #f04438; color: #f04438; background: #fff1f0; }

.ops-drawer-body {
    flex: 1; overflow-y: auto; padding: 0 22px 28px;
    scrollbar-width: thin; scrollbar-color: #e4e7ec transparent;
}
.ops-drawer-body::-webkit-scrollbar { width: 4px; }
.ops-drawer-body::-webkit-scrollbar-thumb { background: #e4e7ec; border-radius: 4px; }

/* ─── Drawer content atoms ───────────────────────────────────────────────── */
.ops-drawer-section { padding: 18px 0 16px; border-bottom: 1px solid var(--line); }
.ops-drawer-section:last-child { border-bottom: 0; }
.ops-drawer-section-label {
    font-size: 9.5px; font-weight: 700; letter-spacing: .09em;
    text-transform: uppercase; color: #98a2b3; margin-bottom: 14px;
    display: flex; align-items: center; gap: 8px;
}
.ops-drawer-section-label::after { content: ''; flex: 1; height: 1px; background: #f5f6f8; }

.ops-meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.ops-meta-item label { font-size: 9.5px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: #98a2b3; display: block; margin-bottom: 5px; }
.ops-meta-item span  { font-size: 12px; font-weight: 600; color: #0d1320; display: block; }
.ops-meta-item small { font-size: 10px; color: #667085; display: block; margin-top: 2px; }
.ops-meta-item.full  { grid-column: span 2; }

/* JSON blocks */
.ops-json-block {
    border-radius: 10px; overflow: hidden; border: 1px solid var(--line);
    margin-bottom: 12px;
}
.ops-json-block:last-child { margin-bottom: 0; }
.ops-json-block-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 9px 14px; background: #f9fafb; border-bottom: 1px solid var(--line);
}
.ops-json-block-head span {
    font-size: 10px; font-weight: 700; letter-spacing: .06em;
    text-transform: uppercase; color: #667085;
    display: flex; align-items: center; gap: 6px;
}
.ops-json-block-head span i { font-size: 11px; color: #98a2b3; }
.ops-json-copy-btn {
    font-size: 10.5px; font-weight: 650; color: #667085; background: none;
    border: 1px solid var(--line); border-radius: 6px; padding: 3px 9px;
    cursor: pointer; transition: all .15s; display: flex; align-items: center; gap: 5px;
}
.ops-json-copy-btn:hover { border-color: #c7c8f6; color: var(--primary); background: #f5f5ff; }
.ops-json-copy-btn.copied { color: #067647; border-color: #a9efc5; background: #ecfdf3; }
pre.ops-json-pre {
    margin: 0; padding: 14px; font-size: 10.5px; line-height: 1.7;
    font-family: 'JetBrains Mono', 'Cascadia Code', ui-monospace, monospace;
    color: #344054; white-space: pre; overflow-x: auto; background: #fff;
    max-height: 280px; overflow-y: auto;
    scrollbar-width: thin; scrollbar-color: #e4e7ec transparent;
}
pre.ops-json-pre::-webkit-scrollbar { width: 4px; height: 4px; }
pre.ops-json-pre::-webkit-scrollbar-thumb { background: #e4e7ec; border-radius: 4px; }

/* JSON syntax colouring */
.json-key    { color: #5b5ce2; font-weight: 600; }
.json-str    { color: #067647; }
.json-num    { color: #dc6803; }
.json-bool   { color: #b54708; font-weight: 600; }
.json-null   { color: #98a2b3; font-style: italic; }

/* ─── Responsive ─────────────────────────────────────────────────────────── */
@media (max-width: 1120px) { .ops-layout { grid-template-columns: 1fr; } }
@media (max-width: 640px)  { .ops-drawer { width: 100%; } }

/* ─── C2B URL Registration card ──────────────────────────────────────────── */
.c2b-card { overflow: hidden; margin-bottom: 20px; }

.c2b-card-head {
    display: flex; align-items: flex-start; justify-content: space-between; gap: 16px;
    padding: 20px 24px 18px; border-bottom: 1px solid var(--line);
}
.c2b-head-left { display: flex; align-items: flex-start; gap: 14px; }
.c2b-icon {
    width: 40px; height: 40px; border-radius: 11px; flex-shrink: 0;
    background: linear-gradient(135deg, #eef4ff, #dde9ff);
    color: #175cd3; font-size: 20px; display: grid; place-items: center;
}
.c2b-title { font-size: 14px; font-weight: 750; color: #0d1320; margin: 0; }
.c2b-sub   { font-size: 11px; color: #667085; margin: 4px 0 0; line-height: 1.55; max-width: 560px; }

.c2b-card-body  { padding: 22px 24px 0; }
.c2b-fields     { display: grid; gap: 20px; }
.c2b-field-row  { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }

.c2b-label {
    display: flex; align-items: center; gap: 7px;
    font-size: 11px; font-weight: 700; color: #344054;
    margin-bottom: 8px;
}
.c2b-label i { font-size: 12px; color: #667085; }
.c2b-label-hint {
    margin-left: auto; font-size: 10px; font-weight: 500; color: #98a2b3;
}

.c2b-input {
    width: 100%; height: 42px; border: 1px solid var(--line); border-radius: 9px;
    background: #fff; color: #0d1320; padding: 0 14px; font-size: 13px; font-weight: 500;
    transition: border-color .15s, box-shadow .15s;
}
.c2b-input:focus { border-color: #a5b4fc; outline: none; box-shadow: 0 0 0 3px rgba(99,102,241,.1); }
.c2b-input.is-invalid { border-color: #f04438; }
.c2b-field-error {
    display: flex; align-items: center; gap: 6px;
    font-size: 10.5px; color: #b42318; margin-top: 6px;
}

/* Segmented control */
.c2b-segmented {
    display: flex; border: 1px solid var(--line); border-radius: 9px;
    background: #f9fafb; overflow: hidden; height: 42px;
}
.c2b-seg-btn {
    flex: 1; border: none; background: transparent; font-size: 11.5px; font-weight: 600;
    color: #667085; cursor: pointer; transition: all .15s; padding: 0 14px;
    white-space: nowrap;
}
.c2b-seg-btn.active {
    background: #fff; color: var(--primary); font-weight: 700;
    box-shadow: 0 1px 4px rgba(0,0,0,.08); border-radius: 8px; margin: 3px;
}
.c2b-seg-btn:not(.active):hover { background: #f0f2f5; color: #344054; }

/* URL preview */
.c2b-urls-preview {
    background: #f8faff; border: 1px solid #dde9ff; border-radius: 11px;
    padding: 16px 18px; display: grid; gap: 10px;
}
.c2b-urls-label {
    display: flex; align-items: center; gap: 7px;
    font-size: 10.5px; font-weight: 700; color: #344054; margin-bottom: 2px;
}
.c2b-urls-label i { color: #667085; }
.c2b-urls-hint { font-size: 10px; font-weight: 500; color: #98a2b3; margin-left: auto; }
.c2b-url-row {
    display: flex; align-items: center; gap: 10px;
    background: #fff; border: 1px solid #dde9ff; border-radius: 8px;
    padding: 9px 12px;
}
.c2b-url-badge {
    flex-shrink: 0; font-size: 9.5px; font-weight: 700; letter-spacing: .04em;
    text-transform: uppercase; border-radius: 5px; padding: 3px 8px;
}
.c2b-url-badge-confirm  { background: #ecfdf3; color: #067647; }
.c2b-url-badge-validate { background: #eef4ff; color: #175cd3; }
.c2b-url-val {
    flex: 1; font-size: 10.5px; font-family: 'JetBrains Mono', ui-monospace, monospace;
    color: #344054; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    min-width: 0;
}
.c2b-url-copy {
    flex-shrink: 0; width: 28px; height: 28px; border-radius: 7px;
    border: 1px solid var(--line); background: #fff; color: #98a2b3;
    display: grid; place-items: center; cursor: pointer; font-size: 12px;
    transition: all .15s;
}
.c2b-url-copy:hover { border-color: #c7c8f6; color: var(--primary); background: #f5f5ff; }
.c2b-url-copy.copied { border-color: #a9efc5; color: #067647; background: #ecfdf3; }

/* Footer */
.c2b-card-footer {
    display: flex; align-items: center; justify-content: space-between; gap: 16px;
    padding: 18px 24px; margin-top: 20px; border-top: 1px solid var(--line);
    background: #fafbff;
}
.c2b-footer-note {
    display: flex; align-items: center; gap: 7px;
    font-size: 11px; color: #667085;
}
.c2b-footer-note i { color: #98a2b3; font-size: 13px; flex-shrink: 0; }
.c2b-submit-btn {
    height: 40px; display: inline-flex; align-items: center; gap: 8px;
    background: var(--primary); border: 1px solid var(--primary); border-radius: 9px;
    color: #fff; font-size: 12px; font-weight: 650; cursor: pointer;
    padding: 0 20px; transition: all .15s; white-space: nowrap;
}
.c2b-submit-btn:hover { background: #4a4bcf; border-color: #4a4bcf; }

@media (max-width: 700px) {
    .c2b-field-row   { grid-template-columns: 1fr; }
    .c2b-card-head   { flex-direction: column; }
    .c2b-card-footer { flex-direction: column; align-items: stretch; }
    .c2b-submit-btn  { justify-content: center; }
}
</style>
@endpush

@section('content')
<div class="ops-page">

    {{-- ═══ PAGE HEADER ══════════════════════════════════════════════════ --}}
    <div class="page-header">
        <div>
            <div class="eyebrow">
                <span class="eyebrow-dot" style="background:#12b76a;box-shadow:0 0 0 4px #e8faf3"></span>
                Payments · M-Pesa
            </div>
            <h1>Daraja API Operations</h1>
            <p>Submit Safaricom Daraja requests and track acknowledgements and provider callbacks.</p>
        </div>
        <div class="page-actions">
            <span class="ops-env-badge {{ $environment === 'production' ? 'ops-env-production' : 'ops-env-sandbox' }}">
                {{ $environment }} endpoint
            </span>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="ops-flash ops-flash-success">
            <i class="bi bi-check-circle-fill"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if(session('error'))
        <div class="ops-flash ops-flash-error">
            <i class="bi bi-exclamation-circle-fill"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif
    @if(session('operation_response'))
        <div class="ops-response-block">
            <strong><i class="bi bi-arrow-return-right me-1"></i> Daraja response</strong>
            <pre>{{ json_encode(session('operation_response'), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    @endif

    {{-- ═══ C2B URL REGISTRATION CARD ══════════════════════════════════ --}}
    @php
        $orgSlug     = auth()->user()->organisation?->slug ?? auth()->user()->organisation_id;
        $callbackBase = rtrim(config('app.url'), '/') . '/api/pay/' . $orgSlug;
        $confirmUrl   = $callbackBase . '/c2b/confirmation';
        $validateUrl  = $callbackBase . '/c2b/validation';
    @endphp
    <div class="panel c2b-card">
        <div class="c2b-card-head">
            <div class="c2b-head-left">
                <div class="c2b-icon"><i class="bi bi-link-45deg"></i></div>
                <div>
                    <h2 class="c2b-title">C2B URL Registration</h2>
                    <p class="c2b-sub">Register your Confirmation and Validation URLs with Safaricom so incoming M-Pesa payments are routed here.</p>
                </div>
            </div>
            <span class="ops-env-badge {{ $environment === 'production' ? 'ops-env-production' : 'ops-env-sandbox' }}">
                {{ $environment }}
            </span>
        </div>

        <div class="c2b-card-body">
            <form method="POST" action="{{ route('mpesa.operations.c2b.register') }}" id="c2b-register-form">
                @csrf
                <input type="hidden" name="product" id="c2b-product-hidden" value="">

                <div class="c2b-fields">

                    {{-- Short code --}}
                    <div class="c2b-field-group">
                        <label class="c2b-label" for="short_code">
                            <i class="bi bi-hash"></i> Business Short Code
                            <span class="c2b-label-hint">Your M-Pesa Pay Bill or Till number</span>
                        </label>
                        <input type="text" id="short_code" name="short_code"
                               class="c2b-input @error('short_code') is-invalid @enderror"
                               value="{{ old('short_code') }}"
                               placeholder="e.g. 174379"
                               pattern="\d{4,10}" inputmode="numeric" required>
                        @error('short_code')
                            <div class="c2b-field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    {{-- API version + response type --}}
                    <div class="c2b-field-row">
                        <div class="c2b-field-group">
                            <label class="c2b-label" for="c2b-version">
                                <i class="bi bi-layers"></i> API Version
                                <span class="c2b-label-hint">v2 recommended for new integrations</span>
                            </label>
                            <div class="c2b-segmented" id="c2b-version">
                                <button type="button" class="c2b-seg-btn" data-value="c2b_v2_register">v2 (recommended)</button>
                                <button type="button" class="c2b-seg-btn" data-value="c2b_v1_register">v1 (PayBill legacy)</button>
                            </div>
                        </div>

                        <div class="c2b-field-group">
                            <label class="c2b-label" for="c2b-response">
                                <i class="bi bi-toggle-on"></i> Response Type
                                <span class="c2b-label-hint">How unvalidated transactions are treated</span>
                            </label>
                            <div class="c2b-segmented" id="c2b-response">
                                <button type="button" class="c2b-seg-btn" data-name="response_type" data-value="Completed">Completed</button>
                                <button type="button" class="c2b-seg-btn" data-name="response_type" data-value="Cancelled">Cancelled</button>
                            </div>
                            <input type="hidden" name="response_type" id="response_type_hidden" value="{{ old('response_type', '') }}">
                        </div>
                    </div>

                    {{-- Auto-generated URLs (read-only preview) --}}
                    <div class="c2b-urls-preview">
                        <div class="c2b-urls-label">
                            <i class="bi bi-gear-fill"></i> Auto-generated callback URLs
                            <span class="c2b-urls-hint">These are injected automatically — no action needed</span>
                        </div>
                        <div class="c2b-url-row">
                            <span class="c2b-url-badge c2b-url-badge-confirm">Confirmation</span>
                            <code class="c2b-url-val" id="preview-confirm">{{ $confirmUrl }}</code>
                            <button type="button" class="c2b-url-copy" onclick="copyC2bUrl('preview-confirm', this)" title="Copy">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                        <div class="c2b-url-row">
                            <span class="c2b-url-badge c2b-url-badge-validate">Validation</span>
                            <code class="c2b-url-val" id="preview-validate">{{ $validateUrl }}</code>
                            <button type="button" class="c2b-url-copy" onclick="copyC2bUrl('preview-validate', this)" title="Copy">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    </div>

                </div>{{-- /.c2b-fields --}}

                <div class="c2b-card-footer">
                    <div class="c2b-footer-note">
                        <i class="bi bi-info-circle"></i>
                        Registration is idempotent — you can re-run it if your URLs change.
                    </div>
                    <button type="submit" class="c2b-submit-btn"
                            onclick="return confirm('Register these URLs with Safaricom now?')">
                        <i class="bi bi-send-check"></i> Register URLs
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ TWO-COLUMN LAYOUT ════════════════════════════════════════════ --}}
    <div class="ops-layout">

        {{-- ── LEFT: New request form ─────────────────────────────────── --}}
        <div class="panel" style="overflow:hidden">
            <div class="ops-panel-head">
                <div>
                    <h2>New API request</h2>
                    <p>Select an endpoint and provide the required fields as JSON.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('mpesa.operations.store') }}">
                @csrf
                <div class="ops-form-body">

                    <div class="mb-4">
                        <label class="ops-field-label" for="product">Product / endpoint</label>
                        <select class="ops-select @error('product') is-invalid @enderror"
                                id="product" name="product" required>
                            @foreach($catalog as $key => $definition)
                                <option value="{{ $key }}"
                                    data-required="{{ json_encode($definition['required']) }}"
                                    data-system="{{ json_encode($definition['system_fields']) }}"
                                    data-path="{{ $definition['path'] }}"
                                    {{ old('product') === $key ? 'selected' : '' }}>
                                    {{ $definition['label'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('product')
                            <div class="ops-error-msg mt-2"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <div class="ops-field-label">
                            Fields <span class="ops-field-hint" id="field-summary"></span>
                        </div>
                        <div class="ops-chips-bar" id="required-chips"></div>
                    </div>

                    <div class="mb-1">
                        <label class="ops-field-label" for="payload">
                            Request body
                            <span class="ops-field-hint">JSON object — amounts in KSh</span>
                        </label>
                        <textarea class="ops-textarea @error('payload') is-invalid @enderror"
                                  id="payload" name="payload"
                                  rows="12" spellcheck="false"
                                  placeholder="Select an endpoint above, then click Insert template.">{{ old('payload', '{}') }}</textarea>
                        @error('payload')
                            <div class="ops-error-msg"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    <div class="ops-caution">
                        <i class="bi bi-shield-exclamation"></i>
                        <div>
                            Live actions call <strong>Safaricom</strong> directly. OAuth tokens, callback URLs,
                            and security credentials are injected automatically — do not include them.
                            Callbacks require a public HTTPS <code>APP_URL</code>.
                        </div>
                    </div>
                </div>

                <div class="ops-form-footer">
                    <button type="button" class="ops-template-btn" id="payload-template">
                        <i class="bi bi-braces"></i> Insert template
                    </button>
                    <button type="submit" class="ops-submit-btn"
                            onclick="return confirm('Submit this request to the configured Daraja environment?')">
                        <i class="bi bi-send"></i> Submit to Daraja
                    </button>
                </div>
            </form>
        </div>

        {{-- ── RIGHT: Recent operations table ────────────────────────── --}}
        <div class="panel" style="overflow:hidden">
            <div class="ops-table-head">
                <div>
                    <h2 style="font-size:13px;font-weight:700;margin:0;color:#0d1320">Recent operations</h2>
                    <p style="font-size:10.5px;color:#98a2b3;margin:3px 0 0;font-weight:500">
                        Click any row to inspect payloads and callback data
                    </p>
                </div>
                <span class="ops-total-badge">{{ number_format($operations->total()) }} total</span>
            </div>

            <div class="admin-table-wrap">
                <table class="ops-table">
                    <colgroup>
                        <col style="width:110px">
                        <col style="width:auto">
                        <col style="width:90px">
                        <col style="width:110px">
                        <col style="width:auto">
                        <col style="width:44px">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Created</th>
                            <th>Product</th>
                            <th>Env</th>
                            <th>Status</th>
                            <th>Reference</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($operations as $op)
                        @php
                            $productLabel = $catalog[$op->product]['label'] ?? $op->product;
                            $productPath  = $catalog[$op->product]['path']  ?? '';
                            $statusClass  = match($op->status) {
                                'completed'  => 'ops-status-completed',
                                'failed'     => 'ops-status-failed',
                                'timeout'    => 'ops-status-timeout',
                                'submitted'  => 'ops-status-submitted',
                                'processing' => 'ops-status-processing',
                                default      => 'ops-status-queued',
                            };
                            $envClass = $op->environment === 'production' ? 'ops-env-pill-prod' : 'ops-env-pill-sand';
                            $ref      = $op->provider_transaction_id ?: $op->provider_conversation_id ?: null;

                            // Build safe data blob for the drawer (no raw payload here)
                            $drawerData = json_encode([
                                'id'          => $op->id,
                                'product'     => $productLabel,
                                'path'        => $productPath,
                                'environment' => strtoupper($op->environment),
                                'status'      => ucfirst($op->status),
                                'status_key'  => $op->status,
                                'created_at'  => $op->created_at->format('d M Y, H:i:s'),
                                'submitted_at'=> $op->submitted_at?->format('d M Y, H:i:s') ?? '—',
                                'completed_at'=> $op->completed_at?->format('d M Y, H:i:s') ?? '—',
                                'tx_id'       => $op->provider_transaction_id ?? null,
                                'conv_id'     => $op->provider_conversation_id ?? null,
                                'error'       => $op->error_message ?? null,
                                'response'    => $op->response_payload ?? null,
                                'callback'    => $op->callback_payload ?? null,
                            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                        @endphp
                        <tr onclick="openOpsDrawer({{ $drawerData }})" title="View details">
                            <td>
                                <span class="ops-ts">{{ $op->created_at->format('d M Y') }}</span>
                                <span class="ops-ts-s">{{ $op->created_at->format('H:i:s') }}</span>
                            </td>
                            <td>
                                <div class="ops-product-cell">
                                    <span class="ops-product-icon"><i class="bi bi-send-check"></i></span>
                                    <div style="min-width:0">
                                        <span class="ops-product-name">{{ $productLabel }}</span>
                                        <span class="ops-product-path">{{ $productPath }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="ops-env-pill {{ $envClass }}">
                                    {{ strtoupper($op->environment) }}
                                </span>
                            </td>
                            <td>
                                <span class="ops-status-pill {{ $statusClass }}">
                                    {{ ucfirst($op->status) }}
                                </span>
                            </td>
                            <td>
                                @if($op->error_message)
                                    <span class="ops-ref-error" title="{{ $op->error_message }}">
                                        <i class="bi bi-exclamation-triangle me-1"></i>{{ Str::limit($op->error_message, 40) }}
                                    </span>
                                @elseif($ref)
                                    <span class="ops-ref">{{ $ref }}</span>
                                    <span class="ops-ref-sub">{{ $op->provider_transaction_id ? 'Transaction ID' : 'Conversation ID' }}</span>
                                @else
                                    <span class="ops-ref-sub">{{ in_array($op->status, ['queued','submitted','processing']) ? 'Awaiting provider' : '—' }}</span>
                                @endif
                            </td>
                            <td>
                                <button type="button" class="ops-detail-btn" title="View detail"
                                        onclick="event.stopPropagation();openOpsDrawer({{ $drawerData }})">
                                    <i class="bi bi-arrow-right"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:0">
                                <div class="ops-empty">
                                    <div class="ops-empty-icon"><i class="bi bi-send"></i></div>
                                    <strong>No operations yet</strong>
                                    <small>Daraja API requests will appear here once submitted.</small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($operations->hasPages())
                <div class="ops-pagination">{{ $operations->links() }}</div>
            @endif
        </div>

    </div>{{-- /.ops-layout --}}
</div>{{-- /.ops-page --}}

{{-- ═══ DETAIL DRAWER ════════════════════════════════════════════════════ --}}
<div class="ops-overlay" id="opsOverlay" onclick="closeOpsDrawer()"></div>
<aside class="ops-drawer" id="opsDrawer" role="dialog" aria-modal="true" aria-label="Operation detail">
    <div class="ops-drawer-head">
        <div>
            <p class="ops-drawer-title" id="drawerTitle">Operation detail</p>
            <span class="ops-drawer-sub" id="drawerSub"></span>
        </div>
        <button type="button" class="ops-drawer-close" onclick="closeOpsDrawer()" aria-label="Close">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="ops-drawer-body" id="drawerBody">
        {{-- filled by JS --}}
    </div>
</aside>
@endsection

@push('scripts')
<script>
/* ── Form: chips + template ───────────────────────────────────────────────── */
(() => {
    const productEl = document.getElementById('product');
    const payloadEl = document.getElementById('payload');
    const chipsEl   = document.getElementById('required-chips');
    const summaryEl = document.getElementById('field-summary');
    const opt       = () => productEl.options[productEl.selectedIndex];

    function renderChips() {
        const req = JSON.parse(opt().dataset.required || '[]');
        const sys = JSON.parse(opt().dataset.system   || '[]');
        chipsEl.innerHTML = '';
        req.forEach(f => chipsEl.insertAdjacentHTML('beforeend',
            `<span class="ops-chip ops-chip-req"><i class="bi bi-asterisk"></i>${f}</span>`));
        sys.forEach(f => chipsEl.insertAdjacentHTML('beforeend',
            `<span class="ops-chip ops-chip-sys"><i class="bi bi-gear"></i>${f}</span>`));
        summaryEl.textContent = (req.length || sys.length)
            ? `${req.length} required · ${sys.length} auto-injected` : '';
    }
    productEl.addEventListener('change', renderChips);
    renderChips();

    const defaults = { ResponseType:'Completed', Amount:1, Size:300, sendReminders:false, invoices:[], externalReferences:[], CommandID:'' };
    document.getElementById('payload-template').addEventListener('click', () => {
        const req = JSON.parse(opt().dataset.required || '[]');
        const tpl = {};
        for (const f of req) tpl[f] = Object.prototype.hasOwnProperty.call(defaults, f) ? defaults[f] : '';
        if (opt().value === 'billmanager_optin') tpl.callbackurl = '@{{result_url}}';
        payloadEl.value = JSON.stringify(tpl, null, 2);
        payloadEl.focus();
    });

    payloadEl.addEventListener('blur', () => {
        const v = payloadEl.value.trim();
        if (!v || v === '{}') return;
        try { JSON.parse(v); payloadEl.classList.remove('is-invalid'); }
        catch { payloadEl.classList.add('is-invalid'); }
    });
    payloadEl.addEventListener('input', () => {
        if (!payloadEl.classList.contains('is-invalid')) return;
        try { JSON.parse(payloadEl.value); payloadEl.classList.remove('is-invalid'); } catch {}
    });
})();

/* ── Detail drawer ────────────────────────────────────────────────────────── */
const overlay = document.getElementById('opsOverlay');
const drawer  = document.getElementById('opsDrawer');
const dTitle  = document.getElementById('drawerTitle');
const dSub    = document.getElementById('drawerSub');
const dBody   = document.getElementById('drawerBody');

function syntaxHighlight(json) {
    if (typeof json !== 'string') json = JSON.stringify(json, null, 2);
    return json
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, m => {
            let cls = 'json-num';
            if (/^"/.test(m)) { cls = /:$/.test(m) ? 'json-key' : 'json-str'; }
            else if (/true|false/.test(m)) cls = 'json-bool';
            else if (/null/.test(m)) cls = 'json-null';
            return `<span class="${cls}">${m}</span>`;
        });
}

function jsonBlock(label, icon, data) {
    if (!data) return '';
    const pretty = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
    const id = 'jb-' + Math.random().toString(36).slice(2);
    return `
    <div class="ops-json-block">
        <div class="ops-json-block-head">
            <span><i class="bi ${icon}"></i>${label}</span>
            <button class="ops-json-copy-btn" onclick="copyJson('${id}', this)">
                <i class="bi bi-clipboard"></i> Copy
            </button>
        </div>
        <pre class="ops-json-pre" id="${id}">${syntaxHighlight(pretty)}</pre>
    </div>`;
}

function statusClass(key) {
    return { completed:'ops-status-completed', failed:'ops-status-failed', timeout:'ops-status-timeout',
             submitted:'ops-status-submitted', processing:'ops-status-processing' }[key] ?? 'ops-status-queued';
}

function openOpsDrawer(op) {
    dTitle.textContent = op.product;
    dSub.textContent   = op.path + ' · ' + op.environment;

    const hasPayloads = op.response || op.callback;
    const refLine = op.tx_id   ? `<span class="ops-ref">${op.tx_id}</span><span class="ops-ref-sub">Transaction ID</span>`
                  : op.conv_id ? `<span class="ops-ref">${op.conv_id}</span><span class="ops-ref-sub">Conversation ID</span>`
                  : `<span class="ops-ref-sub" style="color:#98a2b3">No provider reference yet</span>`;
    const errLine = op.error
        ? `<div class="ops-meta-item full"><label>Error</label><span style="color:#b42318">${op.error}</span></div>` : '';

    dBody.innerHTML = `
        {{-- Meta section --}}
        <div class="ops-drawer-section">
            <div class="ops-drawer-section-label">Details</div>
            <div class="ops-meta-grid">
                <div class="ops-meta-item">
                    <label>Status</label>
                    <span><span class="ops-status-pill ${statusClass(op.status_key)}">${op.status}</span></span>
                </div>
                <div class="ops-meta-item">
                    <label>Environment</label>
                    <span><span class="ops-env-pill ${op.environment === 'PRODUCTION' ? 'ops-env-pill-prod' : 'ops-env-pill-sand'}">${op.environment}</span></span>
                </div>
                <div class="ops-meta-item">
                    <label>Created</label>
                    <span>${op.created_at}</span>
                </div>
                <div class="ops-meta-item">
                    <label>Submitted</label>
                    <span>${op.submitted_at}</span>
                </div>
                <div class="ops-meta-item">
                    <label>Completed</label>
                    <span>${op.completed_at}</span>
                </div>
                <div class="ops-meta-item">
                    <label>Reference</label>
                    <div>${refLine}</div>
                </div>
                ${errLine}
            </div>
        </div>
        ${hasPayloads ? `
        <div class="ops-drawer-section">
            <div class="ops-drawer-section-label">Payloads</div>
            ${jsonBlock('Daraja response', 'bi-arrow-down-left-circle', op.response)}
            ${jsonBlock('Provider callback', 'bi-arrow-return-left', op.callback)}
        </div>` : `
        <div class="ops-drawer-section">
            <div style="text-align:center;padding:32px 0">
                <div style="width:44px;height:44px;border-radius:12px;background:#f5f6f8;display:grid;place-items:center;margin:0 auto 12px;font-size:20px;color:#c0c8d8"><i class="bi bi-hourglass-split"></i></div>
                <div style="font-size:12px;color:#667085;font-weight:500">No payload data yet</div>
                <div style="font-size:10.5px;color:#98a2b3;margin-top:4px">Waiting for Daraja to acknowledge or callback</div>
            </div>
        </div>`}
    `;

    overlay.classList.add('open');
    drawer.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeOpsDrawer() {
    overlay.classList.remove('open');
    drawer.classList.remove('open');
    document.body.style.overflow = '';
}

function copyJson(preId, btn) {
    const text = document.getElementById(preId)?.innerText ?? '';
    navigator.clipboard.writeText(text).then(() => {
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Copied';
        btn.classList.add('copied');
        setTimeout(() => {
            btn.innerHTML = '<i class="bi bi-clipboard"></i> Copy';
            btn.classList.remove('copied');
        }, 2000);
    });
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeOpsDrawer(); });

/* ── C2B register: segmented controls ───────────────────────────────────── */
(() => {
    // Version toggle (v1/v2)
    const versionBtns  = document.querySelectorAll('#c2b-version .c2b-seg-btn');
    const productInput = document.getElementById('c2b-product-hidden');
    versionBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            versionBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            productInput.value = btn.dataset.value;
        });
    });

    // Response type toggle
    const responseBtns  = document.querySelectorAll('#c2b-response .c2b-seg-btn');
    const responseInput = document.getElementById('response_type_hidden');
    responseBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            responseBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            responseInput.value = btn.dataset.value;
        });
    });

    // Guard: require both selections before submit
    document.getElementById('c2b-register-form')?.addEventListener('submit', function (e) {
        if (!productInput.value) {
            e.preventDefault();
            alert('Please select an API version.');
            return;
        }
        if (!responseInput.value) {
            e.preventDefault();
            alert('Please select a response type.');
            return;
        }
    });
})();

function copyC2bUrl(elId, btn) {
    const text = document.getElementById(elId)?.textContent?.trim() ?? '';
    navigator.clipboard.writeText(text).then(() => {
        const prev = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check-lg"></i>';
        btn.classList.add('copied');
        setTimeout(() => { btn.innerHTML = prev; btn.classList.remove('copied'); }, 2000);
    });
}
</script>
@endpush
