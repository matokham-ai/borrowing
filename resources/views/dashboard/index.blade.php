@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

{{-- ══════════════════════════════════════════
     HERO BANNER
══════════════════════════════════════════ --}}
<div class="dash-hero">
    <div class="dash-hero-left">
        <div class="dash-hero-eyebrow">{{ now()->format('l, d F Y') }}</div>
        <h1 class="dash-hero-title">{{ $greeting }}, {{ $firstName }} <span class="dash-hero-wave">👋</span></h1>
        <p class="dash-hero-sub">Here's what's happening across your lending portfolio today.</p>
    </div>
    <div class="dash-hero-right">
        <div class="dash-hero-bg"></div>
        <div class="dash-quote-card">
            <p class="dash-quote-text">"Financial access<br>creates brighter tomorrows."</p>
            <span class="dash-quote-author">— LendFlow</span>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════
     STAT CARDS
══════════════════════════════════════════ --}}
<div class="stats-grid">
    <x-stat-card
        icon="bi-people-fill"
        label="Total Borrowers"
        value="{{ $totalBorrowers }}"
        :delta="$borrowersDelta"
        color="indigo"
        sparkline="borrowers" />

    <x-stat-card
        icon="bi-bank2"
        label="Active Portfolio"
        value="{{ $activePortfolio }}"
        :delta="$portfolioDelta"
        color="blue"
        sparkline="portfolio" />

    <x-stat-card
        icon="bi-percent"
        label="Collection Rate"
        value="{{ $collectionRate }}"
        :delta="$collectionDelta"
        color="green"
        sparkline="collection" />

    <x-stat-card
        icon="bi-shield-exclamation"
        label="At Risk"
        value="{{ $atRisk }}"
        :delta="$atRiskDelta"
        color="red"
        sparkline="atrisk" />
</div>

{{-- ══════════════════════════════════════════
     CHARTS ROW
══════════════════════════════════════════ --}}
<div class="dash-charts-row">

    {{-- Portfolio Performance --}}
    <section class="panel dash-perf-panel">
        <div class="panel-head">
            <div>
                <h2>Portfolio Performance</h2>
                <p>Disbursements and collections · Last 6 months</p>
            </div>
            <div class="dash-perf-controls">
                <div class="chart-period-tabs">
                    <button class="cpt-btn" data-period="7">7D</button>
                    <button class="cpt-btn" data-period="30">30D</button>
                    <button class="cpt-btn active" data-period="180">6M</button>
                    <button class="cpt-btn" data-period="365">1Y</button>
                </div>
                <div class="chart-date-range">
                    <i class="bi bi-calendar3"></i>
                    <span>{{ now()->subMonths(5)->format('M Y') }} – {{ now()->format('M Y') }}</span>
                </div>
            </div>
        </div>
        <div class="chart-wrap"><canvas id="portfolioChart"></canvas></div>
    </section>

    {{-- Portfolio Health --}}
    <section class="panel dash-health-panel">
        <div class="panel-head">
            <div>
                <h2>Portfolio Health</h2>
                <p>Current loan distribution</p>
            </div>
            <select class="select-sm">
                <option>By Status</option>
                <option>By Product</option>
                <option>By Tier</option>
            </select>
        </div>

        <div class="health-donut-wrap">
            <canvas id="healthChart"></canvas>
            <div class="health-donut-center">
                <strong id="healthTotalLoans">—</strong>
                <span>Loans</span>
            </div>
        </div>

        <div class="health-legend">
            @php
                $healthItems = [
                    ['label' => 'Current',    'key' => 'current',    'color' => 'current'],
                    ['label' => 'Watch',      'key' => 'watch',      'color' => 'watch'],
                    ['label' => 'Delinquent', 'key' => 'delinquent', 'color' => 'delinquent'],
                    ['label' => 'Default',    'key' => 'default',    'color' => 'default'],
                ];
                $healthCounts = [
                    'current'    => round($health['current']   / 100 * array_sum($health) ?: 0),
                    'watch'      => round($health['watch']     / 100 * array_sum($health) ?: 0),
                    'delinquent' => round($health['delinquent']/ 100 * array_sum($health) ?: 0),
                    'default'    => round($health['default']   / 100 * array_sum($health) ?: 0),
                ];
            @endphp
            @foreach($healthItems as $item)
            <div class="health-legend-row">
                <span class="legend-dot {{ $item['color'] }}"></span>
                <span class="health-legend-label">{{ $item['label'] }}</span>
                <span class="health-legend-pct">{{ $health[$item['key']] }}%</span>
                <span class="health-legend-count">{{ $healthCounts[$item['key']] }}</span>
            </div>
            @endforeach
        </div>

        @php $currentPct = $health['current']; @endphp
        <div class="health-status-bar {{ $currentPct >= 60 ? 'healthy' : ($currentPct >= 40 ? 'watch' : 'danger') }}">
            <i class="bi {{ $currentPct >= 60 ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill' }}"></i>
            <div>
                <strong>Portfolio is {{ $currentPct >= 60 ? 'Healthy' : ($currentPct >= 40 ? 'Under Watch' : 'At Risk') }}</strong>
                <span>{{ $currentPct }}% of loans are performing as expected.</span>
            </div>
            <i class="bi bi-chevron-right ms-auto"></i>
        </div>
    </section>

