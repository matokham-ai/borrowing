@extends('layouts.app')
@section('title', 'Lending Configuration')

@section('content')

{{-- ══════════════════════════════════════════
     PAGE HEADER
══════════════════════════════════════════ --}}
<div class="page-header">
    <div>
        <div class="eyebrow">ORGANISATION CONFIGURATION</div>
        <h1>Lending Configuration</h1>
        <p>Configure default lending parameters and policies.</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('organisation.index') }}" class="btn btn-light">
            <i class="bi bi-arrow-left"></i> Back to Overview
        </a>
    </div>
</div>

{{-- ══════════════════════════════════════════
     CONFIGURATION TABS
══════════════════════════════════════════ --}}
<div class="org-config-tabs-wrapper">
    <div class="org-config-tabs">
        <a href="{{ route('organisation.general') }}" class="org-config-tab">
            <i class="bi bi-gear"></i> General
        </a>
        <a href="{{ route('organisation.lending') }}" class="org-config-tab active">
            <i class="bi bi-bank"></i> Lending
        </a>
        <a href="{{ route('organisation.payments') }}" class="org-config-tab">
            <i class="bi bi-credit-card"></i> Payments
        </a>
        <a href="{{ route('organisation.notifications') }}" class="org-config-tab">
            <i class="bi bi-bell"></i> Notifications
        </a>
        <a href="{{ route('organisation.branding') }}" class="org-config-tab">
            <i class="bi bi-palette"></i> Branding
        </a>
    </div>
</div>

{{-- ══════════════════════════════════════════
     LENDING CONFIGURATION FORM
══════════════════════════════════════════ --}}
<div class="config-form-container">
    <form action="{{ route('organisation.update.lending') }}" method="POST">
        @csrf
        @method('PUT')

        @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
        @endif

        @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="config-form-section">
            <h3>Default Loan Parameters</h3>
            <p class="config-form-subtitle">Standard settings applied to new loan products</p>

            <div class="form-grid">
                <div class="form-group">
                    <label for="default_loan_term">Default Loan Term</label>
                    <input type="number" id="default_loan_term" name="default_loan_term"
                           class="form-control" value="{{ $organisation->default_loan_term }}" required min="1">
                    <small class="form-text">Default loan duration in days</small>
                </div>

                <div class="form-group">
                    <label for="default_repayment_frequency">Default Repayment Frequency</label>
                    <select id="default_repayment_frequency" name="default_repayment_frequency" class="form-control" required>
                        <option value="daily" {{ $organisation->default_repayment_frequency === 'daily' ? 'selected' : '' }}>Daily</option>
                        <option value="weekly" {{ $organisation->default_repayment_frequency === 'weekly' ? 'selected' : '' }}>Weekly</option>
                        <option value="monthly" {{ $organisation->default_repayment_frequency === 'monthly' ? 'selected' : '' }}>Monthly</option>
                    </select>
                    <small class="form-text">How often borrowers make repayments</small>
                </div>

                <div class="form-group">
                    <label for="interest_calculation_method">Interest Calculation Method</label>
                    <select id="interest_calculation_method" name="interest_calculation_method" class="form-control" required>
                        <option value="reducing_balance" {{ $organisation->interest_calculation_method === 'reducing_balance' ? 'selected' : '' }}>Reducing Balance</option>
                        <option value="flat_rate" {{ $organisation->interest_calculation_method === 'flat_rate' ? 'selected' : '' }}>Flat Rate</option>
                    </select>
                    <small class="form-text">Method for calculating loan interest</small>
                </div>

                <div class="form-group">
                    <label for="maximum_active_loans">Maximum Active Loans</label>
                    <input type="number" id="maximum_active_loans" name="maximum_active_loans"
                           class="form-control" value="{{ $organisation->maximum_active_loans }}" required min="1">
                    <small class="form-text">Maximum number of concurrent loans per borrower</small>
                </div>
            </div>
        </div>

        <div class="config-form-section">
            <h3>Late Payment Handling</h3>
            <p class="config-form-subtitle">Configure how your organisation handles late payments</p>

            <div class="form-grid">
                <div class="form-group">
                    <label for="late_payment_handling">Enable Late Payment Handling</label>
                    <div class="form-check">
                        <input type="checkbox" id="late_payment_handling" name="late_payment_handling"
                               class="form-check-input" {{ $organisation->late_payment_handling ? 'checked' : '' }}>
                        <label for="late_payment_handling" class="form-check-label">
                            Automatically apply late payment fees and penalties
                        </label>
                    </div>
                    <small class="form-text">When enabled, the system will apply configured late payment rules</small>
                </div>

                <div class="form-group">
                    <label for="grace_period">Grace Period</label>
                    <input type="number" id="grace_period" name="grace_period"
                           class="form-control" value="{{ $organisation->grace_period }}" required min="0">
                    <small class="form-text">Number of days before late payment penalties apply</small>
                </div>
            </div>
        </div>

        <div class="config-form-info">
            <div class="config-form-info-icon">
                <i class="bi bi-info-circle"></i>
            </div>
            <div class="config-form-info-content">
                <strong>Note about Loan Products</strong>
                <p>These settings serve as defaults for new loan products. Individual loan products can override these defaults with their own specific parameters. Configure detailed loan product settings in the Loan Products section.</p>
            </div>
        </div>

        <div class="config-form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle"></i> Save Changes
            </button>
            <a href="{{ route('organisation.index') }}" class="btn btn-light">
                Cancel
            </a>
        </div>
    </form>
