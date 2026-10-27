@extends('layouts.app')
@section('title', 'Branding Configuration')

@section('content')

{{-- ══════════════════════════════════════════
     PAGE HEADER
══════════════════════════════════════════ --}}
<div class="page-header">
    <div>
        <div class="eyebrow">ORGANISATION CONFIGURATION</div>
        <h1>Branding Configuration</h1>
        <p>Customize your organisation's visual identity.</p>
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
        <a href="{{ route('organisation.payments') }}" class="org-config-tab">
            <i class="bi bi-credit-card"></i> Payments
        </a>
        <a href="{{ route('organisation.notifications') }}" class="org-config-tab">
            <i class="bi bi-bell"></i> Notifications
        </a>
        <a href="{{ route('organisation.branding') }}" class="org-config-tab active">
            <i class="bi bi-palette"></i> Branding
        </a>
    </div>
</div>

{{-- ══════════════════════════════════════════
     BRANDING CONFIGURATION FORM
══════════════════════════════════════════ --}}
<div class="config-form-container">
    <form id="branding-form" action="{{ route('organisation.update.branding') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input type="hidden" id="remove_logo" name="remove_logo" value="0">

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
            <h3>Organisation Logo</h3>
            <p class="config-form-subtitle">Upload and manage your organisation's logo</p>

            <div class="branding-logo-section">
                <div class="branding-logo-preview">
                    @if($organisation->logo_path)
                    <img src="{{ asset($organisation->logo_path) }}" alt="Organisation Logo">
                    @else
                    <div class="branding-logo-placeholder">
                        <i class="bi bi-building-fill"></i>
                        <span>No logo uploaded</span>
                    </div>
                    @endif
                </div>
                <div class="branding-logo-upload">
                    <div class="form-group">
                        <label for="logo">Upload Logo</label>
                        <input type="file" id="logo" name="logo" class="form-control" accept="image/*">
                        <small class="form-text">Recommended: Square format, minimum 200x200px, PNG or JPG</small>
                    </div>
                    @if($organisation->logo_path)
                    <button type="button" class="btn btn-outline-danger" onclick="confirmLogoRemoval()">
                        <i class="bi bi-trash"></i> Remove Current Logo
                    </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="config-form-section">
            <h3>Brand Colors</h3>
            <p class="config-form-subtitle">Define your organisation's color scheme</p>

            <div class="form-grid">
                <div class="form-group">
                    <label for="primary_brand_color">Primary Brand Color</label>
                    <div class="color-input-wrapper">
                        <input type="color" id="primary_brand_color_preview" class="color-preview-input"
                               value="{{ $organisation->primary_brand_color ?? '#1F4B99' }}"
                               onchange="document.getElementById('primary_brand_color').value = this.value">
                        <input type="text" id="primary_brand_color" name="primary_brand_color"
                               class="form-control" value="{{ $organisation->primary_brand_color ?? '#1F4B99' }}"
                               pattern="^#[0-9A-Fa-f]{6}$" maxlength="7"
                               onchange="document.getElementById('primary_brand_color_preview').value = this.value">
                    </div>
                    <small class="form-text">Primary color for buttons, links, and accents</small>
                </div>

                <div class="form-group">
                    <label for="secondary_brand_color">Secondary Brand Color</label>
                    <div class="color-input-wrapper">
                        <input type="color" id="secondary_brand_color_preview" class="color-preview-input"
                               value="{{ $organisation->secondary_brand_color ?? '#6366f1' }}"
                               onchange="document.getElementById('secondary_brand_color').value = this.value">
                        <input type="text" id="secondary_brand_color" name="secondary_brand_color"
                               class="form-control" value="{{ $organisation->secondary_brand_color ?? '#6366f1' }}"
                               pattern="^#[0-9A-Fa-f]{6}$" maxlength="7"
                               onchange="document.getElementById('secondary_brand_color_preview').value = this.value">
                    </div>
                    <small class="form-text">Secondary color for complementary elements</small>
                </div>
            </div>
        </div>

        <div class="config-form-section">
            <h3>Borrower Portal Branding</h3>
            <p class="config-form-subtitle">Configure branding for the borrower-facing portal</p>

            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" id="use_org_branding" name="use_org_branding"
                           class="form-check-input" {{ $organisation->use_org_branding ? 'checked' : '' }}>
                    <label for="use_org_branding" class="form-check-label">
                        Use organisation branding on borrower portal
                    </label>
                </div>
                <small class="form-text">When enabled, borrowers will see your logo and brand colors on their portal</small>
            </div>
        </div>

        <div class="config-form-preview">
            <h4>Preview</h4>
            <div class="branding-preview-card" style="border-top: 4px solid {{ $organisation->primary_brand_color ?? '#1F4B99' }}">
                <div class="branding-preview-header">
                    @if($organisation->logo_path)
                    <img src="{{ asset($organisation->logo_path) }}" alt="Logo" class="branding-preview-logo">
                    @else
                    <div class="branding-preview-logo-placeholder" style="background-color: {{ $organisation->primary_brand_color ?? '#1F4B99' }}">
                        <i class="bi bi-building-fill"></i>
                    </div>
                    @endif
                    <div class="branding-preview-title">{{ $organisation->name }}</div>
                </div>
                <div class="branding-preview-body">
                    <button class="btn btn-primary" style="background-color: {{ $organisation->primary_brand_color ?? '#1F4B99' }}; border-color: {{ $organisation->primary_brand_color ?? '#1F4B99' }}">
                        Primary Button
                    </button>
                    <button class="btn btn-outline-primary" style="color: {{ $organisation->primary_brand_color ?? '#1F4B99' }}; border-color: {{ $organisation->primary_brand_color ?? '#1F4B99' }}">
                        Secondary Button
                    </button>
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

<script>
function confirmLogoRemoval() {
    if (confirm('Are you sure you want to remove the current logo?')) {
        document.getElementById('remove_logo').value = '1';
        document.getElementById('logo').value = '';
        document.getElementById('branding-form').requestSubmit();
    }
}
</script>

@endsection