</div>

{{-- ══════════════════════════════════════════
     TABLES ROW
══════════════════════════════════════════ --}}
<div class="dash-tables-row">

    {{-- Recent Loan Applications --}}
    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>Recent Loan Applications</h2>
                <p>Applications requiring attention</p>
            </div>
            <a href="{{ route('loan-applications.index') }}" class="view-link">View all <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>BORROWER</th>
                        <th>PRODUCT</th>
                        <th>AMOUNT</th>
                        <th>RISK</th>
                        <th>STATUS</th>
                        <th>SUBMITTED</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentApplications as $i => $row)
                    <tr>
                        <td class="text-muted-sm">{{ $row['ref'] }}</td>
                        <td>
                            <div class="borrower-cell">
                                <span class="mini-avatar avatar-{{ $i % 6 }}">{{ $row['initials'] }}</span>
                                <span>{{ $row['name'] }}</span>
                            </div>
                        </td>
                        <td>{{ $row['product'] }}</td>
                        <td><strong>{{ $row['amount'] }}</strong></td>
                        <td><span class="status-pill {{ $row['risk_class'] }}">{{ $row['risk'] }}</span></td>
                        <td><span class="status-pill {{ $row['status_class'] }}">{{ $row['status'] }}</span></td>
                        <td class="text-muted-sm">{{ now()->format('d M Y') }}</td>
                        <td><button class="row-dots-btn"><i class="bi bi-three-dots"></i></button></td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="empty-row">No applications yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Upcoming Loan Dues --}}
    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>Upcoming Loan Dues</h2>
                <p>Next unpaid installments across active loans</p>
            </div>
            <a href="{{ route('loans.active') }}" class="view-link">View all <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>LOAN #</th>
                        <th>BORROWER</th>
                        <th>DUE AMOUNT</th>
                        <th>DUE DATE</th>
                        <th>DAYS LEFT</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($upcomingLoanDues as $due)
                    <tr>
                        <td><strong>{{ $due['loan'] }}</strong></td>
                        <td>{{ $due['borrower'] }}</td>
                        <td><strong>{{ $due['amount'] }}</strong></td>
                        <td class="text-muted-sm">{{ $due['due_date'] }}</td>
                        <td>
                            <span class="days-pill {{ $due['status_class'] }}">
                                {{ $due['days_remaining'] >= 0 ? $due['days_remaining'] . ' days' : abs($due['days_remaining']) . 'd over' }}
                            </span>
                        </td>
                        <td><button class="row-dots-btn"><i class="bi bi-three-dots"></i></button></td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="empty-row">No upcoming dues.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

</div>

{{-- ══════════════════════════════════════════
     QUICK ACTIONS + SYSTEM STATUS
══════════════════════════════════════════ --}}
<div class="dash-footer-row">
    <div class="quick-actions-bar">
        <span class="quick-actions-label">Quick Actions</span>
        <a href="{{ route('loan-applications.create') }}" class="qa-btn qa-btn-primary">
            <i class="bi bi-plus-lg"></i> New Application
        </a>
        <a href="{{ route('payments.index') }}" class="qa-btn">
            <i class="bi bi-credit-card"></i> Register Payment
        </a>
        <a href="{{ route('borrowers.create') }}" class="qa-btn">
            <i class="bi bi-person-plus"></i> Import Borrowers
        </a>
        <a href="{{ route('payments.index', ['view' => 'reversals']) }}" class="qa-btn">
            <i class="bi bi-phone"></i> M-PESA Reversal
        </a>
        <a href="{{ route('dashboard.export') }}" class="qa-btn">
            <i class="bi bi-bar-chart"></i> Generate Report
        </a>
    </div>
    <div class="system-status-bar">
        <span class="sys-status-dot online"></span>
        <span class="sys-status-text">All systems operational</span>
        <span class="sys-status-time">Last updated: {{ now()->format('d M Y, h:i A') }}</span>
    </div>
</div>

<script>
    const dashboardChartData  = {{ Js::from($chartData) }};
    const dashboardHealthData = {{ Js::from(array_values($health)) }};
    const dashboardHealthRaw  = {{ Js::from($health) }};
</script>
@endsection
