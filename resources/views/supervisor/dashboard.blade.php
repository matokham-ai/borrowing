@extends('layouts.app')

@section('title', 'Supervisor Dashboard')

@push('styles')
<style>
.supervisor-page{padding:0 30px}
.supervisor-hero{position:relative;overflow:hidden;display:flex;align-items:center;justify-content:space-between;gap:24px;margin:24px 0 20px;padding:30px 34px;border:1px solid #e3e7ff;border-radius:20px;background:radial-gradient(circle at 88% 15%,rgba(117,106,255,.22),transparent 30%),linear-gradient(120deg,#fff 0%,#f4f5ff 58%,#eef2ff 100%);box-shadow:0 12px 32px rgba(31,41,90,.06)}
.supervisor-hero-copy{position:relative;z-index:1}
.supervisor-kicker{display:flex;align-items:center;gap:8px;margin:0 0 10px;color:#5b5ce2;font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase}
.supervisor-kicker span{width:7px;height:7px;border-radius:50%;background:#12b76a;box-shadow:0 0 0 4px rgba(18,183,106,.12)}
.supervisor-hero h1{margin:0;color:#111a35;font-size:27px;letter-spacing:-.04em}
.supervisor-hero p{margin:9px 0 0;color:#667085;font-size:12px}
.supervisor-branch{position:relative;z-index:1;display:flex;align-items:center;gap:12px;min-width:205px;padding:13px 15px;border:1px solid rgba(91,92,226,.13);border-radius:14px;background:rgba(255,255,255,.76);box-shadow:0 8px 20px rgba(31,41,90,.05)}
.supervisor-branch-icon{display:grid;width:38px;height:38px;place-items:center;border-radius:12px;background:#eeedff;color:#5b5ce2;font-size:16px}
.supervisor-branch small,.supervisor-branch strong{display:block}
.supervisor-branch small{margin-bottom:4px;color:#98a2b3;font-size:9px;font-weight:700;letter-spacing:.1em;text-transform:uppercase}
.supervisor-branch strong{color:#202945;font-size:12px}
.supervisor-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:20px}
.supervisor-stat{position:relative;overflow:hidden;min-height:132px;padding:18px 19px;border:1px solid #eaecf0;border-radius:16px;background:#fff;box-shadow:0 5px 18px rgba(16,24,40,.035)}
.supervisor-stat:after{position:absolute;right:-22px;bottom:-38px;width:100px;height:100px;border-radius:50%;background:var(--stat-tint,#f0efff);content:"";opacity:.7}
.supervisor-stat-top{position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between}
.supervisor-stat-icon{display:grid;width:34px;height:34px;place-items:center;border-radius:11px;background:var(--stat-tint,#f0efff);color:var(--stat-color,#5b5ce2);font-size:15px}
.supervisor-stat-tag{color:#98a2b3;font-size:8px;font-weight:700;letter-spacing:.1em}
.supervisor-stat strong{position:relative;z-index:1;display:block;margin-top:13px;color:#101828;font-size:25px;letter-spacing:-.04em}
.supervisor-stat-label{position:relative;z-index:1;display:block;margin-top:3px;color:#667085;font-size:10px}
.supervisor-grid{display:grid;grid-template-columns:minmax(0,1.65fr) minmax(250px,.8fr);gap:18px;align-items:start}
.supervisor-panel{overflow:hidden;border:1px solid #eaecf0;border-radius:16px;background:#fff;box-shadow:0 5px 18px rgba(16,24,40,.035)}
.supervisor-panel-head{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:19px 21px 15px;border-bottom:1px solid #f2f4f7}
.supervisor-panel-head h2{margin:0;color:#101828;font-size:14px;letter-spacing:-.02em}
.supervisor-panel-head p{margin:5px 0 0;color:#98a2b3;font-size:10px}
.supervisor-count{padding:5px 9px;border-radius:20px;background:#f1f0ff;color:#5b5ce2;font-size:9px;font-weight:700}
.supervisor-table-wrap{overflow:auto}
.supervisor-table{width:100%;border-collapse:collapse;white-space:nowrap}
.supervisor-table th{padding:11px 17px;background:#fcfcfd;color:#98a2b3;font-size:8px;font-weight:700;letter-spacing:.09em;text-align:left}
.supervisor-table td{padding:12px 17px;border-top:1px solid #f2f4f7;color:#475467;font-size:10px}
.supervisor-person{display:flex;align-items:center;gap:9px}
.supervisor-avatar{display:grid;width:30px;height:30px;place-items:center;border-radius:10px;background:#efefff;color:#595bd6;font-size:10px;font-weight:700}
.supervisor-person strong,.supervisor-person small{display:block}
.supervisor-person strong{color:#202945;font-size:10px}
.supervisor-person small{margin-top:3px;color:#98a2b3;font-size:9px}
.supervisor-status{display:inline-flex;align-items:center;gap:5px;padding:5px 8px;border-radius:20px;background:#fff7e8;color:#b54708;font-size:8px;font-weight:700}
.supervisor-status:before{width:5px;height:5px;border-radius:50%;background:#f79009;content:""}
.supervisor-open{display:grid;width:27px;height:27px;place-items:center;border:1px solid #eaecf0;border-radius:8px;color:#667085;text-decoration:none}
.supervisor-open:hover{border-color:#c7c8ff;background:#f6f5ff;color:#5b5ce2}
.supervisor-empty{padding:40px 20px;color:#98a2b3;text-align:center}
.supervisor-empty i{display:block;margin-bottom:10px;color:#12b76a;font-size:22px}
.supervisor-empty strong{display:block;margin-bottom:5px;color:#344054;font-size:12px}
.supervisor-actions{padding:9px}
.supervisor-action{display:flex;align-items:center;gap:12px;padding:13px 12px;border-radius:12px;color:inherit;text-decoration:none;transition:background .15s,transform .15s}
.supervisor-action:hover{transform:translateX(2px);background:#f8f8ff}
.supervisor-action-icon{display:grid;width:35px;height:35px;place-items:center;border-radius:11px;background:#f0efff;color:#5b5ce2;font-size:14px}
.supervisor-action-copy{flex:1}
.supervisor-action-copy strong,.supervisor-action-copy small{display:block}
.supervisor-action-copy strong{color:#202945;font-size:10px}
.supervisor-action-copy small{margin-top:4px;color:#98a2b3;font-size:9px}
.supervisor-action-arrow{color:#98a2b3;font-size:10px}
.supervisor-note{display:flex;gap:10px;margin:9px 12px 12px;padding:12px;border:1px solid #e8e9ff;border-radius:12px;background:#f8f8ff;color:#667085;font-size:9px;line-height:1.6}
.supervisor-note i{color:#5b5ce2;font-size:13px}
@media(max-width:1000px){.supervisor-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.supervisor-grid{grid-template-columns:1fr}}
@media(max-width:600px){.supervisor-page{padding:0 15px}.supervisor-hero{align-items:flex-start;flex-direction:column;padding:23px}.supervisor-hero h1{font-size:22px}.supervisor-branch{width:100%}.supervisor-stats{gap:9px}.supervisor-stat{min-height:120px;padding:14px}.supervisor-stat strong{font-size:22px}.supervisor-panel-head{padding:16px}.supervisor-table th,.supervisor-table td{padding-right:12px;padding-left:12px}}
</style>
@endpush

@section('content')
<div class="supervisor-page">
    <section class="supervisor-hero">
        <div class="supervisor-hero-copy">
            <div class="supervisor-kicker"><span></span> Branch operations · {{ now()->format('l, d F Y') }}</div>
            <h1>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ $firstName }}</h1>
            <p>Your branch overview, review priorities and borrower activity in one place.</p>
        </div>
        <div class="supervisor-branch">
            <span class="supervisor-branch-icon"><i class="bi bi-diagram-3-fill"></i></span>
            <span><small>Assigned branch</small><strong>{{ $branch?->name ?? 'Branch not assigned' }}</strong></span>
        </div>
    </section>

    <section class="supervisor-stats" aria-label="Branch performance">
        <article class="supervisor-stat" style="--stat-color:#b54708;--stat-tint:#fff4e5">
            <div class="supervisor-stat-top"><span class="supervisor-stat-icon"><i class="bi bi-inbox-fill"></i></span><span class="supervisor-stat-tag">NEEDS REVIEW</span></div>
            <strong>{{ number_format($stats['review_queue']) }}</strong><span class="supervisor-stat-label">Applications awaiting your review</span>
        </article>
        <article class="supervisor-stat" style="--stat-color:#087443;--stat-tint:#e9f9f0">
            <div class="supervisor-stat-top"><span class="supervisor-stat-icon"><i class="bi bi-check2-circle"></i></span><span class="supervisor-stat-tag">READY FOR ADMIN</span></div>
            <strong>{{ number_format($stats['first_level_approved']) }}</strong><span class="supervisor-stat-label">First-level approvals recorded</span>
        </article>
        <article class="supervisor-stat" style="--stat-color:#3538cd;--stat-tint:#efefff">
            <div class="supervisor-stat-top"><span class="supervisor-stat-icon"><i class="bi bi-people-fill"></i></span><span class="supervisor-stat-tag">YOUR BRANCH</span></div>
            <strong>{{ number_format($stats['borrowers']) }}</strong><span class="supervisor-stat-label">Registered borrowers</span>
        </article>
        <article class="supervisor-stat" style="--stat-color:#175cd3;--stat-tint:#eff8ff">
            <div class="supervisor-stat-top"><span class="supervisor-stat-icon"><i class="bi bi-bank2"></i></span><span class="supervisor-stat-tag">ACTIVE PORTFOLIO</span></div>
            <strong>KSh {{ number_format($stats['portfolio_cents'] / 100) }}</strong><span class="supervisor-stat-label">{{ number_format($stats['active_loans']) }} active or overdue loans</span>
        </article>
    </section>

    <div class="supervisor-grid">
        <section class="supervisor-panel">
            <div class="supervisor-panel-head">
                <div><h2>Applications to review</h2><p>Submitted applications from your branch that need attention.</p></div>
                <a href="{{ route('applications.index', ['status' => 'submitted']) }}" class="supervisor-count">{{ number_format($stats['review_queue']) }} in queue</a>
            </div>
            @if($reviewQueue->isEmpty())
                <div class="supervisor-empty"><i class="bi bi-check-circle"></i><strong>You’re all caught up</strong>No applications are waiting for first-level review.</div>
            @else
                <div class="supervisor-table-wrap">
                    <table class="supervisor-table">
                        <thead><tr><th>APPLICATION</th><th>FACILITY</th><th>REQUESTED</th><th>STATUS</th><th></th></tr></thead>
                        <tbody>
                        @foreach($reviewQueue as $application)
                            <tr>
                                <td><div class="supervisor-person"><span class="supervisor-avatar">{{ strtoupper(substr($application->borrower->user->name ?? 'B', 0, 1)) }}</span><span><strong>{{ $application->borrower->user->name ?? 'Unknown borrower' }}</strong><small>#{{ str_pad($application->id, 6, '0', STR_PAD_LEFT) }} · {{ optional($application->submitted_at ?? $application->created_at)->diffForHumans() }}</small></span></div></td>
                                <td>{{ $application->loanProduct->name ?? '—' }}</td>
                                <td><strong>KSh {{ number_format($application->requested_amount_cents / 100) }}</strong></td>
                                <td><span class="supervisor-status">{{ str_replace('_', ' ', ucfirst($application->status)) }}</span></td>
                                <td><a class="supervisor-open" href="{{ route('applications.show', $application) }}" aria-label="Review application"><i class="bi bi-arrow-up-right"></i></a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="supervisor-panel">
            <div class="supervisor-panel-head"><div><h2>Quick actions</h2><p>Get to the work that matters.</p></div></div>
            <div class="supervisor-actions">
                <a href="{{ route('applications.index') }}" class="supervisor-action"><span class="supervisor-action-icon"><i class="bi bi-file-earmark-check"></i></span><span class="supervisor-action-copy"><strong>Review applications</strong><small>{{ number_format($stats['review_queue']) }} waiting for review</small></span><i class="bi bi-arrow-right supervisor-action-arrow"></i></a>
                <a href="{{ route('applications.index', ['status' => 'returned']) }}" class="supervisor-action"><span class="supervisor-action-icon"><i class="bi bi-arrow-return-left"></i></span><span class="supervisor-action-copy"><strong>Returned for correction</strong><small>{{ number_format($stats['returned']) }} need follow-up</small></span><i class="bi bi-arrow-right supervisor-action-arrow"></i></a>
                <a href="{{ route('applications.index', ['status' => 'first_level_approved']) }}" class="supervisor-action"><span class="supervisor-action-icon"><i class="bi bi-check2-all"></i></span><span class="supervisor-action-copy"><strong>First-level approvals</strong><small>{{ number_format($stats['first_level_approved']) }} sent for final review</small></span><i class="bi bi-arrow-right supervisor-action-arrow"></i></a>
                <a href="{{ route('borrowers.index') }}" class="supervisor-action"><span class="supervisor-action-icon"><i class="bi bi-people"></i></span><span class="supervisor-action-copy"><strong>Manage borrowers</strong><small>View and maintain branch profiles</small></span><i class="bi bi-arrow-right supervisor-action-arrow"></i></a>
                @if(auth()->user()->hasPermission('create-borrowers'))
                    <a href="{{ route('borrowers.create') }}" class="supervisor-action"><span class="supervisor-action-icon"><i class="bi bi-person-plus"></i></span><span class="supervisor-action-copy"><strong>Add a borrower</strong><small>Create a new branch profile</small></span><i class="bi bi-arrow-right supervisor-action-arrow"></i></a>
                @endif
                <a href="{{ route('loans.active') }}" class="supervisor-action"><span class="supervisor-action-icon"><i class="bi bi-bank"></i></span><span class="supervisor-action-copy"><strong>Active loans</strong><small>Track repayment performance</small></span><i class="bi bi-arrow-right supervisor-action-arrow"></i></a>
            </div>
            <div class="supervisor-note"><i class="bi bi-shield-check"></i><span>Every application and borrower shown here is limited to your assigned branch. Decisions are recorded in the audit trail.</span></div>
        </section>
    </div>
</div>
@endsection
