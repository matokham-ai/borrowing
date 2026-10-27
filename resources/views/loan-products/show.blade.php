@extends('layouts.app')

@section('title', $product->name)

@section('content')
<style>
.pd{max-width:1100px;margin:auto;color:#101828}
.pd-head{display:flex;justify-content:space-between;align-items:center;gap:15px;margin-bottom:23px}
.pd-card{background:#fff;border:1px solid #e4e7ec;border-radius:15px;overflow:hidden;margin-bottom:18px}
.pd-intro{padding:25px;display:flex;align-items:center;gap:16px}
.pd-icon{height:54px;width:54px;border-radius:15px;background:#f1efff;color:#6554e8;display:grid;place-items:center;font-size:23px}
.pd-name{font-size:23px;font-weight:800;letter-spacing:-.6px;margin:0 0 5px}
.pd-code{font-size:12px;color:#98a2b3}
.pd-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr))}
.pd-item{padding:20px 23px;border-top:1px solid #eaecf0}
.pd-label{font-size:10px;color:#98a2b3;font-weight:700;text-transform:uppercase;letter-spacing:.06em}
.pd-value{font-size:15px;font-weight:750;margin-top:7px}
.pd-description{padding:20px 23px;border-top:1px solid #eaecf0;font-size:12px;color:#667085;line-height:1.7}
.pd-btn{display:inline-flex;align-items:center;gap:7px;padding:10px 14px;border:1px solid #e4e7ec;border-radius:9px;text-decoration:none;color:#344054;font-size:12px;font-weight:700;background:#fff}
.pd-primary{background:#6554e8;border-color:#6554e8;color:white}
@media(max-width:650px){.pd-head{align-items:flex-start;flex-direction:column}.pd-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.pd-item{padding:16px}}
</style>

<div class="pd">
    <div class="pd-head">
        <div>
            <div style="font-size:10px;color:#7665ed;font-weight:800;letter-spacing:.13em">PRODUCT DETAILS</div>
            <h1 style="font-size:25px;font-weight:800;margin:7px 0">Loan product overview</h1>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="pd-btn" href="{{ route('loan-products.index') }}">Back</a>
            <a class="pd-btn pd-primary" href="{{ route('loan-products.edit', $product) }}"><i class="bi bi-pencil"></i> Edit product</a>
        </div>
    </div>

    <section class="pd-card">
        <div class="pd-intro">
            <div class="pd-icon"><i class="bi bi-wallet2"></i></div>
            <div>
                <h2 class="pd-name">{{ $product->name }}</h2>
                <div class="pd-code">{{ $product->code }}</div>
                <div style="margin-top:10px;font-size:11px;font-weight:700;color:{{ $product->is_active ? '#027a48' : '#667085' }}">{{ $product->is_active ? '● Active' : '● Inactive' }}</div>
            </div>
        </div>
        <div class="pd-grid">
            <div class="pd-item"><div class="pd-label">Minimum loan</div><div class="pd-value">KES {{ number_format($product->minimum_amount_cents / 100, 2) }}</div></div>
            <div class="pd-item"><div class="pd-label">Maximum loan</div><div class="pd-value">KES {{ number_format($product->maximum_amount_cents / 100, 2) }}</div></div>
            <div class="pd-item"><div class="pd-label">Interest rate</div><div class="pd-value">{{ number_format($product->interest_rate * 100, 2) }}% {{ str_replace('_', ' ', $product->interest_period) }}</div></div>
            <div class="pd-item"><div class="pd-label">Loan term</div><div class="pd-value">{{ $product->minimum_term_days }}–{{ $product->maximum_term_days }} days</div></div>
            <div class="pd-item"><div class="pd-label">Interest method</div><div class="pd-value">{{ ucfirst(str_replace('_', ' ', $product->interest_type)) }}</div></div>
            <div class="pd-item"><div class="pd-label">Repayment frequency</div><div class="pd-value">{{ ucfirst(str_replace('_', ' ', $product->repayment_frequency)) }}</div></div>
            <div class="pd-item">
                <div class="pd-label">Fee structure</div>
                <div class="pd-value">
                    @if($product->fee_type === 'percentage')
                        {{ number_format($product->fee_rate * 100, 2) }}%
                    @elseif($product->fee_type === 'fixed')
                        KES {{ number_format($product->fixed_fee_cents / 100, 2) }}
                    @else
                        None
                    @endif
                </div>
            </div>
            <div class="pd-item"><div class="pd-label">Grace period</div><div class="pd-value">{{ $product->grace_period_days }} days</div></div>
            <div class="pd-item"><div class="pd-label">Version</div><div class="pd-value">{{ $product->version }}</div></div>
        </div>
        <div class="pd-description">
            <strong style="color:#344054">Description</strong>
            <div style="margin-top:7px">{{ $product->description ?: 'No description has been added.' }}</div>
            <div style="margin-top:15px">
                Borrower appraisal: <strong>{{ $product->requires_appraisal ? 'Required' : 'Not required' }}</strong>
                &nbsp; · &nbsp;
                Financial statement: <strong>{{ $product->requires_statement ? 'Required' : 'Not required' }}</strong>
            </div>
        </div>
    </section>
</div>
@endsection
