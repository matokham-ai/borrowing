@extends('layouts.app')

@section('title', $product->exists ? 'Edit Loan Product' : 'New Loan Product')

@section('content')
<style>
.pf{max-width:1120px;margin:auto;color:#101828}
.pf-head{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:24px}
.pf-kicker{font-size:10px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:#7665ed}
.pf-title{font-size:26px;font-weight:800;letter-spacing:-.8px;margin:5px 0}
.pf-subtitle{font-size:12px;color:#667085;margin:0}
.pf-card{background:#fff;border:1px solid #e4e7ec;border-radius:15px;overflow:hidden;margin-bottom:18px}
.pf-section{padding:21px 23px;border-bottom:1px solid #eaecf0}
.pf-section:last-child{border-bottom:0}
.pf-section h3{font-size:13px;font-weight:800;margin:0 0 5px}
.pf-section p{font-size:11px;color:#98a2b3;margin:0 0 19px}
.pf-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:17px}
.pf-field label{display:block;font-size:11px;font-weight:700;color:#344054;margin-bottom:7px}
.pf-control{display:block;width:100%;height:41px;border:1px solid #d0d5dd;border-radius:9px;padding:0 12px;background:#fff;font-size:12px;color:#344054;outline:none}
textarea.pf-control{height:92px;padding:11px;resize:vertical}
.pf-control:focus{border-color:#8b7cf6;box-shadow:0 0 0 3px #6554e818}
.pf-hint{font-size:10px;color:#98a2b3;margin-top:5px}
.pf-error{font-size:11px;color:#d92d20;margin-top:5px}
.pf-checks{display:flex;flex-wrap:wrap;gap:12px}
.pf-check{display:flex;align-items:center;gap:9px;padding:12px;border:1px solid #eaecf0;border-radius:10px;font-size:11px;font-weight:600;flex:1;min-width:190px}
.pf-check input{accent-color:#6554e8}
.pf-footer{display:flex;justify-content:flex-end;gap:9px;padding:16px 23px;background:#fcfcfd;border-top:1px solid #eaecf0}
.pf-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 15px;border:1px solid #e4e7ec;border-radius:9px;text-decoration:none;font-size:12px;font-weight:700;background:#fff;color:#344054;cursor:pointer}
.pf-primary{background:#6554e8;border-color:#6554e8;color:#fff}
.pf-primary:hover{background:#5141d0;color:#fff}
@media(max-width:650px){.pf-grid{grid-template-columns:1fr}.pf-head{align-items:flex-start;flex-direction:column}.pf-section{padding:18px}}
</style>

<div class="pf">
    <div class="pf-head">
        <div>
            <div class="pf-kicker">Lending / Product configuration</div>
            <h1 class="pf-title">{{ $product->exists ? 'Edit loan product' : 'Create loan product' }}</h1>
            <p class="pf-subtitle">Define lending limits, pricing and repayment requirements.</p>
        </div>
        <a href="{{ route('loan-products.index') }}" class="pf-btn"><i class="bi bi-arrow-left"></i> Back to catalogue</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Please correct the following:</strong>
            <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $product->exists ? route('loan-products.update', $product) : route('loan-products.store') }}">
        @csrf
        @if($product->exists) @method('PUT') @endif

        <section class="pf-card">
            <div class="pf-section">
                <h3><i class="bi bi-card-text me-2"></i>Product identity</h3>
                <p>Give the product a clear name and a unique organisation-level code.</p>
                <div class="pf-grid">
                    <div class="pf-field">
                        <label for="name">Product name *</label>
                        <input id="name" name="name" class="pf-control" required maxlength="150" value="{{ old('name', $product->name) }}" placeholder="e.g. Business Growth Loan">
                        @error('name')<div class="pf-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="pf-field">
                        <label for="code">Product code *</label>
                        <input id="code" name="code" class="pf-control" required maxlength="50" value="{{ old('code', $product->code) }}" placeholder="e.g. BG-001">
                        <div class="pf-hint">Letters, numbers, hyphens and underscores only.</div>
                        @error('code')<div class="pf-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="pf-field" style="grid-column:1/-1">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="pf-control" maxlength="5000" placeholder="Describe the purpose and intended borrowers.">{{ old('description', $product->description) }}</textarea>
                        @error('description')<div class="pf-error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="pf-section">
                <h3><i class="bi bi-cash-stack me-2"></i>Lending limits</h3>
                <p>Set the principal amount and borrowing duration permitted under this product.</p>
                <div class="pf-grid">
                    <div class="pf-field">
                        <label for="minimum_amount">Minimum loan amount (KES) *</label>
                        <input id="minimum_amount" name="minimum_amount" type="number" min="0.01" step="0.01" class="pf-control" required value="{{ old('minimum_amount', $product->exists ? $product->minimum_amount_cents / 100 : '') }}">
                        @error('minimum_amount')<div class="pf-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="pf-field">
                        <label for="maximum_amount">Maximum loan amount (KES) *</label>
                        <input id="maximum_amount" name="maximum_amount" type="number" min="0.01" step="0.01" class="pf-control" required value="{{ old('maximum_amount', $product->exists ? $product->maximum_amount_cents / 100 : '') }}">
                        @error('maximum_amount')<div class="pf-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="pf-field">
                        <label for="minimum_term_days">Minimum term (days) *</label>
                        <input id="minimum_term_days" name="minimum_term_days" type="number" min="1" max="3650" class="pf-control" required value="{{ old('minimum_term_days', $product->minimum_term_days) }}">
                        @error('minimum_term_days')<div class="pf-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="pf-field">
                        <label for="maximum_term_days">Maximum term (days) *</label>
                        <input id="maximum_term_days" name="maximum_term_days" type="number" min="1" max="3650" class="pf-control" required value="{{ old('maximum_term_days', $product->maximum_term_days) }}">
                        @error('maximum_term_days')<div class="pf-error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="pf-section">
                <h3><i class="bi bi-percent me-2"></i>Interest and fees</h3>
                <p>Specify how interest and any product fees are calculated. Rates are entered as percentages.</p>
                <div class="pf-grid">
                    <div class="pf-field">
                        <label for="interest_period">Rate period *</label>
                        <select id="interest_period" name="interest_period" class="pf-control" required>
                            <option value="per_month" @selected(old('interest_period', $product->interest_period) === 'per_month')>Per month</option>
                            <option value="per_loan" @selected(old('interest_period', $product->interest_period) === 'per_loan')>For the full loan</option>
                        </select>
                        @error('interest_period')<div class="pf-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="pf-field">
                        <label for="interest_type">Interest calculation *</label>
                        <select id="interest_type" name="interest_type" class="pf-control" required>
                            <option value="flat" @selected(old('interest_type', $product->interest_type) === 'flat')>Flat rate</option>
                            <option value="reducing_balance" @selected(old('interest_type', $product->interest_type) === 'reducing_balance')>Reducing balance</option>
                        </select>
                        <div class="pf-hint">Monthly rates and reducing-balance calculations are saved as inactive until pricing support is approved.</div>
                        @error('interest_type')<div class="pf-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="pf-field">
                        <label for="interest_rate">Interest rate (%) *</label>
                        <input id="interest_rate" name="interest_rate" type="number" min="0" max="100" step="0.01" class="pf-control" required value="{{ old('interest_rate', $product->exists ? $product->interest_rate * 100 : 5) }}">
                        @error('interest_rate')<div class="pf-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="pf-field">
                        <label for="fee_type">Fee structure *</label>
                        <select id="fee_type" name="fee_type" class="pf-control" required>
                            <option value="none" @selected(old('fee_type', $product->fee_type) === 'none')>No fee</option>
                            <option value="percentage" @selected(old('fee_type', $product->fee_type) === 'percentage')>Percentage fee</option>
                            <option value="fixed" @selected(old('fee_type', $product->fee_type) === 'fixed')>Fixed fee (KES)</option>
                        </select>
                        @error('fee_type')<div class="pf-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="pf-field">
                        <label for="fee_rate">Fee rate (%)</label>
                        <input id="fee_rate" name="fee_rate" type="number" min="0" max="100" step="0.01" class="pf-control" value="{{ old('fee_rate', $product->exists ? $product->fee_rate * 100 : 0) }}">
                        @error('fee_rate')<div class="pf-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="pf-field">
                        <label for="fixed_fee">Fixed fee (KES)</label>
                        <input id="fixed_fee" name="fixed_fee" type="number" min="0" step="0.01" class="pf-control" value="{{ old('fixed_fee', $product->exists ? $product->fixed_fee_cents / 100 : 0) }}">
                        @error('fixed_fee')<div class="pf-error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="pf-section">
                <h3><i class="bi bi-calendar2-check me-2"></i>Repayment settings</h3>
                <p>Configure the repayment interval and any initial grace period.</p>
                <div class="pf-grid">
                    <div class="pf-field">
                        <label for="repayment_frequency">Repayment frequency *</label>
                        <select id="repayment_frequency" name="repayment_frequency" class="pf-control" required>
                            @foreach(['daily'=>'Daily','weekly'=>'Weekly','biweekly'=>'Every two weeks','monthly'=>'Monthly'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('repayment_frequency', $product->repayment_frequency) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('repayment_frequency')<div class="pf-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="pf-field">
                        <label for="grace_period_days">Grace period (days) *</label>
                        <input id="grace_period_days" name="grace_period_days" type="number" min="0" max="365" class="pf-control" required value="{{ old('grace_period_days', $product->grace_period_days ?? 0) }}">
                        @error('grace_period_days')<div class="pf-error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="pf-section">
                <h3><i class="bi bi-shield-check me-2"></i>Eligibility and availability</h3>
                <p>Set the checks required before an applicant can proceed.</p>
                <div class="pf-checks">
                    <label class="pf-check"><input type="checkbox" name="requires_appraisal" value="1" @checked(old('requires_appraisal', $product->requires_appraisal))> Require borrower appraisal</label>
                    <label class="pf-check"><input type="checkbox" name="requires_statement" value="1" @checked(old('requires_statement', $product->requires_statement))> Require financial statement</label>
                    <label class="pf-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->exists ? $product->is_active : true))> Product is active</label>
                </div>
            </div>

            <div class="pf-footer">
                <a href="{{ route('loan-products.index') }}" class="pf-btn">Cancel</a>
                <button type="submit" class="pf-btn pf-primary"><i class="bi bi-check2-circle"></i> {{ $product->exists ? 'Save changes' : 'Create loan product' }}</button>
            </div>
        </section>
    </form>
</div>
@endsection
