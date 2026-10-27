@extends('layouts.app')

@section('title', $application ? 'Edit Loan Application' : 'New Loan Application')

@push('styles')
<style>
.loan-wizard { max-width: 1080px; margin: 0 auto; }
.wizard-summary { display:flex; align-items:center; justify-content:space-between; gap:20px; padding:16px 20px; margin-bottom:18px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; }
.wizard-summary span { display:block; color:#64748b; font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; }
.wizard-summary strong { display:block; color:#0f172a; font-size:1rem; margin-top:3px; }
.wizard-stepper { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px; margin:0 0 20px; }
.wizard-step-nav { display:flex; align-items:center; gap:10px; padding:12px; border:1px solid #e2e8f0; background:#fff; border-radius:10px; color:#64748b; text-align:left; }
.wizard-step-nav.active { border-color:#2563eb; background:#eff6ff; color:#1d4ed8; }
.wizard-step-nav.complete { border-color:#86efac; color:#15803d; }
.wizard-step-number { display:grid; place-items:center; width:28px; height:28px; flex:0 0 28px; border-radius:50%; background:#f1f5f9; font-weight:700; }
.wizard-step-nav.active .wizard-step-number { background:#2563eb; color:#fff; }
.wizard-step-nav.complete .wizard-step-number { background:#dcfce7; color:#15803d; }
.wizard-step-nav small { display:block; margin-top:2px; color:#64748b; font-size:.7rem; }
.wizard-panel { padding:20px 0 6px; border-top:1px solid #eef2f6; }
.wizard-panel[hidden] { display:none; }
.wizard-panel-heading { margin:0 0 18px; }
.wizard-panel-heading h2 { margin:0 0 5px; font-size:1.1rem; }
.wizard-panel-heading p { margin:0; color:#64748b; font-size:.85rem; }
.borrower-details { margin-top:14px; padding:16px; border:1px solid #dbeafe; border-radius:10px; background:#f8fbff; }
.borrower-details[hidden] { display:none; }
.borrower-details h3 { margin:0 0 12px; font-size:.95rem; }
.borrower-details-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
.borrower-details-item dt { color:#64748b; font-size:.75rem; }
.borrower-details-item dd { margin:3px 0 0; color:#0f172a; font-weight:600; overflow-wrap:anywhere; }
.wizard-actions { display:flex; flex-wrap:wrap; justify-content:space-between; gap:10px; align-items:center; }
.wizard-actions-group { display:flex; gap:8px; }
.secure-note { display:flex; gap:10px; align-items:flex-start; padding:12px 14px; margin:14px 0; border-radius:8px; background:#f0f9ff; color:#075985; font-size:.82rem; }
.capture-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin-top:12px; }
.capture-card { border:1.5px dashed #cbd5e1; border-radius:10px; padding:16px; min-height:175px; background:#fbfdff; }
.capture-card label { display:block; font-weight:650; margin-bottom:3px; }
.capture-card p { color:#64748b; font-size:.78rem; margin:0 0 12px; }
.capture-actions { display:flex; flex-wrap:wrap; gap:8px; }
.capture-file { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0,0,0,0); }
.capture-preview { display:flex; gap:10px; align-items:center; margin-top:12px; }
.capture-preview img { width:70px; height:70px; object-fit:cover; border-radius:8px; border:1px solid #e2e8f0; }
.capture-preview[hidden] { display:none; }
.collateral-card { border:1px solid #e2e8f0; border-radius:10px; padding:16px; margin:12px 0; background:#fff; }
.collateral-card-header { display:flex; justify-content:space-between; gap:12px; align-items:center; margin-bottom:12px; }
.collateral-card-header h3 { margin:0; font-size:.95rem; }
.collateral-total { padding:14px 16px; border-radius:10px; background:#eff6ff; display:flex; justify-content:space-between; font-weight:650; }
.review-checklist { display:grid; gap:10px; }
.review-check { display:flex; gap:12px; align-items:flex-start; padding:14px; border:1px solid #e2e8f0; border-radius:9px; }
.review-check-icon { color:#16a34a; font-size:1.15rem; }
.review-check.pending .review-check-icon { color:#d97706; }
.review-check strong { display:block; }
.review-check small { color:#64748b; }
.duplicate-warning { display:none; margin-top:8px; color:#92400e; padding:10px 12px; background:#fffbeb; border-radius:8px; font-size:.82rem; }
.duplicate-warning.visible { display:block; }
.consent-row { display:flex; gap:10px; align-items:flex-start; margin-top:18px; padding:14px; border:1px solid #e2e8f0; border-radius:8px; }
@media(max-width:760px) {
    .wizard-stepper { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .capture-grid { grid-template-columns:1fr; }
    .wizard-summary { align-items:flex-start; flex-direction:column; }
    .wizard-step-nav { padding:9px; }
    .borrower-details-grid { grid-template-columns:1fr; }
}
/* ── Eligibility panel ─────────────────────────────────────────── */
.eligibility-panel {
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 20px 24px;
    margin-top: 20px;
    transition: border-color .2s;
}
.eligibility-panel.eligible   { border-color: #22c55e; background: #f0fdf4; }
.eligibility-panel.ineligible { border-color: #ef4444; background: #fef2f2; }
.eligibility-panel.warning    { border-color: #f59e0b; background: #fffbeb; }
.eligibility-panel.loading    { border-color: #94a3b8; }

.ep-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
}
.ep-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    padding: 3px 10px;
    border-radius: 20px;
}
.ep-badge.eligible   { background:#dcfce7; color:#16a34a; }
.ep-badge.ineligible { background:#fee2e2; color:#dc2626; }
.ep-badge.warning    { background:#fef3c7; color:#d97706; }
.ep-badge.info       { background:#dbeafe; color:#2563eb; }

.ep-offer {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}
.ep-offer-item span { font-size:.75rem; color:#64748b; display:block; }
.ep-offer-item strong { font-size:1.25rem; color:#0f172a; }

.ep-scores {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 14px;
}
.score-chip {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 8px 14px;
    min-width: 120px;
}
.score-chip span { font-size:.7rem; color:#64748b; display:block; }
.score-chip strong { font-size:1rem; color:#0f172a; }

.score-bar-wrap { margin-top:4px; }
.score-bar {
    height: 4px;
    background: #e2e8f0;
    border-radius: 2px;
    overflow: hidden;
}
.score-bar-fill {
    height: 100%;
    border-radius: 2px;
    transition: width .4s ease;
}
.score-bar-fill.high   { background: #22c55e; }
.score-bar-fill.medium { background: #f59e0b; }
.score-bar-fill.low    { background: #ef4444; }

.ep-kyc {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size:.82rem;
    padding: 6px 0;
    border-top: 1px solid #e2e8f0;
    margin-top: 8px;
}
.kyc-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}
.kyc-dot.verified { background:#22c55e; }
.kyc-dot.manual   { background:#3b82f6; }
.kyc-dot.pending  { background:#f59e0b; }
.kyc-dot.failed   { background:#ef4444; }
.kyc-dot.missing  { background:#94a3b8; }

.ep-reasons { margin-top: 10px; }
.ep-reasons ul { margin:0; padding-left:18px; }
.ep-reasons li { font-size:.82rem; margin-bottom:4px; color:#dc2626; }

.ep-warnings { margin-top: 8px; }
.ep-warnings ul { margin:0; padding-left:18px; }
.ep-warnings li { font-size:.82rem; margin-bottom:4px; color:#d97706; }

.ep-placeholder {
    text-align: center;
    color: #94a3b8;
    font-size: .85rem;
    padding: 18px 0 6px;
}
.ep-placeholder i { font-size: 1.6rem; display:block; margin-bottom:6px; }

.max-offer-note {
    font-size:.78rem;
    color:#2563eb;
    margin-top:4px;
}

.searchable-select { position:relative; }
.searchable-select-list {
    position:absolute;
    z-index:20;
    top:100%;
    left:0;
    right:0;
    max-height:240px;
    overflow-y:auto;
    margin:4px 0 0;
    padding:4px;
    list-style:none;
    background:#fff;
    border:1px solid #d0d5dd;
    border-radius:8px;
    box-shadow:0 8px 20px rgba(16,24,40,.12);
}
.searchable-select-list[hidden] { display:none; }
.searchable-select-option {
    display:block;
    width:100%;
    padding:8px 10px;
    border:0;
    border-radius:5px;
    background:transparent;
    color:#101828;
    text-align:left;
    font:inherit;
    cursor:pointer;
}
.searchable-select-option:hover,
.searchable-select-option[aria-selected="true"] { background:#f2f4f7; }
.searchable-select-empty { padding:8px 10px; color:#667085; font-size:12px; }

/* Application wizard visual system */
.page-header.application-page-header {
    max-width: 1136px;
    margin: 0 auto 18px;
    padding: 22px 26px;
    align-items: center;
    border: 1px solid #e8eaf3;
    border-radius: 18px;
    background:
        radial-gradient(ellipse at 92% 0%, rgba(99,102,241,.09), transparent 34%),
        linear-gradient(115deg, #fff 0%, #fbfbff 100%);
    box-shadow: 0 8px 24px rgba(31,41,75,.045);
}
.application-page-header .eyebrow {
    width: fit-content;
    padding: 6px 9px;
    border: 1px solid #e7e7ff;
    border-radius: 999px;
    background: #f5f5ff;
    color: #5556c9;
    font-size: 9px;
    letter-spacing: .12em;
}
.application-page-header h1 { margin-top: 10px; font-size: clamp(23px, 3vw, 30px); letter-spacing: -.04em; }
.application-page-header p { margin-top: 6px; font-size: 12px; }
.application-page-header > .btn { height: 38px; padding: 0 14px; border-radius: 10px; }
.loan-wizard { max-width: 1080px; }
.wizard-summary {
    min-height: 94px;
    padding: 18px 22px;
    margin-bottom: 16px;
    border-color: #e6e8f0;
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 8px 24px rgba(31,41,75,.045);
}
.wizard-summary > div { position: relative; flex: 1; padding-left: 42px; }
.wizard-summary > div + div { border-left: 1px solid #edf0f5; padding-left: 58px; }
.wizard-summary-icon {
    display: grid; place-items: center; width: 32px; height: 32px;
    position: absolute; left: 0; top: 50%; transform: translateY(-50%);
    border: 1px solid #e7e7ff; border-radius: 10px;
    background: #f5f5ff; color: #5b5ce2;
    font-size: 14px;
}
.wizard-summary span { font-size: 9px; font-weight: 700; letter-spacing: .1em; }
.wizard-summary strong { font-size: 14px; letter-spacing: -.02em; }
.wizard-summary > div:nth-child(3) strong { color: #4f46c8; font-size: 17px; }
.form-card#appForm {
    overflow: visible;
    border: 1px solid #e5e7ef;
    border-radius: 18px;
    box-shadow: 0 14px 36px rgba(31,41,75,.07), 0 2px 6px rgba(31,41,75,.025);
}
#appForm .form-card-header {
    display: flex;
    align-items: center;
    gap: 14px;
    min-height: 84px;
    padding: 20px 28px;
    border-bottom: 1px solid #eef0f5;
    background: linear-gradient(110deg, #fff 0%, #fcfcff 100%);
}
.wizard-heading-icon {
    display: grid;
    place-items: center;
    flex: 0 0 42px;
    width: 42px;
    height: 42px;
    border: 1px solid #e8e7ff;
    border-radius: 13px;
    background: linear-gradient(145deg, #f5f4ff, #ecebff);
    color: #5b5ce2;
    font-size: 20px;
}
#appForm .form-card-header > div { min-width: 0; }
#appForm .form-card-header h2 { margin: 0; color: #182230; font-size: 15px; font-weight: 700; letter-spacing: -.02em; }
#appForm .form-card-header p { margin: 4px 0 0; color: #7b8496; font-size: 11px; }
.wizard-stepper {
    position: relative;
    gap: 10px;
    padding: 18px 28px;
    margin: 0;
    border-bottom: 1px solid #eef0f5;
    background: #fcfcfe;
}
.wizard-step-nav {
    position: relative;
    min-width: 0;
    min-height: 60px;
    padding: 10px 12px;
    border-color: #e9eaf1;
    border-radius: 12px;
    color: #586174;
    font: inherit;
    font-size: 11px;
    font-weight: 650;
    cursor: pointer;
    transition: border-color .18s ease, background .18s ease, box-shadow .18s ease, transform .18s ease;
}
.wizard-step-nav:hover { transform: translateY(-1px); border-color: #c9c9f7; box-shadow: 0 4px 12px rgba(69,70,150,.06); }
.wizard-step-nav.active {
    border-color: #7778e8;
    background: linear-gradient(115deg, #f5f5ff, #fff);
    color: #4445bb;
    box-shadow: 0 0 0 3px rgba(91,92,226,.07);
}
.wizard-step-nav.complete { border-color: #cdeedd; background: #f8fdf9; color: #137a4b; }
.wizard-step-number { width: 30px; height: 30px; flex-basis: 30px; background: #f1f2f6; color: #687184; font-size: 11px; }
.wizard-step-nav.active .wizard-step-number { background: linear-gradient(145deg, #6b6ce9, #5051ce); box-shadow: 0 3px 8px rgba(91,92,226,.25); color: #fff; }
.wizard-step-nav.complete .wizard-step-number { background: #e5f8ed; color: #16834f; }
.wizard-step-nav small { color: #8a92a2; font-size: 9px; font-weight: 500; }
.wizard-progress {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 28px 0;
    color: #7c8494;
    font-size: 10px;
}
.wizard-progress-track { height: 4px; flex: 1; overflow: hidden; border-radius: 99px; background: #ececf4; }
.wizard-progress-fill { width: 25%; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #7778e8, #5556d6); transition: width .25s ease; }
.wizard-progress-label { min-width: 82px; text-align: right; font-weight: 650; color: #5556c9; }
.wizard-panel { padding: 24px 28px 26px; border-top: 0; animation: wizard-panel-in .22s ease both; }
.wizard-panel-heading { margin-bottom: 22px; }
.wizard-panel-heading h2 { color: #182230; font-size: 19px; letter-spacing: -.035em; }
.wizard-panel-heading p { max-width: 680px; font-size: 11px; line-height: 1.6; }
#appForm .form-field label { display: block; margin: 0 0 7px; color: #344054; font-size: 11px; font-weight: 650; }
#appForm .form-field label > span { color: #d0445c; }
#appForm .form-field label small,
#appForm h3 + .form-grid label small { margin-left: 4px; color: #9299a8; font-size: 9px; font-weight: 500; }
#appForm .form-input {
    min-height: 42px;
    padding: 0 12px;
    border: 1px solid #dfe2ea;
    border-radius: 10px;
    background-color: #fff;
    color: #202939;
    font-size: 11px;
    box-shadow: 0 1px 2px rgba(16,24,40,.025);
    transition: border-color .16s ease, box-shadow .16s ease, background .16s ease;
}
#appForm textarea.form-input { min-height: 82px; padding: 11px 12px; }
#appForm .form-input:hover { border-color: #c8ccd8; }
#appForm .form-input:focus { border-color: #7778e8; box-shadow: 0 0 0 4px rgba(91,92,226,.1); }
#appForm .form-field .form-error { display: block; margin-top: 5px; color: #bd374a; font-size: 10px; }
#appForm .form-grid { gap: 18px 20px; padding: 0; }
#appForm .wizard-panel h3 { margin: 20px 0 12px; color: #293345; font-size: 12px; font-weight: 700; letter-spacing: -.01em; }
#appForm .eligibility-panel {
    padding: 17px 19px;
    border: 1px solid #e6e8f1;
    border-radius: 13px;
    background: linear-gradient(135deg, #fafaff, #f8fafc);
}
#appForm .capture-card {
    min-height: 180px;
    border: 1px dashed #d6d9e5;
    border-radius: 13px;
    background: linear-gradient(145deg, #fff, #fafbff);
    transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
}
#appForm .capture-card:hover { transform: translateY(-2px); border-color: #9999ec; box-shadow: 0 8px 18px rgba(31,41,75,.05); }
#appForm .capture-card > label { font-size: 11px; }
#appForm .capture-card p { font-size: 10px; }
#appForm .collateral-card,
#appForm .review-check { border-color: #e6e8ef; border-radius: 12px; box-shadow: 0 2px 7px rgba(31,41,75,.025); }
#appForm .collateral-total { border: 1px solid #e5e5ff; border-radius: 12px; background: linear-gradient(110deg, #f7f7ff, #f2f5ff); color: #4445bb; }
#appForm .secure-note { border: 1px solid #dcecf5; border-radius: 10px; line-height: 1.55; }
#appForm .consent-row { border-color: #e5e7ef; border-radius: 11px; background: #fcfcfe; line-height: 1.55; font-size: 11px; }
#appForm .wizard-actions {
    position: sticky;
    bottom: 0;
    z-index: 15;
    padding: 13px 28px;
    border-top: 1px solid #e9ebf2;
    border-radius: 0 0 18px 18px;
    background: rgba(255,255,255,.94);
    box-shadow: 0 -8px 22px rgba(31,41,75,.035);
    backdrop-filter: blur(12px);
}
#appForm .wizard-actions .btn { min-height: 38px; padding: 0 14px; border-radius: 10px; font-size: 10px; }
#appForm .wizard-actions .btn-primary { box-shadow: 0 4px 10px rgba(91,92,226,.17); }
#appForm .wizard-actions-group { gap: 9px; }
#appForm .alert { margin: 14px 28px 0; border-radius: 10px; font-size: 11px; }
.duplicate-warning { border: 1px solid #f5d89c; border-radius: 10px; }
@keyframes wizard-panel-in { from { opacity: .6; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }
@media(max-width:760px) {
    .page-header.application-page-header { margin: 0 14px 14px; padding: 18px; border-radius: 14px; }
    .application-page-header > .btn { align-self: flex-start; }
    .wizard-summary { gap: 14px; padding: 16px; }
    .wizard-summary > div { min-height: 34px; }
    .wizard-summary > div + div { padding-left: 48px; }
    .wizard-stepper { padding: 14px 16px; }
    .wizard-progress { padding: 12px 16px 0; }
    .wizard-panel { padding: 20px 16px 22px; }
    #appForm .form-card-header { padding: 17px 16px; }
    #appForm .wizard-actions { padding: 12px 16px; }
}
@media(max-width:520px) {
    .page-header.application-page-header { align-items: flex-start; }
    .page-header.application-page-header > .btn { width: 100%; justify-content: center; }
    .wizard-summary { display: grid; grid-template-columns: 1fr; }
    .wizard-summary > div,
    .wizard-summary > div + div { min-height: 36px; padding-left: 44px; border-left: 0; }
    .wizard-stepper { gap: 7px; }
    .wizard-step-nav { min-height: 56px; gap: 7px; padding: 8px; font-size: 10px; }
    .wizard-step-number { width: 26px; height: 26px; flex-basis: 26px; }
    #appForm .wizard-actions { align-items: stretch; flex-direction: column; }
    #appForm .wizard-actions > div:first-child,
    #appForm .wizard-actions-group { display: flex; width: 100%; }
    #appForm .wizard-actions > div:first-child .btn,
    #appForm .wizard-actions-group .btn { flex: 1; justify-content: center; }
}
@media(prefers-reduced-motion:reduce) {
    .wizard-step-nav, .wizard-progress-fill, .wizard-panel, #appForm .capture-card { transition: none; animation: none; }
}
</style>
@endpush

@section('content')
<div class="page-header application-page-header">
    <div>
        <div class="eyebrow"><i class="bi bi-stars"></i> LOAN APPLICATIONS</div>
        <h1>{{ $application ? 'Edit Loan Application' : 'New Application' }}</h1>
        <p class="page-subtitle">A guided workspace to build a complete, decision-ready loan application.</p>
    </div>
    <a href="{{ route('applications.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
</div>

@php
    $guarantorData = $securityData['guarantor'] ?? [];
    $collateralData = $securityData['collateral'] ?? [];
@endphp
<div class="loan-wizard">
    <div class="wizard-summary" aria-label="Loan application summary">
        <div><i class="wizard-summary-icon bi bi-file-earmark-text" aria-hidden="true"></i><span>Application reference</span><strong>{{ $application ? '#LN-'.str_pad($application->id, 6, '0', STR_PAD_LEFT) : 'New application' }}</strong></div>
        <div><i class="wizard-summary-icon bi bi-person" aria-hidden="true"></i><span>Borrower</span><strong id="summaryBorrower">Select borrower</strong></div>
        <div><i class="wizard-summary-icon bi bi-cash-stack" aria-hidden="true"></i><span>Requested amount</span><strong id="summaryAmount">KES 0</strong></div>
    </div>
    <form method="POST" enctype="multipart/form-data"
        action="{{ $application ? route('applications.update', $application) : route('applications.store') }}"
        class="form-card" id="appForm" novalidate>
        @csrf
        @if($application) @method('PUT') @endif
        <input type="hidden" name="collateral_present" value="1">

        <div class="form-card-header">
            <span class="wizard-heading-icon" aria-hidden="true"><i class="bi bi-layers"></i></span>
            <div>
                <h2>{{ $application ? 'Complete your loan application' : 'Start a loan application' }}</h2>
                <p>Capture the loan details, guarantor information and supporting security in one place.</p>
            </div>
        </div>

        <nav class="wizard-stepper" aria-label="Loan application steps">
            <button type="button" class="wizard-step-nav active" data-step-nav="1"><span class="wizard-step-number">1</span><span>Borrower &amp; Loan<small>Application details</small></span></button>
            <button type="button" class="wizard-step-nav" data-step-nav="2"><span class="wizard-step-number">2</span><span>Guarantor<small>Details and ID</small></span></button>
            <button type="button" class="wizard-step-nav" data-step-nav="3"><span class="wizard-step-number">3</span><span>Collateral<small>Optional assets</small></span></button>
            <button type="button" class="wizard-step-nav" data-step-nav="4"><span class="wizard-step-number">4</span><span>Review &amp; Submit<small>Completeness check</small></span></button>
        </nav>
        <div class="wizard-progress" aria-label="Application progress">
            <span>Application progress</span>
            <div class="wizard-progress-track" role="progressbar" aria-label="Application steps completed" aria-valuemin="1" aria-valuemax="4" aria-valuenow="1">
                <div class="wizard-progress-fill" id="wizardProgressFill"></div>
            </div>
            <span class="wizard-progress-label" id="wizardProgressLabel">Step 1 of 4</span>
        </div>

        @if($errors->any())
            <div class="alert alert-danger" role="alert">Please check the highlighted fields and complete any missing required information.</div>
        @endif

        <section class="wizard-panel" data-step="1">
        <div class="wizard-panel-heading">
            <h2>Borrower &amp; loan</h2>
            <p>Choose the borrower and loan product. A live eligibility check runs automatically.</p>
        </div>
        <div class="form-grid">

            {{-- Borrower --}}
            <div class="form-field form-field-full">
                <label for="borrower_search">Borrower <span>*</span></label>
                <div class="searchable-select">
                    <input id="borrower_search" type="text" class="form-input"
                        placeholder="Search by borrower name or phone" autocomplete="off"
                        role="combobox" aria-autocomplete="list" aria-expanded="false"
                        aria-controls="borrower_options">
                    <ul id="borrower_options" class="searchable-select-list" role="listbox" hidden></ul>
                </div>
                <select id="borrower_id" name="borrower_id" class="form-input" required>
                    <option value="">Select borrower</option>
                    @foreach($borrowers as $borrower)
                        <option value="{{ $borrower->id }}" @selected(old('borrower_id', $application?->borrower_id) == $borrower->id)>
                            {{ $borrower->user->name ?? 'Borrower #'.$borrower->id }} · {{ $borrower->phone_number }}
                        </option>
                    @endforeach
                </select>
                @error('borrower_id') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div id="borrowerDetails" class="borrower-details form-field-full" aria-live="polite" hidden>
                <h3>Selected borrower details</h3>
                <dl class="borrower-details-grid">
                    <div class="borrower-details-item"><dt>Name</dt><dd data-borrower-detail="name">—</dd></div>
                    <div class="borrower-details-item"><dt>Phone</dt><dd data-borrower-detail="phone">—</dd></div>
                    <div class="borrower-details-item"><dt>Email</dt><dd data-borrower-detail="email">—</dd></div>
                    <div class="borrower-details-item"><dt>County</dt><dd data-borrower-detail="residence_county">—</dd></div>
                    <div class="borrower-details-item"><dt>Address</dt><dd data-borrower-detail="residence_address">—</dd></div>
                </dl>
            </div>

            {{-- Product --}}
            <div class="form-field form-field-full">
                <label for="loan_product_search">Loan Product <span>*</span></label>
                <div class="searchable-select">
                    <input id="loan_product_search" type="text" class="form-input"
                        placeholder="Search loan products" autocomplete="off"
                        role="combobox" aria-autocomplete="list" aria-expanded="false"
                        aria-controls="loan_product_options">
                    <ul id="loan_product_options" class="searchable-select-list" role="listbox" hidden></ul>
                </div>
                <select id="loan_product_id" name="loan_product_id" class="form-input" required>
                    <option value="">Select product</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}"
                            data-min="{{ $product->minimum_amount_cents / 100 }}"
                            data-max="{{ $product->maximum_amount_cents / 100 }}"
                            data-min-term="{{ $product->minimum_term_days }}"
                            data-max-term="{{ $product->maximum_term_days }}"
                            @selected(old('loan_product_id', $application?->loan_product_id) == $product->id)>
                            {{ $product->name }} ({{ $product->code }})
                        </option>
                    @endforeach
                </select>
                @error('loan_product_id') <div class="form-error">{{ $message }}</div> @enderror
            </div>

        </div>

        {{-- ── Live Eligibility Panel ─────────────────────────────── --}}
        <div id="eligibilityPanel" class="eligibility-panel">
            <div class="ep-placeholder">
                <i class="bi bi-person-check"></i>
                Select a borrower and product to see the eligibility assessment.
            </div>
        </div>

        <div class="form-grid" style="margin-top:20px">

            <div class="form-field">
                <label for="requested_amount">Requested Amount (KSh) <span>*</span></label>
                <input id="requested_amount" type="number" step="0.01" min="1"
                    name="requested_amount" value="{{ old('requested_amount', $application ? $application->requested_amount_cents / 100 : '') }}"
                    class="form-input" required>
                <small id="amount-hint" class="form-hint">Select a product to see limits.</small>
                @error('requested_amount') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-field">
                <label for="requested_term_days">Requested Term (Days) <span>*</span></label>
                <input id="requested_term_days" type="number" min="1"
                    name="requested_term_days" value="{{ old('requested_term_days', $application?->requested_term_days) }}"
                    class="form-input" required>
                <small id="term-hint" class="form-hint"></small>
                @error('requested_term_days') <div class="form-error">{{ $message }}</div> @enderror
            </div>

        </div>
        </section>

        <section class="wizard-panel" data-step="2" hidden>
            <div class="wizard-panel-heading">
                <h2>Guarantor information</h2>
                <p>Register one person guaranteeing this loan. A possible match is only a warning; staff decide whether records are related.</p>
            </div>
            <h3>Personal details</h3>
            <div class="form-grid">
                <div class="form-field">
                    <label for="guarantor_name">Full legal name <span>*</span></label>
                    <input class="form-input" id="guarantor_name" name="guarantor[name]" required maxlength="255" value="{{ old('guarantor.name', $guarantorData['name'] ?? '') }}">
                    @error('guarantor.name')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-field">
                    <label for="guarantor_national_id">National ID number <span>*</span></label>
                    <input class="form-input" id="guarantor_national_id" name="guarantor[national_id]" required maxlength="30" value="{{ old('guarantor.national_id', $guarantorData['national_id'] ?? '') }}" autocomplete="off">
                    @error('guarantor.national_id')<div class="form-error">{{ $message }}</div>@enderror
                    <div id="duplicateWarning" class="duplicate-warning" role="status">A possible existing guarantor record was found in this organisation. Review it before continuing; records are never merged automatically.</div>
                </div>
                <div class="form-field">
                    <label for="guarantor_phone">Phone number <span>*</span></label>
                    <input class="form-input" id="guarantor_phone" name="guarantor[phone]" type="tel" inputmode="tel" required maxlength="25" placeholder="07XX XXX XXX" value="{{ old('guarantor.phone', $guarantorData['phone'] ?? '') }}">
                    @error('guarantor.phone')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-field">
                    <label for="guarantor_email">Email address <small>Optional</small></label>
                    <input class="form-input" id="guarantor_email" name="guarantor[email]" type="email" maxlength="255" value="{{ old('guarantor.email', $guarantorData['email'] ?? '') }}">
                    @error('guarantor.email')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-field">
                    <label for="guarantor_relationship">Relationship to borrower <span>*</span></label>
                    <select class="form-input" id="guarantor_relationship" name="guarantor[relationship]" required>
                        <option value="">Select relationship</option>
                        @foreach(['Spouse', 'Parent', 'Sibling', 'Relative', 'Friend', 'Colleague', 'Business partner', 'Other'] as $relationship)
                            <option value="{{ $relationship }}" @selected(old('guarantor.relationship', $guarantorData['relationship'] ?? '') === $relationship)>{{ $relationship }}</option>
                        @endforeach
                    </select>
                    @error('guarantor.relationship')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-field">
                    <label for="guarantor_occupation">Occupation <small>Optional</small></label>
                    <input class="form-input" id="guarantor_occupation" name="guarantor[occupation]" maxlength="120" value="{{ old('guarantor.occupation', $guarantorData['occupation'] ?? '') }}" placeholder="e.g. Business owner">
                </div>
            </div>
            <h3 style="margin-top:22px">Contact and residence</h3>
            <div class="form-grid">
                <div class="form-field form-field-full">
                    <label for="guarantor_address">Residential address <span>*</span></label>
                    <textarea class="form-input" id="guarantor_address" name="guarantor[address]" required maxlength="1000" rows="2" placeholder="Estate, town, landmark or physical address">{{ old('guarantor.address', $guarantorData['address'] ?? '') }}</textarea>
                    @error('guarantor.address')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-field">
                    <label for="guarantor_employer">Employer / business <small>Optional</small></label>
                    <input class="form-input" id="guarantor_employer" name="guarantor[employer]" maxlength="255" value="{{ old('guarantor.employer', $guarantorData['employer'] ?? '') }}">
                </div>
                <div class="form-field">
                    <label for="guarantor_alternative_contact">Alternative contact <small>Optional</small></label>
                    <input class="form-input" id="guarantor_alternative_contact" name="guarantor[alternative_contact]" maxlength="255" value="{{ old('guarantor.alternative_contact', $guarantorData['alternative_contact'] ?? '') }}">
                </div>
            </div>
            <h3 style="margin-top:22px">Guarantor identification</h3>
            <p class="page-subtitle">JPEG, PNG or WebP · up to 5 MB per image. Use a clear, readable image.</p>
            <div class="capture-grid">
                @foreach([
                    ['guarantor_photo', 'Guarantor photo', 'Face clearly visible', 'photo', true],
                    ['guarantor_id_front', 'ID front', 'All text readable', 'id_front', true],
                    ['guarantor_id_back', 'National ID — back', 'Capture reverse side where relevant', 'id_back', false],
                ] as [$input, $label, $hint, $key, $required])
                    @php $savedImage = $guarantorData[$key] ?? null; @endphp
                    <div class="capture-card">
                        <label for="{{ $input }}">{{ $label }} @if($required)<span>*</span>@else<small>Optional</small>@endif</label>
                        <p>{{ $hint }}</p>
                        <div class="capture-actions">
                            <label class="btn btn-light btn-sm" for="{{ $input }}"><i class="bi bi-camera"></i> Take photo</label>
                            <label class="btn btn-light btn-sm" for="{{ $input }}"><i class="bi bi-upload"></i> Upload image</label>
                            <input class="capture-file" id="{{ $input }}" name="{{ $input }}" type="file" accept="image/jpeg,image/png,image/webp" capture="environment" @if($required) data-security-required="1" @endif>
                        </div>
                        <input type="hidden" name="remove_{{ $input }}" id="remove_{{ $input }}" value="0">
                        <div class="capture-preview" id="preview_{{ $input }}" @if(!$savedImage) hidden @endif>
                            @if($savedImage)
                                <img src="{{ route('applications.security-file', [$application, $input === 'guarantor_photo' ? 'guarantor-photo' : ($input === 'guarantor_id_front' ? 'guarantor-id-front' : 'guarantor-id-back')]) }}" alt="{{ $label }} preview">
                                <span>Previously uploaded</span>
                            @else
                                <img alt="{{ $label }} preview">
                                <span></span>
                            @endif
                            <button type="button" class="btn btn-light btn-sm" data-preview="{{ $input }}">Preview</button>
                            <button type="button" class="btn btn-light btn-sm" data-remove="{{ $input }}">Remove</button>
                        </div>
                        @error($input)<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                @endforeach
            </div>
            @if(!$application?->guarantor_consent_at)
                <label class="consent-row">
                    <input type="checkbox" name="guarantor_consent" value="1" required @checked(old('guarantor_consent'))>
                    <span>I confirm that the guarantor has been informed about the guarantee obligations and how their personal information will be processed.</span>
                </label>
            @else
                <div class="secure-note"><i class="bi bi-check-circle"></i><span>Guarantor consent was recorded on {{ $application->guarantor_consent_at->format('d M Y, H:i') }}. Confirm again if submitting an updated application.</span></div>
                <label class="consent-row">
                    <input type="checkbox" name="guarantor_consent" value="1" required @checked(old('guarantor_consent'))>
                    <span>I confirm that the guarantor has been informed about the guarantee obligations and personal information processing.</span>
                </label>
            @endif
            @error('guarantor_consent')<div class="form-error">{{ $message }}</div>@enderror
            <div class="secure-note"><i class="bi bi-shield-lock"></i><span>Identity images are stored privately, accessible only to authorised staff in this organisation, and retained for seven years after the application or loan closes.</span></div>
        </section>

        <section class="wizard-panel" data-step="3" hidden>
            <div class="wizard-panel-heading">
                <h2>Collateral assets <span id="collateralCount" class="form-hint"></span></h2>
                <p>Collateral is optional. Add each asset securing this loan; estimated value is not the amount the lender can recover.</p>
            </div>
            <div id="collateralCards">
                @foreach(old('collateral', $collateralData) as $index => $asset)
                    @include('applications.partials.collateral-card', ['index' => $index, 'asset' => $asset, 'application' => $application])
                @endforeach
            </div>
            <template id="collateralTemplate">
                @include('applications.partials.collateral-card', ['index' => '__INDEX__', 'asset' => [], 'application' => null])
            </template>
            <button type="button" class="btn btn-light" id="addCollateral"><i class="bi bi-plus-lg"></i> Add collateral item</button>
            <div class="collateral-total" style="margin-top:16px"><span>Combined estimated value</span><strong id="collateralTotal">KES 0</strong></div>
            <div class="secure-note"><i class="bi bi-info-circle"></i><span>Collateral ownership and identity verification remain pending until an authorised reviewer confirms them.</span></div>
        </section>

        <section class="wizard-panel" data-step="4" hidden>
            <div class="wizard-panel-heading">
                <h2>Review and submit</h2>
                <p>Check the security package and application details before sending it for review.</p>
            </div>
            <div class="review-checklist" id="reviewChecklist">
                <div class="review-check" data-check="loan"><i class="bi bi-check-circle review-check-icon"></i><div><strong>Borrower and loan details</strong><small>Borrower, product, amount and term selected</small></div></div>
                <div class="review-check pending" data-check="guarantor"><i class="bi bi-exclamation-circle review-check-icon"></i><div><strong>Guarantor details</strong><small>Complete all required personal and contact details</small></div></div>
                <div class="review-check pending" data-check="documents"><i class="bi bi-exclamation-circle review-check-icon"></i><div><strong>Guarantor photo and ID front</strong><small>Required images must be uploaded</small></div></div>
                <div class="review-check pending" data-check="collateral"><i class="bi bi-exclamation-circle review-check-icon"></i><div><strong>Collateral verification</strong><small>No collateral registered; verification is not applicable</small></div></div>
                <div class="review-check pending" data-check="consent"><i class="bi bi-exclamation-circle review-check-icon"></i><div><strong>Guarantor consent</strong><small>Confirm the guarantor has been informed</small></div></div>
            </div>
            <div class="secure-note"><i class="bi bi-shield-lock"></i><span>Documents are accessible only to authorised users in this organisation. Document completeness does not mean identity or collateral ownership has been verified.</span></div>
            <p id="submissionBlocked" class="form-error" role="status">Submission is blocked until required details, documents and consent are complete.</p>
        </section>

        <div class="form-card-footer wizard-actions">
            <div>
                <a href="{{ route('applications.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" name="submit_mode" value="draft" class="btn btn-light" formnovalidate>Save draft</button>
            </div>
            <div class="wizard-actions-group">
                <button type="button" class="btn btn-light" id="wizardBack" hidden>Back</button>
                <button type="button" class="btn btn-primary" id="wizardContinue">Save and continue</button>
                <button type="submit" name="submit_mode" value="submit" class="btn btn-primary" id="submitBtn" hidden>
                    <i class="bi bi-send"></i> Submit for approval
                </button>
            </div>
        </div>
    </form>
</div>

<script>
(function () {
    const eligibilityUrl = @json(rtrim(url('/applications/eligibility'), '/')); // base URL
    const csrfToken      = document.querySelector('meta[name="csrf-token"]').content;

    const borrowerSel = document.getElementById('borrower_id');
    const productSel  = document.getElementById('loan_product_id');
    const panel       = document.getElementById('eligibilityPanel');
    const amountInput = document.getElementById('requested_amount');
    const termInput   = document.getElementById('requested_term_days');
    const amountHint  = document.getElementById('amount-hint');
    const termHint    = document.getElementById('term-hint');
    const submitBtn   = document.getElementById('submitBtn');
    const borrowerDetailsPanel = document.getElementById('borrowerDetails');

    let debounceTimer = null;
    let checkController = null;

    function renderBorrowerDetails(details) {
        borrowerDetailsPanel.hidden = !borrowerSel.value || !details;
        if (!details) return;

        borrowerDetailsPanel.querySelectorAll('[data-borrower-detail]').forEach(field => {
            const key = field.dataset.borrowerDetail;
            field.textContent = details[key] || 'Not provided';
        });
    }

    function makeSearchable(select, input, list) {
        const options = Array.from(select.options).filter(option => option.value);
        let activeIndex = -1;

        function closeList() {
            list.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
            activeIndex = -1;
        }

        function choose(option) {
            input.value = option.textContent.trim();
            input.setCustomValidity('');
            select.value = option.value;
            closeList();
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        select.addEventListener('change', () => {
            if (!select.value) return;
            input.value = select.options[select.selectedIndex].textContent.trim();
            input.setCustomValidity('');
            closeList();
        });

        if (select.value) {
            input.value = select.options[select.selectedIndex].textContent.trim();
        }

        function renderOptions(query) {
            const normalizedQuery = query.trim().toLocaleLowerCase();
            const matches = options.filter(option =>
                option.textContent.trim().toLocaleLowerCase().includes(normalizedQuery)
            );
            list.replaceChildren();

            if (!matches.length) {
                const empty = document.createElement('li');
                empty.className = 'searchable-select-empty';
                empty.textContent = 'No matching options';
                empty.setAttribute('role', 'presentation');
                list.append(empty);
            } else {
                matches.forEach((option, index) => {
                    const item = document.createElement('li');
                    item.setAttribute('role', 'presentation');
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'searchable-select-option';
                    button.id = `${list.id}-${index}`;
                    button.dataset.value = option.value;
                    button.setAttribute('role', 'option');
                    button.setAttribute('aria-selected', 'false');
                    button.textContent = option.textContent.trim();
                    button.addEventListener('mousedown', event => event.preventDefault());
                    button.addEventListener('click', () => choose(option));
                    item.append(button);
                    list.append(item);
                });
            }

            list.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            activeIndex = -1;
        }

        function setActive(index) {
            const items = list.querySelectorAll('[role="option"]');
            if (!items.length) return;
            activeIndex = (index + items.length) % items.length;
            items.forEach((item, itemIndex) => {
                item.setAttribute('aria-selected', String(itemIndex === activeIndex));
            });
            input.setAttribute('aria-activedescendant', items[activeIndex].id);
            items[activeIndex].scrollIntoView({ block: 'nearest' });
        }

        input.addEventListener('focus', () => renderOptions(input.value));
        input.addEventListener('input', () => {
            input.setCustomValidity('Select an option from the list.');
            if (select.value) {
                select.value = '';
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
            renderOptions(input.value);
        });
        input.addEventListener('keydown', event => {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (list.hidden) renderOptions(input.value);
                setActive(activeIndex + 1);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                setActive(activeIndex - 1);
            } else if (event.key === 'Enter' && !list.hidden && activeIndex >= 0) {
                event.preventDefault();
                const selectedValue = list.querySelectorAll('[role="option"]')[activeIndex].dataset.value;
                const option = options.find(item => item.value === selectedValue);
                if (option) choose(option);
            } else if (event.key === 'Escape') {
                closeList();
            }
        });
        input.addEventListener('blur', () => {
            window.setTimeout(() => {
                if (input.value !== select.options[select.selectedIndex]?.textContent.trim()) {
                    input.value = select.value ? select.options[select.selectedIndex].textContent.trim() : '';
                    input.setCustomValidity('');
                }
                closeList();
            }, 100);
        });
        select.required = false;
        select.hidden = true;
        select.tabIndex = -1;
        select.setAttribute('aria-hidden', 'true');
        if (select.value) {
            input.value = select.options[select.selectedIndex].textContent.trim();
        }
    }

    makeSearchable(
        borrowerSel,
        document.getElementById('borrower_search'),
        document.getElementById('borrower_options')
    );
    makeSearchable(
        productSel,
        document.getElementById('loan_product_search'),
        document.getElementById('loan_product_options')
    );

    function scoreFill(val) {
        if (val === null || val === undefined) return 'N/A';
        return Math.round(val * 100);
    }

    function scoreClass(val) {
        if (val === null || val === undefined) return 'medium';
        if (val >= 0.65) return 'high';
        if (val >= 0.35) return 'medium';
        return 'low';
    }

    function kycDotClass(status) {
        return ['verified','manual'].includes(status) ? status : (status || 'missing');
    }

    function kycLabel(status) {
        const map = {
            verified: 'Verified',
            manual:   'Manually Cleared',
            pending:  'Pending Verification',
            failed:   'Verification Failed',
            missing:  'No KYC Record',
        };
        return map[status] || status;
    }

    function fmt(cents) {
        return 'KSh ' + Number(cents / 100).toLocaleString('en-KE', {minimumFractionDigits:2, maximumFractionDigits:2});
    }

    function renderLoading() {
        panel.className = 'eligibility-panel loading';
        panel.innerHTML = `
            <div class="ep-placeholder">
                <i class="bi bi-arrow-repeat" style="animation:spin 1s linear infinite;display:block;font-size:1.6rem;margin-bottom:6px"></i>
                Running eligibility check…
            </div>
            <style>@keyframes spin{to{transform:rotate(360deg)}}</style>`;
    }

    function renderBorrowerOnly(data) {
        // No product selected — show borrower health card
        const b  = data.borrower  || {};
        const k  = data.kyc       || {};
        const fp = data.financial_profile || {};
        const tierMaxCents = b.max_loan_amount_cents;

        const kycSt   = k.verification_status || 'missing';
        const kycOk   = ['verified','manual'].includes(kycSt);
        const fscore  = fp.financial_score  != null ? parseFloat(fp.financial_score)  : null;
        const bscore  = b.behaviour_score   != null ? parseFloat(b.behaviour_score)   : null;

        panel.className = 'eligibility-panel' + (kycOk ? '' : ' warning');

        panel.innerHTML = `
            <div class="ep-header">
                <span class="ep-badge info"><i class="bi bi-person-circle"></i> Borrower Profile</span>
                <span style="font-size:.8rem;color:#64748b">Select a product for the full eligibility check</span>
            </div>

            <div class="ep-scores">
                ${renderScoreChip('Financial Score', fscore)}
                ${renderScoreChip('Behaviour Score', bscore)}
                <div class="score-chip">
                    <span>Tier</span>
                    <strong>${b.tier_name || '—'}</strong>
                </div>
                <div class="score-chip">
                    <span>Maximum loan by tier</span>
                    <strong>${tierMaxCents !== null && tierMaxCents !== undefined ? fmt(Number(tierMaxCents)) : 'N/A'}</strong>
                </div>
                <div class="score-chip">
                    <span>Successful Loans</span>
                    <strong>${b.successful_loans_count ?? 0}</strong>
                </div>
            </div>

            <div class="ep-kyc">
                <span class="kyc-dot ${kycDotClass(kycSt)}"></span>
                <span><strong>KYC:</strong> ${kycLabel(kycSt)}
                ${k.document_type ? ' · ' + k.document_type.replace('_',' ') : ''}
                ${fp.model_version ? ' &nbsp;|&nbsp; Financial data: ' + (fp.model_version === 'declared-v1' ? 'Self-declared' : 'M-Pesa statement · ' + (fp.analysis_months||0) + ' months') : ' &nbsp;|&nbsp; No financial profile'}</span>
            </div>`;
    }

    function renderScoreChip(label, val) {
        const pct  = val !== null ? Math.round(val * 100) : null;
        const cls  = scoreClass(val);
        const fill = pct !== null ? pct : 0;
        return `
            <div class="score-chip">
                <span>${label}</span>
                <strong>${pct !== null ? pct + ' / 100' : 'N/A'}</strong>
                <div class="score-bar-wrap">
                    <div class="score-bar">
                        <div class="score-bar-fill ${cls}" style="width:${fill}%"></div>
                    </div>
                </div>
            </div>`;
    }

    function renderResult(data) {
        if (data.product_required) {
            renderBorrowerOnly(data);
            return;
        }

        const eligible   = data.eligible;
        const reasons    = data.reasons   || [];
        const warnings   = data.warnings  || [];
        const maxKes     = data.max_amount_kes  || 0;
        const maxTerm    = data.max_term_days   || 0;
        const blended    = data.blended_score;
        const fscore     = data.financial_score;
        const bscore     = data.behaviour_score;
        const kycSt      = data.kyc_status || 'unknown';

        const stateClass = eligible ? 'eligible' : (reasons.length ? 'ineligible' : 'warning');
        panel.className  = `eligibility-panel ${stateClass}`;

        // Update amount/term inputs with engine's max as upper bound hint
        if (eligible) {
            amountInput.max = maxKes;
            termInput.max   = maxTerm;
            amountHint.innerHTML = `
                Product range: KSh ${Number(productSel.options[productSel.selectedIndex]?.dataset.min||0).toLocaleString()} –
                KSh ${Number(productSel.options[productSel.selectedIndex]?.dataset.max||0).toLocaleString()}
                <span class="max-offer-note">
                    <i class="bi bi-stars"></i> Engine max offer: <strong>${fmt(data.max_amount_cents)}</strong>
                </span>`;
            termHint.textContent = `Max eligible term: ${maxTerm} days`;

            if (!amountInput.value) amountInput.value = maxKes;
            if (!termInput.value)   termInput.value   = maxTerm;
            amountInput.dispatchEvent(new Event('input', { bubbles: true }));
        }

        let reasonsHtml = '';
        if (reasons.length) {
            reasonsHtml = `<div class="ep-reasons">
                <ul>${reasons.map(r => `<li>${r}</li>`).join('')}</ul>
            </div>`;
        }

        let warningsHtml = '';
        if (warnings.length) {
            warningsHtml = `<div class="ep-warnings">
                <ul>${warnings.map(w => `<li>${w}</li>`).join('')}</ul>
            </div>`;
        }

        panel.innerHTML = `
            <div class="ep-header">
                ${eligible
                    ? `<span class="ep-badge eligible"><i class="bi bi-check-circle-fill"></i> Eligible</span>`
                    : `<span class="ep-badge ineligible"><i class="bi bi-x-circle-fill"></i> Not Eligible</span>`
                }
                <span style="font-size:.8rem;color:#64748b">Tier: <strong>${data.tier_name || '—'}</strong></span>
            </div>

            ${eligible ? `
            <div class="ep-offer">
                <div class="ep-offer-item">
                    <span>Max Offer</span>
                    <strong>${fmt(data.max_amount_cents)}</strong>
                </div>
                <div class="ep-offer-item">
                    <span>Max Term</span>
                    <strong>${maxTerm} days</strong>
                </div>
            </div>` : ''}

            <div class="ep-scores">
                ${renderScoreChip('Blended Score', blended)}
                ${renderScoreChip('Financial Score', fscore)}
                ${renderScoreChip('Behaviour Score', bscore)}
            </div>

            <div class="ep-kyc">
                <span class="kyc-dot ${kycDotClass(kycSt)}"></span>
                <span><strong>KYC:</strong> ${kycLabel(kycSt)}</span>
            </div>

            ${reasonsHtml}
            ${warningsHtml}`;
    }

    function runCheck() {
        const borrowerId = borrowerSel.value;
        const productId  = productSel.value;

        if (!borrowerId) {
            checkController?.abort();
            renderBorrowerDetails(null);
            panel.className = 'eligibility-panel';
            panel.innerHTML = `<div class="ep-placeholder"><i class="bi bi-person-check"></i>Select a borrower and product to see the eligibility assessment.</div>`;
            return;
        }

        checkController?.abort();
        checkController = new AbortController();
        renderLoading();

        const url = `${eligibilityUrl}/${borrowerId}` + (productId ? `?loan_product_id=${productId}` : '');

        fetch(url, {
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            signal: checkController.signal
        })
        .then(response => {
            if (!response.ok) throw new Error('Borrower details could not be loaded.');
            return response.json();
        })
        .then(data => {
            renderBorrowerDetails(data.borrower_details);
            renderResult(data);
        })
        .catch(error => {
            if (error.name === 'AbortError') return;
            panel.className = 'eligibility-panel warning';
            panel.innerHTML = `<div class="ep-placeholder" style="color:#d97706"><i class="bi bi-exclamation-triangle"></i>Could not load eligibility check. Proceed manually.</div>`;
        });
    }

    function debounced() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(runCheck, 200);
    }

    borrowerSel.addEventListener('change', function () {
        renderBorrowerDetails(null);
        debounced();
    });
    productSel.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        if (opt.value) {
            amountHint.textContent = `KSh ${Number(opt.dataset.min).toLocaleString()} – KSh ${Number(opt.dataset.max).toLocaleString()} · ${opt.dataset.minTerm}–${opt.dataset.maxTerm} days`;
            termHint.textContent   = `Product range: ${opt.dataset.minTerm}–${opt.dataset.maxTerm} days`;
        }
        debounced();
    });

    // Trigger on page load if old() values are pre-filled
    if (borrowerSel.value) debounced();
})();
</script>
<script>
(function () {
    const form = document.getElementById('appForm');
    const panels = Array.from(document.querySelectorAll('[data-step]'));
    const navItems = Array.from(document.querySelectorAll('[data-step-nav]'));
    const backButton = document.getElementById('wizardBack');
    const continueButton = document.getElementById('wizardContinue');
    const submitButton = document.getElementById('submitBtn');
    const progressFill = document.getElementById('wizardProgressFill');
    const progressTrack = document.querySelector('.wizard-progress-track');
    const progressLabel = document.getElementById('wizardProgressLabel');
    const borrower = document.getElementById('borrower_id');
    const product = document.getElementById('loan_product_id');
    const amount = document.getElementById('requested_amount');
    const nationalId = document.getElementById('guarantor_national_id');
    let currentStep = 1;
    let matchTimer;

    function setStep(step) {
        currentStep = step;
        progressFill.style.width = `${step * 25}%`;
        progressTrack.setAttribute('aria-valuenow', String(step));
        progressLabel.textContent = `Step ${step} of ${panels.length}`;
        panels.forEach(panel => { panel.hidden = Number(panel.dataset.step) !== step; });
        navItems.forEach(item => {
            const itemStep = Number(item.dataset.stepNav);
            item.classList.toggle('active', itemStep === step);
            item.classList.toggle('complete', itemStep < step);
            item.setAttribute('aria-current', itemStep === step ? 'step' : 'false');
        });
        backButton.hidden = step === 1;
        continueButton.hidden = step === 4;
        submitButton.hidden = step !== 4;
        if (step === 4) refreshChecklist();
        window.scrollTo({ top: form.getBoundingClientRect().top + window.scrollY - 24, behavior: 'smooth' });
    }

    function validateCurrentStep() {
        const panel = panels.find(item => Number(item.dataset.step) === currentStep);
        const invalid = Array.from(panel.querySelectorAll('input,select,textarea'))
            .find(field => !field.disabled && !field.checkValidity());
        if (invalid) {
            invalid.reportValidity();
            invalid.focus();
            return false;
        }
        if (currentStep === 2) {
            const missing = panel.querySelector('[data-security-required]:not([data-has-file="1"])');
            if (missing) {
                missing.setCustomValidity('Upload a clear image before continuing.');
                missing.reportValidity();
                missing.focus();
                return false;
            }
        }
        if (currentStep === 3) {
            const missingPhotos = Array.from(panel.querySelectorAll('[data-collateral-card]')).find(card => !hasCollateralPhoto(card));
            if (missingPhotos) {
                missingPhotos.querySelector('.collateral-photo-input').focus();
                missingPhotos.querySelector('.collateral-photo-input').setCustomValidity('Upload at least one photograph for this collateral item.');
                missingPhotos.querySelector('.collateral-photo-input').reportValidity();
                return false;
            }
        }
        return true;
    }

    navItems.forEach(item => item.addEventListener('click', () => setStep(Number(item.dataset.stepNav))));
    backButton.addEventListener('click', () => setStep(Math.max(1, currentStep - 1)));
    continueButton.addEventListener('click', () => {
        if (validateCurrentStep()) setStep(Math.min(4, currentStep + 1));
    });

    form.addEventListener('submit', event => {
        if (event.submitter && event.submitter.value === 'draft') return;

        refreshChecklist();
        const invalidPanel = panels.find(panel =>
            Array.from(panel.querySelectorAll('input,select,textarea'))
                .some(field => !field.disabled && !field.checkValidity())
        );
        const missingPhoto = document.querySelector('[data-security-required]:not([data-has-file="1"])');
        const missingCollateralPhoto = Array.from(document.querySelectorAll('[data-collateral-card]'))
            .some(card => !hasCollateralPhoto(card));

        if (invalidPanel || missingPhoto || missingCollateralPhoto) {
            event.preventDefault();
            const step = invalidPanel ? Number(invalidPanel.dataset.step) : (missingPhoto ? 2 : 3);
            setStep(step);
            const firstInvalid = Array.from(panels.find(panel => Number(panel.dataset.step) === step).querySelectorAll('input,select,textarea'))
                .find(field => !field.disabled && !field.checkValidity());
            if (firstInvalid) firstInvalid.reportValidity();
            else if (missingPhoto) {
                missingPhoto.setCustomValidity('Upload a clear image before submission.');
                missingPhoto.reportValidity();
            }
        }
    });

    function updateSummary() {
        const borrowerOption = borrower.options[borrower.selectedIndex];
        document.getElementById('summaryBorrower').textContent = borrower.value
            ? borrowerOption.textContent.split('·')[0].trim()
            : 'Select borrower';
        const kes = Number(amount.value || 0).toLocaleString('en-KE', { maximumFractionDigits: 2 });
        document.getElementById('summaryAmount').textContent = `KES ${kes}`;
    }
    borrower.addEventListener('change', updateSummary);
    amount.addEventListener('input', updateSummary);
    product.addEventListener('change', updateSummary);
    updateSummary();

    function hasCollateralPhoto(card) {
        const input = card.querySelector('.collateral-photo-input');
        const hasNew = Boolean(input && input.files && input.files.length);
        const hasSaved = Array.from(card.querySelectorAll('.saved-collateral-photos label input[type="checkbox"]'))
            .some(checkbox => !checkbox.checked);
        return hasNew || hasSaved;
    }

    function refreshChecklist() {
        const personalFields = ['guarantor_name', 'guarantor_national_id', 'guarantor_phone', 'guarantor_relationship', 'guarantor_address'];
        const personalComplete = personalFields.every(id => document.getElementById(id).value.trim() !== '');
        const docsComplete = ['guarantor_photo', 'guarantor_id_front'].every(id => {
            const input = document.getElementById(id);
            return input.files.length > 0 || (input.dataset.hasFile === '1' && document.getElementById(`remove_${id}`).value !== '1');
        });
        const consentComplete = Boolean(form.querySelector('[name="guarantor_consent"]:checked'));
        const cards = Array.from(document.querySelectorAll('[data-collateral-card]'));
        const collateralComplete = cards.every(hasCollateralPhoto);
        setReviewStatus('guarantor', personalComplete, 'Complete all required personal and contact details');
        setReviewStatus('documents', docsComplete, 'Guarantor photo and ID front must be uploaded');
        setReviewStatus('consent', consentComplete, 'Confirm the guarantor has been informed');
        const collateralCheck = document.querySelector('[data-check="collateral"]');
        collateralCheck.classList.toggle('pending', !collateralComplete || cards.length > 0);
        collateralCheck.querySelector('small').textContent = cards.length
            ? (collateralComplete ? 'Photos provided; ownership verification remains pending' : 'Each collateral item needs at least one photograph')
            : 'No collateral registered; verification is not applicable';
        const loanComplete = Boolean(borrower.value && product.value && amount.value && document.getElementById('requested_term_days').value);
        const blocked = !(loanComplete && personalComplete && docsComplete && consentComplete && collateralComplete);
        document.getElementById('submissionBlocked').hidden = !blocked;
        document.querySelector('[data-check="loan"]').classList.toggle('pending', !loanComplete);
    }

    function setReviewStatus(name, complete, message) {
        const row = document.querySelector(`[data-check="${name}"]`);
        row.classList.toggle('pending', !complete);
        row.querySelector('.review-check-icon').className = complete
            ? 'bi bi-check-circle review-check-icon'
            : 'bi bi-exclamation-circle review-check-icon';
        row.querySelector('small').textContent = message;
    }
    form.addEventListener('input', refreshChecklist);
    form.addEventListener('change', refreshChecklist);

    const fileInputs = ['guarantor_photo', 'guarantor_id_front', 'guarantor_id_back'];
    fileInputs.forEach(id => {
        const input = document.getElementById(id);
        const preview = document.getElementById(`preview_${id}`);
        const image = preview.querySelector('img');
        input.dataset.hasFile = preview.hidden ? '0' : '1';
        input.addEventListener('change', () => {
            input.setCustomValidity('');
            const file = input.files[0];
            if (!file) return;
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
                input.value = '';
                input.dataset.hasFile = '0';
                input.setCustomValidity('Choose a JPEG, PNG or WebP image up to 5 MB.');
                input.reportValidity();
                return;
            }
            image.src = URL.createObjectURL(file);
            preview.querySelector('span').textContent = file.name;
            preview.hidden = false;
            input.dataset.hasFile = '1';
            document.getElementById(`remove_${id}`).value = '0';
            refreshChecklist();
        });
    });

    document.querySelectorAll('[data-preview]').forEach(button => button.addEventListener('click', () => {
        const image = document.querySelector(`#preview_${button.dataset.preview} img`);
        if (image && image.src) window.open(image.src, '_blank', 'noopener');
    }));
    document.querySelectorAll('[data-remove]').forEach(button => button.addEventListener('click', () => {
        const id = button.dataset.remove;
        const input = document.getElementById(id);
        input.value = '';
        input.dataset.hasFile = '0';
        document.getElementById(`remove_${id}`).value = '1';
        document.getElementById(`preview_${id}`).hidden = true;
        refreshChecklist();
    }));

    function updateCollateral() {
        const cards = Array.from(document.querySelectorAll('[data-collateral-card]'));
        document.getElementById('collateralCount').textContent = `${cards.length} item${cards.length === 1 ? '' : 's'}`;
        const total = cards.reduce((sum, card) => sum + Number(card.querySelector('.collateral-value').value || 0), 0);
        document.getElementById('collateralTotal').textContent = `KES ${total.toLocaleString('en-KE', { maximumFractionDigits: 2 })}`;
        refreshChecklist();
    }
    function bindCollateralCard(card) {
        card.querySelector('[data-remove-collateral]').addEventListener('click', () => {
            card.remove();
            updateCollateral();
        });
        card.querySelector('.collateral-value').addEventListener('input', updateCollateral);
        const input = card.querySelector('.collateral-photo-input');
        input.addEventListener('change', () => {
            input.setCustomValidity('');
            const list = card.querySelector('[data-caption-list]');
            list.replaceChildren();
            card.querySelector('[data-photo-preview-list]')?.remove();
            const previousFiles = input._selectedFiles || [];
            const files = [...previousFiles, ...Array.from(input.files)];
            if (files.some(file => !['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024)) {
                const transfer = new DataTransfer();
                previousFiles.forEach(file => transfer.items.add(file));
                input.files = transfer.files;
                input.setCustomValidity('Choose JPEG, PNG or WebP images up to 5 MB each.');
                input.reportValidity();
                updateCollateral();
                return;
            }
            const transfer = new DataTransfer();
            files.forEach(file => transfer.items.add(file));
            input.files = transfer.files;
            input._selectedFiles = files;
            files.forEach((file, index) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'form-field';
                const label = document.createElement('label');
                label.textContent = `Caption for ${file.name}`;
                const caption = document.createElement('input');
                caption.className = 'form-input';
                caption.name = `${input.name.replace('[photos][]', '[captions][]')}`;
                caption.maxLength = 150;
                caption.placeholder = 'e.g. Front view';
                wrapper.append(label, caption);
                list.append(wrapper);
            });
            if (input.files.length) {
                const previews = document.createElement('div');
                previews.dataset.photoPreviewList = '';
                previews.className = 'capture-preview';
                files.forEach(file => {
                    const image = document.createElement('img');
                    image.alt = `Preview of ${file.name}`;
                    image.src = URL.createObjectURL(file);
                    image.addEventListener('load', () => {
                        if (image.naturalWidth < 400 || image.naturalHeight < 300) {
                            const warning = document.createElement('small');
                            warning.textContent = `${file.name} may be too small to read clearly.`;
                            warning.className = 'form-error';
                            previews.append(warning);
                        }
                    });
                    previews.append(image);
                });
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'btn btn-light btn-sm';
                remove.textContent = 'Remove selected photos';
                remove.addEventListener('click', () => {
                    input.value = '';
                    input._selectedFiles = [];
                    list.replaceChildren();
                    previews.remove();
                    updateCollateral();
                });
                previews.append(remove);
                card.querySelector('.saved-collateral-photos').after(previews);
            }
            input.setCustomValidity('');
            updateCollateral();
        });
    }

    document.querySelectorAll('[data-collateral-card]').forEach(bindCollateralCard);
    document.getElementById('addCollateral').addEventListener('click', () => {
        const indexes = Array.from(document.querySelectorAll('[data-collateral-card]'))
            .map(card => Number(card.dataset.index));
        const index = indexes.length ? Math.max(...indexes) + 1 : 0;
        const template = document.getElementById('collateralTemplate').innerHTML.replaceAll('__INDEX__', String(index));
        const holder = document.createElement('div');
        holder.innerHTML = template.trim();
        const card = holder.firstElementChild;
        document.getElementById('collateralCards').append(card);
        bindCollateralCard(card);
        updateCollateral();
        card.querySelector('input').focus();
    });
    document.querySelectorAll('.saved-collateral-photos input[type="checkbox"]').forEach(input => input.addEventListener('change', updateCollateral));
    updateCollateral();

    nationalId.addEventListener('input', () => {
        window.clearTimeout(matchTimer);
        document.getElementById('duplicateWarning').classList.remove('visible');
        if (nationalId.value.trim().length < 4) return;
        matchTimer = window.setTimeout(async () => {
            const payload = { national_id: nationalId.value.trim() };
            @if($application)
                payload.application_id = @json($application->id);
            @endif
            try {
                const response = await fetch(@json(route('applications.guarantor-match')), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(payload)
                });
                if (!response.ok) throw new Error('Could not check for a possible existing guarantor.');
                const result = await response.json();
                document.getElementById('duplicateWarning').classList.toggle('visible', result.possible_match);
            } catch (error) {
                const warning = document.getElementById('duplicateWarning');
                warning.textContent = 'A duplicate check could not be completed. Please check existing guarantor records before saving.';
                warning.classList.add('visible');
            }
        }, 350);
    });

    const firstInvalidStep = panels.find(panel => panel.querySelector('.form-error:not(#submissionBlocked)'));
    setStep(firstInvalidStep ? Number(firstInvalidStep.dataset.step) : 1);
})();
</script>
@endsection
