@extends('layouts.app')
@section('title', $borrowerRecord->name)
@section('content')

@php
    $statusClass = match($borrowerRecord->account_status) {
        'active'      => 'success',
        'frozen'      => 'warning',
        default       => 'danger',
    };
@endphp

{{-- Breadcrumb --}}
<div class="bw-breadcrumb">
    <a href="{{ route('borrowers.index') }}"><i class="bi bi-people"></i> Borrowers</a>
    <i class="bi bi-chevron-right"></i>
    <span>{{ $borrowerRecord->name }}</span>
</div>

@if(session('success'))
    <div class="alert-banner success"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert-banner danger"><i class="bi bi-exclamation-circle-fill"></i> {{ session('error') }}</div>
@endif

{{-- Hero card --}}
<div class="bw-hero panel" style="padding:0;margin-bottom:18px">
    <div class="bw-hero-body">
        <div class="bw-hero-avatar">{{ strtoupper(substr($borrowerRecord->name,0,2)) }}</div>
        <div class="bw-hero-info">
            <div class="bw-hero-name">{{ $borrowerRecord->name }}</div>
            <div class="bw-hero-meta">
                <span><i class="bi bi-hash"></i> BR-{{ str_pad($borrowerRecord->id,5,'0',STR_PAD_LEFT) }}</span>
                <span><i class="bi bi-phone"></i> {{ $borrowerRecord->phone_number }}</span>
                @if($borrowerRecord->email)
                    <span><i class="bi bi-envelope"></i> {{ $borrowerRecord->email }}</span>
                @endif
            </div>
            <div class="bw-hero-tags">
                <span class="tag">{{ $borrowerRecord->tier_name ?: 'Tier '.$borrowerRecord->current_tier_id }}</span>
                <span class="status-pill {{ $statusClass }}">{{ ucfirst($borrowerRecord->account_status) }}</span>
                <span class="text-muted-sm">Joined {{ \Carbon\Carbon::parse($borrowerRecord->created_at)->format('d M Y') }}</span>
            </div>
        </div>
    </div>
    <div class="bw-hero-actions">
        @if(auth()->user()->hasPermission('edit-borrowers'))
            <a href="{{ route('borrowers.edit',$borrowerRecord->id) }}" class="btn btn-light">
                <i class="bi bi-pencil"></i> Edit
            </a>
        @endif
        @if(auth()->user()->hasPermission('delete-borrowers'))
            <form method="POST" action="{{ route('borrowers.destroy',$borrowerRecord->id) }}"
                  onsubmit="return confirm('Permanently delete this borrower?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger-soft">
                    <i class="bi bi-trash3"></i> Delete
                </button>
            </form>
        @endif
    </div>
</div>

