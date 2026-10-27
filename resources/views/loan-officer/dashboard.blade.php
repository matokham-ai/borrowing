@extends('layouts.app')

@section('title', 'Loan Officer Dashboard')

@push('styles')
<style>
.officer-page{padding:0 30px}
.officer-hero{position:relative;overflow:hidden;display:flex;align-items:center;justify-content:space-between;gap:24px;margin:24px 0 20px;padding:30px 34px;border:1px solid #e3e7ff;border-radius:20px;background:radial-gradient(circle at 88% 15%,rgba(117,106,255,.22),transparent 30%),linear-gradient(120deg,#fff 0%,#f4f5ff 58%,#eef2ff 100%);box-shadow:0 12px 32px rgba(31,41,90,.06)}
.officer-hero-copy{position:relative;z-index:1}
.officer-kicker{display:flex;align-items:center;gap:8px;margin:0 0 10px;color:#5b5ce2;font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase}
.officer-kicker span{width:7px;height:7px;border-radius:50%;background:#12b76a;box-shadow:0 0 0 4px rgba(18,183,106,.12)}
.officer-hero h1{margin:0;color:#111a35;font-size:27px;letter-spacing:-.04em}
.officer-hero p{margin:9px 0 0;color:#667085;font-size:12px}
.officer-branch{display:inline-flex;align-items:center;gap:7px;margin-top:12px;padding:7px 10px;border:1px solid rgba(91,92,226,.13);border-radius:10px;background:rgba(255,255,255,.75);color:#475467;font-size:9px;font-weight:600}
.officer-branch i{color:#5b5ce2}
.officer-hero-action{position:relative;z-index:1;display:inline-flex;align-items:center;gap:8px;height:38px;padding:0 14px;border-radius:10px;background:#5b5ce2;color:#fff;font-size:10px;font-weight:700;text-decoration:none;box-shadow:0 7px 15px rgba(91,92,226,.2)}
.officer-hero-action:hover{background:#4a4bcf;color:#fff}
.officer-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:20px}
.officer-pipeline{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:20px}
.officer-stage{display:flex;align-items:center;gap:11px;padding:13px 15px;border:1px solid #eaecf0;border-radius:13px;background:#fff;color:inherit;text-decoration:none;box-shadow:0 4px 14px rgba(16,24,40,.025);transition:border-color .15s,transform .15s}
.officer-stage:hover{transform:translateY(-2px);border-color:#c7c8ff}
.officer-stage-index{display:grid;width:30px;height:30px;flex:0 0 30px;place-items:center;border-radius:10px;background:#f1f0ff;color:#5b5ce2;font-size:10px;font-weight:700}
.officer-stage-copy{min-width:0;flex:1}
.officer-stage-copy strong,.officer-stage-copy small{display:block}
.officer-stage-copy strong{color:#344054;font-size:9px}
.officer-stage-copy small{margin-top:4px;color:#98a2b3;font-size:8px}
.officer-stage-count{color:#202945;font-size:15px;font-weight:700}
.officer-stat{position:relative;overflow:hidden;min-height:132px;padding:18px 19px;border:1px solid #eaecf0;border-radius:16px;background:#fff;box-shadow:0 5px 18px rgba(16,24,40,.035)}
.officer-stat:after{position:absolute;right:-22px;bottom:-38px;width:100px;height:100px;border-radius:50%;background:var(--stat-tint,#f0efff);content:"";opacity:.7}
.officer-stat-top{position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between}
.officer-stat-icon{display:grid;width:34px;height:34px;place-items:center;border-radius:11px;background:var(--stat-tint,#f0efff);color:var(--stat-color,#5b5ce2);font-size:15px}
.officer-stat-tag{color:#98a2b3;font-size:8px;font-weight:700;letter-spacing:.1em}
.officer-stat strong{position:relative;z-index:1;display:block;margin-top:13px;color:#101828;font-size:25px;letter-spacing:-.04em}
.officer-stat-label{position:relative;z-index:1;display:block;margin-top:3px;color:#667085;font-size:10px}
.officer-grid{display:grid;grid-template-columns:minmax(0,1.65fr) minmax(250px,.8fr);gap:18px;align-items:start}
.officer-panel{overflow:hidden;border:1px solid #eaecf0;border-radius:16px;background:#fff;box-shadow:0 5px 18px rgba(16,24,40,.035)}
.officer-panel-head{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:19px 21px 15px;border-bottom:1px solid #f2f4f7}
.officer-panel-head h2{margin:0;color:#101828;font-size:14px;letter-spacing:-.02em}
.officer-panel-head p{margin:5px 0 0;color:#98a2b3;font-size:10px}
.officer-count{padding:5px 9px;border-radius:20px;background:#f1f0ff;color:#5b5ce2;font-size:9px;font-weight:700;text-decoration:none}
.officer-table-wrap{overflow:auto}
.officer-table{width:100%;border-collapse:collapse;white-space:nowrap}
.officer-table th{padding:11px 17px;background:#fcfcfd;color:#98a2b3;font-size:8px;font-weight:700;letter-spacing:.09em;text-align:left}
.officer-table td{padding:12px 17px;border-top:1px solid #f2f4f7;color:#475467;font-size:10px}
.officer-person{display:flex;align-items:center;gap:9px}
.officer-avatar{display:grid;width:30px;height:30px;place-items:center;border-radius:10px;background:#efefff;color:#595bd6;font-size:10px;font-weight:700}
.officer-person strong,.officer-person small{display:block}
.officer-person strong{color:#202945;font-size:10px}
.officer-person small{margin-top:3px;color:#98a2b3;font-size:9px}
.officer-status{display:inline-flex;align-items:center;gap:5px;padding:5px 8px;border-radius:20px;background:#fff7e8;color:#b54708;font-size:8px;font-weight:700}
.officer-status:before{width:5px;height:5px;border-radius:50%;background:#f79009;content:""}
.officer-open{display:grid;width:27px;height:27px;place-items:center;border:1px solid #eaecf0;border-radius:8px;color:#667085;text-decoration:none}
.officer-open:hover{border-color:#c7c8ff;background:#f6f5ff;color:#5b5ce2}
.officer-empty{padding:40px 20px;color:#98a2b3;text-align:center}
.officer-empty i{display:block;margin-bottom:10px;color:#5b5ce2;font-size:22px}
.officer-empty strong{display:block;margin-bottom:5px;color:#344054;font-size:12px}
.officer-actions{padding:9px}
.officer-action{display:flex;align-items:center;gap:12px;padding:13px 12px;border-radius:12px;color:inherit;text-decoration:none;transition:background .15s,transform .15s}
.officer-action:hover{transform:translateX(2px);background:#f8f8ff}
.officer-action-icon{display:grid;width:35px;height:35px;place-items:center;border-radius:11px;background:#f0efff;color:#5b5ce2;font-size:14px}
.officer-action-copy{flex:1}
.officer-action-copy strong,.officer-action-copy small{display:block}
.officer-action-copy strong{color:#202945;font-size:10px}
.officer-action-copy small{margin-top:4px;color:#98a2b3;font-size:9px}
.officer-action-arrow{color:#98a2b3;font-size:10px}
.officer-note{display:flex;gap:10px;margin:9px 12px 12px;padding:12px;border:1px solid #e8e9ff;border-radius:12px;background:#f8f8ff;color:#667085;font-size:9px;line-height:1.6}
.officer-note i{color:#5b5ce2;font-size:13px}
@media(max-width:1000px){.officer-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.officer-pipeline{grid-template-columns:repeat(2,minmax(0,1fr))}.officer-grid{grid-template-columns:1fr}}
@media(max-width:600px){.officer-page{padding:0 15px}.officer-hero{align-items:flex-start;flex-direction:column;padding:23px}.officer-hero h1{font-size:22px}.officer-stats{gap:9px}.officer-stat{min-height:120px;padding:14px}.officer-stat strong{font-size:22px}.officer-panel-head{padding:16px}.officer-table th,.officer-table td{padding-right:12px;padding-left:12px}}
</style>
@endpush

@section('content')
<div class="officer-page">
    <section class="officer-hero">
        <div class="officer-hero-copy">
            <div class="officer-kicker"><span></span> Lending operations · {{ now()->format('l, d F Y') }}</div>
            <h1>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ $firstName }}</h1>
            <p>Your borrowers, applications and active portfolio at a glance.</p>
            <span class="officer-branch"><i class="bi bi-geo-alt-fill"></i> {{ $branch?->name ?? 'Branch not assigned' }}</span>
        </div>
        @if(auth()->user()->hasPermission('create-loans'))
            <a href="{{ route('applications.create') }}" class="officer-hero-action"><i class="bi bi-plus-lg"></i> New application</a>
        @endif
    </section>

    <section class="officer-pipeline" aria-label="Loan approval progress">
        <a class="officer-stage" href="{{ route('applications.index', ['status' => 'submitted']) }}"><span class="officer-stage-index"><i class="bi bi-send"></i></span><span class="officer-stage-copy"><strong>Supervisor review</strong><small>Submitted applications</small></span><span class="officer-stage-count">{{ number_format($stats['awaiting_supervisor']) }}</span></a>
        <a class="officer-stage" href="{{ route('applications.index', ['status' => 'first_level_approved']) }}"><span class="officer-stage-index"><i class="bi bi-person-check"></i></span><span class="officer-stage-copy"><strong>Admin approval</strong><small>Supervisor approved</small></span><span class="officer-stage-count">{{ number_format($stats['awaiting_admin']) }}</span></a>
        <a class="officer-stage" href="{{ route('applications.index', ['status' => 'approved']) }}"><span class="officer-stage-index"><i class="bi bi-hourglass-split"></i></span><span class="officer-stage-copy"><strong>Awaiting disbursement</strong><small>Final approval recorded</small></span><span class="officer-stage-count">{{ number_format($stats['awaiting_disbursement']) }}</span></a>
        <a class="officer-stage" href="{{ route('loans.active') }}"><span class="officer-stage-index"><i class="bi bi-check2-circle"></i></span><span class="officer-stage-copy"><strong>Disbursed</strong><small>Active or overdue loans</small></span><span class="officer-stage-count">{{ number_format($stats['active_loans']) }}</span></a>
    </section>

    <section class="officer-stats" aria-label="My portfolio">
        <article class="officer-stat" style="--stat-color:#3538cd;--stat-tint:#efefff">
            <div class="officer-stat-top"><span class="officer-stat-icon"><i class="bi bi-file-earmark-text-fill"></i></span><span class="officer-stat-tag">MY WORK</span></div>
            <strong>{{ number_format($stats['applications']) }}</strong><span class="officer-stat-label">Applications created by you</span>
        </article>
        <article class="officer-stat" style="--stat-color:#b54708;--stat-tint:#fff4e5">
            <div class="officer-stat-top"><span class="officer-stat-icon"><i class="bi bi-hourglass-split"></i></span><span class="officer-stat-tag">IN PROGRESS</span></div>
            <strong>{{ number_format($stats['in_progress']) }}</strong><span class="officer-stat-label">Drafts and applications in review</span>
        </article>
        <article class="officer-stat" style="--stat-color:#087443;--stat-tint:#e9f9f0">
            <div class="officer-stat-top"><span class="officer-stat-icon"><i class="bi bi-people-fill"></i></span><span class="officer-stat-tag">MY CLIENTS</span></div>
            <strong>{{ number_format($stats['borrowers']) }}</strong><span class="officer-stat-label">Borrowers registered by you</span>
        </article>
        <article class="officer-stat" style="--stat-color:#175cd3;--stat-tint:#eff8ff">
            <div class="officer-stat-top"><span class="officer-stat-icon"><i class="bi bi-bank2"></i></span><span class="officer-stat-tag">ACTIVE PORTFOLIO</span></div>
            <strong>KSh {{ number_format($stats['portfolio_cents'] / 100) }}</strong><span class="officer-stat-label">{{ number_format($stats['active_loans']) }} active or overdue loans</span>
        </article>
    </section>

    <div class="officer-grid">
        <section class="officer-panel">
            <div class="officer-panel-head">
                <div><h2>My recent applications</h2><p>Applications you created, from draft through decision.</p></div>
                <a href="{{ route('applications.index') }}" class="officer-count">{{ number_format($stats['applications']) }} total</a>
            </div>
            @if($recentApplications->isEmpty())
                <div class="officer-empty"><i class="bi bi-file-earmark-plus"></i><strong>Your pipeline is ready</strong>Create your first application to start tracking your lending work.</div>
            @else
                <div class="officer-table-wrap">
                    <table class="officer-table">
                        <thead><tr><th>APPLICATION</th><th>FACILITY</th><th>REQUESTED</th><th>STATUS</th><th></th></tr></thead>
                        <tbody>
                        @foreach($recentApplications as $application)
                            <tr>
                                <td><div class="officer-person"><span class="officer-avatar">{{ strtoupper(substr($application->borrower->user->name ?? 'B', 0, 1)) }}</span><span><strong>{{ $application->borrower->user->name ?? 'Unknown borrower' }}</strong><small>#{{ str_pad($application->id, 6, '0', STR_PAD_LEFT) }} · {{ optional($application->created_at)->diffForHumans() }}</small></span></div></td>
                                <td>{{ $application->loanProduct->name ?? '—' }}</td>
                                <td><strong>KSh {{ number_format($application->requested_amount_cents / 100) }}</strong></td>
                                <td><span class="officer-status">{{ str_replace('_', ' ', ucfirst($application->loan?->status ?? $application->status)) }}</span></td>
                                <td><a class="officer-open" href="{{ route('applications.show', $application) }}" aria-label="Open application"><i class="bi bi-arrow-up-right"></i></a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="officer-panel">
            <div class="officer-panel-head"><div><h2>Quick actions</h2><p>Pick up where you left off.</p></div></div>
            <div class="officer-actions">
                <a href="{{ route('applications.index') }}" class="officer-action"><span class="officer-action-icon"><i class="bi bi-files"></i></span><span class="officer-action-copy"><strong>My applications</strong><small>{{ number_format($stats['in_progress']) }} in progress</small></span><i class="bi bi-arrow-right officer-action-arrow"></i></a>
                @if(auth()->user()->hasPermission('create-loans'))
                    <a href="{{ route('applications.create') }}" class="officer-action"><span class="officer-action-icon"><i class="bi bi-file-earmark-plus"></i></span><span class="officer-action-copy"><strong>Start an application</strong><small>Prepare a borrower loan request</small></span><i class="bi bi-arrow-right officer-action-arrow"></i></a>
                @endif
                <a href="{{ route('borrowers.index') }}" class="officer-action"><span class="officer-action-icon"><i class="bi bi-people"></i></span><span class="officer-action-copy"><strong>Borrowers</strong><small>Manage borrower profiles</small></span><i class="bi bi-arrow-right officer-action-arrow"></i></a>
                @if(auth()->user()->hasPermission('create-borrowers'))
                    <a href="{{ route('borrowers.create') }}" class="officer-action"><span class="officer-action-icon"><i class="bi bi-person-plus"></i></span><span class="officer-action-copy"><strong>Add a borrower</strong><small>Register a new borrower</small></span><i class="bi bi-arrow-right officer-action-arrow"></i></a>
                @endif
                <a href="{{ route('loans.active') }}" class="officer-action"><span class="officer-action-icon"><i class="bi bi-bank"></i></span><span class="officer-action-copy"><strong>Active loans</strong><small>{{ number_format($stats['active_loans']) }} active or overdue</small></span><i class="bi bi-arrow-right officer-action-arrow"></i></a>
            </div>
            <div class="officer-note"><i class="bi bi-shield-check"></i><span>This dashboard reflects applications and borrowers attributed to your account. All changes remain in the lending audit trail.</span></div>
        </section>
    </div>
</div>
@endsection
