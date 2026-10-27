@extends('layouts.app')
@section('title', 'Payments Configuration')

@section('content')

{{-- ══════════════════════════════════════════
     PAGE HEADER
══════════════════════════════════════════ --}}
<div class="page-header">
    <div>
        <div class="eyebrow">ORGANISATION CONFIGURATION</div>
        <h1>Payments Configuration</h1>
        <p>Configure payment processing and reconciliation settings.</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('organisation.index') }}" class="btn btn-light">
            <i class="bi bi-arrow-left"></i> Back to Overview
        </a>
    </div>
</div>

{{-- ══════════════════════════════════════════
     CONFIGURATION TABS
══════════════════════════════════════════ --}}
<div class="org-config-tabs-wrapper">
    <div class="org-config-tabs">
        <a href="{{ route('organisation.general') }}" class="org-config-tab">
            <i class="bi bi-gear"></i> General
        </a>
        <a href="{{ route('organisation.lending') }}" class="org-config-tab">
            <i class="bi bi-bank"></i> Lending
        </a>
        <a href="{{ route('organisation.payments') }}" class="org-config-tab active">
            <i class="bi bi-credit-card"></i> Payments
        </a>
        <a href="{{ route('organisation.notifications') }}" class="org-config-tab">
            <i class="bi bi-bell"></i> Notifications
        </a>
        <a href="{{ route('organisation.branding') }}" class="org-config-tab">
            <i class="bi bi-palette"></i> Branding
        </a>
    </div>
</div>

{{-- ══════════════════════════════════════════
     PAYMENTS CONFIGURATION
══════════════════════════════════════════ --}}
<div class="config-form-container">
    <form action="{{ route('organisation.update.payments') }}" method="POST">
        @csrf
        @method('PUT')

        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @php($selectedEnvironment = old('environment', $paymentSettings->first()?->environment ?? 'sandbox'))
        @php($selectedSetting = $paymentSettings->firstWhere('environment', $selectedEnvironment))

        <div class="config-form-section">
            <h3>Payment Gateway</h3>
            <p class="config-form-subtitle">Connect this organisation to M-Pesa Daraja for collections and STK Push payments.</p>

            <div class="form-grid">
                <div class="form-group">
                    <label for="environment">Environment</label>
                    <select id="environment" name="environment" class="form-control" required>
                        <option value="sandbox" @selected($selectedEnvironment === 'sandbox')>Sandbox</option>
                        <option value="production" @selected($selectedEnvironment === 'production')>Production</option>
                    </select>
                    <small class="form-text">Use Sandbox while testing. Production sends real payment requests.</small>
                </div>

                <div class="form-group">
                    <label for="business_shortcode">Business Shortcode</label>
                    <input id="business_shortcode" name="business_shortcode" class="form-control" value="{{ old('business_shortcode', $selectedSetting?->business_shortcode) }}" maxlength="30">
                    <small class="form-text">The paybill or business number used for collections.</small>
                </div>
            </div>
        </div>

        <div class="config-form-section">
            <h3>Daraja Credentials</h3>
            <p class="config-form-subtitle">Credentials are encrypted at rest. Leave a credential blank to keep the saved value.</p>

            <div class="form-grid">
                <div class="form-group">
                    <label for="consumer_key">Consumer Key</label>
                    <input id="consumer_key" name="consumer_key" type="password" class="form-control" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="consumer_secret">Consumer Secret</label>
                    <input id="consumer_secret" name="consumer_secret" type="password" class="form-control" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="passkey">STK Passkey</label>
                    <input id="passkey" name="passkey" type="password" class="form-control" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="g2_password">G2 Password <span class="form-label-muted">Optional</span></label>
                    <input id="g2_password" name="g2_password" type="password" class="form-control" autocomplete="new-password">
                </div>
            </div>
        </div>

        <div class="config-form-section">
            <h3>Routing Identifiers</h3>
            <p class="config-form-subtitle">Optional identifiers used by your M-Pesa account structure.</p>

            <div class="form-grid">
                <div class="form-group">
                    <label for="head_office">Head Office</label>
                    <input id="head_office" name="head_office" class="form-control" value="{{ old('head_office', $selectedSetting?->head_office) }}" maxlength="30">
                </div>
                <div class="form-group">
                    <label for="store_number">Store Number</label>
                    <input id="store_number" name="store_number" class="form-control" value="{{ old('store_number', $selectedSetting?->store_number) }}" maxlength="30">
                </div>
                <div class="form-group">
                    <label for="till_number">Till Number</label>
                    <input id="till_number" name="till_number" class="form-control" value="{{ old('till_number', $selectedSetting?->till_number) }}" maxlength="30">
                </div>
            </div>
        </div>

        <div class="config-form-actions">
            <label class="form-check">
                <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $selectedSetting?->is_active ?? true))>
                <span class="form-check-label">Enable payment processing for this environment</span>
            </label>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Save Payment Settings</button>
            <a href="{{ route('organisation.index') }}" class="btn btn-light">Cancel</a>
        </div>
    </form>
</div>

@endsection
