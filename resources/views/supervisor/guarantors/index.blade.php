@extends('layouts.app')

@section('title', 'Guarantors')

@push('styles')
<style>
.guarantors-page{padding:0 30px}
.guarantors-hero{display:flex;align-items:center;justify-content:space-between;gap:18px;margin:24px 0 18px;padding:25px 28px;border:1px solid #e3e7ff;border-radius:18px;background:radial-gradient(circle at 90% 15%,rgba(117,106,255,.18),transparent 28%),linear-gradient(115deg,#fff,#f3f4ff);box-shadow:0 10px 28px rgba(31,41,90,.05)}
.guarantors-kicker{margin:0 0 8px;color:#5b5ce2;font-size:9px;font-weight:700;letter-spacing:.12em;text-transform:uppercase}
.guarantors-hero h1{margin:0;color:#111a35;font-size:24px;letter-spacing:-.035em}
.guarantors-hero p{margin:7px 0 0;color:#667085;font-size:11px}
.guarantors-total{display:flex;align-items:center;gap:11px;padding:11px 14px;border:1px solid #e8e9ff;border-radius:13px;background:rgba(255,255,255,.8)}
.guarantors-total-icon{display:grid;width:36px;height:36px;place-items:center;border-radius:11px;background:#efefff;color:#5b5ce2}
.guarantors-total strong,.guarantors-total small{display:block}
.guarantors-total strong{color:#202945;font-size:17px}
.guarantors-total small{margin-top:2px;color:#98a2b3;font-size:9px}
.guarantors-panel{overflow:hidden;border:1px solid #eaecf0;border-radius:16px;background:#fff;box-shadow:0 5px 18px rgba(16,24,40,.035)}
.guarantors-panel-head{padding:18px 20px;border-bottom:1px solid #f2f4f7}
.guarantors-panel-head h2{margin:0;color:#101828;font-size:14px}
.guarantors-panel-head p{margin:5px 0 0;color:#98a2b3;font-size:10px}
.guarantors-filter{display:flex;gap:9px;padding:13px 18px;border-bottom:1px solid #f2f4f7}
.guarantors-search{position:relative;flex:1}
.guarantors-search i{position:absolute;top:10px;left:11px;color:#98a2b3}
.guarantors-search input{width:100%;height:35px;padding:0 12px 0 32px;border:1px solid #eaecf0;border-radius:9px;outline:none;color:#344054;font-size:10px}
.guarantors-search input:focus{border-color:#a5a6f6;box-shadow:0 0 0 3px rgba(91,92,226,.08)}
.guarantors-table-wrap{overflow:auto}
.guarantors-table{width:100%;border-collapse:collapse;white-space:nowrap}
.guarantors-table th{padding:11px 17px;background:#fcfcfd;color:#98a2b3;font-size:8px;font-weight:700;letter-spacing:.09em;text-align:left}
.guarantors-table td{padding:12px 17px;border-top:1px solid #f2f4f7;color:#475467;font-size:10px}
.guarantor-person{display:flex;align-items:center;gap:9px}
.guarantor-avatar{display:grid;width:30px;height:30px;place-items:center;border-radius:10px;background:#efefff;color:#595bd6;font-size:10px;font-weight:700}
.guarantor-person strong,.guarantor-person small{display:block}
.guarantor-person strong{color:#202945;font-size:10px}
.guarantor-person small{margin-top:3px;color:#98a2b3;font-size:9px}
.guarantor-application{display:inline-flex;align-items:center;gap:5px;padding:5px 8px;border-radius:20px;background:#f1f0ff;color:#595bd6;font-size:8px;font-weight:700}
.guarantor-open{display:inline-flex;align-items:center;gap:6px;color:#5b5ce2;font-size:9px;font-weight:600;text-decoration:none}
.guarantor-open:hover{text-decoration:underline}
.guarantors-empty{padding:44px 20px;color:#98a2b3;text-align:center}
.guarantors-empty i{display:block;margin-bottom:9px;color:#5b5ce2;font-size:22px}
.guarantors-empty strong{display:block;margin-bottom:5px;color:#344054;font-size:12px}
.guarantors-pagination{padding:15px 18px}
@media(max-width:650px){.guarantors-page{padding:0 15px}.guarantors-hero{align-items:flex-start;flex-direction:column;padding:21px}.guarantors-hero h1{font-size:21px}.guarantors-total{width:100%}.guarantors-filter{padding:11px}.guarantors-table th,.guarantors-table td{padding-right:12px;padding-left:12px}}
</style>
@endpush

@section('content')
<div class="guarantors-page">
    <section class="guarantors-hero">
        <div>
            <p class="guarantors-kicker">Branch operations · {{ auth()->user()->branch?->name ?? 'Assigned branch' }}</p>
            <h1>Guarantors</h1>
            <p>Review guarantor records attached to applications in your branch.</p>
        </div>
        <div class="guarantors-total">
            <span class="guarantors-total-icon"><i class="bi bi-person-badge-fill"></i></span>
            <span><strong>{{ number_format($applications->total()) }}</strong><small>Applications with security details</small></span>
        </div>
    </section>

    <section class="guarantors-panel">
        <div class="guarantors-panel-head">
            <h2>Guarantor register</h2>
            <p>Open an application to review full details or update its guarantor information.</p>
        </div>
        <form method="GET" class="guarantors-filter">
            <label class="guarantors-search">
                <i class="bi bi-search"></i>
                <input type="search" name="search" value="{{ $search }}" placeholder="Search borrower, product or application number">
            </label>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
            @if($search)
                <a href="{{ route('supervisor.guarantors.index') }}" class="btn btn-light">Clear</a>
            @endif
        </form>
        @if($guarantors->isEmpty())
            <div class="guarantors-empty"><i class="bi bi-person-badge"></i><strong>No guarantor records found</strong>Guarantor information will appear here when it is added to a branch loan application.</div>
        @else
            <div class="guarantors-table-wrap">
                <table class="guarantors-table">
                    <thead><tr><th>GUARANTOR</th><th>RELATIONSHIP</th><th>BORROWER</th><th>APPLICATION</th><th>STATUS</th><th></th></tr></thead>
                    <tbody>
                    @foreach($guarantors as $application)
                        @php $guarantor = data_get($application->security_data, 'guarantor', []); @endphp
                        <tr>
                            <td><div class="guarantor-person"><span class="guarantor-avatar">{{ strtoupper(substr($guarantor['name'], 0, 1)) }}</span><span><strong>{{ $guarantor['name'] }}</strong><small>{{ $guarantor['phone'] ?? 'Phone not provided' }}</small></span></div></td>
                            <td>{{ $guarantor['relationship'] ?? '—' }}</td>
                            <td>{{ $application->borrower->user->name ?? 'Unknown borrower' }}</td>
                            <td><span class="guarantor-application">#{{ str_pad($application->id, 6, '0', STR_PAD_LEFT) }}</span><small style="display:block;margin-top:4px;color:#98a2b3">{{ $application->loanProduct->name ?? 'Loan application' }}</small></td>
                            <td>{{ str_replace('_', ' ', ucfirst($application->status)) }}</td>
                            <td><a class="guarantor-open" href="{{ route('applications.show', $application) }}">Open application <i class="bi bi-arrow-up-right"></i></a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="guarantors-pagination">{{ $applications->links() }}</div>
        @endif
    </section>
</div>
@endsection
