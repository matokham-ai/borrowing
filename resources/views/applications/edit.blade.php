@extends('layouts.app')

@section('title', 'Edit Application')

@section('content')
<div class="page-header">
    <div>
        <div class="eyebrow">LOAN APPLICATIONS</div>
        <h1>Edit Application #{{ str_pad($application->id, 6, '0', STR_PAD_LEFT) }}</h1>
        <p class="page-subtitle">Update an application before a decision is made.</p>
    </div>
    <a href="{{ route('applications.show', $application) }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="form-layout">
    <form method="POST" action="{{ route('applications.update', $application) }}" class="form-card">
        @csrf
        @method('PUT')

        <div class="form-grid">
            <div class="form-field form-field-full">
                <label>Borrower <span>*</span></label>
                <select name="borrower_id" class="form-input" required>
                    @foreach($borrowers as $borrower)
                        <option value="{{ $borrower->id }}" @selected($application->borrower_id == $borrower->id)>
                            {{ $borrower->user->name ?? 'Borrower #'.$borrower->id }} · {{ $borrower->phone_number }}
                        </option>
                    @endforeach
                </select>
                @error('borrower_id') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-field">
                <label>Loan Product <span>*</span></label>
                <select name="loan_product_id" class="form-input" required>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected($application->loan_product_id == $product->id)>
                            {{ $product->name }} ({{ $product->code }})
                        </option>
                    @endforeach
                </select>
                @error('loan_product_id') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-field">
                <label>Requested Amount (KSh) <span>*</span></label>
                <input type="number" step="0.01" name="requested_amount" class="form-input"
                       value="{{ old('requested_amount', $application->requested_amount_cents / 100) }}" required>
                @error('requested_amount') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div class="form-field">
                <label>Requested Term (Days) <span>*</span></label>
                <input type="number" min="1" name="requested_term_days" class="form-input"
                       value="{{ old('requested_term_days', $application->requested_term_days) }}" required>
                @error('requested_term_days') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-card-footer">
            <a href="{{ route('applications.show', $application) }}" class="btn btn-light">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>
@endsection
