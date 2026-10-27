@extends('layouts.app')
@section('title', 'Add Borrower')

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
.wz-sidebar {
    position: sticky;
    top: 88px;
}
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
    font-size: 11px;
    font-weight: 700;
    color: #fff;
    letter-spacing: .04em;
    text-transform: uppercase;
}
.wz-prog-bar-wrap {
    margin-top: 10px;
    height: 4px;
    background: rgba(255,255,255,.12);
    border-radius: 4px;
    overflow: hidden;
}
.wz-prog-bar {
    height: 100%;
    background: linear-gradient(90deg, #6366f1, #8b5cf6);
    border-radius: 4px;
    transition: width .4s cubic-bezier(.4,0,.2,1);
}
.wz-prog-label {
    margin-top: 8px;
    font-size: 10px;
    color: rgba(255,255,255,.5);
}
.wz-prog-label span { color: #a5b4fc; font-weight: 700; }
.wz-steps { padding: 12px 10px; display: flex; flex-direction: column; gap: 2px; }
.wz-step {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 8px 9px;
    border-radius: 10px;
    cursor: pointer;
    transition: background .15s;
    position: relative;
    border: none;
    background: transparent;
    width: 100%;
    text-align: left;
}
.wz-step:hover { background: #f5f5ff; }
.wz-step.active { background: #eef2ff; }
.wz-step.done .wz-step-num { background: #ecfdf3; color: #027a48; border-color: #a9efc5; }
.wz-step.active .wz-step-num { background: var(--primary); color: #fff; border-color: var(--primary); }
.wz-step.error .wz-step-num { background: #fef3f2; color: #b42318; border-color: #fecdca; }
.wz-step-num {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: 1.5px solid var(--line);
    display: grid;
    place-items: center;
    font-size: 11px;
    font-weight: 700;
    flex-shrink: 0;
    transition: background .2s, color .2s, border-color .2s;
    color: #98a2b3;
}
.wz-step-body { flex: 1; min-width: 0; }
.wz-step-label {
    display: block;
    font-size: 11.5px;
    font-weight: 650;
    color: #344054;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.wz-step.active .wz-step-label { color: var(--primary); }
.wz-step-sub {
    display: block;
    font-size: 9.5px;
    color: #98a2b3;
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.wz-step-check {
    font-size: 12px;
    color: #12b76a;
    flex-shrink: 0;
    opacity: 0;
    transition: opacity .2s;
}
.wz-step.done .wz-step-check { opacity: 1; }
.wz-tip {
    margin: 0 10px 12px;
    padding: 11px 13px;
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 10px;
    font-size: 10px;
    color: #92400e;
    line-height: 1.5;
}
.wz-tip i { color: #d97706; margin-right: 5px; }

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
    width: 38px;
    height: 38px;
    border-radius: 11px;
    display: grid;
    place-items: center;
    font-size: 17px;
    flex-shrink: 0;
}
.form-card-head-flex {
    display: flex;
    align-items: center;
    gap: 13px;
}
.form-card-head-flex .form-card-icon { background: #eef2ff; color: var(--primary); }
.form-card-head-flex.green .form-card-icon { background: #ecfdf3; color: #027a48; }
.form-card-head-flex.amber .form-card-icon { background: #fffaeb; color: #b54708; }
.form-card-head-flex.violet .form-card-icon { background: #f5f3ff; color: #6d28d9; }
.form-card-head-flex.rose .form-card-icon { background: #fff1f2; color: #be123c; }

/* ═══════════════════════════════════════════
   INPUT ENHANCEMENTS
═══════════════════════════════════════════ */
.input-prefix {
    display: flex;
    align-items: center;
    height: 38px;
    border: 1px solid #d0d5dd;
    border-radius: 8px;
    overflow: hidden;
    transition: border-color .15s, box-shadow .15s;
}
.input-prefix:focus-within {
    border-color: #7c7df0;
    box-shadow: 0 0 0 3px rgba(91,92,226,.1);
}
.input-prefix.is-invalid { border-color: #f04438; }
.input-pre {
    padding: 0 10px;
    background: #f9fafb;
    border-right: 1px solid #d0d5dd;
    font-size: 12px;
    color: #667085;
    height: 100%;
    display: flex;
    align-items: center;
    white-space: nowrap;
    flex-shrink: 0;
}
.input-prefix .form-input {
    border: 0;
    border-radius: 0;
    flex: 1;
    box-shadow: none !important;
}
.input-prefix .form-input:focus { box-shadow: none !important; }

.form-input-icon {
    position: relative;
}
.form-input-icon i {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    color: #98a2b3;
    font-size: 14px;
    pointer-events: none;
}
.form-input-icon .form-input { padding-left: 33px; }

.form-hint {
    font-size: 10px;
    color: #98a2b3;
    margin-top: -2px;
}

/* ═══════════════════════════════════════════
   RADIO OPTION CARDS
═══════════════════════════════════════════ */
.opt-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 8px;
}
.opt-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 13px 10px 11px;
    border: 1.5px solid var(--line);
    border-radius: 10px;
    cursor: pointer;
    transition: border-color .15s, background .15s;
    position: relative;
    text-align: center;
}
.opt-card input { position: absolute; opacity: 0; width: 0; height: 0; }
.opt-card:hover { border-color: #b0b1f5; background: #fafafe; }
.opt-card.selected { border-color: var(--primary); background: #f5f5ff; }
.opt-card-icon {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    display: grid;
    place-items: center;
    font-size: 16px;
    background: #f2f4f7;
    color: #667085;
    transition: background .15s, color .15s;
}
.opt-card.selected .opt-card-icon { background: #eef2ff; color: var(--primary); }
.opt-card-label { font-size: 11px; font-weight: 650; color: #344054; }
.opt-card.selected .opt-card-label { color: var(--primary); }
.opt-card-check {
    position: absolute;
    top: 7px;
    right: 7px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 1.5px solid var(--line);
    display: grid;
    place-items: center;
    font-size: 9px;
    color: transparent;
    transition: background .15s, border-color .15s, color .15s;
}
.opt-card.selected .opt-card-check { background: var(--primary); border-color: var(--primary); color: #fff; }

/* ═══════════════════════════════════════════
   SECTION DIVIDER
═══════════════════════════════════════════ */
.form-section-divider {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 20px;
    color: #98a2b3;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
}
.form-section-divider::before,
.form-section-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--line);
}

/* ═══════════════════════════════════════════
   WIZARD NAV BUTTONS
═══════════════════════════════════════════ */
.wz-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 0 0;
}
.wz-nav-right { display: flex; gap: 8px; align-items: center; }
.btn-wz-next {
    height: 38px;
    background: var(--primary);
    color: #fff;
    border: none;
    border-radius: 9px;
    padding: 0 18px;
    font-size: 12px;
    font-weight: 650;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    cursor: pointer;
    transition: background .15s, box-shadow .15s, transform .1s;
}
.btn-wz-next:hover { background: #4748c4; box-shadow: 0 6px 18px rgba(91,92,226,.28); transform: translateY(-1px); }
.btn-wz-back {
    height: 38px;
    background: #fff;
    color: #344054;
    border: 1px solid var(--line);
    border-radius: 9px;
    padding: 0 16px;
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    cursor: pointer;
    transition: background .15s;
}
.btn-wz-back:hover { background: #f9fafb; }
.btn-wz-submit {
    height: 38px;
    background: #027a48;
    color: #fff;
    border: none;
    border-radius: 9px;
    padding: 0 22px;
    font-size: 12px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: background .15s, box-shadow .15s, transform .1s;
}
.btn-wz-submit:hover { background: #035c38; box-shadow: 0 6px 18px rgba(2,122,72,.25); transform: translateY(-1px); }

/* ═══════════════════════════════════════════
   REVIEW PANEL (step 5)
═══════════════════════════════════════════ */
.review-section { padding: 18px 20px; border-bottom: 1px solid var(--line); }
.review-section:last-child { border-bottom: 0; }
.review-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}
.review-section-title {
    font-size: 11px;
    font-weight: 700;
    color: #344054;
    text-transform: uppercase;
    letter-spacing: .05em;
}
.review-edit-btn {
    font-size: 10px;
    color: var(--primary);
    background: none;
    border: none;
    cursor: pointer;
    font-weight: 600;
    padding: 0;
    display: flex;
    align-items: center;
    gap: 4px;
}
.review-edit-btn:hover { text-decoration: underline; }
.review-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 10px;
}
.review-item {}
.review-item dt {
    font-size: 9.5px;
    color: #98a2b3;
    text-transform: uppercase;
    letter-spacing: .06em;
    font-weight: 600;
    margin-bottom: 3px;
}
.review-item dd {
    margin: 0;
    font-size: 12px;
    font-weight: 600;
    color: #101828;
}
.review-item dd.empty { color: #d0d5dd; font-weight: 400; font-style: italic; }

/* ═══════════════════════════════════════════
   STATUS BADGE (account status picker)
═══════════════════════════════════════════ */
.status-opt-active .opt-card-icon { background: #ecfdf3; color: #027a48; }
.status-opt-active.selected { border-color: #027a48; background: #f0fdf7; }
.status-opt-active.selected .opt-card-icon { background: #dcfce7; color: #027a48; }
.status-opt-active.selected .opt-card-label { color: #027a48; }
.status-opt-active.selected .opt-card-check { background: #027a48; border-color: #027a48; }

.status-opt-frozen .opt-card-icon { background: #fffaeb; color: #b54708; }
.status-opt-frozen.selected { border-color: #b54708; background: #fffcf0; }
.status-opt-frozen.selected .opt-card-icon { background: #fef3c7; color: #b54708; }
.status-opt-frozen.selected .opt-card-label { color: #b54708; }
.status-opt-frozen.selected .opt-card-check { background: #b54708; border-color: #b54708; }

.status-opt-blacklisted .opt-card-icon { background: #fef3f2; color: #b42318; }
.status-opt-blacklisted.selected { border-color: #b42318; background: #fff8f7; }
.status-opt-blacklisted.selected .opt-card-icon { background: #fee2e2; color: #b42318; }
.status-opt-blacklisted.selected .opt-card-label { color: #b42318; }
.status-opt-blacklisted.selected .opt-card-check { background: #b42318; border-color: #b42318; }

/* ═══════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════ */
@media (max-width: 900px) {
    .wz-shell { grid-template-columns: 1fr; }
    .wz-sidebar { position: static; }
    .wz-steps { flex-direction: row; flex-wrap: wrap; gap: 4px; }
    .wz-step { flex: 0 0 auto; width: auto; }
    .wz-step-sub { display: none; }
}
@media (max-width: 600px) {
    .opt-grid { grid-template-columns: repeat(2, 1fr); }
    .review-grid { grid-template-columns: 1fr 1fr; }
}
</style>
@endpush

@section('content')

<div class="bw-breadcrumb">
    <a href="{{ route('borrowers.index') }}"><i class="bi bi-people"></i> Borrowers</a>
    <i class="bi bi-chevron-right"></i>
    <span>Add Borrower</span>
</div>

<div class="page-header" style="margin-bottom:28px">
    <div>
        <div class="eyebrow">BORROWERS</div>
        <h1>New Borrower Profile</h1>
        <p>Complete all sections below before the borrower can be issued a loan.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('borrowers.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

@if($errors->any())
    <div class="alert-banner danger" style="max-width:1060px">
        <i class="bi bi-exclamation-circle-fill"></i>
        <strong>Please correct the errors below.</strong>&nbsp;
        {{ $errors->first() }}
    </div>
@endif

{{-- Determine which step had an error so we can reopen it --}}
@php
$errStep = 1;
if ($errors->hasAny(['name','email','phone_number','date_of_birth','gender','nationality'])) $errStep = 1;
elseif ($errors->hasAny(['doc_type','doc_number','doc_country'])) $errStep = 2;
elseif ($errors->hasAny(['employment_type','employer_name','job_title','monthly_income','monthly_expenses','existing_debt','other_income','residence_county','residence_address','housing_status','years_at_address'])) $errStep = 3;
elseif ($errors->hasAny(['next_of_kin_name','next_of_kin_relationship','next_of_kin_phone'])) $errStep = 4;
elseif ($errors->hasAny(['current_tier_id','account_status','branch_id'])) $errStep = 5;
@endphp

<form method="POST" action="{{ route('borrowers.store') }}" id="wzForm" novalidate>
@csrf

<div class="wz-shell">

    {{-- ── LEFT: Progress sidebar ── --}}
    <div class="wz-sidebar">
        <div class="wz-progress-card">
            <div class="wz-prog-head">
                <div class="wz-prog-head-title">Application Progress</div>
                <div class="wz-prog-bar-wrap">
                    <div class="wz-prog-bar" id="wzProgressBar" style="width:20%"></div>
                </div>
                <div class="wz-prog-label"><span id="wzProgText">Step 1</span> of 5</div>
            </div>
            <div class="wz-steps" id="wzStepNav">
                <button type="button" class="wz-step active" data-step="1">
                    <div class="wz-step-num">1</div>
                    <div class="wz-step-body">
                        <span class="wz-step-label">Personal Info</span>
                        <span class="wz-step-sub">Identity &amp; contact details</span>
                    </div>
                    <i class="bi bi-check-circle-fill wz-step-check"></i>
                </button>
                <button type="button" class="wz-step" data-step="2">
                    <div class="wz-step-num">2</div>
                    <div class="wz-step-body">
                        <span class="wz-step-label">KYC / ID Verification</span>
                        <span class="wz-step-sub">Government document</span>
                    </div>
                    <i class="bi bi-check-circle-fill wz-step-check"></i>
                </button>
                <button type="button" class="wz-step" data-step="3">
                    <div class="wz-step-num">3</div>
                    <div class="wz-step-body">
                        <span class="wz-step-label">Financial Profile</span>
                        <span class="wz-step-sub">Income &amp; employment</span>
                    </div>
                    <i class="bi bi-check-circle-fill wz-step-check"></i>
                </button>
                <button type="button" class="wz-step" data-step="4">
                    <div class="wz-step-num">4</div>
                    <div class="wz-step-body">
                        <span class="wz-step-label">Next of Kin</span>
                        <span class="wz-step-sub">Emergency contact</span>
                    </div>
                    <i class="bi bi-check-circle-fill wz-step-check"></i>
                </button>
                <button type="button" class="wz-step" data-step="5">
                    <div class="wz-step-num">5</div>
                    <div class="wz-step-body">
                        <span class="wz-step-label">Lending &amp; Review</span>
                        <span class="wz-step-sub">Tier, status &amp; confirm</span>
                    </div>
                    <i class="bi bi-check-circle-fill wz-step-check"></i>
                </button>
            </div>
            <div class="wz-tip">
                <i class="bi bi-lightbulb-fill"></i>
                All sections must be completed before a loan can be issued. KYC and financial data power the credit appraisal engine.
            </div>
        </div>
    </div>

    {{-- ── RIGHT: Step panels ── --}}
    <div class="wz-body">

        {{-- ════════════════════════════════
             STEP 1 · Personal Information
        ════════════════════════════════ --}}
        <div class="wz-step-panel" data-panel="1">

            <div class="form-card">
                <div class="form-card-head">
                    <div class="form-card-head-flex">
                        <div class="form-card-icon"><i class="bi bi-person-vcard"></i></div>
                        <div>
                            <h2>Personal Information</h2>
                            <p>Legal name, contact details and demographics.</p>
                        </div>
                    </div>
                </div>
                <div class="form-grid">
                    {{-- Full name --}}
                    <div class="form-group form-group-full">
                        <label for="name">Full Legal Name <span style="color:#f04438">*</span></label>
                        <div class="form-input-icon">
                            <i class="bi bi-person"></i>
                            <input id="name" name="name" value="{{ old('name') }}"
                                   class="form-input @error('name') is-invalid @enderror"
                                   placeholder="As it appears on the national ID" required>
                        </div>
                        @error('name')<span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                    </div>

                    {{-- Phone --}}
                    <div class="form-group">
                        <label for="phone_number">Primary Phone <span style="color:#f04438">*</span></label>
                        <div class="input-prefix @error('phone_number') is-invalid @enderror">
                            <span class="input-pre"><i class="bi bi-phone" style="margin-right:5px"></i>+254</span>
                            <input id="phone_number" name="phone_number" value="{{ old('phone_number') }}"
                                   class="form-input"
                                   placeholder="7XX XXX XXX" maxlength="12" required>
                        </div>
                        <span class="form-hint">Enter without the country code, e.g. 712 345 678</span>
                        @error('phone_number')<span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                    </div>

                    {{-- Email --}}
                    <div class="form-group">
                        <label for="email">Email Address <span class="label-hint">optional</span></label>
                        <div class="form-input-icon">
                            <i class="bi bi-envelope"></i>
                            <input id="email" type="email" name="email" value="{{ old('email') }}"
                                   class="form-input @error('email') is-invalid @enderror"
                                   placeholder="borrower@example.com">
                        </div>
                        @error('email')<span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                    </div>

                    <div class="form-section-divider form-group-full">Demographics</div>

                    {{-- Date of birth --}}
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth <span style="color:#f04438">*</span></label>
                        <input id="date_of_birth" type="date" name="date_of_birth"
                               value="{{ old('date_of_birth') }}"
                               max="{{ date('Y-m-d', strtotime('-18 years')) }}"
                               class="form-input @error('date_of_birth') is-invalid @enderror" required>
                        <span class="form-hint">Must be at least 18 years old</span>
                        @error('date_of_birth')<span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                    </div>

                    {{-- Gender --}}
                    <div class="form-group">
                        <label>Gender <span style="color:#f04438">*</span></label>
                        <div class="opt-grid" style="grid-template-columns:repeat(3,1fr)">
                            @foreach(['male'=>['Male','bi-gender-male'],'female'=>['Female','bi-gender-female'],'other'=>['Other','bi-gender-ambiguous']] as $v=>[$l,$ic])
                            <label class="opt-card @if(old('gender','male')===$v) selected @endif">
                                <input type="radio" name="gender" value="{{ $v }}" required
                                       @checked(old('gender','male')===$v)>
                                <div class="opt-card-icon"><i class="bi {{ $ic }}"></i></div>
                                <span class="opt-card-label">{{ $l }}</span>
                                <div class="opt-card-check"><i class="bi bi-check"></i></div>
                            </label>
                            @endforeach
                        </div>
                        @error('gender')<span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                    </div>

                    {{-- Nationality --}}
                    <div class="form-group">
                        <label for="nationality">Nationality <span style="color:#f04438">*</span></label>
                        <select id="nationality" name="nationality"
                                class="form-input @error('nationality') is-invalid @enderror" required>
                            <option value="">Select…</option>
                            <option value="KE" @selected(old('nationality','KE')==='KE')>Kenyan</option>
                            <option value="UG" @selected(old('nationality')==='UG')>Ugandan</option>
                            <option value="TZ" @selected(old('nationality')==='TZ')>Tanzanian</option>
                            <option value="RW" @selected(old('nationality')==='RW')>Rwandan</option>
                            <option value="ET" @selected(old('nationality')==='ET')>Ethiopian</option>
                            <option value="SO" @selected(old('nationality')==='SO')>Somali</option>
                            <option value="OTHER" @selected(old('nationality')==='OTHER')>Other</option>
                        </select>
                        @error('nationality')<span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                    </div>

                    {{-- Preferred language --}}
                    <div class="form-group">
                        <label for="preferred_language">Preferred Language <span class="label-hint">optional</span></label>
                        <select id="preferred_language" name="preferred_language" class="form-input">
                            <option value="">Select…</option>
                            <option value="en" @selected(old('preferred_language','en')==='en')>English</option>
                            <option value="sw" @selected(old('preferred_language')==='sw')>Kiswahili</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="wz-nav">
                <a href="{{ route('borrowers.index') }}" class="btn btn-light">Cancel</a>
                <div class="wz-nav-right">
                    <button type="button" class="btn-wz-next" onclick="wzNext(1)">
                        Continue <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════
             STEP 2 · KYC / ID Verification
        ════════════════════════════════ --}}
        <div class="wz-step-panel" data-panel="2">

            {{-- ── Upload & OCR card ── --}}
            <div class="form-card" id="kycUploadCard">
                <div class="form-card-head">
                    <div class="form-card-head-flex green">
                        <div class="form-card-icon"><i class="bi bi-shield-check"></i></div>
                        <div>
                            <h2>KYC — Identity Verification</h2>
                            <p>Upload the borrower's ID image — fields will be extracted automatically.</p>
                        </div>
                    </div>
                </div>

                <div style="padding:20px;display:flex;flex-direction:column;gap:16px">

                    {{-- Drop-zone --}}
                    <div id="kycDropZone" class="kyc-drop-zone">
                        <input type="file" id="kycFileInput" accept="image/jpeg,image/png,image/webp"
                               style="display:none">
                        {{-- Hidden field carries the stored file key after upload --}}
                        <input type="hidden" name="doc_file_key" id="kycFileKey" value="{{ old('doc_file_key') }}">

                        <div class="kyc-dz-idle" id="kycDzIdle">
                            <div class="kyc-dz-icon">
                                <i class="bi bi-credit-card-2-front"></i>
                            </div>
                            <div class="kyc-dz-title">Drop ID image here or <span class="kyc-dz-link">browse</span></div>
                            <div class="kyc-dz-sub">JPEG, PNG or WebP · max 6 MB · front side of document</div>
                        </div>

                        <div class="kyc-dz-preview" id="kycDzPreview" style="display:none">
                            <img id="kycPreviewImg" src="" alt="ID preview">
                            <button type="button" class="kyc-dz-remove" id="kycRemoveBtn"
                                    title="Remove image">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Scan button --}}
                    <div id="kycScanRow" style="display:none">
                        <button type="button" id="kycScanBtn" class="btn-kyc-scan">
                            <i class="bi bi-magic" id="kycScanIcon"></i>
                            <span id="kycScanLabel">Scan &amp; Extract Fields</span>
                        </button>
                        <span class="kyc-scan-hint">Text extraction powered by OCR.space</span>
                    </div>

                    {{-- OCR result banner --}}
                    <div id="kycOcrBanner" style="display:none"></div>

                    {{-- Confidence chips (shown after successful OCR) --}}
                    <div id="kycConfidenceRow" class="kyc-confidence-row" style="display:none"></div>
                </div>
            </div>

            {{-- ── Extracted / manual fields card ── --}}
            <div class="form-card" id="kycFieldsCard">
                <div class="form-card-head" style="background:#f8fafc">
                    <div style="display:flex;align-items:center;justify-content:space-between;width:100%">
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:26px;height:26px;border-radius:7px;background:#eef2ff;
                                        color:var(--primary);display:grid;place-items:center;font-size:13px">
                                <i class="bi bi-pencil-square"></i>
                            </div>
                            <div>
                                <div style="font-size:12px;font-weight:700;color:#101828">
                                    Document Details
                                </div>
                                <div style="font-size:10px;color:#98a2b3;margin-top:1px" id="kycFieldsSubtitle">
                                    Fill in manually or scan the document above to auto-populate.
                                </div>
                            </div>
                        </div>
                        <div id="kycOcrBadge" style="display:none" class="kyc-ocr-badge">
                            <i class="bi bi-stars"></i> Auto-filled by OCR
                        </div>
                    </div>
                </div>

                <div class="form-grid">

                    {{-- Document type --}}
                    <div class="form-group form-group-full">
                        <label>Document Type <span style="color:#f04438">*</span></label>
                        <div class="opt-grid" id="docTypeOptGrid">
                            @foreach([
                                'national_id'    => ['National ID',      'bi-credit-card-2-front'],
                                'passport'       => ['Passport',         'bi-passport'],
                                'alien_id'       => ['Alien ID',         'bi-person-badge'],
                                'driving_licence'=> ['Driving Licence',  'bi-car-front'],
                            ] as $v => [$l, $ic])
                            <label class="opt-card @if(old('doc_type','national_id')===$v) selected @endif"
                                   data-doctype="{{ $v }}">
                                <input type="radio" name="doc_type" value="{{ $v }}" required
                                       @checked(old('doc_type','national_id')===$v)>
                                <div class="opt-card-icon"><i class="bi {{ $ic }}"></i></div>
                                <span class="opt-card-label">{{ $l }}</span>
                                <div class="opt-card-check"><i class="bi bi-check"></i></div>
                            </label>
                            @endforeach
                        </div>
                        @error('doc_type')
                            <span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Document number --}}
                    <div class="form-group">
                        <label for="doc_number">
                            Document Number <span style="color:#f04438">*</span>
                            <span class="kyc-ocr-pill" id="pillDocNumber" style="display:none">OCR</span>
                        </label>
                        <div class="form-input-icon">
                            <i class="bi bi-hash"></i>
                            <input id="doc_number" name="doc_number" value="{{ old('doc_number') }}"
                                   class="form-input @error('doc_number') is-invalid @enderror"
                                   placeholder="e.g. 12345678" required>
                        </div>
                        @error('doc_number')
                            <span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Issuing country --}}
                    <div class="form-group">
                        <label for="doc_country">
                            Issuing Country <span style="color:#f04438">*</span>
                            <span class="kyc-ocr-pill" id="pillDocCountry" style="display:none">OCR</span>
                        </label>
                        <select id="doc_country" name="doc_country"
                                class="form-input @error('doc_country') is-invalid @enderror" required>
                            <option value="">Select…</option>
                            <option value="KE" @selected(old('doc_country','KE')==='KE')>Kenya</option>
                            <option value="UG" @selected(old('doc_country')==='UG')>Uganda</option>
                            <option value="TZ" @selected(old('doc_country')==='TZ')>Tanzania</option>
                            <option value="RW" @selected(old('doc_country')==='RW')>Rwanda</option>
                            <option value="ET" @selected(old('doc_country')==='ET')>Ethiopia</option>
                            <option value="SO" @selected(old('doc_country')==='SO')>Somalia</option>
                            <option value="OTHER" @selected(old('doc_country')==='OTHER')>Other</option>
                        </select>
                        @error('doc_country')
                            <span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Full name (OCR fills this + step-1 name) --}}
                    <div class="form-group form-group-full">
                        <label for="kyc_full_name">
                            Full Name on Document
                            <span class="label-hint">auto-syncs with Personal Info</span>
                            <span class="kyc-ocr-pill" id="pillFullName" style="display:none">OCR</span>
                        </label>
                        <div class="form-input-icon">
                            <i class="bi bi-person"></i>
                            <input id="kyc_full_name" name="kyc_full_name"
                                   value="{{ old('kyc_full_name') }}"
                                   class="form-input"
                                   placeholder="Extracted from document">
                        </div>
                        <span class="form-hint">If different from Step 1, both will be saved; the ID name is used for KYC matching.</span>
                    </div>

                    {{-- Date of birth (OCR fills this + step-1 DOB) --}}
                    <div class="form-group">
                        <label for="kyc_dob">
                            Date of Birth on Document
                            <span class="kyc-ocr-pill" id="pillDob" style="display:none">OCR</span>
                        </label>
                        <input id="kyc_dob" type="date" name="kyc_dob"
                               value="{{ old('kyc_dob') }}"
                               class="form-input">
                    </div>

                    {{-- Expiry date --}}
                    <div class="form-group">
                        <label for="kyc_expiry">
                            Document Expiry
                            <span class="label-hint">optional</span>
                            <span class="kyc-ocr-pill" id="pillExpiry" style="display:none">OCR</span>
                        </label>
                        <input id="kyc_expiry" type="date" name="kyc_expiry"
                               value="{{ old('kyc_expiry') }}"
                               class="form-input">
                    </div>

                    <div class="form-section-divider form-group-full">Verification Status</div>

                    <div class="form-group form-group-full">
                        <label>Verification Status</label>
                        <div class="secure-note">
                            <i class="bi bi-hourglass-split"></i>
                            Pending supervisor review. Registration staff cannot approve their own identity documents.
                        </div>
                    </div>

                    {{-- Verification notes --}}
                    <div class="form-group form-group-full">
                        <label for="verification_notes">
                            Verification Notes <span class="label-hint">optional</span>
                        </label>
                        <textarea id="verification_notes" name="verification_notes" rows="2"
                                  class="form-input"
                                  placeholder="Notes on document quality, discrepancies, etc.">{{ old('verification_notes') }}</textarea>
                    </div>

                    {{-- Raw OCR text (hidden, sent with form for audit) --}}
                    <input type="hidden" name="ocr_raw_text" id="kycRawText" value="{{ old('ocr_raw_text') }}">
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
             STEP 3 · Financial Profile
        ════════════════════════════════ --}}
        <div class="wz-step-panel" data-panel="3">

            {{-- ── M-Pesa Statement Upload card ──────────────────────────── --}}
            <div class="form-card" id="stmtUploadCard">
                <div class="form-card-head">
                    <div class="form-card-head-flex amber">
                        <div class="form-card-icon"><i class="bi bi-phone"></i></div>
                        <div>
                            <h2>M-Pesa Statement Analysis</h2>
                            <p>Upload a 3–6 month M-Pesa PDF statement to automatically extract income &amp; expenses.</p>
                        </div>
                    </div>
                </div>
                <div style="padding:20px;display:flex;flex-direction:column;gap:14px">

                    {{-- Drop zone --}}
                    <div id="stmtDropZone" class="kyc-drop-zone" style="min-height:130px">
                        <input type="file" id="stmtFileInput" accept="application/pdf" style="display:none">
                        <input type="hidden" name="stmt_job_id"  id="stmtJobId"  value="{{ old('stmt_job_id') }}">
                        <input type="hidden" name="stmt_file_key" id="stmtFileKey" value="{{ old('stmt_file_key') }}">

                        <div class="kyc-dz-idle" id="stmtDzIdle">
                            <div class="kyc-dz-icon" style="background:#fffaeb;color:#b54708">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </div>
                            <div class="kyc-dz-title">
                                Drop M-Pesa PDF here or <span class="kyc-dz-link" id="stmtBrowse">browse</span>
                            </div>
                            <div class="kyc-dz-sub">PDF only · max 20 MB · Safaricom M-Pesa statement</div>
                        </div>
                        <div class="kyc-dz-preview" id="stmtDzPreview" style="display:none;padding:14px 16px">
                            <div style="display:flex;align-items:center;gap:12px">
                                <div style="width:40px;height:40px;border-radius:10px;background:#fffaeb;color:#b54708;
                                            display:grid;place-items:center;font-size:20px;flex-shrink:0">
                                    <i class="bi bi-file-earmark-pdf-fill"></i>
                                </div>
                                <div style="min-width:0;flex:1">
                                    <div id="stmtFileName" style="font-size:12px;font-weight:650;color:#101828;
                                         white-space:nowrap;overflow:hidden;text-overflow:ellipsis"></div>
                                    <div id="stmtFileSize" style="font-size:10px;color:#98a2b3;margin-top:2px"></div>
                                </div>
                                <button type="button" id="stmtRemoveBtn" class="kyc-dz-remove"
                                        style="position:static;background:#f2f4f7;color:#667085">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Upload progress bar --}}
                    <div id="stmtProgressWrap" style="display:none">
                        <div style="display:flex;justify-content:space-between;align-items:center;
                                    margin-bottom:6px;font-size:11px;color:#667085">
                            <span id="stmtProgressLabel">Analysing statement…</span>
                            <span id="stmtProgressPct" style="font-weight:700;color:var(--primary)">0%</span>
                        </div>
                        <div style="height:5px;border-radius:4px;background:#eaecf0;overflow:hidden">
                            <div id="stmtProgressBar" style="height:100%;width:0%;background:linear-gradient(90deg,#f79009,#b54708);
                                 border-radius:4px;transition:width .4s ease"></div>
                        </div>
                    </div>

                    {{-- Result banner --}}
                    <div id="stmtBanner" style="display:none"></div>

                    {{-- Analysis results card --}}
                    <div id="stmtResultCard" style="display:none">
                        <div style="background:#f8fafc;border:1px solid #eaecf0;border-radius:12px;overflow:hidden">
                            <div style="padding:13px 16px;border-bottom:1px solid #eaecf0;display:flex;
                                        align-items:center;justify-content:space-between">
                                <div style="display:flex;align-items:center;gap:9px">
                                    <div style="width:26px;height:26px;border-radius:7px;background:#ecfdf3;
                                                color:#027a48;display:grid;place-items:center;font-size:13px">
                                        <i class="bi bi-graph-up"></i>
                                    </div>
                                    <span style="font-size:12px;font-weight:700;color:#101828">Statement Analysis</span>
                                </div>
                                <span id="stmtPeriodLabel" style="font-size:10px;color:#98a2b3"></span>
                            </div>
                            {{-- Score bar --}}
                            <div style="padding:14px 16px;border-bottom:1px solid #eaecf0">
                                <div style="display:flex;justify-content:space-between;margin-bottom:6px">
                                    <span style="font-size:11px;font-weight:650;color:#344054">Financial Score</span>
                                    <span id="stmtScoreVal" style="font-size:12px;font-weight:700;color:var(--primary)"></span>
                                </div>
                                <div style="height:7px;border-radius:4px;background:#eaecf0;overflow:hidden">
                                    <div id="stmtScoreBar" style="height:100%;width:0%;border-radius:4px;
                                         background:linear-gradient(90deg,#6366f1,#8b5cf6);transition:width .6s ease"></div>
                                </div>
                                <div style="display:flex;justify-content:space-between;margin-top:8px;gap:10px">
                                    <div style="flex:1;text-align:center;padding:8px;background:#fff;border-radius:8px;
                                                border:1px solid #eaecf0">
                                        <div style="font-size:9px;color:#98a2b3;text-transform:uppercase;letter-spacing:.05em">Income Stability</div>
                                        <div id="stmtStabilityVal" style="font-size:13px;font-weight:700;color:#027a48;margin-top:3px"></div>
                                    </div>
                                    <div style="flex:1;text-align:center;padding:8px;background:#fff;border-radius:8px;
                                                border:1px solid #eaecf0">
                                        <div style="font-size:9px;color:#98a2b3;text-transform:uppercase;letter-spacing:.05em">Cashflow</div>
                                        <div id="stmtCashflowVal" style="font-size:13px;font-weight:700;color:#3538cd;margin-top:3px"></div>
                                    </div>
                                    <div style="flex:1;text-align:center;padding:8px;background:#fff;border-radius:8px;
                                                border:1px solid #eaecf0">
                                        <div style="font-size:9px;color:#98a2b3;text-transform:uppercase;letter-spacing:.05em">Debt Ratio</div>
                                        <div id="stmtDebtRatioVal" style="font-size:13px;font-weight:700;color:#b54708;margin-top:3px"></div>
                                    </div>
                                </div>
                            </div>
                            {{-- Monthly grid --}}
                            <div style="padding:14px 16px">
                                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px">
                                    <div>
                                        <div style="font-size:9px;color:#98a2b3;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px">Avg Monthly Income</div>
                                        <div id="stmtAvgIncome" style="font-size:15px;font-weight:700;color:#101828"></div>
                                    </div>
                                    <div>
                                        <div style="font-size:9px;color:#98a2b3;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px">Avg Monthly Expenses</div>
                                        <div id="stmtAvgExpense" style="font-size:15px;font-weight:700;color:#101828"></div>
                                    </div>
                                    <div>
                                        <div style="font-size:9px;color:#98a2b3;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px">Net Cashflow</div>
                                        <div id="stmtAvgCashflow" style="font-size:15px;font-weight:700;color:#101828"></div>
                                    </div>
                                </div>
                                <div id="stmtTxMeta" style="margin-top:10px;font-size:10px;color:#98a2b3"></div>
                            </div>
                        </div>
                    </div>

                    <div style="font-size:10px;color:#98a2b3">
                        <i class="bi bi-lock-fill" style="margin-right:4px;color:#d0d5dd"></i>
                        Your statement is stored securely and never shared. Upload is optional — you can fill income fields manually below.
                    </div>
                </div>
            </div>

            {{-- ── Employment & declared income card ──────────────────────── --}}
            <div class="form-card">
                <div class="form-card-head" style="background:#f8fafc">
                    <div style="display:flex;align-items:center;justify-content:space-between;width:100%">
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:26px;height:26px;border-radius:7px;background:#fffaeb;
                                        color:#b54708;display:grid;place-items:center;font-size:13px">
                                <i class="bi bi-briefcase"></i>
                            </div>
                            <div>
                                <div style="font-size:12px;font-weight:700;color:#101828">Employment &amp; Income</div>
                                <div style="font-size:10px;color:#98a2b3;margin-top:1px" id="stmtFieldsSubtitle">
                                    Confirm or correct the figures below.
                                </div>
                            </div>
                        </div>
                        <div id="stmtAutoFillBadge" style="display:none" class="kyc-ocr-badge"
                             style="background:linear-gradient(135deg,#f79009,#b54708)">
                            <i class="bi bi-stars"></i> Auto-filled from statement
                        </div>
                    </div>
                </div>
                <div class="form-grid">

                    {{-- Employment type --}}
                    <div class="form-group form-group-full">
                        <label>Employment Type <span style="color:#f04438">*</span></label>
                        <div class="opt-grid">
                            @foreach([
                                'salaried'     => ['Salaried',      'bi-building'],
                                'self_employed'=> ['Self-Employed', 'bi-shop'],
                                'business'     => ['Business Owner','bi-graph-up-arrow'],
                                'casual'       => ['Casual/Gig',    'bi-clock'],
                                'unemployed'   => ['Unemployed',    'bi-person-x'],
                                'student'      => ['Student',       'bi-mortarboard'],
                            ] as $v => [$l, $ic])
                            <label class="opt-card @if(old('employment_type','salaried')===$v) selected @endif">
                                <input type="radio" name="employment_type" value="{{ $v }}" required
                                       @checked(old('employment_type','salaried')===$v)>
                                <div class="opt-card-icon"><i class="bi {{ $ic }}"></i></div>
                                <span class="opt-card-label">{{ $l }}</span>
                                <div class="opt-card-check"><i class="bi bi-check"></i></div>
                            </label>
                            @endforeach
                        </div>
                        @error('employment_type')<span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                    </div>

                    {{-- Employer --}}
                    <div class="form-group">
                        <label for="employer_name">Employer / Business Name <span class="label-hint">optional</span></label>
                        <div class="form-input-icon">
                            <i class="bi bi-building"></i>
                            <input id="employer_name" name="employer_name" value="{{ old('employer_name') }}"
                                   class="form-input" placeholder="e.g. Safaricom PLC">
                        </div>
                    </div>

                    {{-- Job title --}}
                    <div class="form-group">
                        <label for="job_title">Job Title / Role <span class="label-hint">optional</span></label>
                        <div class="form-input-icon">
                            <i class="bi bi-person-badge"></i>
                            <input id="job_title" name="job_title" value="{{ old('job_title') }}"
                                   class="form-input" placeholder="e.g. Software Engineer">
                        </div>
                    </div>

                    <div class="form-section-divider form-group-full">Monthly Financials (KES)</div>

                    {{-- Monthly income --}}
                    <div class="form-group">
                        <label for="monthly_income">
                            Monthly Income <span style="color:#f04438">*</span>
                            <span class="kyc-ocr-pill" id="pillStmtIncome"
                                  style="display:none;background:linear-gradient(135deg,#f79009,#b54708)">Statement</span>
                        </label>
                        <div class="input-prefix @error('monthly_income') is-invalid @enderror">
                            <span class="input-pre">KES</span>
                            <input id="monthly_income" type="number" name="monthly_income"
                                   value="{{ old('monthly_income') }}" min="0" step="1"
                                   class="form-input" placeholder="0" required>
                        </div>
                        <span class="form-hint">Net take-home income per month</span>
                        @error('monthly_income')<span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                    </div>

                    {{-- Monthly expenses --}}
                    <div class="form-group">
                        <label for="monthly_expenses">
                            Monthly Expenses <span style="color:#f04438">*</span>
                            <span class="kyc-ocr-pill" id="pillStmtExpense"
                                  style="display:none;background:linear-gradient(135deg,#f79009,#b54708)">Statement</span>
                        </label>
                        <div class="input-prefix @error('monthly_expenses') is-invalid @enderror">
                            <span class="input-pre">KES</span>
                            <input id="monthly_expenses" type="number" name="monthly_expenses"
                                   value="{{ old('monthly_expenses') }}" min="0" step="1"
                                   class="form-input" placeholder="0" required>
                        </div>
                        <span class="form-hint">Recurring monthly obligations</span>
                        @error('monthly_expenses')<span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                    </div>

                    {{-- Existing debt --}}
                    <div class="form-group">
                        <label for="existing_debt">
                            Monthly Debt Service <span class="label-hint">optional</span>
                            <span class="kyc-ocr-pill" id="pillStmtDebt"
                                  style="display:none;background:linear-gradient(135deg,#f79009,#b54708)">Statement</span>
                        </label>
                        <div class="input-prefix">
                            <span class="input-pre">KES</span>
                            <input id="existing_debt" type="number" name="existing_debt"
                                   value="{{ old('existing_debt','0') }}" min="0" step="1"
                                   class="form-input" placeholder="0">
                        </div>
                        <span class="form-hint">Loans, hire-purchase, credit cards, etc.</span>
                    </div>

                    {{-- Other income --}}
                    <div class="form-group">
                        <label for="other_income">Other Monthly Income <span class="label-hint">optional</span></label>
                        <div class="input-prefix">
                            <span class="input-pre">KES</span>
                            <input id="other_income" type="number" name="other_income"
                                   value="{{ old('other_income','0') }}" min="0" step="1"
                                   class="form-input" placeholder="0">
                        </div>
                        <span class="form-hint">Rental, dividends, remittances, etc.</span>
                    </div>

                    <div class="form-section-divider form-group-full">Residence</div>

                    {{-- County --}}
                    <div class="form-group">
                        <label for="residence_county">County / Region <span style="color:#f04438">*</span></label>
                        <select id="residence_county" name="residence_county"
                                class="form-input @error('residence_county') is-invalid @enderror" required>
                            <option value="">Select county…</option>
                            @foreach(['Nairobi','Mombasa','Kisumu','Nakuru','Eldoret','Thika','Nyeri','Meru',
                                      'Kisii','Kakamega','Machakos','Kilifi','Garissa','Isiolo','Kajiado',
                                      'Kiambu','Kirinyaga','Kitui','Laikipia','Lamu','Makueni','Mandera',
                                      'Marsabit','Migori','Murang\'a','Nandi','Narok','Nithi','Nyamira',
                                      'Nyandarua','Samburu','Siaya','Taita-Taveta','Tana River','Trans Nzoia',
                                      'Turkana','Uasin Gishu','Vihiga','Wajir','West Pokot'] as $c)
                            <option value="{{ $c }}" @selected(old('residence_county')===$c)>{{ $c }}</option>
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
                                   value="{{ old('residence_address') }}"
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
                            <option value="own"     @selected(old('housing_status')==='own')>Owns Home</option>
                            <option value="renting" @selected(old('housing_status')==='renting')>Renting</option>
                            <option value="family"  @selected(old('housing_status')==='family')>Family Home</option>
                            <option value="other"   @selected(old('housing_status')==='other')>Other</option>
                        </select>
                    </div>

                    {{-- Years at address --}}
                    <div class="form-group">
                        <label for="years_at_address">Years at Current Address <span class="label-hint">optional</span></label>
                        <input id="years_at_address" type="number" name="years_at_address"
                               value="{{ old('years_at_address') }}" min="0" max="60" step="1"
                               class="form-input @error('years_at_address') is-invalid @enderror"
                               aria-invalid="{{ $errors->has('years_at_address') ? 'true' : 'false' }}"
                               aria-describedby="years_at_address_hint years_at_address_error"
                               placeholder="e.g. 3">
                        <span id="years_at_address_hint" class="form-hint">Enter a whole number from 0 to 60, or leave blank.</span>
                        @error('years_at_address')
                            <span id="years_at_address_error" class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>
                        @enderror
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
             STEP 4 · Next of Kin
        ════════════════════════════════ --}}
        <div class="wz-step-panel" data-panel="4">

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

                    {{-- NOK name --}}
                    <div class="form-group form-group-full">
                        <label for="next_of_kin_name">Full Name <span style="color:#f04438">*</span></label>
                        <div class="form-input-icon">
                            <i class="bi bi-person"></i>
                            <input id="next_of_kin_name" name="next_of_kin_name"
                                   value="{{ old('next_of_kin_name') }}"
                                   class="form-input @error('next_of_kin_name') is-invalid @enderror"
                                   placeholder="Next of kin full name" required>
                        </div>
                        @error('next_of_kin_name')<span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                    </div>

                    {{-- Relationship --}}
                    <div class="form-group">
                        <label for="next_of_kin_relationship">Relationship <span style="color:#f04438">*</span></label>
                        <select id="next_of_kin_relationship" name="next_of_kin_relationship"
                                class="form-input @error('next_of_kin_relationship') is-invalid @enderror" required>
                            <option value="">Select relationship…</option>
                            @foreach(['Spouse','Parent','Sibling','Child','Friend','Colleague','Other'] as $r)
                            <option value="{{ strtolower($r) }}" @selected(old('next_of_kin_relationship')===strtolower($r))>{{ $r }}</option>
                            @endforeach
                        </select>
                        @error('next_of_kin_relationship')<span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                    </div>

                    {{-- NOK phone --}}
                    <div class="form-group">
                        <label for="next_of_kin_phone">Phone Number <span style="color:#f04438">*</span></label>
                        <div class="input-prefix @error('next_of_kin_phone') is-invalid @enderror">
                            <span class="input-pre"><i class="bi bi-phone" style="margin-right:5px"></i>+254</span>
                            <input id="next_of_kin_phone" name="next_of_kin_phone"
                                   value="{{ old('next_of_kin_phone') }}"
                                   class="form-input" placeholder="7XX XXX XXX" maxlength="12" required>
                        </div>
                        @error('next_of_kin_phone')<span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                    </div>

                    {{-- NOK ID --}}
                    <div class="form-group">
                        <label for="next_of_kin_id_number">ID Number <span class="label-hint">optional</span></label>
                        <div class="form-input-icon">
                            <i class="bi bi-credit-card-2-front"></i>
                            <input id="next_of_kin_id_number" name="next_of_kin_id_number"
                                   value="{{ old('next_of_kin_id_number') }}"
                                   class="form-input" placeholder="National ID number">
                        </div>
                    </div>

                    {{-- NOK email --}}
                    <div class="form-group">
                        <label for="next_of_kin_email">Email <span class="label-hint">optional</span></label>
                        <div class="form-input-icon">
                            <i class="bi bi-envelope"></i>
                            <input id="next_of_kin_email" type="email" name="next_of_kin_email"
                                   value="{{ old('next_of_kin_email') }}"
                                   class="form-input" placeholder="nok@example.com">
                        </div>
                    </div>

                    {{-- Physical address --}}
                    <div class="form-group">
                        <label for="next_of_kin_address">Physical Address <span class="label-hint">optional</span></label>
                        <div class="form-input-icon">
                            <i class="bi bi-geo-alt"></i>
                            <input id="next_of_kin_address" name="next_of_kin_address"
                                   value="{{ old('next_of_kin_address') }}"
                                   class="form-input" placeholder="e.g. Kiambu Road, Nairobi">
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div class="form-group form-group-full">
                        <label for="next_of_kin_notes">Additional Notes <span class="label-hint">optional</span></label>
                        <textarea id="next_of_kin_notes" name="next_of_kin_notes" rows="2"
                                  class="form-input"
                                  placeholder="Any additional context about this contact…">{{ old('next_of_kin_notes') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="wz-nav">
                <button type="button" class="btn-wz-back" onclick="wzBack(4)">
                    <i class="bi bi-arrow-left"></i> Back
                </button>
                <div class="wz-nav-right">
                    <button type="button" class="btn-wz-next" onclick="wzNext(4)">
                        Continue <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════
             STEP 5 · Lending & Review
        ════════════════════════════════ --}}
        <div class="wz-step-panel" data-panel="5">

            {{-- Lending settings --}}
            <div class="form-card">
                <div class="form-card-head">
                    <div class="form-card-head-flex rose">
                        <div class="form-card-icon"><i class="bi bi-bank"></i></div>
                        <div>
                            <h2>Lending Profile</h2>
                            <p>Initial tier assignment and account standing.</p>
                        </div>
                    </div>
                </div>
                <div class="form-grid">
                    {{-- Automatic tier recommendation --}}
                    <div class="form-group form-group-full">
                        <label>Borrower Tier <span class="label-hint">assigned automatically</span></label>
                        <input type="hidden" name="current_tier_id" id="currentTierId"
                               value="{{ $recommendedTierId }}">
                        <div id="tierAutoSummary" class="form-hint" role="status" aria-live="polite"></div>
                        <div class="opt-grid" id="tierOptGrid"
                             data-entry-tier-id="{{ optional($tiers->firstWhere('is_entry_tier', true) ?: $tiers->first())->id }}"
                             data-financial-score="{{ $financialScore ?? '' }}">
                            @forelse($tiers as $t)
                            <div class="opt-card @if((int)$recommendedTierId===(int)$t->id) selected @endif"
                                 data-auto-tier
                                 data-tier-id="{{ $t->id }}"
                                 data-tier-number="{{ $t->tier_number }}"
                                 data-tier-name="{{ $t->name }}"
                                 data-min-financial-score="{{ $t->minimum_financial_score ?? '' }}"
                                 data-min-behaviour-score="{{ $t->minimum_behaviour_score ?? 0 }}"
                                 data-min-successful-loans="{{ $t->minimum_successful_loans }}"
                                 data-min-on-time-loans="{{ $t->minimum_on_time_loans }}"
                                 data-min-consecutive-on-time-loans="{{ $t->minimum_consecutive_on_time_loans }}">
                                <div class="opt-card-icon" style="font-size:13px;font-weight:700">T{{ $t->tier_number }}</div>
                                <span class="opt-card-label" style="font-size:10.5px">{{ $t->name }}</span>
                                 <span style="font-size:9px;color:#667085">
                                     Min score {{ number_format((float) $t->minimum_financial_score * 100) }}/100
                                 </span>
                                 <div class="opt-card-check"><i class="bi bi-check"></i></div>
                            </div>
                            @empty
                            <p style="font-size:12px;color:#98a2b3;padding:8px 0">No active tiers found. <a href="#" style="color:var(--primary)">Add a tier first.</a></p>
                            @endforelse
                        </div>
                        @error('current_tier_id')<span class="field-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</span>@enderror
                    </div>

                    {{-- Account status --}}
                    <div class="form-group form-group-full">
                        <label>Initial Account Status <span style="color:#f04438">*</span></label>
                        <div class="opt-grid" style="grid-template-columns:repeat(3,1fr)">
                            <label class="opt-card status-opt-active @if(old('account_status','active')==='active') selected @endif">
                                <input type="radio" name="account_status" value="active"
                                       @checked(old('account_status','active')==='active')>
                                <div class="opt-card-icon"><i class="bi bi-person-check"></i></div>
                                <span class="opt-card-label">Active</span>
                                <div class="opt-card-check"><i class="bi bi-check"></i></div>
                            </label>
                            <label class="opt-card status-opt-frozen @if(old('account_status')==='frozen') selected @endif">
                                <input type="radio" name="account_status" value="frozen"
                                       @checked(old('account_status')==='frozen')>
                                <div class="opt-card-icon"><i class="bi bi-person-lock"></i></div>
                                <span class="opt-card-label">Frozen</span>
                                <div class="opt-card-check"><i class="bi bi-check"></i></div>
                            </label>
                            <label class="opt-card status-opt-blacklisted @if(old('account_status')==='blacklisted') selected @endif">
                                <input type="radio" name="account_status" value="blacklisted"
                                       @checked(old('account_status')==='blacklisted')>
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
                                    <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>
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
                        <p>Double-check all details before creating the borrower profile.</p>
                    </div>
                </div>

                <div class="review-section">
                    <div class="review-section-head">
                        <span class="review-section-title"><i class="bi bi-person" style="margin-right:6px"></i>Personal</span>
                        <button type="button" class="review-edit-btn" onclick="wzGoto(1)"><i class="bi bi-pencil"></i> Edit</button>
                    </div>
                    <dl class="review-grid">
                        <div class="review-item"><dt>Full Name</dt><dd id="rv-name" class="empty">—</dd></div>
                        <div class="review-item"><dt>Phone</dt><dd id="rv-phone" class="empty">—</dd></div>
                        <div class="review-item"><dt>Email</dt><dd id="rv-email" class="empty">—</dd></div>
                        <div class="review-item"><dt>Date of Birth</dt><dd id="rv-dob" class="empty">—</dd></div>
                        <div class="review-item"><dt>Gender</dt><dd id="rv-gender" class="empty">—</dd></div>
                        <div class="review-item"><dt>Nationality</dt><dd id="rv-nationality" class="empty">—</dd></div>
                    </dl>
                </div>

                <div class="review-section">
                    <div class="review-section-head">
                        <span class="review-section-title"><i class="bi bi-shield-check" style="margin-right:6px"></i>KYC</span>
                        <button type="button" class="review-edit-btn" onclick="wzGoto(2)"><i class="bi bi-pencil"></i> Edit</button>
                    </div>
                    <dl class="review-grid">
                        <div class="review-item"><dt>Document Type</dt><dd id="rv-doctype" class="empty">—</dd></div>
                        <div class="review-item"><dt>Document Number</dt><dd id="rv-docnum" class="empty">—</dd></div>
                        <div class="review-item"><dt>Issuing Country</dt><dd id="rv-doccountry" class="empty">—</dd></div>
                        <div class="review-item"><dt>Verification</dt><dd id="rv-verifstatus" class="empty">—</dd></div>
                    </dl>
                </div>

                <div class="review-section">
                    <div class="review-section-head">
                        <span class="review-section-title"><i class="bi bi-briefcase" style="margin-right:6px"></i>Financial</span>
                        <button type="button" class="review-edit-btn" onclick="wzGoto(3)"><i class="bi bi-pencil"></i> Edit</button>
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
                        <span class="review-section-title"><i class="bi bi-bank" style="margin-right:6px"></i>Lending Tier</span>
                        <button type="button" class="review-edit-btn" onclick="wzGoto(5)"><i class="bi bi-pencil"></i> Review</button>
                    </div>
                    <dl class="review-grid">
                        <div class="review-item"><dt>Automatically Assigned Tier</dt><dd id="rv-tier" class="empty">—</dd></div>
                        <div class="review-item"><dt>Financial Score</dt><dd id="rv-financial-score" class="empty">—</dd></div>
                    </dl>
                </div>

                <div class="review-section">
                    <div class="review-section-head">
                        <span class="review-section-title"><i class="bi bi-people" style="margin-right:6px"></i>Next of Kin</span>
                        <button type="button" class="review-edit-btn" onclick="wzGoto(4)"><i class="bi bi-pencil"></i> Edit</button>
                    </div>
                    <dl class="review-grid">
                        <div class="review-item"><dt>Name</dt><dd id="rv-nokname" class="empty">—</dd></div>
                        <div class="review-item"><dt>Relationship</dt><dd id="rv-nokrel" class="empty">—</dd></div>
                        <div class="review-item"><dt>Phone</dt><dd id="rv-nokphone" class="empty">—</dd></div>
                    </dl>
                </div>
            </div>

            <div class="wz-nav">
                <button type="button" class="btn-wz-back" onclick="wzBack(5)">
                    <i class="bi bi-arrow-left"></i> Back
                </button>
                <div class="wz-nav-right">
                    <button type="submit" class="btn-wz-submit" id="wzSubmitBtn">
                        <i class="bi bi-person-plus-fill"></i> Create Borrower Profile
                    </button>
                </div>
            </div>
        </div>

    </div>{{-- /.wz-body --}}
</div>{{-- /.wz-shell --}}
</form>

<script>
(function () {
    'use strict';

    /* ── State ── */
    const TOTAL = 5;
    let current = {{ $errStep }};

    /* ── DOM helpers ── */
    const panels     = () => document.querySelectorAll('.wz-step-panel');
    const stepBtns   = () => document.querySelectorAll('.wz-step');
    const panel      = n  => document.querySelector(`.wz-step-panel[data-panel="${n}"]`);
    const stepBtn    = n  => document.querySelector(`.wz-step[data-step="${n}"]`);
    const progBar    = document.getElementById('wzProgressBar');
    const progText   = document.getElementById('wzProgText');
    const tierGrid   = document.getElementById('tierOptGrid');
    const tierInput  = document.getElementById('currentTierId');
    const tierSummary= document.getElementById('tierAutoSummary');

    window.updateAutomaticTier = function (financialScore) {
        if (!tierGrid || !tierInput) return;

        const tiles = Array.from(tierGrid.querySelectorAll('[data-auto-tier]'));
        const score = financialScore === null || financialScore === ''
            ? null
            : Number(financialScore);
        const hasScore = Number.isFinite(score) && score >= 0 && score <= 1;
        const eligible = hasScore
            ? tiles.filter(tile => {
                const minimumScore = tile.dataset.minFinancialScore;
                return minimumScore !== '' && score >= Number(minimumScore)
                    && Number(tile.dataset.minBehaviourScore || 0) <= 0
                    && Number(tile.dataset.minSuccessfulLoans || 0) === 0
                    && Number(tile.dataset.minOnTimeLoans || 0) === 0
                    && Number(tile.dataset.minConsecutiveOnTimeLoans || 0) === 0;
            }).sort((a, b) => Number(b.dataset.tierNumber) - Number(a.dataset.tierNumber))
            : [];

        const recommended = eligible[0]
            || tiles.find(tile => tile.dataset.tierId === tierGrid.dataset.entryTierId)
            || tiles[0];

        tiles.forEach(tile => tile.classList.toggle('selected', tile === recommended));
        tierInput.value = recommended ? recommended.dataset.tierId : '';
        tierGrid.dataset.financialScore = hasScore ? String(score) : '';

        if (recommended) {
            if (hasScore && recommended.dataset.tierId !== tierGrid.dataset.entryTierId) {
                tierSummary.textContent = `${recommended.dataset.tierName} is recommended based on the statement financial score (${Math.round(score * 100)}/100).`;
            } else if (hasScore) {
                tierSummary.textContent = `Financial score: ${Math.round(score * 100)}/100. No higher tier meets its configured score and borrower-history requirements, so ${recommended.dataset.tierName} is assigned.`;
            } else {
                tierSummary.textContent = `${recommended.dataset.tierName} is the entry tier. Upload and analyze an M-Pesa statement to assess eligibility for a higher tier.`;
            }
        } else {
            tierSummary.textContent = 'No active borrower tier is configured.';
        }
    };

    /* ── Activate step ── */
    function activate(n) {
        panels().forEach(p => p.classList.remove('active'));
        stepBtns().forEach(b => b.classList.remove('active'));
        panel(n).classList.add('active');
        stepBtn(n).classList.add('active');
        const pct = Math.round((n / TOTAL) * 100);
        progBar.style.width = pct + '%';
        progText.textContent = 'Step ' + n;
        current = n;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    /* ── Validation per step ── */
    function validateStep(n) {
        const p = panel(n);
        const required = p.querySelectorAll('[required]');
        let valid = true;
        let firstInvalid = null;
        required.forEach(el => {
            if (el.type === 'radio') {
                const group = p.querySelectorAll(`[name="${el.name}"]`);
                const checked = Array.from(group).some(r => r.checked);
                if (!checked) {
                    valid = false;
                    if (!firstInvalid) firstInvalid = el;
                    group.forEach(r => r.closest('.opt-card')?.classList.add('error-ring'));
                }
            } else if (!el.value.trim() || !el.checkValidity()) {
                valid = false;
                if (!firstInvalid) firstInvalid = el;
                el.classList.add('is-invalid');
                const clearInvalid = () => el.classList.remove('is-invalid');
                el.addEventListener('input', clearInvalid, { once: true });
                el.addEventListener('change', clearInvalid, { once: true });
            }
        });
        stepBtn(n).classList.toggle('error', !valid);
        if (!valid && p.classList.contains('active') && firstInvalid) {
            const target = firstInvalid.type === 'radio'
                ? firstInvalid.closest('.opt-card')
                : firstInvalid;
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstInvalid.focus({ preventScroll: true });
            if (firstInvalid.type !== 'radio') firstInvalid.reportValidity();
        }
        return valid;
    }

    /* ── Navigate ── */
    window.wzNext = function (from) {
        if (!validateStep(from)) return;
        markDone(from);
        if (from < TOTAL) {
            activate(from + 1);
            if (from + 1 === TOTAL) updateReview();
        }
    };
    window.wzBack = function (from) {
        activate(from - 1);
    };
    window.wzGoto = function (n) {
        activate(n);
    };

    /* ── Mark step done ── */
    function markDone(n) {
        const b = stepBtn(n);
        b.classList.remove('active', 'error');
        b.classList.add('done');
    }

    /* ── Opt-card radio behaviour ── */
    document.addEventListener('change', function (e) {
        if (e.target.type !== 'radio') return;
        const name = e.target.name;
        document.querySelectorAll(`[name="${name}"]`).forEach(r => {
            r.closest('.opt-card')?.classList.remove('selected', 'error-ring');
        });
        if (e.target.checked) {
            e.target.closest('.opt-card')?.classList.add('selected');
        }
    });

    /* ── Sidebar step navigation (click) ── */
    stepBtns().forEach(btn => {
        btn.addEventListener('click', function () {
            const target = parseInt(this.dataset.step);
            // Allow going back freely; going forward only if previous steps are done
            if (target < current || this.classList.contains('done')) {
                activate(target);
                if (target === TOTAL) updateReview();
            } else if (target === current) {
                // stay
            } else {
                // try to validate each step in sequence up to target
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

    /* ── Review panel population ── */
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
        const card = checked.closest('.opt-card');
        if (card) {
            const lbl = card.querySelector('.opt-card-label');
            return lbl ? lbl.textContent.trim() : checked.value;
        }
        return checked.value;
    }
    function selectLabel(name) {
        const el = document.querySelector(`[name="${name}"]`);
        if (!el || !el.selectedIndex) return '';
        return el.options[el.selectedIndex].text;
    }
    function fmtKes(v) {
        const n = parseInt(v || '0');
        return isNaN(n) ? '—' : 'KES ' + n.toLocaleString();
    }
    function set(id, text) {
        const el = document.getElementById(id);
        if (!el) return;
        const v = text.trim();
        el.textContent = v || '—';
        el.classList.toggle('empty', !v);
    }

    function updateReview() {
        set('rv-name',       val('name'));
        set('rv-phone',      val('phone_number') ? '+254 ' + val('phone_number') : '');
        set('rv-email',      val('email'));
        set('rv-dob',        val('date_of_birth'));
        set('rv-gender',     radioLabel('gender'));
        set('rv-nationality',selectLabel('nationality'));
        set('rv-doctype',    radioLabel('doc_type').replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase()));
        set('rv-docnum',     val('doc_number'));
        set('rv-doccountry', selectLabel('doc_country'));
        set('rv-verifstatus','Pending supervisor review');
        set('rv-employment', radioLabel('employment_type').replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase()));
        set('rv-employer',   val('employer_name'));
        set('rv-income',     fmtKes(val('monthly_income')));
        set('rv-expenses',   fmtKes(val('monthly_expenses')));
        set('rv-county',     selectLabel('residence_county'));
        set('rv-address',    val('residence_address'));
        set('rv-nokname',    val('next_of_kin_name'));
        set('rv-nokrel',     selectLabel('next_of_kin_relationship'));
        set('rv-nokphone',   val('next_of_kin_phone') ? '+254 ' + val('next_of_kin_phone') : '');
        const selectedTier = tierGrid?.querySelector(`[data-tier-id="${tierInput.value}"]`);
        set('rv-tier', selectedTier ? `T${selectedTier.dataset.tierNumber} · ${selectedTier.dataset.tierName}` : '');
        const financialScore = tierGrid?.dataset.financialScore;
        set('rv-financial-score', financialScore
            ? `${Math.round(Number(financialScore) * 100)} / 100 (statement analyzed)`
            : 'Statement not analyzed');
    }

    /* ── Submit guard ── */
    document.getElementById('wzForm').addEventListener('submit', function () {
        const btn = document.getElementById('wzSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-repeat" style="animation:spin .8s linear infinite"></i> Creating…';
    });

    /* ── Restore step on validation error ── */
    activate(current);
    window.updateAutomaticTier(tierGrid?.dataset.financialScore || null);
    // Mark all steps before error step as done
    for (let s = 1; s < current; s++) markDone(s);

    /* ── Error ring cleanup on typing ── */
    document.addEventListener('input', function(e) {
        e.target.closest('.opt-card')?.classList.remove('error-ring');
    });

})();
</script>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
.error-ring { border-color: #f04438 !important; background: #fff8f7 !important; }

/* ═══════════════════════════════════════════
   KYC DROP-ZONE & OCR UI
═══════════════════════════════════════════ */

/* Drop zone container */
.kyc-drop-zone {
    border: 2px dashed #d0d5dd;
    border-radius: 14px;
    background: #fafafa;
    min-height: 148px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    position: relative;
    transition: border-color .2s, background .2s;
    overflow: hidden;
}
.kyc-drop-zone:hover,
.kyc-drop-zone.drag-over {
    border-color: var(--primary);
    background: #f5f5ff;
}
.kyc-drop-zone.has-file {
    border-style: solid;
    border-color: #a5b4fc;
    background: #f5f5ff;
    cursor: default;
    min-height: 0;
}

/* Idle state */
.kyc-dz-idle {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 28px 20px;
    text-align: center;
    pointer-events: none;
}
.kyc-dz-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: #eef2ff;
    color: var(--primary);
    display: grid;
    place-items: center;
    font-size: 24px;
    margin-bottom: 2px;
}
.kyc-dz-title {
    font-size: 13px;
    font-weight: 650;
    color: #344054;
}
.kyc-dz-link {
    color: var(--primary);
    font-weight: 700;
    text-decoration: underline;
    cursor: pointer;
    pointer-events: auto;
}
.kyc-dz-sub {
    font-size: 10.5px;
    color: #98a2b3;
}

/* Preview state */
.kyc-dz-preview {
    width: 100%;
    position: relative;
}
.kyc-dz-preview img {
    width: 100%;
    max-height: 260px;
    object-fit: contain;
    display: block;
    border-radius: 12px;
    padding: 8px;
    background: #fff;
}
.kyc-dz-remove {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: none;
    background: rgba(16,24,40,.55);
    color: #fff;
    display: grid;
    place-items: center;
    font-size: 12px;
    cursor: pointer;
    transition: background .15s;
    backdrop-filter: blur(4px);
}
.kyc-dz-remove:hover { background: #b42318; }

/* Scan button */
.btn-kyc-scan {
    height: 40px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 0 20px;
    font-size: 12px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: opacity .2s, transform .1s, box-shadow .2s;
    box-shadow: 0 4px 14px rgba(99,102,241,.3);
}
.btn-kyc-scan:hover:not(:disabled) {
    opacity: .92;
    transform: translateY(-1px);
    box-shadow: 0 8px 22px rgba(99,102,241,.35);
}
.btn-kyc-scan:disabled {
    opacity: .6;
    cursor: not-allowed;
    transform: none;
}
.kyc-scan-hint {
    margin-left: 12px;
    font-size: 10px;
    color: #98a2b3;
    vertical-align: middle;
}

/* OCR result banner */
.kyc-banner {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 14px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 500;
}
.kyc-banner.success {
    background: #ecfdf3;
    color: #027a48;
    border: 1px solid #a9efc5;
}
.kyc-banner.error {
    background: #fef3f2;
    color: #b42318;
    border: 1px solid #fecdca;
}
.kyc-banner.warn {
    background: #fffaeb;
    color: #b54708;
    border: 1px solid #fde68a;
}
.kyc-banner i { font-size: 15px; flex-shrink: 0; }

/* Confidence chips */
.kyc-confidence-row {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.kyc-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 650;
    border: 1px solid transparent;
}
.kyc-chip.filled  { background: #ecfdf3; color: #027a48; border-color: #a9efc5; }
.kyc-chip.missing { background: #f9fafb; color: #98a2b3; border-color: #eaecf0; }

/* OCR auto-fill pill label */
.kyc-ocr-pill {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff;
    border-radius: 20px;
    padding: 1px 7px;
    font-size: 8.5px;
    font-weight: 700;
    letter-spacing: .03em;
    vertical-align: middle;
    margin-left: 5px;
}

/* Auto-filled field highlight */
.form-input.ocr-filled {
    border-color: #a5b4fc;
    background: #f5f5ff;
    transition: border-color .3s, background .3s;
}

/* OCR badge in fields card header */
.kyc-ocr-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff;
    border-radius: 20px;
    padding: 4px 11px;
    font-size: 10px;
    font-weight: 700;
}
.kyc-ocr-badge i { font-size: 11px; }

/* Scanning shimmer on drop-zone */
.kyc-drop-zone.scanning::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg,
        transparent 0%, rgba(99,102,241,.08) 40%,
        rgba(99,102,241,.14) 50%, rgba(99,102,241,.08) 60%,
        transparent 100%);
    background-size: 200% 100%;
    animation: kycShimmer 1.4s infinite;
    border-radius: 12px;
    pointer-events: none;
}
@keyframes kycShimmer {
    0%   { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
</style>

<script>
/* ═══════════════════════════════════════════════════════════════
   KYC IMAGE UPLOAD + OCR  —  drop-zone & auto-fill logic
═══════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    /* ── DOM refs ─────────────────────────────────────────────────── */
    const dropZone    = document.getElementById('kycDropZone');
    const fileInput   = document.getElementById('kycFileInput');
    const fileKeyEl   = document.getElementById('kycFileKey');
    const dzIdle      = document.getElementById('kycDzIdle');
    const dzPreview   = document.getElementById('kycDzPreview');
    const previewImg  = document.getElementById('kycPreviewImg');
    const removeBtn   = document.getElementById('kycRemoveBtn');
    const scanRow     = document.getElementById('kycScanRow');
    const scanBtn     = document.getElementById('kycScanBtn');
    const scanIcon    = document.getElementById('kycScanIcon');
    const scanLabel   = document.getElementById('kycScanLabel');
    const ocrBanner   = document.getElementById('kycOcrBanner');
    const confRow     = document.getElementById('kycConfidenceRow');
    const ocrBadge    = document.getElementById('kycOcrBadge');
    const fieldsSub   = document.getElementById('kycFieldsSubtitle');
    const rawTextEl   = document.getElementById('kycRawText');

    let selectedFile  = null;   // File object waiting to be uploaded+scanned

    /* ── Drop-zone click opens file picker ───────────────────────── */
    dropZone.addEventListener('click', (e) => {
        if (e.target === removeBtn || removeBtn.contains(e.target)) return;
        if (!dropZone.classList.contains('has-file')) fileInput.click();
    });
    document.querySelector('.kyc-dz-link')?.addEventListener('click', (e) => {
        e.stopPropagation();
        fileInput.click();
    });

    /* ── Drag & drop ─────────────────────────────────────────────── */
    dropZone.addEventListener('dragenter', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
    dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
    dropZone.addEventListener('dragleave', e => { if (!dropZone.contains(e.relatedTarget)) dropZone.classList.remove('drag-over'); });
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        const f = e.dataTransfer.files[0];
        if (f) handleFile(f);
    });

    /* ── File input change ───────────────────────────────────────── */
    fileInput.addEventListener('change', () => {
        if (fileInput.files[0]) handleFile(fileInput.files[0]);
    });

    /* ── Handle a chosen file ────────────────────────────────────── */
    function handleFile(file) {
        const allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!allowed.includes(file.type)) {
            showBanner('error', 'bi-exclamation-circle', 'Only JPEG, PNG and WebP images are supported.');
            return;
        }
        if (file.size > 6 * 1024 * 1024) {
            showBanner('error', 'bi-exclamation-circle', 'Image must be smaller than 6 MB.');
            return;
        }
        selectedFile = file;
        hideBanner();

        // Show thumbnail
        const reader = new FileReader();
        reader.onload = (ev) => {
            previewImg.src = ev.target.result;
            dzIdle.style.display    = 'none';
            dzPreview.style.display = 'block';
            dropZone.classList.add('has-file');
            scanRow.style.display   = 'flex';
            scanRow.style.alignItems = 'center';
        };
        reader.readAsDataURL(file);
    }

    /* ── Remove image ────────────────────────────────────────────── */
    removeBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        resetUpload();
    });

    function resetUpload() {
        selectedFile           = null;
        fileInput.value        = '';
        previewImg.src         = '';
        dzIdle.style.display   = 'flex';
        dzPreview.style.display= 'none';
        dropZone.classList.remove('has-file', 'scanning');
        scanRow.style.display  = 'none';
        scanBtn.disabled       = false;
        fileKeyEl.value        = '';
        hideBanner();
        confRow.style.display  = 'none';
        ocrBadge.style.display = 'none';
        fieldsSub.textContent  = 'Fill in manually or scan the document above to auto-populate.';
        clearOcrHighlights();
    }

    /* ── Scan button ─────────────────────────────────────────────── */
    scanBtn.addEventListener('click', () => {
        if (!selectedFile) return;
        runOcr(selectedFile);
    });

    async function prepareOcrImage(file) {
        if (typeof createImageBitmap !== 'function') return file;

        const bitmap = await createImageBitmap(file);
        const maxDimension = 1800;
        const scale = Math.min(1, maxDimension / Math.max(bitmap.width, bitmap.height));
        if (scale === 1 && file.size <= 1.5 * 1024 * 1024) {
            bitmap.close();
            return file;
        }

        const canvas = document.createElement('canvas');
        canvas.width = Math.round(bitmap.width * scale);
        canvas.height = Math.round(bitmap.height * scale);
        const context = canvas.getContext('2d');
        if (!context) {
            bitmap.close();
            throw new Error('Your browser could not prepare this image. Please try another image.');
        }
        context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
        bitmap.close();

        const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.88));
        if (!blob) throw new Error('Image optimization failed.');

        return new File([blob], 'id-scan.jpg', { type: 'image/jpeg' });
    }

    /* ── Upload + OCR ───────────────────────────────────────────────── */
    async function runOcr(file) {
        scanBtn.disabled         = true;
        scanIcon.className       = 'bi bi-arrow-repeat';
        scanIcon.style.animation = 'spin .8s linear infinite';
        scanLabel.textContent    = 'Preparing image…';
        dropZone.classList.add('scanning');
        hideBanner();
        confRow.style.display    = 'none';

        try {
            const scanFile = await prepareOcrImage(file);
            const formData = new FormData();
            formData.append('id_image', scanFile);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

            scanLabel.textContent = 'Uploading…';
            const uploadRes  = await fetch('{{ route("kyc.ocr") }}', { method: 'POST', body: formData });
            const uploadData = await uploadRes.json();

            if (!uploadRes.ok || !uploadData.success) {
                showBanner('error', 'bi-exclamation-circle-fill', uploadData.error || 'OCR failed.');
                dropZone.classList.remove('scanning');
                resetScanBtn();
                return;
            }

            if (uploadData.file_key) fileKeyEl.value = uploadData.file_key;
            if (!uploadData.job_id) throw new Error('OCR job was not queued.');

            const pollStarted = Date.now();
            const pollInterval = 1500;
            const maxWait = 60000;

            while (Date.now() - pollStarted < maxWait) {
                scanLabel.textContent = 'Scanning… (' + Math.round((Date.now() - pollStarted) / 1000) + 's)';
                await new Promise(resolve => setTimeout(resolve, pollInterval));

                const pollRes = await fetch('/kyc/ocr/' + encodeURIComponent(uploadData.job_id), {
                    headers: { 'Accept': 'application/json' }
                });
                if (!pollRes.ok) throw new Error('Could not check OCR status.');
                const pollData = await pollRes.json();
                if (!pollData.ready) continue;

                if (!pollData.success) {
                    showBanner('error', 'bi-exclamation-circle-fill',
                        pollData.error || 'OCR failed. Please fill the fields manually.');
                    dropZone.classList.remove('scanning');
                    resetScanBtn();
                    return;
                }

                if (pollData.file_key) fileKeyEl.value = pollData.file_key;
                dropZone.classList.remove('scanning');
                scanIcon.style.animation = '';
                applyOcrFields(pollData.fields || {});
                scanIcon.className    = 'bi bi-check-circle-fill';
                scanLabel.textContent = 'Scan complete';
                return;
            }

            dropZone.classList.remove('scanning');
            showBanner('error', 'bi-exclamation-circle-fill',
                'The scan is taking longer than expected. You can fill the fields manually or try again.');
            resetScanBtn();

        } catch (err) {
            dropZone.classList.remove('scanning');
            showBanner('error', 'bi-exclamation-circle-fill',
                err.message || 'Could not complete the scan. Please try again.');
            resetScanBtn();
        }
    }

    function resetScanBtn() {
        scanBtn.disabled          = false;
        scanIcon.className        = 'bi bi-magic';
        scanIcon.style.animation  = '';
        scanLabel.textContent     = 'Scan & Extract Fields';
    }

    /* ── Apply extracted fields to the form ──────────────────────── */
    function applyOcrFields(fields) {
        let filled = 0;
        const chips = [];

        // Helper: set an <input> or <select> and highlight it
        function setField(id, value, label) {
            if (!value) { chips.push({ label, filled: false }); return; }
            const el = document.getElementById(id);
            if (!el) return;
            el.value = value;
            el.classList.add('ocr-filled');
            chips.push({ label, filled: true });
            filled++;
        }

        // doc_number
        setField('doc_number', fields.doc_number, 'ID Number');
        if (fields.doc_number) showPill('pillDocNumber');

        // doc_country
        setField('doc_country', fields.doc_country, 'Country');
        if (fields.doc_country) showPill('pillDocCountry');

        // full_name — fills KYC name field AND step-1 name field
        if (fields.full_name) {
            setField('kyc_full_name', fields.full_name, 'Full Name');
            // Only pre-fill step-1 name if it's still empty
            const nameEl = document.getElementById('name');
            if (nameEl && !nameEl.value.trim()) {
                nameEl.value = fields.full_name;
                nameEl.classList.add('ocr-filled');
            }
            showPill('pillFullName');
        } else {
            chips.push({ label: 'Full Name', filled: false });
        }

        // date_of_birth — fills KYC DOB field AND step-1 DOB field
        if (fields.date_of_birth) {
            setField('kyc_dob', fields.date_of_birth, 'Date of Birth');
            const dobEl = document.getElementById('date_of_birth');
            if (dobEl && !dobEl.value) {
                dobEl.value = fields.date_of_birth;
                dobEl.classList.add('ocr-filled');
            }
            showPill('pillDob');
        } else {
            chips.push({ label: 'Date of Birth', filled: false });
        }

        // expiry_date
        if (fields.expiry_date) {
            setField('kyc_expiry', fields.expiry_date, 'Expiry');
            showPill('pillExpiry');
        }

        // doc_type — select radio card
        if (fields.doc_type) {
            const radioCard = document.querySelector(
                `#docTypeOptGrid [data-doctype="${fields.doc_type}"] input[type="radio"]`
            );
            if (radioCard) {
                // fire change event to trigger existing opt-card selected logic
                radioCard.checked = true;
                radioCard.dispatchEvent(new Event('change', { bubbles: true }));
                chips.push({ label: 'Doc Type', filled: true });
                filled++;
            } else {
                chips.push({ label: 'Doc Type', filled: false });
            }
        }

        // gender — select radio card in step 1
        if (fields.gender) {
            const genderRadio = document.querySelector(`[name="gender"][value="${fields.gender}"]`);
            if (genderRadio) {
                genderRadio.checked = true;
                genderRadio.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        // nationality — step 1 select
        if (fields.nationality) {
            const natEl = document.getElementById('nationality');
            if (natEl) {
                natEl.value = fields.nationality;
                if (!natEl.value) natEl.value = ''; // reset if code not in list
                natEl.classList.add('ocr-filled');
            }
        }

        // raw text for audit
        if (fields.raw_text) rawTextEl.value = fields.raw_text;

        // Build confidence chips
        renderChips(chips);

        // Show OCR badge and update subtitle
        ocrBadge.style.display = 'inline-flex';
        fieldsSub.textContent  = `${filled} field${filled !== 1 ? 's' : ''} auto-filled from the document image.`;

        // Success banner
        showBanner(
            'success',
            'bi-patch-check-fill',
            `${filled} field${filled !== 1 ? 's' : ''} extracted successfully. Review and correct any errors before continuing.`
        );

        // Remove highlights after 4 s so they don't feel permanent
        setTimeout(clearOcrHighlights, 4000);
    }

    /* ── Show/hide helpers ───────────────────────────────────────── */
    function showBanner(type, icon, message) {
        ocrBanner.style.display = 'block';
        ocrBanner.className     = `kyc-banner ${type}`;
        ocrBanner.innerHTML     = `<i class="bi ${icon}"></i><span>${message}</span>`;
    }
    function hideBanner() {
        ocrBanner.style.display = 'none';
        ocrBanner.innerHTML     = '';
    }
    function showPill(id) {
        const el = document.getElementById(id);
        if (el) el.style.display = 'inline-flex';
    }
    function renderChips(chips) {
        confRow.style.display = 'flex';
        confRow.innerHTML = chips.map(c =>
            `<span class="kyc-chip ${c.filled ? 'filled' : 'missing'}">
                <i class="bi bi-${c.filled ? 'check-circle-fill' : 'dash-circle'}"></i>
                ${c.label}
             </span>`
        ).join('');
    }
    function clearOcrHighlights() {
        document.querySelectorAll('.ocr-filled').forEach(el => el.classList.remove('ocr-filled'));
    }

    /* ── If a file_key already exists (e.g. validation error re-load),
          show a placeholder preview ──────────────────────────────── */
    if (fileKeyEl.value) {
        dzIdle.style.display    = 'none';
        dzPreview.style.display = 'block';
        dropZone.classList.add('has-file');
        previewImg.src          = '';   // can't re-display private file, show placeholder
        previewImg.alt          = 'Previously uploaded ID document';
        previewImg.style.cssText= 'min-height:80px;background:#eef2ff;border-radius:10px;padding:24px;';
        scanRow.style.display   = 'none'; // file already processed
        showBanner('warn', 'bi-exclamation-triangle-fill',
            'An ID image was previously uploaded. Scan again if you replaced the file.');
    }
})();
</script>

<script>
/* ═══════════════════════════════════════════════════════════════
   M-PESA STATEMENT UPLOAD — drop-zone, upload, auto-fill
═══════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    /* ── DOM refs ─────────────────────────────────────────── */
    const dropZone    = document.getElementById('stmtDropZone');
    const fileInput   = document.getElementById('stmtFileInput');
    const jobIdEl     = document.getElementById('stmtJobId');
    const fileKeyEl   = document.getElementById('stmtFileKey');
    const dzIdle      = document.getElementById('stmtDzIdle');
    const dzPreview   = document.getElementById('stmtDzPreview');
    const fileName    = document.getElementById('stmtFileName');
    const fileSize    = document.getElementById('stmtFileSize');
    const removeBtn   = document.getElementById('stmtRemoveBtn');
    const progressWrap= document.getElementById('stmtProgressWrap');
    const progressBar = document.getElementById('stmtProgressBar');
    const progressLbl = document.getElementById('stmtProgressLabel');
    const progressPct = document.getElementById('stmtProgressPct');
    const banner      = document.getElementById('stmtBanner');
    const resultCard  = document.getElementById('stmtResultCard');
    const autoFillBadge = document.getElementById('stmtAutoFillBadge');
    const fieldsSub   = document.getElementById('stmtFieldsSubtitle');

    let uploadXHR = null;  // track in-progress upload for cancel

    /* ── Drop-zone interactions ───────────────────────────── */
    dropZone.addEventListener('click', (e) => {
        if (e.target === removeBtn || removeBtn.contains(e.target)) return;
        if (!dropZone.classList.contains('has-file')) fileInput.click();
    });
    document.getElementById('stmtBrowse')?.addEventListener('click', (e) => {
        e.stopPropagation(); fileInput.click();
    });
    dropZone.addEventListener('dragenter', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
    dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
    dropZone.addEventListener('dragleave', e => { if (!dropZone.contains(e.relatedTarget)) dropZone.classList.remove('drag-over'); });
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        const f = e.dataTransfer.files[0];
        if (f) handleFile(f);
    });
    fileInput.addEventListener('change', () => {
        if (fileInput.files[0]) handleFile(fileInput.files[0]);
    });

    /* ── File chosen ──────────────────────────────────────── */
    function handleFile(file) {
        if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
            showBanner('error', 'bi-exclamation-circle-fill', 'Only PDF files are accepted.');
            return;
        }
        if (file.size > 20 * 1024 * 1024) {
            showBanner('error', 'bi-exclamation-circle-fill', 'File must be smaller than 20 MB.');
            return;
        }
        hideBanner();
        hideResult();

        // Show preview row
        fileName.textContent = file.name;
        fileSize.textContent = (file.size / 1024).toFixed(1) + ' KB';
        dzIdle.style.display    = 'none';
        dzPreview.style.display = 'block';
        dropZone.classList.add('has-file');

        uploadStatement(file);
    }

    /* ── Remove ───────────────────────────────────────────── */
    removeBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        if (uploadXHR) { uploadXHR.abort(); uploadXHR = null; }
        resetUpload();
    });

    function resetUpload() {
        fileInput.value = '';
        dzIdle.style.display    = 'flex';
        dzPreview.style.display = 'none';
        dropZone.classList.remove('has-file', 'scanning');
        progressWrap.style.display = 'none';
        setProgress(0, '');
        hideBanner();
        hideResult();
        jobIdEl.value   = '';
        fileKeyEl.value = '';
        window.updateAutomaticTier(null);
        autoFillBadge.style.display = 'none';
        fieldsSub.textContent = 'Confirm or correct the figures below.';
    }

    /* ── Upload via XHR (so we get progress events) ───────── */
    function uploadStatement(file) {
        progressWrap.style.display = 'block';
        setProgress(5, 'Uploading PDF…');
        dropZone.classList.add('scanning');

        const fd = new FormData();
        fd.append('statement', file);
        fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);

        const xhr = new XMLHttpRequest();
        uploadXHR  = xhr;

        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const pct = Math.round((e.loaded / e.total) * 40); // 0–40%
                setProgress(pct, 'Uploading…');
            }
        });

        xhr.addEventListener('load', () => {
            uploadXHR = null;
            dropZone.classList.remove('scanning');

            if (xhr.status >= 200 && xhr.status < 300) {
                setProgress(60, 'Parsing transactions…');
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (!data.success) {
                        progressWrap.style.display = 'none';
                        showBanner('error', 'bi-exclamation-circle-fill',
                            data.error || 'Could not parse the statement. Please check the file.');
                        return;
                    }
                    // Animate progress to 100%
                    animateProgress(60, 100, 600, () => {
                        progressWrap.style.display = 'none';
                        applyStatementResults(data);
                    });
                } catch (e) {
                    progressWrap.style.display = 'none';
                    showBanner('error', 'bi-exclamation-circle-fill', 'Unexpected server response.');
                }
            } else {
                progressWrap.style.display = 'none';
                let msg = 'Upload failed (HTTP ' + xhr.status + ').';
                try {
                    const err = JSON.parse(xhr.responseText);
                    if (err.error) msg = err.error;
                    if (err.message) msg = err.message;
                } catch (_) {}
                showBanner('error', 'bi-exclamation-circle-fill', msg);
            }
        });

        xhr.addEventListener('error', () => {
            uploadXHR = null;
            dropZone.classList.remove('scanning');
            progressWrap.style.display = 'none';
            showBanner('error', 'bi-exclamation-circle-fill', 'Network error — please try again.');
        });

        xhr.addEventListener('abort', () => {
            uploadXHR = null;
            dropZone.classList.remove('scanning');
            progressWrap.style.display = 'none';
        });

        xhr.open('POST', '{{ route("statements.upload") }}');
        xhr.send(fd);
    }

    /* ── Apply results to UI and form fields ──────────────── */
    function applyStatementResults(data) {
        // Store job_id and file_key in hidden fields for BorrowerController::store()
        jobIdEl.value   = data.job_id   || '';
        fileKeyEl.value = data.profile ? (data.file_key || '') : '';

        const p = data.profile || {};
        window.updateAutomaticTier(p.financial_score ?? null);

        // Auto-fill income & expense fields (only if still empty or user hasn't edited)
        autoFill('monthly_income',   Math.round(p.avg_income   || 0), 'pillStmtIncome');
        autoFill('monthly_expenses', Math.round(p.avg_expense  || 0), 'pillStmtExpense');
        autoFill('existing_debt',    Math.round(p.avg_debt_service || 0), 'pillStmtDebt');

        // Show result card
        showResultCard(data);

        // Badge + subtitle
        autoFillBadge.style.display = 'inline-flex';
        const filled = [p.avg_income, p.avg_expense, p.avg_debt_service].filter(v => v > 0).length;
        fieldsSub.textContent = filled + ' field' + (filled !== 1 ? 's' : '') +
            ' auto-filled from ' + (data.transaction_count || 0) +
            ' transactions across ' + (data.analysis_months || 0) + ' months.';

        showBanner('success', 'bi-patch-check-fill',
            'Statement analysed — ' + (data.transaction_count || 0) +
            ' transactions across ' + (data.analysis_months || 0) +
            ' months. Review and confirm the figures below.');

        // Fade out field highlights after 4 s
        setTimeout(() => {
            document.querySelectorAll('.ocr-filled').forEach(el => el.classList.remove('ocr-filled'));
        }, 4000);
    }

    function autoFill(fieldId, value, pillId) {
        if (value <= 0) return;
        const el = document.getElementById(fieldId);
        if (!el) return;
        // Only auto-fill if the field is empty or still at its default 0
        if (el.value === '' || el.value === '0' || el.value === '0.00') {
            el.value = value;
            el.classList.add('ocr-filled');
        }
        const pill = document.getElementById(pillId);
        if (pill) pill.style.display = 'inline-flex';
    }

    /* ── Result card population ───────────────────────────── */
    function showResultCard(data) {
        const p = data.profile || {};
        resultCard.style.display = 'block';

        // Period label
        const start = data.period_start ? data.period_start.slice(0,7) : '';
        const end   = data.period_end   ? data.period_end.slice(0,7)   : '';
        document.getElementById('stmtPeriodLabel').textContent =
            start && end ? start + ' → ' + end : '';

        // Financial score bar
        const score = Math.round((p.financial_score || 0) * 100);
        document.getElementById('stmtScoreVal').textContent   = score + ' / 100';
        document.getElementById('stmtScoreBar').style.width   = score + '%';
        document.getElementById('stmtScoreBar').style.background =
            score >= 70 ? 'linear-gradient(90deg,#12b76a,#027a48)'
          : score >= 45 ? 'linear-gradient(90deg,#f79009,#b54708)'
          :               'linear-gradient(90deg,#f04438,#b42318)';

        document.getElementById('stmtStabilityVal').textContent =
            Math.round((p.income_stability || 0) * 100) + '%';
        document.getElementById('stmtCashflowVal').textContent  =
            Math.round((p.cashflow_score  || 0) * 100) + '%';
        document.getElementById('stmtDebtRatioVal').textContent =
            Math.round((p.debt_service_ratio || 0) * 100) + '%';

        // KES amounts
        const fmt = (v) => 'KES ' + Number(v || 0).toLocaleString('en-KE', {minimumFractionDigits:0, maximumFractionDigits:0});
        document.getElementById('stmtAvgIncome').textContent   = fmt(p.avg_income);
        document.getElementById('stmtAvgExpense').textContent  = fmt(p.avg_expense);
        const net = (p.avg_income || 0) - (p.avg_expense || 0);
        document.getElementById('stmtAvgCashflow').textContent = fmt(net);
        document.getElementById('stmtAvgCashflow').style.color = net >= 0 ? '#027a48' : '#b42318';

        document.getElementById('stmtTxMeta').textContent =
            (data.transaction_count || 0) + ' transactions · ' +
            (data.analysis_months  || 0)  + ' months analysed';
    }

    function hideResult() {
        resultCard.style.display = 'none';
    }

    /* ── Progress helpers ─────────────────────────────────── */
    function setProgress(pct, label) {
        progressBar.style.width  = pct + '%';
        progressPct.textContent  = pct + '%';
        if (label) progressLbl.textContent = label;
    }

    function animateProgress(from, to, durationMs, callback) {
        const start = performance.now();
        function step(now) {
            const t = Math.min((now - start) / durationMs, 1);
            setProgress(Math.round(from + (to - from) * t),
                t < 1 ? 'Analysing transactions…' : 'Done');
            if (t < 1) requestAnimationFrame(step);
            else if (callback) callback();
        }
        requestAnimationFrame(step);
    }

    /* ── Banner helpers ───────────────────────────────────── */
    function showBanner(type, icon, msg) {
        banner.style.display = 'block';
        banner.className     = 'kyc-banner ' + type;
        banner.innerHTML     = '<i class="bi ' + icon + '"></i><span>' + msg + '</span>';
    }
    function hideBanner() {
        banner.style.display = 'none';
        banner.innerHTML     = '';
    }

    /* ── Restore state on validation error re-load ────────── */
    if (jobIdEl.value) {
        dzIdle.style.display    = 'none';
        dzPreview.style.display = 'block';
        dropZone.classList.add('has-file');
        fileName.textContent = 'Previously uploaded statement';
        fileSize.textContent = '';
        showBanner('warn', 'bi-exclamation-triangle-fill',
            'A statement was previously uploaded. Re-upload to re-analyse.');
    }
})();
</script>

@endsection
