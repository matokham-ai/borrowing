@extends('layouts.app')

@section('title', 'Active Loans')

@section('content')
<div class="applications-page">
    <div class="page-header applications-header">
        <div>
            <div class="eyebrow"><span class="eyebrow-dot"></span> LENDING OPERATIONS</div>
            <h1>Active Loans</h1>
            <p class="page-subtitle">Monitor currently disbursed loans across your portfolio.</p>
        </div>
        <div class="applications-header-actions">
            <a href="{{ route('applications.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left"></i> Applications
            </a>
        </div>
    </div>

    <div class="stat-grid application-stat-grid">
        <div class="stat-card stat-card-total">
            <div class="stat-card-top"><span class="stat-card-icon"><i class="bi bi-bank"></i></span><span class="stat-card-kicker">CURRENT</span></div>
            <div class="stat-card-label">Active loans</div>
            <div class="stat-card-value">{{ number_format($stats['count']) }}</div>
            <div class="stat-card-note">Currently in repayment</div>
        </div>
        <div class="stat-card stat-card-approved">
            <div class="stat-card-top"><span class="stat-card-icon"><i class="bi bi-cash-stack"></i></span><span class="stat-card-kicker">PRINCIPAL</span></div>
            <div class="stat-card-label">Principal outstanding</div>
            <div class="stat-card-value">KSh {{ number_format($stats['principal'] / 100, 2) }}</div>
            <div class="stat-card-note">Across active facilities</div>
        </div>
        <div class="stat-card stat-card-pending">
            <div class="stat-card-top"><span class="stat-card-icon"><i class="bi bi-receipt"></i></span><span class="stat-card-kicker">CONTRACTED</span></div>
            <div class="stat-card-label">Total payable</div>
            <div class="stat-card-value">KSh {{ number_format($stats['payable'] / 100, 2) }}</div>
            <div class="stat-card-note">Principal, interest and fees</div>
        </div>
    </div>

    <div class="panel applications-panel">
        <div class="applications-panel-head">
            <div>
                <h2>Active loan portfolio</h2>
                <p>Loans currently being repaid by borrowers.</p>
            </div>
            <span class="result-count">{{ $loans->total() }} {{ $loans->total() === 1 ? 'loan' : 'loans' }}</span>
        </div>

        <form method="GET" class="table-filters applications-filters">
            <div class="filter-search">
                <i class="bi bi-search"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search borrower, email or product...">
            </div>
            <button class="btn btn-light" type="submit">Search</button>
            @if($search)
                <a href="{{ route('loans.active') }}" class="btn btn-light">Clear</a>
            @endif
        </form>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Loan</th>
                        <th>Borrower</th>
                        <th>Product</th>
                        <th>Principal</th>
                        <th>Total payable</th>
                        <th>Term</th>
                        <th>Disbursed</th>
                        <th aria-label="Actions"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($loans as $loan)
                    <tr>
                        <td><strong>LN-{{ str_pad($loan->id, 5, '0', STR_PAD_LEFT) }}</strong></td>
                        <td>
                            <div class="table-person">
                                <span class="table-avatar">{{ strtoupper(substr($loan->borrower->user->name ?? 'B', 0, 1)) }}</span>
                                <div>
                                    <a href="{{ route('borrowers.show', $loan->borrower) }}" class="table-primary-link">{{ $loan->borrower->user->name ?? 'Unknown borrower' }}</a>
                                    <small>{{ $loan->borrower->phone_number }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <strong>{{ $loan->loanProduct->name ?? '—' }}</strong>
                            <small>{{ $loan->loanProduct->code ?? 'Loan facility' }}</small>
                        </td>
                        <td><strong>KSh {{ number_format($loan->principal_cents / 100, 2) }}</strong></td>
                        <td><strong>KSh {{ number_format($loan->total_payable_cents / 100, 2) }}</strong></td>
                        <td>{{ $loan->term_days }} days</td>
                        <td>
                            <strong>{{ optional($loan->disbursed_at)->format('d M Y') ?: '—' }}</strong>
                            <small>{{ optional($loan->matures_at)->format('d M Y') ? 'Matures '.optional($loan->matures_at)->format('d M Y') : '' }}</small>
                        </td>
                        <td class="row-actions">
                            <a href="{{ route('borrowers.show', $loan->borrower) }}" class="row-action row-action-view" title="View borrower">
                                <i class="bi bi-arrow-up-right"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty-state">No active loans found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap">{{ $loans->links() }}</div>
    </div>
</div>
@endsection
