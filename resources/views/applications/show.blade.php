@extends('layouts.app')

@section('title', 'Application #'.str_pad($application->id, 6, '0', STR_PAD_LEFT))

@push('styles')
<style>
/* ── Application review layout ───────────────────────────────── */
.application-review-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 20px;
    align-items: start;
}
@media (max-width: 900px) {
    .application-review-grid { grid-template-columns: 1fr; }
}

/* ── Score cards ─────────────────────────────────────────────── */
.score-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 12px;
    margin: 14px 0;
}
.score-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px 14px;
}
.score-card span   { font-size:.7rem; color:#64748b; display:block; margin-bottom:3px; }
.score-card strong { font-size:1.05rem; color:#0f172a; display:block; }
.score-bar { height:4px; background:#e2e8f0; border-radius:2px; overflow:hidden; margin-top:5px; }
.score-bar-fill { height:100%; border-radius:2px; }
.score-bar-fill.high   { background:#22c55e; }
.score-bar-fill.medium { background:#f59e0b; }
.score-bar-fill.low    { background:#ef4444; }

/* ── Financial profile breakdown ─────────────────────────────── */
.fin-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 7px 0;
    border-bottom: 1px solid #f1f5f9;
    font-size: .84rem;
}
.fin-row:last-child { border-bottom: none; }
.fin-row span  { color: #64748b; }
.fin-row strong { color: #0f172a; }
.fin-row.positive strong { color: #16a34a; }
.fin-row.negative strong { color: #dc2626; }
.fin-row.accent  strong  { color: #2563eb; }

/* ── Decision evidence accordion ─────────────────────────────── */
.snapshot-toggle {
    background: none;
    border: none;
    color: #2563eb;
    font-size: .8rem;
    cursor: pointer;
    padding: 4px 0;
    display: flex;
    align-items: center;
    gap: 5px;
}
.snapshot-toggle:hover { text-decoration: underline; }
.snapshot-body { display: none; margin-top: 12px; }
.snapshot-body.open { display: block; }
.snapshot-json {
    background: #0f172a;
    color: #e2e8f0;
    border-radius: 8px;
    padding: 14px 16px;
    font-size: .72rem;
    line-height: 1.6;
    overflow-x: auto;
    max-height: 320px;
    white-space: pre;
}

/* ── Reasons / warnings ──────────────────────────────────────── */
.reason-list, .warning-list { padding-left: 18px; margin: 6px 0 0; }
.reason-list  li { font-size:.82rem; color:#dc2626; margin-bottom:3px; }
.warning-list li { font-size:.82rem; color:#d97706; margin-bottom:3px; }

/* ── KYC strip ───────────────────────────────────────────────── */
.kyc-strip {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: .82rem;
    padding: 8px 12px;
    border-radius: 6px;
    margin-bottom: 10px;
}
.kyc-strip.verified { background:#f0fdf4; color:#166534; }
.kyc-strip.manual   { background:#eff6ff; color:#1e40af; }
.kyc-strip.pending  { background:#fffbeb; color:#92400e; }
.kyc-strip.failed   { background:#fef2f2; color:#991b1b; }
.kyc-strip.missing  { background:#f8fafc; color:#64748b; }
.kyc-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.kyc-dot.verified { background:#22c55e; }
.kyc-dot.manual   { background:#3b82f6; }
.kyc-dot.pending  { background:#f59e0b; }
.kyc-dot.failed   { background:#ef4444; }
.kyc-dot.missing  { background:#94a3b8; }

/* ── Eligibility badge in header ─────────────────────────────── */
.elig-badge {
    display:inline-flex; align-items:center; gap:5px;
    padding:3px 10px; border-radius:20px;
    font-size:.72rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase;
}
.elig-badge.eligible   { background:#dcfce7; color:#16a34a; }
.elig-badge.ineligible { background:#fee2e2; color:#dc2626; }
.elig-badge.pending    { background:#fef3c7; color:#d97706; }
.loan-progress-panel { margin-bottom:18px; }
.loan-progress-steps { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; padding:4px 0 14px; }
.loan-progress-step { position:relative; display:flex; align-items:center; gap:9px; min-width:0; color:#98a2b3; }
.loan-progress-step:not(:last-child)::after { position:absolute; z-index:0; left:31px; right:-4px; top:15px; height:2px; background:#eaecf0; content:""; }
.loan-progress-step.is-complete:not(:last-child)::after { background:#86efac; }
.loan-progress-marker { position:relative; z-index:1; display:grid; width:30px; height:30px; flex:0 0 30px; place-items:center; border:1px solid #eaecf0; border-radius:50%; background:#fff; color:#98a2b3; font-size:10px; font-weight:700; }
.loan-progress-label { position:relative; z-index:1; padding-right:5px; background:#fff; font-size:10px; font-weight:600; }
.loan-progress-step.is-complete { color:#087443; }
.loan-progress-step.is-complete .loan-progress-marker { border-color:#12b76a; background:#12b76a; color:#fff; }
.loan-progress-step.is-current { color:#4a4bcf; }
.loan-progress-step.is-current .loan-progress-marker { border-color:#5b5ce2; background:#f1f0ff; color:#5b5ce2; box-shadow:0 0 0 3px rgba(91,92,226,.1); }
.loan-progress-message { display:flex; align-items:flex-start; gap:8px; margin:0; padding:11px 12px; border:1px solid #e8e9ff; border-radius:10px; background:#f8f8ff; color:#475467; font-size:10px; line-height:1.55; }
.loan-progress-message i { color:#5b5ce2; font-size:13px; }
@media(max-width:600px) {
    .loan-progress-steps { grid-template-columns:repeat(2,minmax(0,1fr)); row-gap:14px; }
    .loan-progress-step:nth-child(2)::after { display:none; }
    .loan-progress-step:not(:last-child)::after { right:-10px; }
}
</style>
@endpush

@section('content')

@php
    $snap         = $application->decision_snapshot ?? [];
    $scores       = $snap['scores']       ?? [];
    $computation  = $snap['computation']  ?? [];
    $snapBorrower = $snap['borrower']     ?? [];
    $snapFp       = $snap['financial_profile'] ?? [];
    $snapTierRule = $snap['tier_rule']    ?? [];
    $snapProduct  = $snap['product']      ?? [];
    $snapReasons  = $snap['reasons']      ?? [];
    $snapWarnings = $snap['warnings']     ?? [];
    $security     = $application->security_data ?? [];
    $guarantor    = $security['guarantor'] ?? [];
    $collateral   = $security['collateral'] ?? [];

    $kycStatusClass = match($kyc?->verification_status ?? 'missing') {
        'verified'       => 'verified',
        'manual'         => 'manual',
        'pending'        => 'pending',
        'failed'         => 'failed',
        default          => 'missing',
    };

    $isDecided  = in_array($application->status, ['approved','rejected','declined']);
    $isPending  = in_array($application->status, ['pending','submitted','under_review']);
    $canEditApplication = !in_array($application->status, ['approved', 'rejected', 'declined', 'loan_created'], true)
        && (
            !in_array($application->status, ['submitted', 'under_review', 'first_level_approved'], true)
            || auth()->user()->hasRole(['admin', 'super-admin'])
        );
    $canReview = auth()->user()->hasPermission('loans.approve.first_level');
    $canFinalApprove = auth()->user()->hasPermission('loans.approve.final');
    $canReturn = auth()->user()->hasPermission('loans.return');
    $canReject = auth()->user()->hasPermission('loans.reject');
    $canVerifyCollateral = auth()->user()->hasPermission('collateral.verify');
    $canDisburse = auth()->user()->hasPermission('loans.disburse')
        && auth()->user()->hasRole(['admin', 'super-admin']);
    $supervisorApproved = (bool) $application->first_level_approved_by
        || in_array($application->status, ['approved', 'loan_created'], true)
        || $application->loan !== null;
    $adminApproved = (bool) $application->final_approved_by
        || in_array($application->status, ['approved', 'loan_created'], true)
        || $application->loan !== null;
    $loanDisbursed = $application->loan
        && ($application->loan->disbursed_at || in_array($application->loan->status, ['active', 'overdue', 'completed'], true));
    $progressMessage = match (true) {
        $application->status === 'draft' => 'Complete the guarantor and security information, then submit this application for supervisor review.',
        $application->status === 'returned' => 'The supervisor returned this application for correction. Update the requested details and resubmit it.',
        in_array($application->status, ['rejected', 'declined'], true) => 'This application was declined and will not proceed to disbursement.',
        $loanDisbursed => 'The loan has been disbursed. You can continue to monitor its repayment from Active Loans.',
        $application->loan !== null => 'Final approval is complete. An administrator is processing the disbursement.',
        $adminApproved => 'Final approval is complete. The application is waiting for an administrator to disburse the loan.',
        $supervisorApproved => 'Your supervisor approved the application. It is now waiting for final administrator approval.',
        default => 'Your application is in the supervisor review queue. Follow its progress here.',
    };

    // Format helpers
    $kes = fn(?int $cents) => $cents !== null ? 'KSh '.number_format($cents / 100, 2) : '—';
    $pct = fn($v) => $v !== null ? round($v * 100, 1).'%' : '—';
    $scoreClass = function($v) {
        if ($v === null) return 'medium';
        return $v >= 0.65 ? 'high' : ($v >= 0.35 ? 'medium' : 'low');
    };
    $scorePct = fn($v) => $v !== null ? round($v * 100) : null;
@endphp

<div class="page-header">
    <div>
        <div class="eyebrow">APPLICATION REVIEW</div>
        <h1>#{{ str_pad($application->id, 6, '0', STR_PAD_LEFT) }}</h1>
        <p class="page-subtitle">
            {{ $application->borrower->user->name ?? 'Unknown borrower' }}
            · {{ $application->loanProduct->name ?? 'Loan product' }}
        </p>
    </div>
    <div class="page-header-actions">
        @if($canEditApplication)
            <a href="{{ route('applications.edit', $application) }}" class="btn btn-light">
                <i class="bi bi-pencil"></i> Edit
            </a>
        @endif
        <a href="{{ route('applications.index') }}" class="btn btn-light">
            <i class="bi bi-arrow-left"></i> Applications
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
@if($application->status === 'returned' && $application->return_reason)
    <div class="alert alert-warning">
        <strong>Returned for correction:</strong> {{ $application->return_reason }}
    </div>
@endif

<section class="panel loan-progress-panel" aria-label="Loan application progress">
    <div class="panel-header">
        <div>
            <h2>Loan progress</h2>
            <p>Track this request through supervisor review, final approval and disbursement.</p>
        </div>
        <span class="status-pill status-{{ $application->loan?->status ?? $application->status }}">
            {{ str_replace('_', ' ', ucfirst($application->loan?->status ?? $application->status)) }}
        </span>
    </div>
    <div class="loan-progress-steps">
        @foreach([
            ['label' => 'Submitted', 'done' => !empty($application->submitted_at), 'current' => empty($application->submitted_at)],
            ['label' => 'Supervisor review', 'done' => $supervisorApproved, 'current' => !empty($application->submitted_at) && !$supervisorApproved && !in_array($application->status, ['returned', 'rejected', 'declined'], true)],
            ['label' => 'Admin approval', 'done' => $adminApproved, 'current' => $supervisorApproved && !$adminApproved],
            ['label' => 'Disbursement', 'done' => (bool) $loanDisbursed, 'current' => $adminApproved && !$loanDisbursed],
        ] as $step)
            <div class="loan-progress-step {{ $step['done'] ? 'is-complete' : ($step['current'] ? 'is-current' : '') }}">
                <span class="loan-progress-marker">
                    @if($step['done'])<i class="bi bi-check-lg"></i>@else{{ $loop->iteration }}@endif
                </span>
                <span class="loan-progress-label">{{ $step['label'] }}</span>
            </div>
        @endforeach
    </div>
    <p class="loan-progress-message"><i class="bi bi-info-circle"></i> {{ $progressMessage }}</p>
</section>

<div class="application-review-grid">

    {{-- ── Left column ────────────────────────────────────────────── --}}
    <div>

        {{-- Application summary --}}
        <div class="panel application-summary" style="margin-bottom:18px">
            <div class="panel-header">
                <div>
                    <h2>Application Summary</h2>
                    <p>Requested facility and current decision state.</p>
                </div>
                <span class="status-pill status-{{ $application->status }}">
                    {{ str_replace('_', ' ', ucfirst($application->status)) }}
                </span>
            </div>

            <div class="application-kpis">
                <div>
                    <span>Requested</span>
                    <strong>{{ $kes($application->requested_amount_cents) }}</strong>
                </div>
                <div>
                    <span>Term</span>
                    <strong>{{ $application->requested_term_days }} days</strong>
                </div>
                <div>
                    <span>Approved</span>
                    <strong>{{ $application->approved_amount_cents ? $kes($application->approved_amount_cents) : '—' }}</strong>
                </div>
                <div>
                    <span>Decision</span>
                    <strong>{{ $application->decision ? ucfirst($application->decision) : 'Pending' }}</strong>
                </div>
            </div>

            <div class="detail-list">
                <div><span>Product</span>       <strong>{{ $application->loanProduct->name ?? '—' }}</strong></div>
                <div><span>Product Code</span>  <strong>{{ $application->loanProduct->code ?? '—' }}</strong></div>
                <div><span>Submitted</span>     <strong>{{ optional($application->submitted_at ?? $application->created_at)->format('d M Y H:i') }}</strong></div>
                <div><span>Decided</span>       <strong>{{ optional($application->decided_at)->format('d M Y H:i') ?: '—' }}</strong></div>
                @if($application->branch)
                    <div><span>Branch</span><strong>{{ $application->branch->name }}</strong></div>
                @endif
                @if($application->creator)
                    <div><span>Originated by</span><strong>{{ $application->creator->name }}</strong></div>
                @endif
                @if($application->firstLevelApprover)
                    <div>
                        <span>Supervisor approval</span>
                        <strong>{{ $application->firstLevelApprover->name }} · {{ optional($application->first_level_approved_at)->format('d M Y H:i') }}</strong>
                    </div>
                @endif
                @if($application->finalApprover)
                    <div>
                        <span>Final approval</span>
                        <strong>{{ $application->finalApprover->name }} · {{ optional($application->final_approved_at)->format('d M Y H:i') }}</strong>
                    </div>
                @endif
                @if($application->loan_tier_rule_id)
                <div><span>Tier Rule ID</span>  <strong>#{{ $application->loan_tier_rule_id }} v{{ $application->tier_rule_version }}</strong></div>
                @endif
            </div>

            @if($application->decision_reason)
                <div class="decision-note" style="margin-top:12px;padding:10px 14px;background:#f8fafc;border-radius:6px;border-left:3px solid #94a3b8">
                    <span style="font-size:.72rem;color:#64748b;text-transform:uppercase;letter-spacing:.05em">Decision Reason</span>
                    <p style="margin:4px 0 0;font-size:.84rem">{{ $application->decision_reason }}</p>
                </div>
            @endif
        </div>

        @if($application->security_requirements_required || !empty($security))
            <div class="panel" style="margin-bottom:18px">
                <div class="panel-header">
                    <div>
                        <h2>Guarantor and collateral</h2>
                        <p>Document completeness is separate from identity and ownership verification.</p>
                    </div>
                    @if($application->guarantor_consent_at)
                        <span class="status-pill status-approved">Consent recorded</span>
                    @else
                        <span class="status-pill status-pending">Consent missing</span>
                    @endif
                </div>
                @if(!empty($guarantor))
                    <div class="detail-list">
                        <div><span>Guarantor</span><strong>{{ $guarantor['name'] ?? '—' }}</strong></div>
                        <div><span>National ID</span><strong>{{ $guarantor['national_id'] ?? '—' }}</strong></div>
                        <div><span>Phone</span><strong>{{ $guarantor['phone'] ?? '—' }}</strong></div>
                        <div><span>Relationship</span><strong>{{ $guarantor['relationship'] ?? '—' }}</strong></div>
                        <div><span>Address</span><strong>{{ $guarantor['address'] ?? '—' }}</strong></div>
                        @if(!empty($guarantor['email']))
                            <div><span>Email</span><strong>{{ $guarantor['email'] }}</strong></div>
                        @endif
                    </div>
                    <div class="detail-list" style="margin-top:12px">
                        @foreach([
                            'photo' => ['Guarantor photo', 'guarantor-photo'],
                            'id_front' => ['ID front', 'guarantor-id-front'],
                            'id_back' => ['ID back', 'guarantor-id-back'],
                        ] as $key => [$label, $fileKey])
                            <div>
                                <span>{{ $label }}</span>
                                <strong>
                                    @if(!empty($guarantor[$key]))
                                        <a href="{{ route('applications.security-file', [$application, $fileKey]) }}" target="_blank" rel="noopener">View private image</a>
                                    @else
                                        {{ $key === 'id_back' ? 'Not provided (optional)' : 'Missing' }}
                                    @endif
                                </strong>
                            </div>
                        @endforeach
                    </div>
                    <div class="secure-note"><i class="bi bi-shield-exclamation"></i><span>Identity verification: pending authorised review. Consent recorded {{ optional($application->guarantor_consent_at)->format('d M Y H:i') ?? '—' }}. Security data is retained for seven years after closure{{ $application->security_retention_until ? ' (until '.$application->security_retention_until->format('d M Y').')' : '' }}.</span></div>
                @else
                    <p class="page-subtitle">No guarantor details have been saved yet.</p>
                @endif

                <h3 style="margin-top:18px">Collateral ({{ count($collateral) }} items)</h3>
                @forelse($collateral as $index => $asset)
                    <div class="detail-list" style="padding:12px 0;border-top:1px solid #eef2f6">
                        <div><span>Asset</span><strong>{{ $asset['description'] ?? ($asset['type'] ?? 'Collateral') }}</strong></div>
                        <div><span>Owner</span><strong>{{ $asset['owner'] ?? '—' }}</strong></div>
                        <div><span>Estimated value</span><strong>{{ isset($asset['estimated_value_cents']) ? $kes($asset['estimated_value_cents']) : '—' }}</strong></div>
                        <div><span>Location</span><strong>{{ $asset['location'] ?? '—' }}</strong></div>
                        <div><span>Field visit</span><strong>{{ $asset['field_visit_date'] ?? '—' }}</strong></div>
                        <div><span>Condition</span><strong>{{ $asset['condition'] ?? '—' }}</strong></div>
                        <div><span>Ownership evidence</span><strong>{{ $asset['ownership_evidence'] ?? '—' }}</strong></div>
                        @php $verification = $asset['independent_verification'] ?? []; @endphp
                        <div>
                            <span>Independent verification</span>
                            <strong>{{ ucfirst($verification['status'] ?? 'pending') }}</strong>
                        </div>
                        @if(!empty($verification['notes']))
                            <div><span>Review notes</span><strong>{{ $verification['notes'] }}</strong></div>
                        @endif
                        @foreach($asset['photos'] ?? [] as $photoIndex => $photo)
                            <div>
                                <span>{{ $photo['caption'] ?? 'Photograph '.($photoIndex + 1) }}</span>
                                <strong><a href="{{ route('applications.security-file', [$application, "collateral-{$index}-{$photoIndex}"]) }}" target="_blank" rel="noopener">View private image</a></strong>
                            </div>
                        @endforeach
                        @if($canVerifyCollateral && $isPending)
                            <form method="POST" action="{{ route('applications.collateral.verify', [$application, $index]) }}" class="collateral-verification-form">
                                @csrf
                                <div class="form-field">
                                    <label>Independent collateral review</label>
                                    <select class="form-input" name="verification_status" required>
                                        <option value="" selected disabled>Select review outcome</option>
                                        <option value="verified">Ownership/evidence verified</option>
                                        <option value="issue">Issue found — do not approve</option>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label>Verification notes</label>
                                    <textarea class="form-input" name="verification_notes" rows="2" maxlength="2000" required placeholder="Record ownership, condition and any discrepancy"></textarea>
                                </div>
                                <button class="btn btn-light btn-sm" type="submit">Record verification</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="page-subtitle">No collateral recorded for this loan.</p>
                @endforelse
            </div>
        @endif

        {{-- ── Appraisal intelligence panel ─────────────────────── --}}
        <div class="panel" style="margin-bottom:18px">
            <div class="panel-header">
                <div>
                    <h2>Appraisal Intelligence</h2>
                    <p>Scores and eligibility computed by the appraisal engine at submission.</p>
                </div>
                @if(isset($snap['appraisal_model_version']))
                    <span class="elig-badge {{ empty($snapReasons) ? 'eligible' : 'ineligible' }}">
                        @if(empty($snapReasons))
                            <i class="bi bi-check-circle-fill"></i> Eligible
                        @else
                            <i class="bi bi-x-circle-fill"></i> Flagged
                        @endif
                    </span>
                @endif
            </div>

            {{-- Score cards --}}
            <div class="score-grid">
                @php
                    $blended  = $scores['blended_score']   ?? $application->financial_score;
                    $fScore   = $scores['financial_score'] ?? $application->financial_score;
                    $bScore   = $scores['behaviour_score'] ?? $application->behaviour_score;
                @endphp

                <div class="score-card">
                    <span>Blended Score</span>
                    <strong>{{ $scorePct($blended) !== null ? $scorePct($blended).' / 100' : '—' }}</strong>
                    @if($blended !== null)
                    <div class="score-bar"><div class="score-bar-fill {{ $scoreClass($blended) }}" style="width:{{ $scorePct($blended) }}%"></div></div>
                    @endif
                </div>

                <div class="score-card">
                    <span>Financial Score</span>
                    <strong>{{ $scorePct($fScore) !== null ? $scorePct($fScore).' / 100' : '—' }}</strong>
                    @if($fScore !== null)
                    <div class="score-bar"><div class="score-bar-fill {{ $scoreClass($fScore) }}" style="width:{{ $scorePct($fScore) }}%"></div></div>
                    @endif
                </div>

                <div class="score-card">
                    <span>Behaviour Score</span>
                    <strong>{{ $scorePct($bScore) !== null ? $scorePct($bScore).' / 100' : '—' }}</strong>
                    @if($bScore !== null)
                    <div class="score-bar"><div class="score-bar-fill {{ $scoreClass($bScore) }}" style="width:{{ $scorePct($bScore) }}%"></div></div>
                    @endif
                </div>

                @if(!empty($computation['max_offer_cents']))
                <div class="score-card">
                    <span>Engine Max Offer</span>
                    <strong>{{ $kes($computation['max_offer_cents']) }}</strong>
                </div>
                @endif

                @if(!empty($computation['score_factor']))
                <div class="score-card">
                    <span>Score Factor</span>
                    <strong>{{ round($computation['score_factor'] * 100) }}%</strong>
                </div>
                @endif

                <div class="score-card">
                    <span>Model</span>
                    <strong style="font-size:.82rem">{{ $application->appraisal_model_version ?? $snap['appraisal_model_version'] ?? '—' }}</strong>
                </div>
            </div>

            {{-- Reasons / warnings from snapshot --}}
            @if(!empty($snapReasons))
                <div style="margin-top:10px;padding:10px 14px;background:#fef2f2;border-radius:6px;border-left:3px solid #ef4444">
                    <span style="font-size:.72rem;color:#dc2626;font-weight:700;text-transform:uppercase;letter-spacing:.05em">Eligibility Flags</span>
                    <ul class="reason-list">@foreach($snapReasons as $r)<li>{{ $r }}</li>@endforeach</ul>
                </div>
            @endif

            @if(!empty($snapWarnings))
                <div style="margin-top:8px;padding:10px 14px;background:#fffbeb;border-radius:6px;border-left:3px solid #f59e0b">
                    <span style="font-size:.72rem;color:#d97706;font-weight:700;text-transform:uppercase;letter-spacing:.05em">Warnings</span>
                    <ul class="warning-list">@foreach($snapWarnings as $w)<li>{{ $w }}</li>@endforeach</ul>
                </div>
            @endif

            {{-- Computation detail accordion --}}
            @if(!empty($computation))
                <div style="margin-top:14px">
                    <button type="button" class="snapshot-toggle" onclick="this.nextElementSibling.classList.toggle('open');this.querySelector('i').classList.toggle('bi-chevron-up');this.querySelector('i').classList.toggle('bi-chevron-down')">
                        <i class="bi bi-chevron-down"></i> Computation Detail
                    </button>
                    <div class="snapshot-body">
                        <div class="detail-list" style="font-size:.82rem">
                            @foreach([
                                'hard_ceiling_cents'  => 'Hard Ceiling',
                                'affordability_cents' => 'Affordability Cap',
                                'modulated_cents'     => 'Score-modulated Offer',
                                'max_offer_cents'     => 'Final Max Offer',
                            ] as $key => $label)
                                @if(isset($computation[$key]))
                                    <div><span>{{ $label }}</span><strong>{{ $kes($computation[$key]) }}</strong></div>
                                @endif
                            @endforeach
                            @if(isset($computation['score_factor']))
                                <div><span>Score Factor</span><strong>{{ round($computation['score_factor']*100) }}% of ceiling</strong></div>
                            @endif
                            @if(isset($computation['declared_only']))
                                <div><span>Financial Data</span><strong>{{ $computation['declared_only'] ? 'Self-declared (conservative scale applied)' : 'M-Pesa statement' }}</strong></div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- ── Financial profile at time of appraisal ──────────── --}}
        @if(!empty($snapFp))
        <div class="panel" style="margin-bottom:18px">
            <div class="panel-header">
                <div>
                    <h2>Financial Profile</h2>
                    <p>
                        {{ $snapFp['is_declared_only'] ? 'Self-declared data' : 'M-Pesa statement · '.($snapFp['analysis_months'] ?? 0).' months' }}
                        @if(!empty($snapFp['calculated_at']))
                            · as at {{ \Carbon\Carbon::parse($snapFp['calculated_at'])->format('d M Y') }}
                        @endif
                    </p>
                </div>
                @if(!($snapFp['is_declared_only'] ?? true))
                    <span class="elig-badge eligible"><i class="bi bi-bank"></i> Verified</span>
                @else
                    <span class="elig-badge pending"><i class="bi bi-pencil"></i> Declared</span>
                @endif
            </div>

            <div class="fin-row positive">
                <span>Avg Monthly Income</span>
                <strong>KSh {{ number_format($snapFp['avg_monthly_income_kes'] ?? 0, 2) }}</strong>
            </div>
            <div class="fin-row negative">
                <span>Avg Monthly Expenses</span>
                <strong>KSh {{ number_format($snapFp['avg_monthly_expense_kes'] ?? 0, 2) }}</strong>
            </div>
            <div class="fin-row {{ ($snapFp['avg_monthly_net_cashflow_kes'] ?? 0) >= 0 ? 'positive' : 'negative' }}">
                <span>Net Cashflow</span>
                <strong>KSh {{ number_format($snapFp['avg_monthly_net_cashflow_kes'] ?? 0, 2) }}</strong>
            </div>
            <div class="fin-row negative">
                <span>Avg Debt Service</span>
                <strong>KSh {{ number_format($snapFp['avg_monthly_debt_service_kes'] ?? 0, 2) }}</strong>
            </div>
            <div class="fin-row accent">
                <span>Discretionary Cashflow</span>
                <strong>KSh {{ number_format($snapFp['avg_monthly_discretionary_kes'] ?? 0, 2) }}</strong>
            </div>
            @if(!empty($snapFp['avg_monthly_salary_kes']))
            <div class="fin-row">
                <span>Salary Component</span>
                <strong>KSh {{ number_format($snapFp['avg_monthly_salary_kes'], 2) }}</strong>
            </div>
            @endif
            @if(!empty($snapFp['avg_monthly_business_kes']))
            <div class="fin-row">
                <span>Business Income</span>
                <strong>KSh {{ number_format($snapFp['avg_monthly_business_kes'], 2) }}</strong>
            </div>
            @endif

            <div style="margin-top:12px" class="score-grid">
                <div class="score-card">
                    <span>Income Stability</span>
                    <strong>{{ $scorePct($snapFp['income_stability_score'] ?? null) !== null ? $scorePct($snapFp['income_stability_score']).' / 100' : '—' }}</strong>
                    @if(isset($snapFp['income_stability_score']))
                    <div class="score-bar"><div class="score-bar-fill {{ $scoreClass($snapFp['income_stability_score']) }}" style="width:{{ $scorePct($snapFp['income_stability_score']) }}%"></div></div>
                    @endif
                </div>
                <div class="score-card">
                    <span>Cashflow Score</span>
                    <strong>{{ $scorePct($snapFp['cashflow_score'] ?? null) !== null ? $scorePct($snapFp['cashflow_score']).' / 100' : '—' }}</strong>
                    @if(isset($snapFp['cashflow_score']))
                    <div class="score-bar"><div class="score-bar-fill {{ $scoreClass($snapFp['cashflow_score']) }}" style="width:{{ $scorePct($snapFp['cashflow_score']) }}%"></div></div>
                    @endif
                </div>
                <div class="score-card">
                    <span>Debt Service Ratio</span>
                    <strong>{{ isset($snapFp['debt_service_ratio']) ? round($snapFp['debt_service_ratio'] * 100, 1).'%' : '—' }}</strong>
                </div>
            </div>
        </div>
        @elseif($financialProfile)
        {{-- Live financial profile when no snapshot (older application) --}}
        <div class="panel" style="margin-bottom:18px">
            <div class="panel-header">
                <div><h2>Financial Profile (Live)</h2><p>Current profile on record — not from appraisal snapshot.</p></div>
            </div>
            <div class="fin-row positive">
                <span>Avg Monthly Income</span>
                <strong>KSh {{ number_format($financialProfile->average_monthly_income_cents / 100, 2) }}</strong>
            </div>
            <div class="fin-row negative">
                <span>Avg Monthly Expenses</span>
                <strong>KSh {{ number_format($financialProfile->average_monthly_expense_cents / 100, 2) }}</strong>
            </div>
            <div class="fin-row accent">
                <span>Discretionary Cashflow</span>
                <strong>KSh {{ number_format($financialProfile->average_monthly_discretionary_cashflow_cents / 100, 2) }}</strong>
            </div>
            <div class="score-grid" style="margin-top:10px">
                <div class="score-card">
                    <span>Financial Score</span>
                    <strong>{{ $scorePct($financialProfile->financial_score) !== null ? $scorePct($financialProfile->financial_score).' / 100' : '—' }}</strong>
                </div>
                <div class="score-card">
                    <span>Debt Service Ratio</span>
                    <strong>{{ $financialProfile->debt_service_ratio !== null ? round($financialProfile->debt_service_ratio * 100, 1).'%' : '—' }}</strong>
                </div>
            </div>
        </div>
        @endif

        {{-- Resulting loan --}}
        @if($application->loan)
            <div class="panel" style="margin-bottom:18px">
                <div class="panel-header">
                    <div>
                        <h2>Resulting Loan</h2>
                        <p>
                            @if($application->loan->status === 'active')
                                This loan has been disbursed and is active.
                            @elseif($application->loan->status === 'disbursement_pending' && $application->loan->disbursementTransaction?->status === 'failed')
                                M-Pesa did not confirm the transfer. Contact an administrator before retrying.
                            @elseif($application->loan->status === 'disbursement_pending')
                                M-Pesa disbursement is pending confirmation.
                            @elseif($canRetryDisbursement)
                                M-Pesa rejected the request before processing it. You can safely retry.
                            @elseif($application->loan->status === 'disbursement_failed')
                                M-Pesa disbursement failed. Contact an administrator before retrying.
                            @else
                                This application produced a loan.
                            @endif
                        </p>
                    </div>
                    <span class="status-pill status-{{ $application->loan->status }}">{{ ucfirst($application->loan->status) }}</span>
                </div>
                <div class="detail-list">
                    <div><span>Principal</span>       <strong>{{ $kes($application->loan->principal_cents) }}</strong></div>
                    <div><span>Total Payable</span>   <strong>{{ $kes($application->loan->total_payable_cents) }}</strong></div>
                    <div><span>Term</span>             <strong>{{ $application->loan->term_days }} days</strong></div>
                    <div>
                        <span>Disbursed</span>
                        <strong>
                            @if($application->loan->disbursed_at)
                                {{ $kes($application->loan->principal_cents) }} &middot; {{ \Carbon\Carbon::parse($application->loan->disbursed_at)->format('d M Y') }}
                            @else
                                —
                            @endif
                        </strong>
                    </div>
                </div>
                @if($canDisburse && $canRetryDisbursement)
                    <form method="POST" action="{{ route('applications.disburse', $application) }}"
                          onsubmit="return confirm('Retry the sandbox M-Pesa request for this loan?');"
                          style="margin-top:12px">
                        @csrf
                        <button class="btn btn-primary btn-block" type="submit">
                            <i class="bi bi-arrow-repeat"></i> Retry M-Pesa Disbursement
                        </button>
                    </form>
                @endif
            </div>
        @elseif($application->status === 'approved')
            <div class="panel" style="margin-bottom:18px">
                <div class="panel-header">
                    <div>
                        <h2>Disburse Approved Loan</h2>
                        <p>Send the approved amount to the borrower via M-Pesa.</p>
                    </div>
                </div>
                <div class="detail-list">
                    <div><span>Amount</span><strong>{{ $kes($application->approved_amount_cents) }}</strong></div>
                    <div><span>M-Pesa number</span><strong>{{ $application->borrower->phone_number }}</strong></div>
                </div>
                <p class="form-hint" style="margin:12px 0">
                    The loan will remain pending until M-Pesa confirms the transfer.
                </p>
                @if($canDisburse)
                    <form method="POST" action="{{ route('applications.disburse', $application) }}"
                          onsubmit="return confirm('Send {{ $kes($application->approved_amount_cents) }} to {{ $application->borrower->phone_number }} via M-Pesa?');">
                        @csrf
                        <button class="btn btn-primary btn-block" type="submit">
                            <i class="bi bi-send"></i> Disburse via M-Pesa
                        </button>
                    </form>
                @else
                    <div class="secure-note"><i class="bi bi-hourglass-split"></i> Final approval is complete. An administrator will initiate and confirm disbursement.</div>
                @endif
            </div>
        @endif

        {{-- Full decision snapshot accordion --}}
        @if(!empty($snap))
        <div class="panel">
            <button type="button" class="snapshot-toggle" style="width:100%;justify-content:space-between" onclick="this.nextElementSibling.classList.toggle('open');this.querySelector('i').classList.toggle('bi-chevron-up');this.querySelector('i').classList.toggle('bi-chevron-down')">
                <span><i class="bi bi-shield-lock"></i>&nbsp; Full Decision Snapshot (Audit)</span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="snapshot-body">
                <pre class="snapshot-json">{{ json_encode($snap, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>
        @endif

    </div>{{-- end left column --}}

    {{-- ── Right sidebar ──────────────────────────────────────────── --}}
    <aside>

        {{-- Borrower card --}}
        <div class="panel borrower-context" style="margin-bottom:18px">
            <div class="panel-header">
                <div>
                    <h2>Borrower</h2>
                    <p>Context at time of review.</p>
                </div>
            </div>

            {{-- KYC strip --}}
            @php $kycStatus = $kyc?->verification_status ?? 'missing'; @endphp
            <div class="kyc-strip {{ $kycStatusClass }}">
                <span class="kyc-dot {{ $kycStatusClass }}"></span>
                <span>
                    <strong>KYC {{ ucfirst($kycStatus) }}</strong>
                    @if($kyc?->document_type) · {{ str_replace('_',' ', $kyc->document_type) }} @endif
                    @if($kyc?->verified_at) · {{ \Carbon\Carbon::parse($kyc->verified_at)->format('d M Y') }} @endif
                </span>
            </div>

            <div class="borrower-mini">
                <span class="profile-avatar">{{ strtoupper(substr($application->borrower->user->name ?? 'B', 0, 1)) }}</span>
                <div>
                    <strong>{{ $application->borrower->user->name ?? 'Unknown' }}</strong>
                    <small>{{ $application->borrower->phone_number }}</small>
                    <small>{{ $application->borrower->user->email ?? '' }}</small>
                </div>
            </div>

            <div class="detail-list" style="margin-top:10px">
                <div><span>Account</span>            <strong>{{ ucfirst($application->borrower->account_status) }}</strong></div>
                <div><span>Tier</span>               <strong>{{ $application->borrower->currentTier->name ?? '—' }}</strong></div>
                <div><span>Total Loans</span>         <strong>{{ (int) ($borrowerLoanStats->total_loans_count ?? 0) }}</strong></div>
                <div><span>On-time</span>             <strong>{{ $application->borrower->on_time_loans_count }}</strong></div>
                <div><span>Consecutive On-time</span> <strong>{{ $application->borrower->consecutive_on_time_loans }}</strong></div>
                <div><span>Late</span>                <strong>{{ (int) ($borrowerLoanStats->late_loans_count ?? 0) }}</strong></div>
                <div><span>Defaults</span>            <strong>{{ (int) ($borrowerLoanStats->defaulted_loans_count ?? 0) }}</strong></div>
                <div><span>Behaviour Score</span>     <strong>{{ $application->borrower->behaviour_score ?? '—' }}</strong></div>
            </div>

            <a href="{{ route('borrowers.show', $application->borrower) }}" class="btn btn-light btn-block" style="margin-top:12px">
                View Borrower Profile
            </a>
        </div>

        {{-- Staged approval and decision controls --}}
        @if($isPending || $application->status === 'first_level_approved')
        <div class="panel decision-panel">
            <div class="panel-header">
                <div>
                    <h2>Approval workflow</h2>
                    <p>
                        @if(!empty($computation['max_offer_cents']))
                            Engine max: <strong>KSh {{ number_format($computation['max_offer_cents'] / 100, 2) }}</strong>
                        @else
                            Review evidence and record the appropriate decision.
                        @endif
                    </p>
                </div>
            </div>

            @if($application->firstLevelApprover)
                <div class="secure-note">
                    First-level approval recorded by {{ $application->firstLevelApprover->name }}
                    @if($application->first_level_approved_at) · {{ $application->first_level_approved_at->format('d M Y H:i') }} @endif
                    @if($application->first_level_reason)<br>{{ $application->first_level_reason }}@endif
                </div>
            @endif

            @if($canReview && $isPending)
                <form method="POST" action="{{ route('applications.first-approve', $application) }}">
                    @csrf
                    <div class="form-field">
                        <label>Supervisor review notes</label>
                        <textarea name="decision_reason" class="form-input" rows="3" required maxlength="5000" placeholder="Summarize document and collateral checks…"></textarea>
                    </div>
                    <button class="btn btn-primary btn-block" type="submit">
                        <i class="bi bi-check2-circle"></i> Record first-level approval
                    </button>
                </form>
            @endif

            @if($canFinalApprove && $application->status === 'first_level_approved')
                <form method="POST" action="{{ route('applications.approve', $application) }}">
                    @csrf
                    <div class="form-field">
                        <label>Approved Amount (KSh)</label>
                        <input type="number" step="0.01" min="0.01" name="approved_amount" class="form-input"
                               value="{{ $application->requested_amount_cents / 100 }}"
                               @if(!empty($computation['max_offer_cents'])) max="{{ $computation['max_offer_cents'] / 100 }}" @endif>
                        @if(!empty($computation['max_offer_cents']))
                            <small class="form-hint">Maximum eligible: KSh {{ number_format($computation['max_offer_cents'] / 100, 2) }}</small>
                        @endif
                    </div>
                    <div class="form-field">
                        <label>Approved term (days)</label>
                        <input type="number" min="1" name="approved_term_days" class="form-input" value="{{ $application->requested_term_days }}">
                    </div>
                    <div class="form-field">
                        <label>Final decision notes</label>
                        <textarea name="decision_reason" class="form-input" rows="3" maxlength="5000" placeholder="Reason for final approval…"></textarea>
                    </div>
                    <button class="btn btn-primary btn-block" type="submit">
                        <i class="bi bi-check2"></i> Final admin approval
                    </button>
                </form>
            @endif

            @if(($canReturn || $canReject) && ($isPending || $application->status === 'first_level_approved'))
                @if($canReturn)
                    <div class="decision-divider">Request corrections</div>
                    <form method="POST" action="{{ route('applications.return', $application) }}">
                        @csrf
                        <div class="form-field">
                            <label>Correction reason</label>
                            <textarea name="return_reason" class="form-input" rows="3" required maxlength="5000"></textarea>
                        </div>
                        <button class="btn btn-light btn-block" type="submit">Return to originator</button>
                    </form>
                @endif
                @if($canReject)
                    <div class="decision-divider">Reject application</div>
                    <form method="POST" action="{{ route('applications.reject', $application) }}">
                        @csrf
                        <div class="form-field">
                            <label>Rejection reason</label>
                            <textarea name="decision_reason" class="form-input" rows="3" required maxlength="5000"></textarea>
                        </div>
                        <button class="btn btn-danger-outline btn-block" type="submit">
                            <i class="bi bi-x-lg"></i> Reject application
                        </button>
                    </form>
                @endif
            @endif
        </div>
        @endif

    </aside>

</div>
@endsection