</div>

<div class="config-form-container tier-management">
    <div class="config-form-section">
        <div class="tier-management-header">
            <div>
                <h3>Borrower Tiers</h3>
                <p class="config-form-subtitle">Manage tier names, loan limits and qualification thresholds.</p>
            </div>
            <span class="tier-count">{{ $tiers->count() }} {{ $tiers->count() === 1 ? 'tier' : 'tiers' }}</span>
        </div>

        @if($tiers->isEmpty())
        <div class="config-form-info">
            <div class="config-form-info-icon"><i class="bi bi-info-circle"></i></div>
            <div class="config-form-info-content">
                <strong>No borrower tiers configured</strong>
                <p>Add your first borrower tier below to start setting lending limits.</p>
            </div>
        </div>
        @endif

        <div class="tier-config-list">
            @foreach($tiers as $tier)
            <div class="tier-config-card">
                <form action="{{ route('organisation.lending.tiers.update', $tier->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="tier-card-heading">
                        <div>
                            <span class="tier-config-name">{{ $tier->name }}</span>
                            <span class="tier-config-number">Tier {{ $tier->tier_number }}{{ $tier->is_entry_tier ? ' · Entry tier' : '' }}</span>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-check-lg"></i> Save Tier
                        </button>
                    </div>
                    <div class="tier-card-fields">
                        <div class="form-group">
                            <label for="tier-{{ $tier->id }}-name">Tier name</label>
                            <input type="text" id="tier-{{ $tier->id }}-name" name="name"
                                   class="form-control" value="{{ $tier->name }}" maxlength="100" required>
                        </div>
                        <div class="form-group">
                            <label for="tier-{{ $tier->id }}-minimum">Minimum loan (KSh)</label>
                            <input type="number" id="tier-{{ $tier->id }}-minimum" name="min_loan_amount"
                                   class="form-control" value="{{ number_format($tier->min_loan_amount_cents / 100, 2, '.', '') }}"
                                   min="0" max="100000000000" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="tier-{{ $tier->id }}-maximum">Maximum loan (KSh)</label>
                            <input type="number" id="tier-{{ $tier->id }}-maximum" name="max_loan_amount"
                                   class="form-control" value="{{ number_format($tier->max_loan_amount_cents / 100, 2, '.', '') }}"
                                   min="0" max="100000000000" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="tier-{{ $tier->id }}-acceptance">Acceptance percentage</label>
                            <div class="tier-config-percent">
                                <input type="number" id="tier-{{ $tier->id }}-acceptance" name="acceptance_percentage"
                                       class="form-control" value="{{ number_format((float) ($tier->minimum_financial_score ?? 0) * 100, 2, '.', '') }}"
                                       min="0" max="100" step="0.01" required>
                                <span>%</span>
                            </div>
                            <small class="form-text">Minimum financial score to qualify</small>
                        </div>
                        <div class="form-group">
                            <label for="tier-{{ $tier->id }}-term">Maximum loan term (days)</label>
                            <input type="number" id="tier-{{ $tier->id }}-term" name="max_term_days"
                                   class="form-control" value="{{ $tier->max_term_days }}" min="1" max="65535" required>
                        </div>
                    </div>
                </form>
                <form action="{{ route('organisation.lending.tiers.destroy', $tier->id) }}" method="POST"
                      onsubmit="return confirm('Delete the {{ $tier->name }} tier? Tiers used by borrowers or lending history cannot be deleted.')">
                    @csrf
                    @method('DELETE')
                    <div class="tier-card-actions">
                        <button type="submit" class="btn btn-light btn-sm tier-delete-button">
                            <i class="bi bi-trash"></i> Delete Tier
                        </button>
                    </div>
                </form>
            </div>
            @endforeach
        </div>

        <div class="tier-add-section">
            <h4><i class="bi bi-plus-circle"></i> Add a borrower tier</h4>
            <form action="{{ route('organisation.lending.tiers.store') }}" method="POST">
                @csrf
                <div class="tier-card-fields">
                    <div class="form-group">
                        <label for="new-tier-name">Tier name</label>
                        <input type="text" id="new-tier-name" name="name" class="form-control"
                               value="{{ old('name') }}" maxlength="100" required>
                    </div>
                    <div class="form-group">
                        <label for="new-tier-minimum">Minimum loan (KSh)</label>
                        <input type="number" id="new-tier-minimum" name="min_loan_amount" class="form-control"
                               value="{{ old('min_loan_amount', '0.00') }}" min="0" max="100000000000" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="new-tier-maximum">Maximum loan (KSh)</label>
                        <input type="number" id="new-tier-maximum" name="max_loan_amount" class="form-control"
                               value="{{ old('max_loan_amount') }}" min="0" max="100000000000" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="new-tier-acceptance">Acceptance percentage</label>
                        <div class="tier-config-percent">
                            <input type="number" id="new-tier-acceptance" name="acceptance_percentage" class="form-control"
                                   value="{{ old('acceptance_percentage', '0') }}" min="0" max="100" step="0.01" required>
                            <span>%</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="new-tier-term">Maximum loan term (days)</label>
                        <input type="number" id="new-tier-term" name="max_term_days" class="form-control"
                               value="{{ old('max_term_days', $organisation->default_loan_term) }}" min="1" max="65535" required>
                    </div>
                </div>
                <div class="tier-card-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Add Tier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection