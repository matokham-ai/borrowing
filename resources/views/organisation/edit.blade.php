@extends('layouts.app')
@section('title', 'Edit Organisation')

@section('content')

{{-- ══════════════════════════════════════════
     PAGE HEADER
══════════════════════════════════════════ --}}
<div class="page-header">
    <div>
        <div class="eyebrow">ORGANISATION</div>
        <h1>Edit Organisation</h1>
        <p>Update your organisation's profile and contact information.</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('organisation.index') }}" class="btn btn-light">
            <i class="bi bi-arrow-left"></i> Back to Overview
        </a>
    </div>
</div>

{{-- ══════════════════════════════════════════
     EDIT FORM
══════════════════════════════════════════ --}}
<div class="config-form-container">
    <form action="{{ route('organisation.update') }}" method="POST">
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
            <h3>Identity Information</h3>
            <p class="config-form-subtitle">Basic organisation details and legal information</p>

            <div class="form-grid">
                <div class="form-group">
                    <label for="name">Legal Name *</label>
                    <input type="text" id="name" name="name" class="form-control"
                           value="{{ old('name', $organisation->name) }}" required>
                    <small class="form-text">Official legal name of the organisation</small>
                </div>

                <div class="form-group">
                    <label for="trading_name">Trading Name</label>
                    <input type="text" id="trading_name" name="trading_name" class="form-control"
                           value="{{ old('trading_name', $organisation->trading_name) }}">
                    <small class="form-text">Name used for business operations (if different)</small>
                </div>

                <div class="form-group">
                    <label for="organisation_type">Organisation Type</label>
                    <select id="organisation_type" name="organisation_type" class="form-control">
                        <option value="">Select type...</option>
                        <option value="Microfinance Institution" {{ $organisation->organisation_type === 'Microfinance Institution' ? 'selected' : '' }}>Microfinance Institution</option>
                        <option value="SACCO" {{ $organisation->organisation_type === 'SACCO' ? 'selected' : '' }}>SACCO</option>
                        <option value="Bank" {{ $organisation->organisation_type === 'Bank' ? 'selected' : '' }}>Bank</option>
                        <option value="Credit Union" {{ $organisation->organisation_type === 'Credit Union' ? 'selected' : '' }}>Credit Union</option>
                        <option value="NGO" {{ $organisation->organisation_type === 'NGO' ? 'selected' : '' }}>NGO</option>
                        <option value="Other" {{ $organisation->organisation_type === 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                    <small class="form-text">Type of lending institution</small>
                </div>

                <div class="form-group">
                    <label for="company_reg_number">Registration Number</label>
                    <input type="text" id="company_reg_number" name="company_reg_number" class="form-control"
                           value="{{ old('company_reg_number', $organisation->company_reg_number) }}">
                    <small class="form-text">Official company registration number</small>
                </div>

                <div class="form-group">
                    <label for="tax_pin">Tax PIN</label>
                    <input type="text" id="tax_pin" name="tax_pin" class="form-control"
                           value="{{ old('tax_pin', $organisation->tax_pin) }}">
                    <small class="form-text">Tax identification number</small>
                </div>
            </div>
        </div>

        <div class="config-form-section">
            <h3>Contact Information</h3>
            <p class="config-form-subtitle">How customers and partners can reach your organisation</p>

            <div class="form-grid">
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control"
                           value="{{ old('phone', $organisation->phone) }}">
                    <small class="form-text">Primary contact phone number</small>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="{{ old('email', $organisation->email) }}">
                    <small class="form-text">Primary contact email address</small>
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label for="website">Website</label>
                    <input type="url" id="website" name="website" class="form-control"
                           value="{{ old('website', $organisation->website) }}">
                    <small class="form-text">Organisation website URL</small>
                </div>
            </div>
        </div>

        <div class="config-form-section">
            <h3>Address Information</h3>
            <p class="config-form-subtitle">Physical location and address details</p>

            <div class="form-grid">
                <div class="form-group">
                    <label for="county">County/Region</label>
                    <input type="text" id="county" name="county" class="form-control"
                           value="{{ old('county', $organisation->county) }}">
                    <small class="form-text">County or region where organisation is located</small>
                </div>

                <div class="form-group">
                    <label for="town">Town/City</label>
                    <input type="text" id="town" name="town" class="form-control"
                           value="{{ old('town', $organisation->town) }}">
                    <small class="form-text">Town or city where organisation is located</small>
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label for="physical_address">Physical Address</label>
                    <textarea id="physical_address" name="physical_address" class="form-control" rows="3"
                              placeholder="Street address, building, floor, etc.">{{ old('physical_address', $organisation->physical_address) }}</textarea>
                    <small class="form-text">Complete physical address</small>
                </div>
            </div>
        </div>

        <div class="config-form-section">
            <h3>Branding Settings</h3>
            <p class="config-form-subtitle">Basic brand colors (advanced branding in Branding section)</p>

            <div class="form-grid">
                <div class="form-group">
                    <label for="primary_brand_color">Primary Brand Color</label>
                    <div class="color-input-wrapper">
                        <input type="color" id="primary_brand_color_preview" class="color-preview-input"
                               value="{{ old('primary_brand_color', $organisation->primary_brand_color ?? '#1F4B99') }}"
                               onchange="document.getElementById('primary_brand_color').value = this.value">
                        <input type="text" id="primary_brand_color" name="primary_brand_color"
                               class="form-control" value="{{ old('primary_brand_color', $organisation->primary_brand_color ?? '#1F4B99') }}"
                               pattern="^#[0-9A-Fa-f]{6}$" maxlength="7"
                               onchange="document.getElementById('primary_brand_color_preview').value = this.value">
                    </div>
                    <small class="form-text">Primary brand color (hex format)</small>
                </div>

                <div class="form-group">
                    <label for="secondary_brand_color">Secondary Brand Color</label>
                    <div class="color-input-wrapper">
                        <input type="color" id="secondary_brand_color_preview" class="color-preview-input"
                               value="{{ old('secondary_brand_color', $organisation->secondary_brand_color ?? '#6366f1') }}"
                               onchange="document.getElementById('secondary_brand_color').value = this.value">
                        <input type="text" id="secondary_brand_color" name="secondary_brand_color"
                               class="form-control" value="{{ old('secondary_brand_color', $organisation->secondary_brand_color ?? '#6366f1') }}"
                               pattern="^#[0-9A-Fa-f]{6}$" maxlength="7"
                               onchange="document.getElementById('secondary_brand_color_preview').value = this.value">
                    </div>
                    <small class="form-text">Secondary brand color (hex format)</small>
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <div class="form-check">
                        <input type="checkbox" id="use_org_branding" name="use_org_branding"
                               class="form-check-input" {{ old('use_org_branding', $organisation->use_org_branding) ? 'checked' : '' }}>
                        <label for="use_org_branding" class="form-check-label">
                            Use organisation branding on borrower portal
                        </label>
                    </div>
                    <small class="form-text">Apply your branding to the borrower-facing portal</small>
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