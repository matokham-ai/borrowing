@extends('layouts.app')
@section('title', 'Organisation')

@section('content')

{{-- ══════════════════════════════════════════
     PAGE HEADER
══════════════════════════════════════════ --}}
<div class="page-header">
    <div>
        <div class="eyebrow"><i class="bi bi-building"></i> ORGANISATION / OVERVIEW</div>
        <h1>Organisation overview</h1>
        <p>Your operating snapshot and workspace controls in one place.</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('organisation.edit') }}" class="btn btn-primary">
            <i class="bi bi-pencil-square"></i> Edit Organisation
        </a>
    </div>
</div>

{{-- ══════════════════════════════════════════
     ORGANISATION HEADER
══════════════════════════════════════════ --}}
<div class="org-header">
    <div class="org-header-left">
        @if($organisation->logo_path)
        <div class="org-logo">
            <img src="{{ asset($organisation->logo_path) }}" alt="{{ $organisation->name }}">
        </div>
        @else
        <div class="org-logo org-logo--placeholder">
            <i class="bi bi-building-fill"></i>
        </div>
        @endif

        <div class="org-identity">
            <h2 class="org-name">{{ $organisation->name }}</h2>
            <div class="org-meta">
                <span class="org-slug">{{ $organisation->slug }}</span>
                @if($organisation->organisation_type)
                <span class="org-divider">·</span>
                <span class="org-type">{{ $organisation->organisation_type }}</span>
                @endif
                <span class="org-divider">·</span>
                <span class="org-location">{{ $organisation->country_code }} · {{ $organisation->currency }}</span>
            </div>
        </div>
    </div>

    <div class="org-header-right">
        <div class="org-status org-status--{{ $organisation->is_active ? 'active' : 'inactive' }}">
            <span class="org-status-dot"></span>
            <span>{{ $organisation->is_active ? 'Active' : 'Inactive' }}</span>
        </div>
        <div class="org-id">ID: {{ $organisation->formatted_organisation_id }}</div>
        <div class="org-created">Workspace since {{ $organisation->created_at->format('d M Y') }}</div>
    </div>
</div>

<div class="org-section-intro">
    <div>
        <span class="section-kicker">Performance snapshot</span>
        <h2>Portfolio at a glance</h2>
    </div>
    <span class="section-period"><i class="bi bi-calendar3"></i> This month</span>
</div>

{{-- ══════════════════════════════════════════
     ORGANISATION OVERVIEW STATS
══════════════════════════════════════════ --}}
<div class="org-overview-stats">
    <div class="org-stat-card">
        <div class="org-stat-icon org-stat-icon--indigo">
            <i class="bi bi-people-fill"></i>
        </div>
        <div class="org-stat-content">
            <div class="org-stat-value">{{ number_format($totalBorrowers) }}</div>
            <div class="org-stat-label">Borrowers</div>
            @if($borrowersDelta['direction'] !== 'neutral')
            <div class="org-stat-delta {{ $borrowersDelta['direction'] === 'up' ? 'positive' : 'negative' }}">
                <i class="bi {{ $borrowersDelta['direction'] === 'up' ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}"></i>
                {{ $borrowersDelta['value'] }}%
            </div>
            @endif
        </div>
    </div>

    <div class="org-stat-card">
        <div class="org-stat-icon org-stat-icon--blue">
            <i class="bi bi-bank2"></i>
        </div>
        <div class="org-stat-content">
            <div class="org-stat-value">{{ number_format($activeLoans) }}</div>
            <div class="org-stat-label">Active Loans</div>
            @if($activeLoansDelta['direction'] !== 'neutral')
            <div class="org-stat-delta {{ $activeLoansDelta['direction'] === 'up' ? 'positive' : 'negative' }}">
                <i class="bi {{ $activeLoansDelta['direction'] === 'up' ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}"></i>
                {{ $activeLoansDelta['value'] }}%
            </div>
            @endif
        </div>
    </div>

    <div class="org-stat-card">
        <div class="org-stat-icon org-stat-icon--green">
            <i class="bi bi-cash-stack"></i>
        </div>
        <div class="org-stat-content">
            <div class="org-stat-value">{{ $organisation->currency }} {{ number_format($portfolioCents / 100, 1) }}M</div>
            <div class="org-stat-label">Portfolio</div>
            @if($portfolioDelta['direction'] !== 'neutral')
            <div class="org-stat-delta {{ $portfolioDelta['direction'] === 'up' ? 'positive' : 'negative' }}">
                <i class="bi {{ $portfolioDelta['direction'] === 'up' ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}"></i>
                {{ $portfolioDelta['value'] }}%
            </div>
            @endif
        </div>
    </div>

    <div class="org-stat-card">
        <div class="org-stat-icon org-stat-icon--teal">
            <i class="bi bi-percent"></i>
        </div>
        <div class="org-stat-content">
            <div class="org-stat-value">{{ $collectionRate }}%</div>
            <div class="org-stat-label">Collection Rate</div>
            @if($collectionDelta['direction'] !== 'neutral')
            <div class="org-stat-delta {{ $collectionDelta['direction'] === 'up' ? 'positive' : 'negative' }}">
                <i class="bi {{ $collectionDelta['direction'] === 'up' ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}"></i>
                {{ $collectionDelta['value'] }}%
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════
     SECONDARY STATS ROW
