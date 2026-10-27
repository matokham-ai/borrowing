@extends('layouts.app')

@section('title', $payment ? 'Edit Payment' : 'Add Payment')

@section('content')
<div class="page-header">
    <div>
        <div class="eyebrow"><span class="eyebrow-dot"></span> COLLECTIONS OPERATIONS</div>
        <h1>{{ $payment ? 'Edit payment' : 'Add payment' }}</h1>
        <p>{{ $payment ? 'Update the payment record and its audit details.' : 'Record a cash, bank, or other repayment against a loan.' }}</p>
    </div>
    <a href="{{ route('payments.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back to payments</a>
</div>

<section class="panel" style="max-width:760px;padding:24px">
    <form method="POST" action="{{ $payment ? route('payments.update', $payment) : route('payments.store') }}" class="row g-3">
        @csrf
        @if($payment) @method('PUT') @endif
        @if(!$payment)
            <div class="col-12">
                <label class="form-label" for="loan_id">Loan</label>
                <select name="loan_id" id="loan_id" class="form-select" required>
                    <option value="">Select a loan</option>
                    @foreach($loans as $loan)
                        <option value="{{ $loan->id }}" @selected(old('loan_id') == $loan->id)>#{{ str_pad($loan->id, 6, '0', STR_PAD_LEFT) }} - {{ $loan->borrower?->user?->name ?? 'Borrower' }} ({{ $loan->loanProduct?->name ?? 'Loan' }})</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="col-md-6">
            <label class="form-label" for="amount">Amount (KSh)</label>
            <input type="number" step="0.01" min="0.01" name="amount" id="amount" class="form-control" value="{{ old('amount', $payment ? number_format($payment->amount_cents / 100, 2, '.', '') : '') }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="payment_method">Method</label>
            <select name="payment_method" id="payment_method" class="form-select" required>
                @foreach(['cash' => 'Cash', 'bank_transfer' => 'Bank transfer', 'mpesa' => 'M-Pesa', 'credit_balance' => 'Credit balance'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('payment_method', $payment?->payment_method ?? 'cash') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="external_reference">Reference</label>
            <input type="text" name="external_reference" id="external_reference" class="form-control" value="{{ old('external_reference', $payment?->external_reference) }}" maxlength="100">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="received_at">Received at</label>
            <input type="datetime-local" name="received_at" id="received_at" class="form-control" value="{{ old('received_at', $payment?->received_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}" required>
        </div>
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> {{ $payment ? 'Save changes' : 'Record payment' }}</button>
            <a href="{{ route('payments.index') }}" class="btn btn-light">Cancel</a>
        </div>
    </form>

    @if($payment)
        <hr class="my-4">
        <h2 class="h6">{{ $payment->isUnmatched() ? 'Match unmatched payment' : 'Change linked loan' }}</h2>
        <p class="text-muted small">Choose the loan this payment belongs to. It will be allocated immediately when it is matched.</p>
        <form method="POST" action="{{ route('payments.match', $payment) }}" class="d-flex gap-2">
            @csrf
            <select name="loan_id" class="form-select" required>
                <option value="">Select a loan</option>
                @foreach($loans as $loan)
                    <option value="{{ $loan->id }}">#{{ str_pad($loan->id, 6, '0', STR_PAD_LEFT) }} - {{ $loan->borrower?->user?->name ?? 'Borrower' }} ({{ $loan->loanProduct?->name ?? 'Loan' }})</option>
                @endforeach
            </select>
            <button class="btn btn-primary" type="submit"><i class="bi bi-link-45deg"></i> Match</button>
        </form>
        @if(!$payment->isUnmatched())
            <form method="POST" action="{{ route('payments.unmatch', $payment) }}" class="mt-3" onsubmit="return confirm('Unmatch this payment and return it to reconciliation?')">
                @csrf
                <button class="btn btn-outline-warning" type="submit"><i class="bi bi-link-45deg"></i> Unmatch payment</button>
            </form>
        @endif
    @endif

    @if($payment)
        @if($payment->status === 'posted' && $payment->loan_id)
            <hr class="my-4">
            <div class="d-flex gap-2 align-items-end flex-wrap">
                <form method="POST" action="{{ route('payments.allocate', $payment) }}">
                    @csrf
                    <button class="btn btn-light" type="submit"><i class="bi bi-arrow-repeat"></i> Refresh allocation</button>
                </form>
                <form method="POST" action="{{ route('payments.reverse', $payment) }}" class="d-flex gap-2 align-items-end" onsubmit="return confirm('{{ $payment->payment_method === 'mpesa' ? 'Submit a reversal request to Safaricom? The payment will remain active until Safaricom confirms the result.' : 'Reverse this payment? It will remain in the audit history.' }}')">
                    @csrf
                    <div><label class="form-label small" for="reason">Reversal reason</label><input type="text" name="reason" id="reason" class="form-control form-control-sm" placeholder="Reason (optional)" maxlength="500"></div>
                    <button class="btn btn-outline-danger" type="submit"><i class="bi bi-arrow-counterclockwise"></i> {{ $payment->payment_method === 'mpesa' ? 'Request M-Pesa reversal' : 'Reverse' }}</button>
                </form>
            </div>
        @endif
        <form method="POST" action="{{ route('payments.destroy', $payment) }}" class="mt-4" onsubmit="return confirm('Remove this payment from active records?')">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger" type="submit"><i class="bi bi-trash"></i> Remove payment</button>
        </form>
    @endif
</section>
@endsection
