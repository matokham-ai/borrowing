@extends('layouts.app')
@section('title', 'General Configuration')

@section('content')

{{-- ══════════════════════════════════════════
     PAGE HEADER
══════════════════════════════════════════ --}}
<div class="page-header">
    <div>
        <div class="eyebrow">ORGANISATION CONFIGURATION</div>
        <h1>General Settings</h1>
        <p>Configure your organisation's basic operating settings.</p>
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
        <a href="{{ route('organisation.general') }}" class="org-config-tab active">
            <i class="bi bi-gear"></i> General
        </a>
        <a href="{{ route('organisation.lending') }}" class="org-config-tab">
            <i class="bi bi-bank"></i> Lending
        </a>
        <a href="{{ route('organisation.payments') }}" class="org-config-tab">
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
     GENERAL CONFIGURATION FORM
══════════════════════════════════════════ --}}
<div class="config-form-container">
    <form action="{{ route('organisation.update.general') }}" method="POST">
        @csrf
        @method('PUT')

        @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
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

        <div class="config-form-section">
            <h3>Basic Settings</h3>
            <p class="config-form-subtitle">Core operating parameters for your organisation</p>

            <div class="form-grid">
                <div class="form-group">
                    <label for="country_code">Country</label>
                    <select id="country_code" name="country_code" class="form-control" required>
                        <option value="KE" {{ $organisation->country_code === 'KE' ? 'selected' : '' }}>Kenya</option>
                        <option value="UG" {{ $organisation->country_code === 'UG' ? 'selected' : '' }}>Uganda</option>
                        <option value="TZ" {{ $organisation->country_code === 'TZ' ? 'selected' : '' }}>Tanzania</option>
                        <option value="RW" {{ $organisation->country_code === 'RW' ? 'selected' : '' }}>Rwanda</option>
                        <option value="ZM" {{ $organisation->country_code === 'ZM' ? 'selected' : '' }}>Zambia</option>
                        <option value="ZW" {{ $organisation->country_code === 'ZW' ? 'selected' : '' }}>Zimbabwe</option>
                    </select>
                    <small class="form-text">The country where your organisation operates</small>
                </div>

                <div class="form-group">
                    <label for="currency">Currency</label>
                    <select id="currency" name="currency" class="form-control" required>
                        <option value="KES" {{ $organisation->currency === 'KES' ? 'selected' : '' }}>KES - Kenyan Shilling</option>
                        <option value="UGX" {{ $organisation->currency === 'UGX' ? 'selected' : '' }}>UGX - Ugandan Shilling</option>
                        <option value="TZS" {{ $organisation->currency === 'TZS' ? 'selected' : '' }}>TZS - Tanzanian Shilling</option>
                        <option value="RWF" {{ $organisation->currency === 'RWF' ? 'selected' : '' }}>RWF - Rwandan Franc</option>
                        <option value="ZMW" {{ $organisation->currency === 'ZMW' ? 'selected' : '' }}>ZMW - Zambian Kwacha</option>
                        <option value="ZWL" {{ $organisation->currency === 'ZWL' ? 'selected' : '' }}>ZWL - Zimbabwean Dollar</option>
                    </select>
                    <small class="form-text">Primary currency for all transactions</small>
                </div>

                <div class="form-group">
                    <label for="timezone">Timezone</label>
                    <select id="timezone" name="timezone" class="form-control" required>
                        <option value="Africa/Nairobi" {{ $organisation->timezone === 'Africa/Nairobi' ? 'selected' : '' }}>Africa/Nairobi</option>
                        <option value="Africa/Kampala" {{ $organisation->timezone === 'Africa/Kampala' ? 'selected' : '' }}>Africa/Kampala</option>
                        <option value="Africa/Dar_es_Salaam" {{ $organisation->timezone === 'Africa/Dar_es_Salaam' ? 'selected' : '' }}>Africa/Dar_es_Salaam</option>
                        <option value="Africa/Kigali" {{ $organisation->timezone === 'Africa/Kigali' ? 'selected' : '' }}>Africa/Kigali</option>
                        <option value="Africa/Lusaka" {{ $organisation->timezone === 'Africa/Lusaka' ? 'selected' : '' }}>Africa/Lusaka</option>
                        <option value="Africa/Harare" {{ $organisation->timezone === 'Africa/Harare' ? 'selected' : '' }}>Africa/Harare</option>
                    </select>
                    <small class="form-text">Timezone for scheduling and reporting</small>
                </div>

                <div class="form-group">
                    <label for="date_format">Date Format</label>
                    <select id="date_format" name="date_format" class="form-control" required>
                        <option value="DD/MM/YYYY" {{ $organisation->date_format === 'DD/MM/YYYY' ? 'selected' : '' }}>DD/MM/YYYY</option>
                        <option value="MM/DD/YYYY" {{ $organisation->date_format === 'MM/DD/YYYY' ? 'selected' : '' }}>MM/DD/YYYY</option>
                        <option value="YYYY-MM-DD" {{ $organisation->date_format === 'YYYY-MM-DD' ? 'selected' : '' }}>YYYY-MM-DD</option>
                        <option value="DD-MM-YYYY" {{ $organisation->date_format === 'DD-MM-YYYY' ? 'selected' : '' }}>DD-MM-YYYY</option>
                    </select>
                    <small class="form-text">Preferred date display format</small>
                </div>
            </div>
        </div>

        <div class="config-form-section">
            <h3>Financial Year</h3>
            <p class="config-form-subtitle">Define your organisation's financial reporting period</p>

            <div class="form-grid">
                <div class="form-group">
                    <label for="financial_year_start">Financial Year Start</label>
                    <select id="financial_year_start" name="financial_year_start" class="form-control" required>
                        @foreach(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $month)
                        <option value="{{ $month }}" {{ $organisation->financial_year_start === $month ? 'selected' : '' }}>{{ $month }}</option>
                        @endforeach
                    </select>
                    <small class="form-text">Month when your financial year begins</small>
                </div>

                <div class="form-group">
                    <label for="financial_year_end">Financial Year End</label>
                    <select id="financial_year_end" name="financial_year_end" class="form-control" required>
                        @foreach(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $month)
                        <option value="{{ $month }}" {{ $organisation->financial_year_end === $month ? 'selected' : '' }}>{{ $month }}</option>
                        @endforeach
                    </select>
                    <small class="form-text">Month when your financial year ends</small>
                </div>
            </div>
        </div>

        <div class="config-form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle"></i> Save Changes
            </button>
            <a href="{{ route('organisation.index') }}" class="btn btn-light">
                Cancel
            </a>
        </div>
    </form>
</div>

@endsection