══════════════════════════════════════════ --}}
<div class="org-secondary-stats">
    <div class="org-secondary-stat">
        <div class="org-secondary-label">Overdue Amount</div>
        <div class="org-secondary-value">{{ $organisation->currency }} {{ number_format($overdueCents / 100) }}</div>
    </div>
    <div class="org-secondary-stat">
        <div class="org-secondary-label">PAR 30</div>
        <div class="org-secondary-value">{{ $par30 }}%</div>
    </div>
    <div class="org-secondary-stat">
        <div class="org-secondary-label">Disbursed This Month</div>
        <div class="org-secondary-value">{{ $organisation->currency }} {{ number_format($disbursedThisMonth / 100) }}</div>
    </div>
    <div class="org-secondary-stat">
        <div class="org-secondary-label">Repayments This Month</div>
        <div class="org-secondary-value">{{ $organisation->currency }} {{ number_format($repaymentsThisMonth / 100) }}</div>
    </div>
</div>

{{-- ══════════════════════════════════════════
     ORGANISATION PROFILE
══════════════════════════════════════════ --}}
<div class="org-profile-section">
    <div class="org-profile-card">
        <div class="org-profile-header">
            <h3>Organisation Profile</h3>
            <a href="{{ route('organisation.edit') }}" class="org-profile-edit">
                <i class="bi bi-pencil-square"></i> Edit
            </a>
        </div>

        <div class="org-profile-grid">
            <div class="org-profile-section">
                <h4>Identity</h4>
                <div class="org-profile-fields">
                    <div class="org-profile-field">
                        <label>Legal Name</label>
                        <div class="org-profile-value">{{ $organisation->name }}</div>
                    </div>
                    @if($organisation->trading_name)
                    <div class="org-profile-field">
                        <label>Trading Name</label>
                        <div class="org-profile-value">{{ $organisation->trading_name }}</div>
                    </div>
                    @endif
                    @if($organisation->organisation_type)
                    <div class="org-profile-field">
                        <label>Organisation Type</label>
                        <div class="org-profile-value">{{ $organisation->organisation_type }}</div>
                    </div>
                    @endif
                    @if($organisation->company_reg_number)
                    <div class="org-profile-field">
                        <label>Registration Number</label>
                        <div class="org-profile-value">{{ $organisation->company_reg_number }}</div>
                    </div>
                    @endif
                    @if($organisation->tax_pin)
                    <div class="org-profile-field">
                        <label>Tax PIN</label>
                        <div class="org-profile-value">{{ $organisation->tax_pin }}</div>
                    </div>
                    @endif
                    @if($organisation->phone)
                    <div class="org-profile-field">
                        <label>Phone</label>
                        <div class="org-profile-value">{{ $organisation->phone }}</div>
                    </div>
                    @endif
                    @if($organisation->email)
                    <div class="org-profile-field">
                        <label>Email</label>
                        <div class="org-profile-value">{{ $organisation->email }}</div>
                    </div>
                    @endif
                    @if($organisation->website)
                    <div class="org-profile-field">
                        <label>Website</label>
                        <div class="org-profile-value">
                            <a href="{{ $organisation->website }}" target="_blank">{{ $organisation->website }}</a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="org-profile-section">
                <h4>Address</h4>
                <div class="org-profile-fields">
                    <div class="org-profile-field">
                        <label>Country</label>
                        <div class="org-profile-value">{{ $organisation->country_code }}</div>
                    </div>
                    @if($organisation->county)
                    <div class="org-profile-field">
                        <label>County</label>
                        <div class="org-profile-value">{{ $organisation->county }}</div>
                    </div>
                    @endif
                    @if($organisation->town)
                    <div class="org-profile-field">
                        <label>Town</label>
                        <div class="org-profile-value">{{ $organisation->town }}</div>
                    </div>
                    @endif
                    @if($organisation->physical_address)
                    <div class="org-profile-field">
                        <label>Physical Address</label>
                        <div class="org-profile-value">{{ $organisation->physical_address }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="org-status-card">
        <div class="org-status-header">
            <h3>Organisation Status</h3>
        </div>

        <div class="org-status-indicator">
            <div class="org-status-dot org-status-dot--{{ $organisation->is_active ? 'active' : 'inactive' }}"></div>
            <div class="org-status-text">
                <strong>{{ $organisation->is_active ? 'Active' : 'Inactive' }}</strong>
                <span>{{ $organisation->is_active ? 'Your organisation is operational and accepting transactions.' : 'Your organisation is currently inactive.' }}</span>
            </div>
        </div>

        <div class="org-status-timeline">
            <div class="org-status-item">
                <label>Account created</label>
                <div>{{ $organisation->created_at->format('d F Y') }}</div>
            </div>
            <div class="org-status-item">
                <label>Last activity</label>
                <div>{{ $organisation->updated_at->format('d F Y, H:i') }}</div>
            </div>
        </div>

        @if($organisation->is_active)
        <div class="org-status-actions">
            <form action="{{ route('organisation.update.status') }}" method="POST"
                  onsubmit="return confirm('Are you sure you want to deactivate this organisation? This will prevent all lending operations.')">
                @csrf
                @method('PATCH')
                <input type="hidden" name="is_active" value="0">
                <button type="submit" class="btn btn-outline-danger">
                    <i class="bi bi-x-circle"></i> Deactivate Organisation
                </button>
            </form>
        </div>
        @else
        <div class="org-status-actions">
            <form action="{{ route('organisation.update.status') }}" method="POST"
                  onsubmit="return confirm('Are you sure you want to activate this organisation?')">
                @csrf
                @method('PATCH')
                <input type="hidden" name="is_active" value="1">
                <button type="submit" class="btn btn-outline-success">
                    <i class="bi bi-check-circle"></i> Activate Organisation
                </button>
            </form>
        </div>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════════
     ORGANISATION CONFIGURATION
══════════════════════════════════════════ --}}
<div class="org-config-section">
    <div class="org-config-header">
        <h3>Organisation Configuration</h3>
        <p>Configure how your organisation operates within LendFlow</p>
    </div>

    <div class="org-config-tabs">
        <a href="{{ route('organisation.general') }}" class="org-config-tab {{ request()->routeIs('organisation.general') ? 'active' : '' }}">
            <i class="bi bi-gear"></i> General
        </a>
        <a href="{{ route('organisation.lending') }}" class="org-config-tab {{ request()->routeIs('organisation.lending') ? 'active' : '' }}">
            <i class="bi bi-bank"></i> Lending
        </a>
        <a href="{{ route('organisation.payments') }}" class="org-config-tab {{ request()->routeIs('organisation.payments') ? 'active' : '' }}">
            <i class="bi bi-credit-card"></i> Payments
        </a>
        <a href="{{ route('organisation.notifications') }}" class="org-config-tab {{ request()->routeIs('organisation.notifications') ? 'active' : '' }}">
            <i class="bi bi-bell"></i> Notifications
        </a>
        <a href="{{ route('organisation.branding') }}" class="org-config-tab {{ request()->routeIs('organisation.branding') ? 'active' : '' }}">
            <i class="bi bi-palette"></i> Branding
        </a>
    </div>

    <div class="org-config-preview">
        <div class="org-config-preview-card">
            <div class="org-config-preview-header">
                <i class="bi bi-gear"></i>
                <span>General Settings</span>
            </div>
            <div class="org-config-preview-content">
                <div class="org-config-preview-item">
                    <label>Country</label>
                    <span>{{ $organisation->country_code }}</span>
                </div>
                <div class="org-config-preview-item">
                    <label>Currency</label>
                    <span>{{ $organisation->currency }}</span>
                </div>
                <div class="org-config-preview-item">
                    <label>Timezone</label>
                    <span>{{ $organisation->timezone }}</span>
                </div>
                <div class="org-config-preview-item">
                    <label>Date Format</label>
                    <span>{{ $organisation->date_format }}</span>
                </div>
            </div>
            <a href="{{ route('organisation.general') }}" class="org-config-preview-link">
                Configure <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="org-config-preview-card">
            <div class="org-config-preview-header">
                <i class="bi bi-bank"></i>
                <span>Lending Configuration</span>
            </div>
            <div class="org-config-preview-content">
                <div class="org-config-preview-item">
                    <label>Default Loan Term</label>
                    <span>{{ $organisation->default_loan_term }} days</span>
                </div>
                <div class="org-config-preview-item">
                    <label>Repayment Frequency</label>
                    <span>{{ ucfirst($organisation->default_repayment_frequency) }}</span>
                </div>
                <div class="org-config-preview-item">
                    <label>Interest Calculation</label>
                    <span>{{ ucfirst(str_replace('_', ' ', $organisation->interest_calculation_method)) }}</span>
                </div>
                <div class="org-config-preview-item">
                    <label>Maximum Active Loans</label>
                    <span>{{ $organisation->maximum_active_loans }}</span>
                </div>
            </div>
            <a href="{{ route('organisation.lending') }}" class="org-config-preview-link">
                Configure <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="org-config-preview-card">
            <div class="org-config-preview-header">
                <i class="bi bi-palette"></i>
                <span>Branding</span>
            </div>
            <div class="org-config-preview-content">
                <div class="org-config-preview-item">
                    <label>Primary Color</label>
                    <div class="color-preview">
                        <span class="color-swatch" style="background-color: {{ $organisation->primary_brand_color }}"></span>
                        <span>{{ $organisation->primary_brand_color }}</span>
                    </div>
                </div>
                @if($organisation->secondary_brand_color)
                <div class="org-config-preview-item">
                    <label>Secondary Color</label>
                    <div class="color-preview">
                        <span class="color-swatch" style="background-color: {{ $organisation->secondary_brand_color }}"></span>
                        <span>{{ $organisation->secondary_brand_color }}</span>
                    </div>
                </div>
                @endif
                <div class="org-config-preview-item">
                    <label>Use Organisation Branding</label>
                    <span>{{ $organisation->use_org_branding ? 'Yes' : 'No' }}</span>
                </div>
            </div>
            <a href="{{ route('organisation.branding') }}" class="org-config-preview-link">
                Configure <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</div>

@endsection
