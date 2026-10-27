@extends('layouts.app')

@section('title', 'Loan Applications')

@section('content')
<div class="applications-page">
<div class="page-header applications-header">
    <div>
        <div class="eyebrow"><span class="eyebrow-dot"></span> LENDING OPERATIONS</div>
        <h1>Loan Applications</h1>
        <p class="page-subtitle">Review, track and manage borrower applications.</p>
    </div>
    <div class="applications-header-actions">
        <span class="header-updated"><i class="bi bi-clock-history"></i> Updated just now</span>
        @if(!auth()->user()->hasRole('supervisor') || auth()->user()->hasRole(['admin', 'super-admin']))
            <a href="{{ route('applications.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i>
                New Application
            </a>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="stat-grid application-stat-grid">
    <a href="{{ route('applications.index') }}" class="stat-card stat-card-total">
        <div class="stat-card-top"><span class="stat-card-icon"><i class="bi bi-files"></i></span><span class="stat-card-kicker">ALL TIME</span></div>
        <div class="stat-card-label">Total applications</div>
        <div class="stat-card-value">{{ number_format($stats['total']) }}</div>
        <div class="stat-card-note">Across your lending portfolio</div>
    </a>
    <a href="{{ route('applications.index', ['status' => 'under_review']) }}" class="stat-card stat-card-pending {{ $status === 'under_review' ? 'is-active' : '' }}">
        <div class="stat-card-top"><span class="stat-card-icon"><i class="bi bi-hourglass-split"></i></span><span class="stat-card-kicker">ACTION NEEDED</span></div>
        <div class="stat-card-label">Awaiting decision</div>
        <div class="stat-card-value">{{ number_format($stats['pending']) }}</div>
        <div class="stat-card-note"><strong>Review queue</strong> · needs attention</div>
    </a>
    <a href="{{ route('applications.index', ['status' => 'approved']) }}" class="stat-card stat-card-approved {{ $status === 'approved' ? 'is-active' : '' }}">
        <div class="stat-card-top"><span class="stat-card-icon"><i class="bi bi-check2-circle"></i></span><span class="stat-card-kicker">POSITIVE OUTCOME</span></div>
        <div class="stat-card-label">Approved</div>
        <div class="stat-card-value">{{ number_format($stats['approved']) }}</div>
        <div class="stat-card-note">Applications accepted</div>
    </a>
    <a href="{{ route('applications.index', ['status' => 'rejected']) }}" class="stat-card stat-card-rejected {{ $status === 'rejected' ? 'is-active' : '' }}">
        <div class="stat-card-top"><span class="stat-card-icon"><i class="bi bi-x-circle"></i></span><span class="stat-card-kicker">CLOSED</span></div>
        <div class="stat-card-label">Rejected</div>
        <div class="stat-card-value">{{ number_format($stats['rejected']) }}</div>
        <div class="stat-card-note">Applications declined</div>
    </a>
</div>

<div class="panel applications-panel">
    <div class="applications-panel-head">
        <div>
            <h2>Application pipeline</h2>
            <p>Monitor every request from submission to decision.</p>
        </div>
            <span class="result-count">{{ $applications->total() }} {{ $applications->total() === 1 ? 'application' : 'applications' }}</span>
    </div>
    <form method="GET" class="table-filters applications-filters">
        <div class="filter-search">
            <i class="bi bi-search"></i>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search borrower, email or product...">
        </div>
        <select name="status" class="filter-select" onchange="this.form.submit()">
            <option value="">All statuses</option>
            @foreach(['pending','submitted','under_review','first_level_approved','returned','approved','rejected','declined','expired'] as $value)
                <option value="{{ $value }}" @selected($status === $value)>{{ str_replace('_', ' ', ucfirst($value)) }}</option>
            @endforeach
        </select>
        <button class="btn btn-light" type="submit">Filter</button>
        @if($search || $status)
            <a href="{{ route('applications.index') }}" class="btn btn-light">Clear</a>
        @endif
    </form>
    <nav class="application-queue-tabs" aria-label="Application queue">
        <a href="{{ route('applications.index') }}" class="{{ $status === '' ? 'is-active' : '' }}">All applications</a>
        <a href="{{ route('applications.index', ['status' => 'under_review']) }}" class="{{ $status === 'under_review' ? 'is-active' : '' }}">Awaiting review</a>
        <a href="{{ route('applications.index', ['status' => 'first_level_approved']) }}" class="{{ $status === 'first_level_approved' ? 'is-active' : '' }}">Final admin review</a>
        <a href="{{ route('applications.index', ['status' => 'returned']) }}" class="{{ $status === 'returned' ? 'is-active' : '' }}">Returned</a>
        <a href="{{ route('applications.index', ['status' => 'approved']) }}" class="{{ $status === 'approved' ? 'is-active' : '' }}">Approved</a>
        <a href="{{ route('applications.index', ['status' => 'rejected']) }}" class="{{ $status === 'rejected' ? 'is-active' : '' }}">Declined</a>
    </nav>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Application</th>
                    <th>Borrower</th>
                    <th>Facility</th>
                    <th>Requested</th>
                    <th>Decision</th>
                    <th>Submitted</th>
                    <th aria-label="Actions"></th>
                </tr>
            </thead>
            <tbody>
            @forelse($applications as $application)
                <tr>
                    <td>
                        <strong>#{{ str_pad($application->id, 6, '0', STR_PAD_LEFT) }}</strong>
                    </td>
                    <td>
                        <div class="table-person">
                            <span class="table-avatar">{{ strtoupper(substr($application->borrower->user->name ?? 'B', 0, 1)) }}</span>
                            <div>
                                <a href="{{ route('borrowers.show', $application->borrower) }}" class="table-primary-link">
                                    {{ $application->borrower->user->name ?? 'Unknown borrower' }}
                                </a>
                                <small>{{ $application->borrower->phone_number }}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <strong>{{ $application->loanProduct->name ?? '—' }}</strong>
                        <small>{{ $application->loanProduct->code ?? 'Loan facility' }}</small>
                    </td>
                    <td>
                        <strong class="requested-amount">KSh {{ number_format($application->requested_amount_cents / 100) }}</strong>
                        <small>{{ $application->requested_term_days }} day term</small>
                    </td>
                    <td>
                        @php $progressStatus = $application->loan?->status ?? $application->status; @endphp
                        <span class="decision-status status-{{ $progressStatus }}">
                            <span class="decision-dot"></span>{{ str_replace('_', ' ', ucfirst($progressStatus)) }}
                        </span>
                    </td>
                    <td>
                        <strong>{{ optional($application->submitted_at ?? $application->created_at)->format('d M Y') }}</strong>
                        <small>{{ optional($application->submitted_at ?? $application->created_at)->format('H:i') }}</small>
                    </td>
                    <td class="row-actions">
                        <a href="{{ route('applications.show', $application) }}" class="row-action row-action-view" title="Review application">
                            <i class="bi bi-arrow-up-right"></i>
                        </a>
                        @if(
                            in_array($application->status, ['pending', 'returned'], true)
                            || (
                                !auth()->user()->hasRole('loan-officer')
                                && in_array($application->status, ['submitted', 'under_review'], true)
                            )
                        )
                            <a href="{{ route('applications.edit', $application) }}" class="row-action" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty-state">No loan applications found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrap">{{ $applications->links() }}</div>
</div>
</div>
@endsection
