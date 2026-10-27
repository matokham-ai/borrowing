@extends('layouts.app')
@section('title', 'Edit · '.$borrowerRecord->name)

@push('styles')
<style>
/* ═══════════════════════════════════════════
   WIZARD LAYOUT
═══════════════════════════════════════════ */
.wz-shell {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 24px;
    max-width: 1060px;
    align-items: start;
}
.wz-sidebar { position: sticky; top: 88px; }
.wz-progress-card {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(16,24,40,.04);
}
.wz-prog-head {
    padding: 16px 18px 14px;
    border-bottom: 1px solid var(--line);
    background: linear-gradient(135deg, #0d1320 0%, #1a2540 100%);
}
.wz-prog-head-title {
    font-size: 11px; font-weight: 700; color: #fff;
    letter-spacing: .04em; text-transform: uppercase;
}
.wz-prog-bar-wrap {
    margin-top: 10px; height: 4px;
    background: rgba(255,255,255,.12); border-radius: 4px; overflow: hidden;
}
.wz-prog-bar {
    height: 100%;
    background: linear-gradient(90deg, #6366f1, #8b5cf6);
    border-radius: 4px; transition: width .4s cubic-bezier(.4,0,.2,1);
}
.wz-prog-label { margin-top: 8px; font-size: 10px; color: rgba(255,255,255,.5); }
.wz-prog-label span { color: #a5b4fc; font-weight: 700; }
.wz-steps { padding: 12px 10px; display: flex; flex-direction: column; gap: 2px; }
.wz-step {
    display: flex; align-items: center; gap: 11px; padding: 8px 9px;
    border-radius: 10px; cursor: pointer; transition: background .15s;
    position: relative; border: none; background: transparent;
    width: 100%; text-align: left;
}
.wz-step:hover { background: #f5f5ff; }
.wz-step.active { background: #eef2ff; }
.wz-step.done .wz-step-num  { background: #ecfdf3; color: #027a48; border-color: #a9efc5; }
.wz-step.active .wz-step-num { background: var(--primary); color: #fff; border-color: var(--primary); }
.wz-step.error .wz-step-num  { background: #fef3f2; color: #b42318; border-color: #fecdca; }
.wz-step-num {
    width: 28px; height: 28px; border-radius: 50%;
    border: 1.5px solid var(--line); display: grid; place-items: center;
    font-size: 11px; font-weight: 700; flex-shrink: 0;
    transition: background .2s, color .2s, border-color .2s; color: #98a2b3;
}
.wz-step-body { flex: 1; min-width: 0; }
.wz-step-label {
    display: block; font-size: 11.5px; font-weight: 650; color: #344054;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.wz-step.active .wz-step-label { color: var(--primary); }
.wz-step-sub {
    display: block; font-size: 9.5px; color: #98a2b3; margin-top: 2px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.wz-step-check { font-size: 12px; color: #12b76a; flex-shrink: 0; opacity: 0; transition: opacity .2s; }
.wz-step.done .wz-step-check { opacity: 1; }
.wz-tip {
    margin: 0 10px 12px; padding: 11px 13px;
    background: #fffbeb; border: 1px solid #fde68a;
    border-radius: 10px; font-size: 10px; color: #92400e; line-height: 1.5;
}
.wz-tip i { color: #d97706; margin-right: 5px; }

/* ── Borrower stats mini-card in sidebar ── */
.wz-stats-card {
    margin-top: 12px; background: #fff; border: 1px solid var(--line);
    border-radius: 16px; overflow: hidden;
    box-shadow: 0 1px 4px rgba(16,24,40,.04);
}
.wz-stats-head {
    padding: 13px 16px 11px;
    border-bottom: 1px solid var(--line);
    background: #f8fafc;
    display: flex; align-items: center; gap: 9px;
}
.wz-stats-head-icon {
    width: 26px; height: 26px; border-radius: 7px;
    background: #eef2ff; color: var(--primary);
    display: grid; place-items: center; font-size: 13px; flex-shrink: 0;
}
.wz-stats-head-title { font-size: 11px; font-weight: 700; color: #101828; }
.wz-stats-body { padding: 12px 14px; display: flex; flex-direction: column; gap: 8px; }
.wz-stat-row {
    display: flex; justify-content: space-between; align-items: center;
    font-size: 10.5px; padding: 5px 0;
    border-bottom: 1px dashed #f2f4f7;
}
.wz-stat-row:last-child { border-bottom: none; }
.wz-stat-label { color: #98a2b3; font-weight: 500; }
.wz-stat-value { font-weight: 700; color: #101828; }
.wz-stat-value.green  { color: #027a48; }
.wz-stat-value.amber  { color: #b54708; }
.wz-stat-value.red    { color: #b42318; }

/* Tier badge */
.tier-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 9px; border-radius: 20px;
    font-size: 10px; font-weight: 700;
    background: #eef2ff; color: var(--primary); border: 1px solid #c7d2fe;
}

/* ═══════════════════════════════════════════
   STEP PANELS
═══════════════════════════════════════════ */
.wz-body { display: flex; flex-direction: column; gap: 0; }
.wz-step-panel { display: none; flex-direction: column; gap: 16px; }
.wz-step-panel.active { display: flex; }

/* ═══════════════════════════════════════════
   CARD ENHANCEMENTS
═══════════════════════════════════════════ */
.form-card-icon {
    width: 38px; height: 38px; border-radius: 11px;
    display: grid; place-items: center; font-size: 17px; flex-shrink: 0;
}
.form-card-head-flex { display: flex; align-items: center; gap: 13px; }
.form-card-head-flex .form-card-icon   { background: #eef2ff; color: var(--primary); }
.form-card-head-flex.green .form-card-icon  { background: #ecfdf3; color: #027a48; }
.form-card-head-flex.amber .form-card-icon  { background: #fffaeb; color: #b54708; }
.form-card-head-flex.violet .form-card-icon { background: #f5f3ff; color: #6d28d9; }
.form-card-head-flex.rose .form-card-icon   { background: #fff1f2; color: #be123c; }
.form-card-head-flex.teal .form-card-icon   { background: #f0fdfa; color: #0d9488; }

/* ═══════════════════════════════════════════
   INPUT ENHANCEMENTS
═══════════════════════════════════════════ */
.input-prefix {
    display: flex; align-items: center; height: 38px;
    border: 1px solid #d0d5dd; border-radius: 8px;
    overflow: hidden; transition: border-color .15s, box-shadow .15s;
}
.input-prefix:focus-within { border-color: #7c7df0; box-shadow: 0 0 0 3px rgba(91,92,226,.1); }
.input-prefix.is-invalid { border-color: #f04438; }
.input-pre {
    padding: 0 10px; background: #f9fafb; border-right: 1px solid #d0d5dd;
    font-size: 12px; color: #667085; height: 100%; display: flex;
    align-items: center; white-space: nowrap; flex-shrink: 0;
}
.input-prefix .form-input { border: 0; border-radius: 0; flex: 1; box-shadow: none !important; }
.input-prefix .form-input:focus { box-shadow: none !important; }

.form-input-icon { position: relative; }
.form-input-icon i {
    position: absolute; left: 11px; top: 50%; transform: translateY(-50%);
    color: #98a2b3; font-size: 14px; pointer-events: none;
}
.form-input-icon .form-input { padding-left: 33px; }
.form-hint { font-size: 10px; color: #98a2b3; margin-top: -2px; }

/* ═══════════════════════════════════════════
   RADIO OPTION CARDS
═══════════════════════════════════════════ */
.opt-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 8px; }
.opt-card {
    display: flex; flex-direction: column; align-items: center; gap: 6px;
    padding: 13px 10px 11px; border: 1.5px solid var(--line);
    border-radius: 10px; cursor: pointer;
    transition: border-color .15s, background .15s;
    position: relative; text-align: center;
}
.opt-card input { position: absolute; opacity: 0; width: 0; height: 0; }
.opt-card:hover  { border-color: #b0b1f5; background: #fafafe; }
.opt-card.selected { border-color: var(--primary); background: #f5f5ff; }
.opt-card-icon {
    width: 34px; height: 34px; border-radius: 9px; display: grid;
    place-items: center; font-size: 16px; background: #f2f4f7; color: #667085;
    transition: background .15s, color .15s;
}
.opt-card.selected .opt-card-icon { background: #eef2ff; color: var(--primary); }
.opt-card-label { font-size: 11px; font-weight: 650; color: #344054; }
.opt-card.selected .opt-card-label { color: var(--primary); }
.opt-card-check {
    position: absolute; top: 7px; right: 7px; width: 16px; height: 16px;
    border-radius: 50%; border: 1.5px solid var(--line); display: grid;
    place-items: center; font-size: 9px; color: transparent;
    transition: background .15s, border-color .15s, color .15s;
}
.opt-card.selected .opt-card-check { background: var(--primary); border-color: var(--primary); color: #fff; }

/* status variants */
.status-opt-active.selected  { border-color: #027a48; background: #f0fdf7; }
.status-opt-active.selected .opt-card-icon  { background: #dcfce7; color: #027a48; }
.status-opt-active.selected .opt-card-label { color: #027a48; }
.status-opt-active.selected .opt-card-check { background: #027a48; border-color: #027a48; }
.status-opt-frozen.selected  { border-color: #b54708; background: #fffcf0; }
.status-opt-frozen.selected .opt-card-icon  { background: #fef3c7; color: #b54708; }
.status-opt-frozen.selected .opt-card-label { color: #b54708; }
.status-opt-frozen.selected .opt-card-check { background: #b54708; border-color: #b54708; }
.status-opt-blacklisted.selected { border-color: #b42318; background: #fff8f7; }
.status-opt-blacklisted.selected .opt-card-icon  { background: #fee2e2; color: #b42318; }
.status-opt-blacklisted.selected .opt-card-label { color: #b42318; }
.status-opt-blacklisted.selected .opt-card-check { background: #b42318; border-color: #b42318; }

/* ═══════════════════════════════════════════
   SECTION DIVIDER
═══════════════════════════════════════════ */
.form-section-divider {
    display: flex; align-items: center; gap: 10px; padding: 0 20px;
    color: #98a2b3; font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .07em;
}
.form-section-divider::before, .form-section-divider::after {
    content: ''; flex: 1; height: 1px; background: var(--line);
}

/* ═══════════════════════════════════════════
   WIZARD NAV BUTTONS
═══════════════════════════════════════════ */
.wz-nav {
    display: flex; justify-content: space-between;
    align-items: center; padding: 16px 0 0;
}
.wz-nav-right { display: flex; gap: 8px; align-items: center; }
.btn-wz-next {
    height: 38px; background: var(--primary); color: #fff;
    border: none; border-radius: 9px; padding: 0 18px;
    font-size: 12px; font-weight: 650; display: inline-flex;
    align-items: center; gap: 7px; cursor: pointer;
    transition: background .15s, box-shadow .15s, transform .1s;
}
.btn-wz-next:hover { background: #4748c4; box-shadow: 0 6px 18px rgba(91,92,226,.28); transform: translateY(-1px); }
.btn-wz-back {
    height: 38px; background: #fff; color: #344054;
    border: 1px solid var(--line); border-radius: 9px; padding: 0 16px;
    font-size: 12px; font-weight: 600; display: inline-flex;
    align-items: center; gap: 7px; cursor: pointer; transition: background .15s;
}
.btn-wz-back:hover { background: #f9fafb; }
.btn-wz-submit {
    height: 38px; background: #027a48; color: #fff;
    border: none; border-radius: 9px; padding: 0 22px;
    font-size: 12px; font-weight: 700; display: inline-flex;
    align-items: center; gap: 8px; cursor: pointer;
    transition: background .15s, box-shadow .15s, transform .1s;
}
.btn-wz-submit:hover { background: #035c38; box-shadow: 0 6px 18px rgba(2,122,72,.25); transform: translateY(-1px); }

/* ═══════════════════════════════════════════
   REVIEW PANEL (step 4)
═══════════════════════════════════════════ */
.review-section { padding: 18px 20px; border-bottom: 1px solid var(--line); }
.review-section:last-child { border-bottom: 0; }
.review-section-head {
    display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;
}
.review-section-title { font-size: 11px; font-weight: 700; color: #344054; text-transform: uppercase; letter-spacing: .05em; }
.review-edit-btn {
    font-size: 10px; color: var(--primary); background: none;
    border: none; cursor: pointer; font-weight: 600; padding: 0;
    display: flex; align-items: center; gap: 4px;
}
.review-edit-btn:hover { text-decoration: underline; }
.review-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px; }
.review-item dt {
    font-size: 9.5px; color: #98a2b3; text-transform: uppercase;
    letter-spacing: .06em; font-weight: 600; margin-bottom: 3px;
}
.review-item dd { margin: 0; font-size: 12px; font-weight: 600; color: #101828; }
.review-item dd.empty { color: #d0d5dd; font-weight: 400; font-style: italic; }

/* ═══════════════════════════════════════════
   CHANGE INDICATOR CHIP
═══════════════════════════════════════════ */
.changed-badge {
    display: inline-flex; align-items: center; gap: 4px;
    background: #fffaeb; border: 1px solid #fde68a;
    color: #92400e; border-radius: 20px; padding: 1px 8px;
    font-size: 9px; font-weight: 700; letter-spacing: .03em;
    vertical-align: middle; margin-left: 7px;
}

/* ═══════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════ */
@media (max-width: 900px) {
    .wz-shell { grid-template-columns: 1fr; }
    .wz-sidebar { position: static; }
    .wz-steps { flex-direction: row; flex-wrap: wrap; gap: 4px; }
    .wz-step { flex: 0 0 auto; width: auto; }
    .wz-step-sub { display: none; }
    .wz-stats-card { display: none; }
}
@media (max-width: 600px) {
    .opt-grid { grid-template-columns: repeat(2, 1fr); }
    .review-grid { grid-template-columns: 1fr 1fr; }
}
@keyframes spin { to { transform: rotate(360deg); } }
.error-ring { border-color: #f04438 !important; background: #fff8f7 !important; }
</style>
@endpush

@section('content')

@php
    $profile = $profileData ? json_decode($profileData->payload, true) : [];
    $nok     = $profile['next_of_kin'] ?? [];
    $bid     = 'BR-'.str_pad($borrowerRecord->id, 5, '0', STR_PAD_LEFT);

    /* Determine which step had a server-side validation error */
    $errStep = 1;
    if ($errors->hasAny(['employment_type','employer_name','job_title','monthly_income','monthly_expenses','existing_debt','other_income'])) $errStep = 2;
    elseif ($errors->hasAny(['residence_county','residence_address','housing_status','years_at_address'])) $errStep = 2;
    elseif ($errors->hasAny(['next_of_kin_name','next_of_kin_relationship','next_of_kin_phone','next_of_kin_id_number','next_of_kin_email','next_of_kin_address','next_of_kin_notes'])) $errStep = 3;
    elseif ($errors->hasAny(['current_tier_id','account_status'])) $errStep = 4;
@endphp

{{-- Breadcrumb --}}
<div class="bw-breadcrumb">
    <a href="{{ route('borrowers.index') }}"><i class="bi bi-people"></i> Borrowers</a>
    <i class="bi bi-chevron-right"></i>
    <a href="{{ route('borrowers.show', $borrowerRecord->id) }}">{{ $borrowerRecord->name }}</a>
    <i class="bi bi-chevron-right"></i>
    <span>Edit</span>
</div>

{{-- Page header --}}
<div class="page-header" style="margin-bottom:28px">
    <div>
        <div class="eyebrow">BORROWERS</div>
        <h1>Edit Borrower Profile</h1>
        <p>{{ $bid }} · {{ $borrowerRecord->name }}</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('borrowers.show', $borrowerRecord->id) }}" class="btn btn-light">
            <i class="bi bi-arrow-left"></i> Back to Profile
        </a>
    </div>
</div>

@if($errors->any())
    <div class="alert-banner danger" style="max-width:1060px;margin-bottom:20px">
        <i class="bi bi-exclamation-circle-fill"></i>
        <strong>Please correct the errors below.</strong>&nbsp;{{ $errors->first() }}
    </div>
@endif

<form method="POST" action="{{ route('borrowers.update', $borrowerRecord->id) }}"
      id="wzForm" novalidate>
@csrf
@method('PUT')

<div class="wz-shell">

    {{-- ══════════════════════════════════════
         LEFT · Progress sidebar
    ══════════════════════════════════════ --}}
    <div class="wz-sidebar">

        {{-- Progress card --}}
        <div class="wz-progress-card">
            <div class="wz-prog-head">
                <div class="wz-prog-head-title">Edit Progress</div>
                <div class="wz-prog-bar-wrap">
                    <div class="wz-prog-bar" id="wzProgressBar" style="width:25%"></div>
                </div>
                <div class="wz-prog-label"><span id="wzProgText">Step 1</span> of 4</div>
            </div>
            <div class="wz-steps" id="wzStepNav">
                <button type="button" class="wz-step active" data-step="1">
                    <div class="wz-step-num">1</div>
                    <div class="wz-step-body">
                        <span class="wz-step-label">Identity &amp; Contact</span>
                        <span class="wz-step-sub">Personal details</span>
                    </div>
                    <i class="bi bi-check-circle-fill wz-step-check"></i>
                </button>
                <button type="button" class="wz-step" data-step="2">
                    <div class="wz-step-num">2</div>
                    <div class="wz-step-body">
                        <span class="wz-step-label">Financial &amp; Residence</span>
                        <span class="wz-step-sub">Income, employment &amp; address</span>
                    </div>
                    <i class="bi bi-check-circle-fill wz-step-check"></i>
                </button>
                <button type="button" class="wz-step" data-step="3">
                    <div class="wz-step-num">3</div>
                    <div class="wz-step-body">
                        <span class="wz-step-label">Next of Kin</span>
                        <span class="wz-step-sub">Emergency contact</span>
                    </div>
                    <i class="bi bi-check-circle-fill wz-step-check"></i>
                </button>
                <button type="button" class="wz-step" data-step="4">
                    <div class="wz-step-num">4</div>
                    <div class="wz-step-body">
                        <span class="wz-step-label">Lending &amp; Review</span>
                        <span class="wz-step-sub">Tier, status &amp; confirm</span>
                    </div>
                    <i class="bi bi-check-circle-fill wz-step-check"></i>
                </button>
            </div>
            <div class="wz-tip">
                <i class="bi bi-pencil-square"></i>
                You are editing an existing borrower. Only changed fields will affect the credit profile — KYC data must be updated from the borrower profile page.
            </div>
        </div>

        {{-- Borrower stats mini-card --}}
        <div class="wz-stats-card">
            <div class="wz-stats-head">
                <div class="wz-stats-head-icon"><i class="bi bi-bar-chart-line"></i></div>
                <div>
                    <div class="wz-stats-head-title">Current Standing</div>
                </div>
            </div>
            <div class="wz-stats-body">
                <div class="wz-stat-row">
                    <span class="wz-stat-label">Tier</span>
                    <span class="wz-stat-value">
                        <span class="tier-badge">T{{ $borrowerRecord->tier_number ?? '—' }} · {{ $borrowerRecord->tier_name ?? 'Unassigned' }}</span>
                    </span>
                </div>
                <div class="wz-stat-row">
                    <span class="wz-stat-label">Score</span>
                    <span class="wz-stat-value" style="color:var(--primary)">{{ number_format((float)$borrowerRecord->behaviour_score, 4) }}</span>
                </div>
                <div class="wz-stat-row">
                    <span class="wz-stat-label">Max Loan</span>
                    <span class="wz-stat-value green">KSh {{ number_format(($borrowerRecord->max_loan_amount_cents ?? 0) / 100, 0) }}</span>
                </div>
                <div class="wz-stat-row">
                    <span class="wz-stat-label">Lifetime Loans</span>
                    <span class="wz-stat-value">{{ number_format((int)$borrowerRecord->lifetime_loans_count) }}</span>
                </div>
                <div class="wz-stat-row">
                    <span class="wz-stat-label">On-time</span>
                    <span class="wz-stat-value green">{{ number_format((int)$borrowerRecord->on_time_loans_count) }}</span>
                </div>
                <div class="wz-stat-row">
                    <span class="wz-stat-label">Late</span>
                    <span class="wz-stat-value amber">{{ number_format((int)$borrowerRecord->late_loans_count) }}</span>
                </div>
                <div class="wz-stat-row">
                    <span class="wz-stat-label">Defaulted</span>
                    <span class="wz-stat-value red">{{ number_format((int)$borrowerRecord->defaulted_loans_count) }}</span>
                </div>
                <div class="wz-stat-row">
                    <span class="wz-stat-label">Streak</span>
                    <span class="wz-stat-value">{{ number_format((int)$borrowerRecord->consecutive_on_time_loans) }} on-time</span>
                </div>
            </div>
        </div>

    </div>{{-- /.wz-sidebar --}}

    {{-- ══════════════════════════════════════
         RIGHT · Step panels
    ══════════════════════════════════════ --}}
    <div class="wz-body">

        {{-- ════════════════════════════════
             STEP 1 · Identity & Contact
        ════════════════════════════════ --}}
        <div class="wz-step-panel" data-panel="1">

            <div class="form-card">
                <div class="form-card-head">
                    <div class="form-card-head-flex">
                        <div class="form-card-icon"><i class="bi bi-person-vcard"></i></div>
                        <div>
                            <h2>Identity &amp; Contact</h2>
                            <p>Legal name, phone, email and demographics.</p>
                        </div>
                    </div>
                    <span style="font-size:10px;color:#98a2b3;font-weight:600">{{ $bid }}</span>
                </div>
                <div class="form-grid">

                    {{-- Full name --}}
                    <div class="form-group form-group-full">
                        <label for="name">Full Legal Name <span style="color:#f04438">*</span></label>
                        <div class="form-input-icon">
                            <i class="bi bi-person"></i>
                            <input id="name" name="name"
                                   value="{{ old('name', $borrowerRecord->name) }}"
                                   class="form-input @error('name') is-invalid @enderror"
                                   placeholder="As it appears on the national ID" required>
                        </div>
                        @error('name')<span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                    </div>

                    {{-- Phone --}}
                    <div class="form-group">
                        <label for="phone_number">Primary Phone <span style="color:#f04438">*</span></label>
                        <div class="form-input-icon">
                            <i class="bi bi-phone"></i>
                            <input id="phone_number" name="phone_number"
                                   value="{{ old('phone_number', $borrowerRecord->phone_number) }}"
                                   class="form-input @error('phone_number') is-invalid @enderror"
                                   placeholder="e.g. 0712 345 678" required>
                        </div>
                        @error('phone_number')<span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                    </div>

                    {{-- Email --}}
                    <div class="form-group">
                        <label for="email">Email Address <span class="label-hint">optional</span></label>
                        <div class="form-input-icon">
                            <i class="bi bi-envelope"></i>
                            <input id="email" type="email" name="email"
                                   value="{{ old('email', $borrowerRecord->email) }}"
                                   class="form-input @error('email') is-invalid @enderror"
                                   placeholder="borrower@example.com">
                        </div>
                        @error('email')<span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                    </div>

                    <div class="form-section-divider form-group-full">Demographics</div>

                    {{-- Gender --}}
                    <div class="form-group">
                        <label>Gender <span class="label-hint">optional</span></label>
                        <div class="opt-grid" style="grid-template-columns:repeat(3,1fr)">
                            @foreach(['male' => ['Male','bi-gender-male'], 'female' => ['Female','bi-gender-female'], 'other' => ['Other','bi-gender-ambiguous']] as $v => [$l, $ic])
                            <label class="opt-card @if(old('gender', $profile['gender'] ?? '') === $v) selected @endif">
                                <input type="radio" name="gender" value="{{ $v }}"
                                       @checked(old('gender', $profile['gender'] ?? '') === $v)>
                                <div class="opt-card-icon"><i class="bi {{ $ic }}"></i></div>
                                <span class="opt-card-label">{{ $l }}</span>
                                <div class="opt-card-check"><i class="bi bi-check"></i></div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Nationality --}}
                    <div class="form-group">
                        <label for="nationality">Nationality <span class="label-hint">optional</span></label>
                        <select id="nationality" name="nationality" class="form-input">
                            <option value="">Select…</option>
                            @foreach(['KE' => 'Kenyan', 'UG' => 'Ugandan', 'TZ' => 'Tanzanian', 'RW' => 'Rwandan', 'ET' => 'Ethiopian', 'SO' => 'Somali', 'OTHER' => 'Other'] as $v => $l)
                            <option value="{{ $v }}" @selected(old('nationality', $profile['nationality'] ?? '') === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Preferred language --}}
                    <div class="form-group">
                        <label for="preferred_language">Preferred Language <span class="label-hint">optional</span></label>
                        <select id="preferred_language" name="preferred_language" class="form-input">
                            <option value="">Select…</option>
                            <option value="en" @selected(old('preferred_language', $profile['preferred_language'] ?? 'en') === 'en')>English</option>
                            <option value="sw" @selected(old('preferred_language', $profile['preferred_language'] ?? '') === 'sw')>Kiswahili</option>
                        </select>
                    </div>

                </div>
            </div>

            <div class="wz-nav">
                <a href="{{ route('borrowers.show', $borrowerRecord->id) }}" class="btn-wz-back">
                    <i class="bi bi-arrow-left"></i> Cancel
                </a>
                <div class="wz-nav-right">
                    <button type="button" class="btn-wz-next" onclick="wzNext(1)">
                        Continue <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════
             STEP 2 · Financial & Residence
        ════════════════════════════════ --}}
        <div class="wz-step-panel" data-panel="2">

            {{-- Employment & Income --}}
            <div class="form-card">
                <div class="form-card-head">
                    <div class="form-card-head-flex amber">
                        <div class="form-card-icon"><i class="bi bi-briefcase"></i></div>
                        <div>
                            <h2>Employment &amp; Income</h2>
                            <p>Job details and declared monthly financials.</p>
                        </div>
                    </div>
                </div>
                <div class="form-grid">

                    {{-- Employment type --}}
                    <div class="form-group form-group-full">
                        <label>Employment Type <span class="label-hint">optional</span></label>
                        <div class="opt-grid">
                            @foreach([
                                'salaried'      => ['Salaried',      'bi-building'],
                                'self_employed' => ['Self-Employed', 'bi-shop'],
                                'business'      => ['Business Owner','bi-graph-up-arrow'],
                                'casual'        => ['Casual/Gig',    'bi-clock'],
                                'unemployed'    => ['Unemployed',    'bi-person-x'],
                                'student'       => ['Student',       'bi-mortarboard'],
                            ] as $v => [$l, $ic])
                            <label class="opt-card @if(old('employment_type', $profile['employment_type'] ?? '') === $v) selected @endif">
                                <input type="radio" name="employment_type" value="{{ $v }}"
                                       @checked(old('employment_type', $profile['employment_type'] ?? '') === $v)>
                                <div class="opt-card-icon"><i class="bi {{ $ic }}"></i></div>
                                <span class="opt-card-label">{{ $l }}</span>
                                <div class="opt-card-check"><i class="bi bi-check"></i></div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Employer --}}
                    <div class="form-group">
                        <label for="employer_name">Employer / Business Name <span class="label-hint">optional</span></label>
                        <div class="form-input-icon">
                            <i class="bi bi-building"></i>
                            <input id="employer_name" name="employer_name"
                                   value="{{ old('employer_name', $profile['employer_name'] ?? '') }}"
                                   class="form-input" placeholder="e.g. Safaricom PLC">
                        </div>
                    </div>

                    {{-- Job title --}}
                    <div class="form-group">
                        <label for="job_title">Job Title / Role <span class="label-hint">optional</span></label>
                        <div class="form-input-icon">
                            <i class="bi bi-person-badge"></i>
                            <input id="job_title" name="job_title"
                                   value="{{ old('job_title', $profile['job_title'] ?? '') }}"
                                   class="form-input" placeholder="e.g. Software Engineer">
                        </div>
                    </div>

                    <div class="form-section-divider form-group-full">Monthly Financials (KES)</div>

                    {{-- Monthly income --}}
                    <div class="form-group">
                        <label for="monthly_income">Monthly Income <span class="label-hint">optional</span></label>
                        <div class="input-prefix @error('monthly_income') is-invalid @enderror">
                            <span class="input-pre">KES</span>
                            <input id="monthly_income" type="number" name="monthly_income" min="0" step="1"
                                   value="{{ old('monthly_income', ($profile['monthly_income_cents'] ?? 0) / 100) }}"
                                   class="form-input" placeholder="0">
                        </div>
                        <span class="form-hint">Net take-home income per month</span>
                        @error('monthly_income')<span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                    </div>

                    {{-- Monthly expenses --}}
                    <div class="form-group">
                        <label for="monthly_expenses">Monthly Expenses <span class="label-hint">optional</span></label>
                        <div class="input-prefix @error('monthly_expenses') is-invalid @enderror">
                            <span class="input-pre">KES</span>
                            <input id="monthly_expenses" type="number" name="monthly_expenses" min="0" step="1"
                                   value="{{ old('monthly_expenses', ($profile['monthly_expenses_cents'] ?? 0) / 100) }}"
                                   class="form-input" placeholder="0">
                        </div>
                        <span class="form-hint">Recurring monthly obligations</span>
                        @error('monthly_expenses')<span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                    </div>

                    {{-- Existing debt --}}
                    <div class="form-group">
                        <label for="existing_debt">Monthly Debt Service <span class="label-hint">optional</span></label>
                        <div class="input-prefix">
                            <span class="input-pre">KES</span>
                            <input id="existing_debt" type="number" name="existing_debt" min="0" step="1"
                                   value="{{ old('existing_debt', ($profile['existing_debt_cents'] ?? 0) / 100) }}"
                                   class="form-input" placeholder="0">
                        </div>
                        <span class="form-hint">Loans, hire-purchase, credit cards, etc.</span>
                    </div>

                    {{-- Other income --}}
                    <div class="form-group">
                        <label for="other_income">Other Monthly Income <span class="label-hint">optional</span></label>
                        <div class="input-prefix">
                            <span class="input-pre">KES</span>
                            <input id="other_income" type="number" name="other_income" min="0" step="1"
                                   value="{{ old('other_income', ($profile['other_income_cents'] ?? 0) / 100) }}"
                                   class="form-input" placeholder="0">
                        </div>
                        <span class="form-hint">Rental, dividends, remittances, etc.</span>
                    </div>

                </div>
            </div>

            {{-- Residence --}}
            <div class="form-card">
                <div class="form-card-head">
                    <div class="form-card-head-flex teal">
                        <div class="form-card-icon"><i class="bi bi-house-door"></i></div>
                        <div>
                            <h2>Residence Information</h2>
                            <p>Current address and housing details.</p>
                        </div>
                    </div>
                </div>
                <div class="form-grid">

                    {{-- County --}}
                    <div class="form-group">
                        <label for="residence_county">County / Region <span style="color:#f04438">*</span></label>
                        <select id="residence_county" name="residence_county"
                                class="form-input @error('residence_county') is-invalid @enderror" required>
                            <option value="">Select county…</option>
                            @foreach(['Nairobi','Mombasa','Kisumu','Nakuru','Eldoret','Thika','Nyeri','Meru',
                                      'Kisii','Kakamega','Machakos','Kilifi','Garissa','Isiolo','Kajiado',
                                      'Kiambu','Kirinyaga','Kitui','Laikipia','Lamu','Makueni','Mandera',
                                      'Marsabit','Migori','Murang\'a','Nandi','Narok','Nyamira',
                                      'Nyandarua','Samburu','Siaya','Taita-Taveta','Tana River','Trans Nzoia',
                                      'Turkana','Uasin Gishu','Vihiga','Wajir','West Pokot'] as $c)
                            <option value="{{ $c }}" @selected(old('residence_county', $profile['residence_county'] ?? '') === $c)>{{ $c }}</option>
                            @endforeach
                        </select>
                        @error('residence_county')<span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                    </div>

                    {{-- Address --}}
                    <div class="form-group">
                        <label for="residence_address">Physical Address <span style="color:#f04438">*</span></label>
                        <div class="form-input-icon">
                            <i class="bi bi-geo-alt"></i>
                            <input id="residence_address" name="residence_address"
                                   value="{{ old('residence_address', $profile['residence_address'] ?? '') }}"
                                   class="form-input @error('residence_address') is-invalid @enderror"
                                   placeholder="e.g. Westlands, Nairobi" required>
                        </div>
                        @error('residence_address')<span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                    </div>

                    {{-- Housing status --}}
                    <div class="form-group">
                        <label for="housing_status">Housing Status <span class="label-hint">optional</span></label>
                        <select id="housing_status" name="housing_status" class="form-input">
                            <option value="">Select…</option>
                            <option value="own"     @selected(old('housing_status', $profile['housing_status'] ?? '') === 'own')>Owns Home</option>
                            <option value="renting" @selected(old('housing_status', $profile['housing_status'] ?? '') === 'renting')>Renting</option>
                            <option value="family"  @selected(old('housing_status', $profile['housing_status'] ?? '') === 'family')>Family Home</option>
                            <option value="other"   @selected(old('housing_status', $profile['housing_status'] ?? '') === 'other')>Other</option>
                        </select>
                    </div>

                    {{-- Years at address --}}
                    <div class="form-group">
                        <label for="years_at_address">Years at Current Address <span class="label-hint">optional</span></label>
                        <input id="years_at_address" type="number" name="years_at_address" min="0" max="60" step="1"
                               value="{{ old('years_at_address', $profile['years_at_address'] ?? '') }}"
                               class="form-input" placeholder="e.g. 3">
                    </div>

                </div>
            </div>

            <div class="wz-nav">
                <button type="button" class="btn-wz-back" onclick="wzBack(2)">
                    <i class="bi bi-arrow-left"></i> Back
                </button>
                <div class="wz-nav-right">
                    <button type="button" class="btn-wz-next" onclick="wzNext(2)">
                        Continue <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════
             STEP 3 · Next of Kin
        ════════════════════════════════ --}}
        <div class="wz-step-panel" data-panel="3">

            <div class="form-card">
                <div class="form-card-head">
                    <div class="form-card-head-flex violet">
                        <div class="form-card-icon"><i class="bi bi-people"></i></div>
                        <div>
                            <h2>Next of Kin</h2>
                            <p>Emergency contact and guarantor information.</p>
                        </div>
                    </div>
                </div>
                <div class="form-grid">

                    <div class="form-group form-group-full">
                        <label for="next_of_kin_name">Full Name <span style="color:#f04438">*</span></label>
                        <div class="form-input-icon">
                            <i class="bi bi-person"></i>
                            <input id="next_of_kin_name" name="next_of_kin_name"
                                   value="{{ old('next_of_kin_name', $nok['name'] ?? '') }}"
                                   class="form-input @error('next_of_kin_name') is-invalid @enderror"
                                   placeholder="Next of kin full name" required>
                        </div>
                        @error('next_of_kin_name')<span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label for="next_of_kin_relationship">Relationship <span style="color:#f04438">*</span></label>
                        <select id="next_of_kin_relationship" name="next_of_kin_relationship"
                                class="form-input @error('next_of_kin_relationship') is-invalid @enderror" required>
                            <option value="">Select relationship…</option>
                            @foreach(['Spouse','Parent','Sibling','Child','Friend','Colleague','Other'] as $r)
                            <option value="{{ strtolower($r) }}"
                                    @selected(old('next_of_kin_relationship', $nok['relationship'] ?? '') === strtolower($r))>
                                {{ $r }}
                            </option>
                            @endforeach
                        </select>
                        @error('next_of_kin_relationship')<span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label for="next_of_kin_phone">Phone Number <span style="color:#f04438">*</span></label>
                        <div class="form-input-icon">
                            <i class="bi bi-phone"></i>
                            <input id="next_of_kin_phone" name="next_of_kin_phone"
                                   value="{{ old('next_of_kin_phone', $nok['phone'] ?? '') }}"
                                   class="form-input @error('next_of_kin_phone') is-invalid @enderror"
                                   placeholder="e.g. 0712 345 678" required>
                        </div>
                        @error('next_of_kin_phone')<span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label for="next_of_kin_id_number">ID Number <span class="label-hint">optional</span></label>
                        <div class="form-input-icon">
                            <i class="bi bi-credit-card-2-front"></i>
                            <input id="next_of_kin_id_number" name="next_of_kin_id_number"
                                   value="{{ old('next_of_kin_id_number', $nok['id_number'] ?? '') }}"
                                   class="form-input" placeholder="National ID number">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="next_of_kin_email">Email <span class="label-hint">optional</span></label>
                        <div class="form-input-icon">
                            <i class="bi bi-envelope"></i>
                            <input id="next_of_kin_email" type="email" name="next_of_kin_email"
                                   value="{{ old('next_of_kin_email', $nok['email'] ?? '') }}"
                                   class="form-input" placeholder="nok@example.com">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="next_of_kin_address">Physical Address <span class="label-hint">optional</span></label>
                        <div class="form-input-icon">
                            <i class="bi bi-geo-alt"></i>
                            <input id="next_of_kin_address" name="next_of_kin_address"
                                   value="{{ old('next_of_kin_address', $nok['address'] ?? '') }}"
                                   class="form-input" placeholder="e.g. Kiambu Road, Nairobi">
                        </div>
                    </div>

                    <div class="form-group form-group-full">
                        <label for="next_of_kin_notes">Additional Notes <span class="label-hint">optional</span></label>
                        <textarea id="next_of_kin_notes" name="next_of_kin_notes" rows="2"
                                  class="form-input"
                                  placeholder="Any additional context about this contact…">{{ old('next_of_kin_notes', $nok['notes'] ?? '') }}</textarea>
                    </div>

                </div>
            </div>

            <div class="wz-nav">
                <button type="button" class="btn-wz-back" onclick="wzBack(3)">
                    <i class="bi bi-arrow-left"></i> Back
                </button>
                <div class="wz-nav-right">
                    <button type="button" class="btn-wz-next" onclick="wzNext(3)">
                        Continue <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════
             STEP 4 · Lending & Review
        ════════════════════════════════ --}}
        <div class="wz-step-panel" data-panel="4">

            {{-- Lending profile --}}
            <div class="form-card">
                <div class="form-card-head">
                    <div class="form-card-head-flex rose">
                        <div class="form-card-icon"><i class="bi bi-bank"></i></div>
                        <div>
                            <h2>Lending Profile</h2>
                            <p>Tier assignment and account standing.</p>
                        </div>
                    </div>
                </div>
                <div class="form-grid">

                    {{-- Tier --}}
                    <div class="form-group form-group-full">
                        <label>Borrower Tier <span style="color:#f04438">*</span></label>
                        <div class="opt-grid" id="tierOptGrid">
                            @forelse($tiers as $t)
                            <label class="opt-card @if((string)old('current_tier_id', $borrowerRecord->current_tier_id) === (string)$t->id) selected @endif">
                                <input type="radio" name="current_tier_id" value="{{ $t->id }}"
                                       @checked((string)old('current_tier_id', $borrowerRecord->current_tier_id) === (string)$t->id)>
                                <div class="opt-card-icon" style="font-size:13px;font-weight:700">T{{ $t->tier_number }}</div>
                                <span class="opt-card-label" style="font-size:10.5px">{{ $t->name }}</span>
                                <div class="opt-card-check"><i class="bi bi-check"></i></div>
                            </label>
                            @empty
                            <p style="font-size:12px;color:#98a2b3;padding:8px 0">No active tiers found.</p>
                            @endforelse
                        </div>
                        @error('current_tier_id')<span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                    </div>

                    {{-- Account status --}}
                    <div class="form-group form-group-full">
                        <label>Account Status <span style="color:#f04438">*</span></label>
                        <div class="opt-grid" style="grid-template-columns:repeat(3,1fr)">
                            <label class="opt-card status-opt-active @if(old('account_status', $borrowerRecord->account_status) === 'active') selected @endif">
                                <input type="radio" name="account_status" value="active"
                                       @checked(old('account_status', $borrowerRecord->account_status) === 'active')>
                                <div class="opt-card-icon"><i class="bi bi-person-check"></i></div>
                                <span class="opt-card-label">Active</span>
                                <div class="opt-card-check"><i class="bi bi-check"></i></div>
                            </label>
                            <label class="opt-card status-opt-frozen @if(old('account_status', $borrowerRecord->account_status) === 'frozen') selected @endif">
                                <input type="radio" name="account_status" value="frozen"
                                       @checked(old('account_status', $borrowerRecord->account_status) === 'frozen')>
                                <div class="opt-card-icon"><i class="bi bi-person-lock"></i></div>
                                <span class="opt-card-label">Frozen</span>
                                <div class="opt-card-check"><i class="bi bi-check"></i></div>
                            </label>
                            <label class="opt-card status-opt-blacklisted @if(old('account_status', $borrowerRecord->account_status) === 'blacklisted') selected @endif">
                                <input type="radio" name="account_status" value="blacklisted"
                                       @checked(old('account_status', $borrowerRecord->account_status) === 'blacklisted')>
                                <div class="opt-card-icon"><i class="bi bi-person-x"></i></div>
                                <span class="opt-card-label">Blacklisted</span>
                                <div class="opt-card-check"><i class="bi bi-check"></i></div>
                            </label>
                        </div>
                        @error('account_status')<span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                    </div>
                    @if(auth()->user()->hasRole(['admin', 'super-admin']))
                        <div class="form-group form-group-full">
                            <label for="branch_id">Assigned branch</label>
                            <select id="branch_id" name="branch_id" class="form-input">
                                <option value="">Unassigned — administrative records only</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) old('branch_id', $borrowerRecord->branch_id) === (string) $branch->id)>
                                        {{ $branch->name }} · {{ $branch->code }}
                                    </option>
                                @endforeach
                            </select>
                            @error('branch_id')<span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                        </div>
                    @endif

                </div>
            </div>

            {{-- Review summary --}}
            <div class="form-card">
                <div class="form-card-head">
                    <div>
                        <h2>Review &amp; Confirm</h2>
                        <p>Double-check all details before saving changes.</p>
                    </div>
                    <span id="changeCountBadge" style="display:none" class="changed-badge">
                        <i class="bi bi-pencil"></i> <span id="changeCountText">0</span> changes
                    </span>
                </div>

                <div class="review-section">
                    <div class="review-section-head">
                        <span class="review-section-title"><i class="bi bi-person" style="margin-right:6px"></i>Identity &amp; Contact</span>
                        <button type="button" class="review-edit-btn" onclick="wzGoto(1)"><i class="bi bi-pencil"></i> Edit</button>
                    </div>
                    <dl class="review-grid">
                        <div class="review-item"><dt>Full Name</dt><dd id="rv-name" class="empty">—</dd></div>
                        <div class="review-item"><dt>Phone</dt><dd id="rv-phone" class="empty">—</dd></div>
                        <div class="review-item"><dt>Email</dt><dd id="rv-email" class="empty">—</dd></div>
                        <div class="review-item"><dt>Gender</dt><dd id="rv-gender" class="empty">—</dd></div>
                        <div class="review-item"><dt>Nationality</dt><dd id="rv-nationality" class="empty">—</dd></div>
                        <div class="review-item"><dt>Language</dt><dd id="rv-language" class="empty">—</dd></div>
                    </dl>
                </div>

                <div class="review-section">
                    <div class="review-section-head">
                        <span class="review-section-title"><i class="bi bi-briefcase" style="margin-right:6px"></i>Financial &amp; Residence</span>
                        <button type="button" class="review-edit-btn" onclick="wzGoto(2)"><i class="bi bi-pencil"></i> Edit</button>
                    </div>
                    <dl class="review-grid">
                        <div class="review-item"><dt>Employment</dt><dd id="rv-employment" class="empty">—</dd></div>
                        <div class="review-item"><dt>Employer</dt><dd id="rv-employer" class="empty">—</dd></div>
                        <div class="review-item"><dt>Monthly Income</dt><dd id="rv-income" class="empty">—</dd></div>
                        <div class="review-item"><dt>Monthly Expenses</dt><dd id="rv-expenses" class="empty">—</dd></div>
                        <div class="review-item"><dt>County</dt><dd id="rv-county" class="empty">—</dd></div>
                        <div class="review-item"><dt>Address</dt><dd id="rv-address" class="empty">—</dd></div>
                    </dl>
                </div>

                <div class="review-section">
                    <div class="review-section-head">
                        <span class="review-section-title"><i class="bi bi-people" style="margin-right:6px"></i>Next of Kin</span>
                        <button type="button" class="review-edit-btn" onclick="wzGoto(3)"><i class="bi bi-pencil"></i> Edit</button>
                    </div>
                    <dl class="review-grid">
                        <div class="review-item"><dt>Name</dt><dd id="rv-nokname" class="empty">—</dd></div>
                        <div class="review-item"><dt>Relationship</dt><dd id="rv-nokrel" class="empty">—</dd></div>
                        <div class="review-item"><dt>Phone</dt><dd id="rv-nokphone" class="empty">—</dd></div>
                    </dl>
                </div>

                <div class="review-section">
                    <div class="review-section-head">
                        <span class="review-section-title"><i class="bi bi-bank" style="margin-right:6px"></i>Lending</span>
                        <button type="button" class="review-edit-btn" onclick="wzGoto(4)"><i class="bi bi-pencil"></i> Edit</button>
                    </div>
                    <dl class="review-grid">
                        <div class="review-item"><dt>Tier</dt><dd id="rv-tier" class="empty">—</dd></div>
                        <div class="review-item"><dt>Status</dt><dd id="rv-status" class="empty">—</dd></div>
                    </dl>
                </div>

            </div>

            <div class="wz-nav">
                <button type="button" class="btn-wz-back" onclick="wzBack(4)">
                    <i class="bi bi-arrow-left"></i> Back
                </button>
                <div class="wz-nav-right">
                    <button type="submit" class="btn-wz-submit" id="wzSubmitBtn">
                        <i class="bi bi-check-lg"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>

    </div>{{-- /.wz-body --}}
</div>{{-- /.wz-shell --}}
</form>

{{-- ══════════════════════════════════════
     DANGER ZONE  (outside the form)
══════════════════════════════════════ --}}
<div class="danger-zone" style="max-width:1060px;margin-top:32px">
    <div class="danger-zone-head">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
            <strong>Danger zone</strong>
            <p>Permanently delete this borrower and their user account. This cannot be undone.</p>
        </div>
    </div>
    <form method="POST" action="{{ route('borrowers.destroy', $borrowerRecord->id) }}"
          onsubmit="return confirm('Permanently delete {{ addslashes($borrowerRecord->name) }}? This action cannot be undone.')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-danger-soft">
            <i class="bi bi-trash3"></i> Delete Borrower
        </button>
    </form>
</div>

<script>
(function () {
    'use strict';

    const TOTAL   = 4;
    let current   = {{ $errStep }};

    /* ── DOM helpers ── */
    const panels   = () => document.querySelectorAll('.wz-step-panel');
    const stepBtns = () => document.querySelectorAll('.wz-step');
    const panel    = n  => document.querySelector(`.wz-step-panel[data-panel="${n}"]`);
    const stepBtn  = n  => document.querySelector(`.wz-step[data-step="${n}"]`);
    const progBar  = document.getElementById('wzProgressBar');
    const progText = document.getElementById('wzProgText');

    /* ── Activate step ── */
    function activate(n) {
        panels().forEach(p => p.classList.remove('active'));
        stepBtns().forEach(b => b.classList.remove('active'));
        panel(n).classList.add('active');
        stepBtn(n).classList.add('active');
        progBar.style.width = Math.round((n / TOTAL) * 100) + '%';
        progText.textContent = 'Step ' + n;
        current = n;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    /* ── Per-step validation ── */
    function validateStep(n) {
        const p = panel(n);
        let valid = true;
        p.querySelectorAll('[required]').forEach(el => {
            if (el.type === 'radio') {
                const checked = Array.from(p.querySelectorAll(`[name="${el.name}"]`)).some(r => r.checked);
                if (!checked) {
                    valid = false;
                    p.querySelectorAll(`[name="${el.name}"]`).forEach(r =>
                        r.closest('.opt-card')?.classList.add('error-ring'));
                }
            } else if (!el.value.trim()) {
                valid = false;
                el.classList.add('is-invalid');
                el.addEventListener('input', () => el.classList.remove('is-invalid'), { once: true });
            }
        });
        return valid;
    }

    /* ── Navigation ── */
    window.wzNext = function (from) {
        if (!validateStep(from)) return;
        markDone(from);
        if (from < TOTAL) {
            activate(from + 1);
            if (from + 1 === TOTAL) updateReview();
        }
    };
    window.wzBack = function (from) { activate(from - 1); };
    window.wzGoto = function (n)    { activate(n); };

    function markDone(n) {
        const b = stepBtn(n);
        b.classList.remove('active', 'error');
        b.classList.add('done');
    }

    /* ── Opt-card radio behaviour ── */
    document.addEventListener('change', function (e) {
        if (e.target.type !== 'radio') return;
        const name = e.target.name;
        document.querySelectorAll(`[name="${name}"]`).forEach(r =>
            r.closest('.opt-card')?.classList.remove('selected', 'error-ring'));
        if (e.target.checked)
            e.target.closest('.opt-card')?.classList.add('selected');
    });

    /* ── Sidebar step click ── */
    stepBtns().forEach(btn => {
        btn.addEventListener('click', function () {
            const target = parseInt(this.dataset.step);
            if (target < current || this.classList.contains('done')) {
                activate(target);
                if (target === TOTAL) updateReview();
            } else if (target === current) {
                // stay
            } else {
                let ok = true;
                for (let s = current; s < target; s++) {
                    if (!validateStep(s)) { ok = false; break; }
                    markDone(s);
                }
                if (ok) {
                    activate(target);
                    if (target === TOTAL) updateReview();
                }
            }
        });
    });

    /* ── Review helpers ── */
    function val(name) {
        const el = document.querySelector(`[name="${name}"]`);
        if (!el) return '';
        if (el.type === 'radio') {
            const checked = document.querySelector(`[name="${name}"]:checked`);
            return checked ? checked.value : '';
        }
        return el.value;
    }
    function radioLabel(name) {
        const checked = document.querySelector(`[name="${name}"]:checked`);
        if (!checked) return '';
        const lbl = checked.closest('.opt-card')?.querySelector('.opt-card-label');
        return lbl ? lbl.textContent.trim() : checked.value;
    }
    function selectLabel(name) {
        const el = document.querySelector(`[name="${name}"]`);
        if (!el || el.selectedIndex < 0) return '';
        return el.options[el.selectedIndex]?.text || '';
    }
    function fmtKes(v) {
        const n = parseInt(v || '0');
        return isNaN(n) || n === 0 ? '—' : 'KES ' + n.toLocaleString();
    }
    function set(id, text) {
        const el = document.getElementById(id);
        if (!el) return;
        const v = (text || '').toString().trim();
        el.textContent = v || '—';
        el.classList.toggle('empty', !v);
    }

    function updateReview() {
        set('rv-name',       val('name'));
        set('rv-phone',      val('phone_number'));
        set('rv-email',      val('email') || '—');
        set('rv-gender',     radioLabel('gender') || '—');
        set('rv-nationality',selectLabel('nationality') || '—');
        set('rv-language',   selectLabel('preferred_language') || '—');
        set('rv-employment', radioLabel('employment_type') || '—');
        set('rv-employer',   val('employer_name') || '—');
        set('rv-income',     fmtKes(val('monthly_income')));
        set('rv-expenses',   fmtKes(val('monthly_expenses')));
        set('rv-county',     selectLabel('residence_county') || '—');
        set('rv-address',    val('residence_address') || '—');
        set('rv-nokname',    val('next_of_kin_name') || '—');
        set('rv-nokrel',     selectLabel('next_of_kin_relationship') || '—');
        set('rv-nokphone',   val('next_of_kin_phone') || '—');
        set('rv-tier',       radioLabel('current_tier_id') || '—');
        set('rv-status',     radioLabel('account_status') || '—');
    }

    /* ── Submit guard ── */
    document.getElementById('wzForm').addEventListener('submit', function () {
        const btn = document.getElementById('wzSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-repeat" style="animation:spin .8s linear infinite"></i> Saving…';
    });

    /* ── Initialise ── */
    activate(current);
    for (let s = 1; s < current; s++) markDone(s);

    /* ── Error ring cleanup ── */
    document.addEventListener('input', function (e) {
        e.target.closest('.opt-card')?.classList.remove('error-ring');
    });

})();
</script>

@endsection