{{-- Stats row --}}
<div class="stats-grid" style="margin-bottom:18px">
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-bank"></i></div>
        <div class="stat-label">Lifetime loans</div>
        <div class="stat-value">{{ number_format((int)$borrowerRecord->lifetime_loans_count) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#ecfdf3;color:#027a48"><i class="bi bi-check2-circle"></i></div>
        <div class="stat-label">On-time streak</div>
        <div class="stat-value">{{ number_format((int)$borrowerRecord->consecutive_on_time_loans) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fff7ed;color:#c2410c"><i class="bi bi-star"></i></div>
        <div class="stat-label">Behaviour score</div>
        <div class="stat-value">{{ number_format((float)$borrowerRecord->behaviour_score, 4) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#f0fdf4;color:#166534"><i class="bi bi-cash-coin"></i></div>
        <div class="stat-label">Max loan amount</div>
        <div class="stat-value">KSh {{ number_format(($borrowerRecord->max_loan_amount_cents ?? 0)/100, 0) }}</div>
    </div>
</div>

{{-- Main grid --}}
<div class="dashboard-grid" style="margin-bottom:18px">

    {{-- Tier & Rating Summary --}}
    <div class="panel panel-wide" style="padding:0">
        <div class="panel-head" style="padding:16px 18px 14px;border-bottom:1px solid var(--line)">
            <div>
                <h2>Tier & Rating Summary</h2>
                <p>Current lending tier and performance metrics.</p>
            </div>
        </div>
        <div style="padding:18px">
            <div class="form-grid">
                <div class="form-group">
                    <label class="text-muted-sm">Current Tier</label>
                    <div style="font-size:1.1rem;font-weight:600">Tier {{ $borrowerRecord->tier_number ?? '—' }} · {{ $borrowerRecord->tier_name ?? 'Unassigned' }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Behaviour Score</label>
                    <div style="font-size:1.1rem;font-weight:600">{{ number_format((float)$borrowerRecord->behaviour_score, 4) }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Max Loan Amount</label>
                    <div style="font-size:1.1rem;font-weight:600">KSh {{ number_format(($borrowerRecord->max_loan_amount_cents ?? 0)/100, 0) }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Min Loan Amount</label>
                    <div style="font-size:1.1rem;font-weight:600">KSh {{ number_format(($borrowerRecord->min_loan_amount_cents ?? 0)/100, 0) }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Max Term Days</label>
                    <div style="font-size:1.1rem;font-weight:600">{{ $borrowerRecord->max_term_days ?? '—' }} days</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Successful Loans</label>
                    <div style="font-size:1.1rem;font-weight:600">{{ number_format((int)$borrowerRecord->successful_loans_count) }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">On-time Loans</label>
                    <div style="font-size:1.1rem;font-weight:600">{{ number_format((int)$borrowerRecord->on_time_loans_count) }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Late Loans</label>
                    <div style="font-size:1.1rem;font-weight:600">{{ number_format((int)$borrowerRecord->late_loans_count) }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Defaulted Loans</label>
                    <div style="font-size:1.1rem;font-weight:600">{{ number_format((int)$borrowerRecord->defaulted_loans_count) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Loans --}}
    <div class="panel panel-wide" style="padding:0">
        <div class="panel-head" style="padding:16px 18px 14px;border-bottom:1px solid var(--line)">
            <div>
                <h2>Loans</h2>
                <p>Current and historical lending relationships.</p>
            </div>
            <a href="#" class="btn btn-light btn-sm"><i class="bi bi-plus-lg"></i> New loan</a>
        </div>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Loan ID</th>
                        <th>Product</th>
                        <th>Principal</th>
                        <th>Payable</th>
                        <th>Status</th>
                        <th>Disbursed</th>
                        <th>Matures</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loans as $loan)
                    <tr>
                        <td><strong>LN-{{ str_pad($loan->id,5,'0',STR_PAD_LEFT) }}</strong></td>
                        <td>{{ $loan->product_name ?: '—' }}</td>
                        <td>KSh {{ number_format($loan->principal_cents/100,2) }}</td>
                        <td>KSh {{ number_format($loan->total_payable_cents/100,2) }}</td>
                        <td>
                            @php $lsc=match($loan->status){'active'=>'success','overdue'=>'warning','defaulted','written_off'=>'danger',default=>'info'}; @endphp
                            <span class="status-pill {{ $lsc }}">{{ ucwords(str_replace('_',' ',$loan->status)) }}</span>
                        </td>
                        <td class="text-muted-sm">{{ $loan->disbursed_at ? \Carbon\Carbon::parse($loan->disbursed_at)->format('d M Y') : '—' }}</td>
                        <td class="text-muted-sm">{{ $loan->matures_at ? \Carbon\Carbon::parse($loan->matures_at)->format('d M Y') : '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="empty-row">No loans recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Repayments --}}
    <div class="panel" style="padding:0">
        <div class="panel-head" style="padding:16px 18px 14px;border-bottom:1px solid var(--line)">
            <div><h2>Recent repayments</h2><p>Latest posted payments.</p></div>
        </div>
        <div style="padding:4px 0">
            @forelse($repayments as $payment)
            <div class="activity">
                <div class="activity-icon"><i class="bi bi-arrow-down-left"></i></div>
                <div>
                    <strong>KSh {{ number_format($payment->amount_cents/100,2) }}</strong>
                    <p>{{ strtoupper($payment->payment_method) }} · {{ $payment->mpesa_receipt_number ?: 'Posted' }}</p>
                </div>
                <time>{{ $payment->received_at ? \Carbon\Carbon::parse($payment->received_at)->format('d M') : '—' }}</time>
            </div>
            @empty
            <div class="empty-state"><i class="bi bi-receipt"></i><p>No repayments recorded.</p></div>
            @endforelse
        </div>
    </div>
</div>

{{-- Applications --}}
<div class="panel" style="padding:0">
    <div class="panel-head" style="padding:16px 18px 14px;border-bottom:1px solid var(--line)">
        <div><h2>Loan applications</h2><p>Application and appraisal history.</p></div>
        <a href="{{ route('applications.create') }}" class="btn btn-light btn-sm"><i class="bi bi-plus-lg"></i> New application</a>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Application</th>
                    <th>Product</th>
                    <th>Requested</th>
                    <th>Approved</th>
                    <th>Decision</th>
                    <th>Status</th>
                    <th>Submitted</th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $app)
                <tr>
                    <td><strong>LA-{{ str_pad($app->id,5,'0',STR_PAD_LEFT) }}</strong></td>
                    <td>{{ $app->product_name ?: '—' }}</td>
                    <td>KSh {{ number_format($app->requested_amount_cents/100,2) }}</td>
                    <td>{{ $app->approved_amount_cents !== null ? 'KSh '.number_format($app->approved_amount_cents/100,2) : '—' }}</td>
                    <td>
                        @if($app->decision)
                            @php $dc=match($app->decision){'approved'=>'success','rejected'=>'danger',default=>'info'}; @endphp
                            <span class="status-pill {{ $dc }}">{{ ucfirst($app->decision) }}</span>
                        @else
                            <span class="text-muted-sm">Pending</span>
                        @endif
                    </td>
                    <td><span class="status-pill info">{{ ucwords(str_replace('_',' ',$app->status)) }}</span></td>
                    <td class="text-muted-sm">{{ $app->submitted_at ? \Carbon\Carbon::parse($app->submitted_at)->format('d M Y') : '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="empty-row">No loan applications recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Comprehensive Borrower Details --}}
<div class="dashboard-grid" style="margin-bottom:18px">

    {{-- Personal Information --}}
    <div class="panel panel-wide" style="padding:0">
        <div class="panel-head" style="padding:16px 18px 14px;border-bottom:1px solid var(--line)">
            <div>
                <h2>Personal Information</h2>
                <p>Borrower's personal and contact details.</p>
            </div>
        </div>
        <div style="padding:18px">
            @php
                $profile = $profileData ? json_decode($profileData->payload, true) : [];
            @endphp
            <div class="form-grid">
                <div class="form-group">
                    <label class="text-muted-sm">Full Name</label>
                    <div style="font-size:1.1rem;font-weight:600">{{ $borrowerRecord->name }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Phone Number</label>
                    <div style="font-size:1.1rem;font-weight:600">{{ $borrowerRecord->phone_number }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Email</label>
                    <div style="font-size:1.1rem;font-weight:600">{{ $borrowerRecord->email ?: '—' }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Date of Birth</label>
                    <div style="font-size:1.1rem;font-weight:600">{{ $kycRecord && $kycRecord->date_of_birth ? \Carbon\Carbon::parse($kycRecord->date_of_birth)->format('d M Y') : '—' }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Gender</label>
                    <div style="font-size:1.1rem;font-weight:600">{{ ucfirst($profile['gender'] ?? '—') }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Nationality</label>
                    <div style="font-size:1.1rem;font-weight:600">{{ strtoupper($profile['nationality'] ?? '—') }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Preferred Language</label>
                    <div style="font-size:1.1rem;font-weight:600">{{ strtoupper($profile['preferred_language'] ?? '—') }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Registration Date</label>
                    <div style="font-size:1.1rem;font-weight:600">{{ $borrowerRecord->registered_at ? \Carbon\Carbon::parse($borrowerRecord->registered_at)->format('d M Y') : '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- KYC/ID Verification --}}
    <div class="panel" style="padding:0">
        <div class="panel-head" style="padding:16px 18px 14px;border-bottom:1px solid var(--line)">
            <div>
                <h2>KYC Verification</h2>
                <p>Identity document verification status and officer decision.</p>
            </div>
            @if($kycRecord)
                @php
                    $kycBadgeClass = match($kycRecord->verification_status) {
                        'verified' => 'success',
                        'manual'   => 'info',
                        'failed'   => 'danger',
                        default    => 'warning',
                    };
                @endphp
                <span class="status-pill {{ $kycBadgeClass }}">
                    {{ ucfirst($kycRecord->verification_status) }}
                </span>
            @endif
        </div>
        <div style="padding:18px">
            @if($kycRecord)
                {{-- Document details --}}
                <div class="form-grid" style="margin-bottom:18px">
                    <div class="form-group">
                        <label class="text-muted-sm">Document Type</label>
                        <div style="font-size:1.05rem;font-weight:600">{{ ucfirst(str_replace('_', ' ', $kycRecord->document_type)) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="text-muted-sm">Document Number</label>
                        <div style="font-size:1.05rem;font-weight:600">{{ $kycRecord->document_number ?: '—' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="text-muted-sm">Country</label>
                        <div style="font-size:1.05rem;font-weight:600">{{ strtoupper($kycRecord->document_country_code ?? '—') }}</div>
                    </div>
                    <div class="form-group">
                        <label class="text-muted-sm">Full Name (KYC)</label>
                        <div style="font-size:1.05rem;font-weight:600">{{ $kycRecord->full_name ?: '—' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="text-muted-sm">Date of Birth</label>
                        <div style="font-size:1.05rem;font-weight:600">{{ $kycRecord->date_of_birth ? \Carbon\Carbon::parse($kycRecord->date_of_birth)->format('d M Y') : '—' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="text-muted-sm">Provider</label>
                        <div style="font-size:1.05rem;font-weight:600">{{ $kycRecord->verification_provider ?: '—' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="text-muted-sm">Verified At</label>
                        <div style="font-size:1.05rem;font-weight:600">
                            {{ $kycRecord->verified_at ? \Carbon\Carbon::parse($kycRecord->verified_at)->format('d M Y H:i') : '—' }}
                        </div>
                    </div>
                    @if($kycRecord->verification_notes)
                    <div class="form-group form-group-full">
                        <label class="text-muted-sm">Notes</label>
                        <div style="font-size:.95rem;background:#f8fafc;padding:8px 12px;border-radius:6px;border:1px solid #e2e8f0">{{ $kycRecord->verification_notes }}</div>
                    </div>
                    @endif
                </div>

                @if(auth()->user()->hasPermission('borrowers.verify_kyc'))
                {{-- Independent KYC verification controls --}}
                @php
                    $isCleared = in_array($kycRecord->verification_status, ['verified', 'manual']);
                @endphp

                @if($isCleared)
                {{-- Already cleared — show a compact re-decision strip --}}
                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                    <div style="display:flex;align-items:center;gap:8px;color:#166534;font-size:.88rem">
                        <i class="bi bi-shield-check" style="font-size:1.1rem"></i>
                        <span>
                            <strong>Identity cleared</strong> ·
                            {{ $kycRecord->verification_status === 'verified' ? 'Verified' : 'Manually cleared' }}
                            @if($kycRecord->verified_at)
                                on {{ \Carbon\Carbon::parse($kycRecord->verified_at)->format('d M Y') }}
                            @endif
                        </span>
                    </div>
                    <button type="button" class="btn btn-light btn-sm"
                        onclick="document.getElementById('kycDecisionPanel').style.display='block';this.style.display='none'">
                        <i class="bi bi-arrow-repeat"></i> Change Decision
                    </button>
                </div>
                <div id="kycDecisionPanel" style="display:none;margin-top:12px">
                @else
                {{-- Pending/failed — show decision panel immediately --}}
                @php $isCleared = false; @endphp
                <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px 16px;margin-bottom:14px;font-size:.85rem;color:#92400e;display:flex;align-items:center;gap:8px">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>
                        @if($kycRecord->verification_status === 'failed')
                            KYC was marked as failed. Review the document and update the decision below.
                        @else
                            KYC is pending verification. Review the document details above and make a decision.
                        @endif
                    </span>
                </div>
                <div id="kycDecisionPanel">
                @endif

                    <form method="POST" action="{{ route('kyc.verify', $borrowerRecord->id) }}">
                        @csrf
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
                            <div>
                                <label class="text-muted-sm" style="display:block;margin-bottom:4px">Verification Decision <span style="color:#dc2626">*</span></label>
                                <select name="verification_status" class="form-input" required>
                                    <option value="">— Select decision —</option>
                                    <option value="verified"  {{ $kycRecord->verification_status === 'verified' ? 'selected' : '' }}>
                                        ✅ Verified — Identity confirmed
                                    </option>
                                    <option value="manual"    {{ $kycRecord->verification_status === 'manual'   ? 'selected' : '' }}>
                                        🔵 Manual Override — Manually cleared for lending
                                    </option>
                                    <option value="failed"    {{ $kycRecord->verification_status === 'failed'   ? 'selected' : '' }}>
                                        ❌ Failed — Document rejected
                                    </option>
                                    <option value="pending"   {{ $kycRecord->verification_status === 'pending'  ? 'selected' : '' }}>
                                        🔄 Reset to Pending — Request re-submission
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="text-muted-sm" style="display:block;margin-bottom:4px">Officer Notes</label>
                                <input type="text" name="verification_notes" class="form-input"
                                    placeholder="Reason, reference number, etc."
                                    value="{{ old('verification_notes', $kycRecord->verification_notes ?? '') }}">
                            </div>
                        </div>
                        <div style="display:flex;gap:8px;align-items:center">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-shield-check"></i> Save KYC Decision
                            </button>
                            @if($isCleared)
                            <button type="button" class="btn btn-light btn-sm"
                                onclick="document.getElementById('kycDecisionPanel').style.display='none';this.closest('form').previousElementSibling.style.display='flex'">
                                Cancel
                            </button>
                            @endif
                            <span style="font-size:.75rem;color:#94a3b8;margin-left:4px">
                                <i class="bi bi-info-circle"></i>
                                Setting <em>Verified</em> or <em>Manual</em> unlocks lending for this borrower.
                            </span>
                        </div>
                    </form>
                </div>
                @endif

            @else
                <div class="empty-state"><i class="bi bi-file-earmark-x"></i><p>No KYC record found for this borrower.</p></div>
            @endif
        </div>
    </div>
</div>

{{-- Financial Profile --}}
<div class="panel" style="padding:0;margin-bottom:18px">
    <div class="panel-head" style="padding:16px 18px 14px;border-bottom:1px solid var(--line)">
        <div>
            <h2>Financial Profile</h2>
            <p>Income, employment, and financial assessment.</p>
        </div>
    </div>
    <div style="padding:18px">
        @if($financialProfile)
            <div class="form-grid">
                <div class="form-group">
                    <label class="text-muted-sm">Employment Type</label>
                    <div style="font-size:1.1rem;font-weight:600">{{ ucfirst(str_replace('_', ' ', $profile['employment_type'] ?? '—')) }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Employer Name</label>
                    <div style="font-size:1.1rem;font-weight:600">{{ $profile['employer_name'] ?? '—' }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Job Title</label>
                    <div style="font-size:1.1rem;font-weight:600">{{ $profile['job_title'] ?? '—' }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Monthly Income</label>
                    <div style="font-size:1.1rem;font-weight:600">KSh {{ number_format(($profile['monthly_income_cents'] ?? 0)/100, 2) }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Monthly Expenses</label>
                    <div style="font-size:1.1rem;font-weight:600">KSh {{ number_format(($profile['monthly_expenses_cents'] ?? 0)/100, 2) }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Existing Debt</label>
                    <div style="font-size:1.1rem;font-weight:600">KSh {{ number_format(($profile['existing_debt_cents'] ?? 0)/100, 2) }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Other Income</label>
                    <div style="font-size:1.1rem;font-weight:600">KSh {{ number_format(($profile['other_income_cents'] ?? 0)/100, 2) }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Residence County</label>
                    <div style="font-size:1.1rem;font-weight:600">{{ $profile['residence_county'] ?? '—' }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Residence Address</label>
                    <div style="font-size:1.1rem;font-weight:600">{{ $profile['residence_address'] ?? '—' }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Housing Status</label>
                    <div style="font-size:1.1rem;font-weight:600">{{ ucfirst($profile['housing_status'] ?? '—') }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Years at Address</label>
                    <div style="font-size:1.1rem;font-weight:600">{{ $profile['years_at_address'] ?? '—' }}</div>
                </div>
            </div>

            @if($financialProfile->financial_score)
            <div style="margin-top:18px;padding-top:18px;border-top:1px solid var(--line)">
                <h3 style="font-size:1rem;margin-bottom:12px">Financial Assessment Scores</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="text-muted-sm">Financial Score</label>
                        <div style="font-size:1.1rem;font-weight:600">{{ number_format($financialProfile->financial_score, 4) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="text-muted-sm">Income Stability Score</label>
                        <div style="font-size:1.1rem;font-weight:600">{{ $financialProfile->income_stability_score ? number_format($financialProfile->income_stability_score, 4) : '—' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="text-muted-sm">Cashflow Score</label>
                        <div style="font-size:1.1rem;font-weight:600">{{ $financialProfile->cashflow_score ? number_format($financialProfile->cashflow_score, 4) : '—' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="text-muted-sm">Debt Service Ratio</label>
                        <div style="font-size:1.1rem;font-weight:600">{{ $financialProfile->debt_service_ratio ? number_format($financialProfile->debt_service_ratio * 100, 2) . '%' : '—' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="text-muted-sm">Analysis Months</label>
                        <div style="font-size:1.1rem;font-weight:600">{{ $financialProfile->analysis_months }}</div>
                    </div>
                    <div class="form-group">
                        <label class="text-muted-sm">Transaction Count</label>
                        <div style="font-size:1.1rem;font-weight:600">{{ number_format($financialProfile->transaction_count) }}</div>
                    </div>
                </div>
            </div>
            @endif
        @else
            <div class="empty-state"><i class="bi bi-graph-down"></i><p>No financial profile found.</p></div>
        @endif
    </div>
</div>

{{-- Next of Kin --}}
<div class="panel" style="padding:0;margin-bottom:18px">
    <div class="panel-head" style="padding:16px 18px 14px;border-bottom:1px solid var(--line)">
        <div>
            <h2>Next of Kin</h2>
            <p>Emergency contact information.</p>
        </div>
    </div>
    <div style="padding:18px">
        @php
            $nok = $profile['next_of_kin'] ?? [];
        @endphp
        @if(!empty($nok))
            <div class="form-grid">
                <div class="form-group">
                    <label class="text-muted-sm">Name</label>
                    <div style="font-size:1.1rem;font-weight:600">{{ $nok['name'] ?? '—' }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Relationship</label>
                    <div style="font-size:1.1rem;font-weight:600">{{ $nok['relationship'] ?? '—' }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Phone</label>
                    <div style="font-size:1.1rem;font-weight:600">{{ $nok['phone'] ?? '—' }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">ID Number</label>
                    <div style="font-size:1.1rem;font-weight:600">{{ $nok['id_number'] ?? '—' }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Email</label>
                    <div style="font-size:1.1rem;font-weight:600">{{ $nok['email'] ?? '—' }}</div>
                </div>
                <div class="form-group">
                    <label class="text-muted-sm">Address</label>
                    <div style="font-size:1.1rem;font-weight:600">{{ $nok['address'] ?? '—' }}</div>
                </div>
                @if(isset($nok['notes']) && $nok['notes'])
                <div class="form-group form-group-full">
                    <label class="text-muted-sm">Notes</label>
                    <div style="font-size:1rem">{{ $nok['notes'] }}</div>
                </div>
                @endif
            </div>
        @else
            <div class="empty-state"><i class="bi bi-person-x"></i><p>No next of kin information found.</p></div>
        @endif
    </div>
</div>

@endsection
