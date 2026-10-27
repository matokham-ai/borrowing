@extends('layouts.app')

@section('title', 'My Guarantors')

@push('styles')
<style>
.officer-guarantors-page{padding:0 30px}
.officer-guarantors-hero{display:flex;align-items:center;justify-content:space-between;gap:18px;margin:24px 0 18px;padding:25px 28px;border:1px solid #e3e7ff;border-radius:18px;background:radial-gradient(circle at 90% 15%,rgba(117,106,255,.18),transparent 28%),linear-gradient(115deg,#fff,#f3f4ff);box-shadow:0 10px 28px rgba(31,41,90,.05)}
.officer-guarantors-kicker{margin:0 0 8px;color:#5b5ce2;font-size:9px;font-weight:700;letter-spacing:.12em;text-transform:uppercase}
.officer-guarantors-hero h1{margin:0;color:#111a35;font-size:24px;letter-spacing:-.035em}
.officer-guarantors-hero p{margin:7px 0 0;color:#667085;font-size:11px}
.officer-guarantors-total{display:flex;align-items:center;gap:11px;padding:11px 14px;border:1px solid #e8e9ff;border-radius:13px;background:rgba(255,255,255,.8)}
.officer-guarantors-total-icon{display:grid;width:36px;height:36px;place-items:center;border-radius:11px;background:#efefff;color:#5b5ce2}
.officer-guarantors-total strong,.officer-guarantors-total small{display:block}
.officer-guarantors-total strong{color:#202945;font-size:17px}
.officer-guarantors-total small{margin-top:2px;color:#98a2b3;font-size:9px}
.officer-guarantors-panel{overflow:hidden;border:1px solid #eaecf0;border-radius:16px;background:#fff;box-shadow:0 5px 18px rgba(16,24,40,.035)}
.officer-guarantors-panel-head{padding:18px 20px;border-bottom:1px solid #f2f4f7}
.officer-guarantors-panel-head h2{margin:0;color:#101828;font-size:14px}
.officer-guarantors-panel-head p{margin:5px 0 0;color:#98a2b3;font-size:10px}
.officer-guarantors-filter{display:flex;gap:9px;padding:13px 18px;border-bottom:1px solid #f2f4f7}
.officer-guarantors-search{position:relative;flex:1}
.officer-guarantors-search i{position:absolute;top:10px;left:11px;color:#98a2b3}
.officer-guarantors-search input{width:100%;height:35px;padding:0 12px 0 32px;border:1px solid #eaecf0;border-radius:9px;outline:none;color:#344054;font-size:10px}
.officer-guarantors-search input:focus{border-color:#a5a6f6;box-shadow:0 0 0 3px rgba(91,92,226,.08)}
.officer-guarantors-table-wrap{overflow:auto}
.officer-guarantors-table{width:100%;border-collapse:collapse;white-space:nowrap}
.officer-guarantors-table th{padding:11px 17px;background:#fcfcfd;color:#98a2b3;font-size:8px;font-weight:700;letter-spacing:.09em;text-align:left}
.officer-guarantors-table td{padding:12px 17px;border-top:1px solid #f2f4f7;color:#475467;font-size:10px}
.officer-guarantor-person{display:flex;align-items:center;gap:9px}
.officer-guarantor-avatar{display:grid;width:30px;height:30px;place-items:center;border-radius:10px;background:#efefff;color:#595bd6;font-size:10px;font-weight:700}
.officer-guarantor-person strong,.officer-guarantor-person small{display:block}
.officer-guarantor-person strong{color:#202945;font-size:10px}
.officer-guarantor-person small{margin-top:3px;color:#98a2b3;font-size:9px}
.officer-guarantor-app{display:inline-flex;align-items:center;gap:5px;padding:5px 8px;border-radius:20px;background:#f1f0ff;color:#595bd6;font-size:8px;font-weight:700}
.officer-guarantor-open{display:inline-flex;align-items:center;gap:6px;color:#5b5ce2;font-size:9px;font-weight:600;text-decoration:none}
.officer-guarantor-open:hover{text-decoration:underline}
.officer-guarantors-empty{padding:44px 20px;color:#98a2b3;text-align:center}
.officer-guarantors-empty i{display:block;margin-bottom:9px;color:#5b5ce2;font-size:22px}
.officer-guarantors-empty strong{display:block;margin-bottom:5px;color:#344054;font-size:12px}
.officer-guarantors-pagination{padding:15px 18px}
@media(max-width:650px){.officer-guarantors-page{padding:0 15px}.officer-guarantors-hero{align-items:flex-start;flex-direction:column;padding:21px}.officer-guarantors-hero h1{font-size:21px}.officer-guarantors-total{width:100%}.officer-guarantors-filter{padding:11px}.officer-guarantors-table th,.officer-guarantors-table td{padding-right:12px;padding-left:12px}}
</style>
@endpush

@section('content')
<div class="officer-guarantors-page">
    <section class="officer-guarantors-hero">
        <div>
            <p class="officer-guarantors-kicker">My lending work · {{ auth()->user()->branch?->name ?? 'Assigned branch' }}</p>
            <h1>My guarantors</h1>
            <p>Guarantors attached to loan applications you originated.</p>
        </div>
        <div class="officer-guarantors-total">
            <span class="officer-guarantors-total-icon"><i class="bi bi-person-badge-fill"></i></span>
            <span><strong>{{ number_format($guarantors->count()) }}</strong><small>Records on this page</small></span>
        </div>
    </section>

    <section class="officer-guarantors-panel">
        <div class="officer-guarantors-panel-head">
            <h2>Guarantor register</h2>
            <p>Open an application to review guarantor documents, consent and progress.</p>
        </div>
        <form method="GET" class="officer-guarantors-filter">
            <label class="officer-guarantors-search">
                <i class="bi bi-search"></i>
                <input type="search" name="search" value="{{ $search }}" placeholder="Search borrower, product or application number">
            </label>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
            @if($search)
                <a href="{{ route('loan-officer.guarantors.index') }}" class="btn btn-light">Clear</a>
            @endif
        </form>
        @if($guarantors->isEmpty())
            <div class="officer-guarantors-empty"><i class="bi bi-person-badge"></i><strong>No guarantors to show yet</strong>Guarantor details will appear here after you add them to a loan application.</div>
        @else
            <div class="officer-guarantors-table-wrap">
                <table class="officer-guarantors-table">
                    <thead><tr><th>GUARANTOR</th><th>RELATIONSHIP</th><th>BORROWER</th><th>APPLICATION</th><th>LOAN STATUS</th><th></th></tr></thead>
                    <tbody>
                    @foreach($guarantors as $application)
                        @php $guarantor = data_get($application->security_data, 'guarantor', []); @endphp
                        <tr>
                            <td><div class="officer-guarantor-person"><span class="officer-guarantor-avatar">{{ strtoupper(substr($guarantor['name'], 0, 1)) }}</span><span><strong>{{ $guarantor['name'] }}</strong><small>{{ $guarantor['phone'] ?? 'Phone not provided' }}</small></span></div></td>
                            <td>{{ $guarantor['relationship'] ?? '—' }}</td>
                            <td>{{ $application->borrower->user->name ?? 'Unknown borrower' }}</td>
                            <td><span class="officer-guarantor-app">#{{ str_pad($application->id, 6, '0', STR_PAD_LEFT) }}</span><small style="display:block;margin-top:4px;color:#98a2b3">{{ $application->loanProduct->name ?? 'Loan application' }}</small></td>
                            <td>{{ str_replace('_', ' ', ucfirst($application->loan?->status ?? $application->status)) }}</td>
                            <td><a class="officer-guarantor-open" href="{{ route('applications.show', $application) }}">View details <i class="bi bi-arrow-up-right"></i></a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="officer-guarantors-pagination">{{ $applications->links() }}</div>
        @endif
    </section>
</div>
@endsection
