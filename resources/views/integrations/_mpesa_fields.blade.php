@php
    $mpesaConfig = $integration?->config ?? [];
    $isCreating = $integration === null;
    $orgSlug = auth()->user()->organisation?->slug;
@endphp

<div class="form-sec">M-Pesa (Daraja / STK)</div>
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label fw-semibold" for="use_ikonnect_for_stk">Use ikonnect for STK</label>
        <select id="use_ikonnect_for_stk" name="use_ikonnect_for_stk"
                class="form-select @error('use_ikonnect_for_stk') is-invalid @enderror">
            <option value="0" {{ old('use_ikonnect_for_stk', data_get($mpesaConfig, 'use_ikonnect_for_stk', false) ? '1' : '0') === '0' ? 'selected' : '' }}>No</option>
            <option value="1" {{ old('use_ikonnect_for_stk', data_get($mpesaConfig, 'use_ikonnect_for_stk', false) ? '1' : '0') === '1' ? 'selected' : '' }}>Yes</option>
        </select>
        @error('use_ikonnect_for_stk')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold" for="shortcode_type">Shortcode Type <span class="text-danger">*</span></label>
        <select id="shortcode_type" name="shortcode_type"
                class="form-select @error('shortcode_type') is-invalid @enderror" required>
            <option value="till" {{ old('shortcode_type', data_get($mpesaConfig, 'shortcode_type', 'till')) === 'till' ? 'selected' : '' }}>Till</option>
            <option value="paybill" {{ old('shortcode_type', data_get($mpesaConfig, 'shortcode_type')) === 'paybill' ? 'selected' : '' }}>Paybill</option>
        </select>
        @error('shortcode_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold" for="shortcode">Till / Paybill Number <span class="text-danger">*</span></label>
        <input type="text" id="shortcode" name="shortcode"
               class="form-control @error('shortcode') is-invalid @enderror"
               value="{{ old('shortcode', data_get($mpesaConfig, 'shortcode')) }}" required>
        @error('shortcode')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
<div class="cred-note mt-3">
    <i class="bi bi-info-circle-fill text-primary mt-1"></i>
    <span>Ensure you provide the correct details below for the payment to work.</span>
</div>
<div class="cred-note">
    <i class="bi bi-shield-lock-fill text-primary mt-1"></i>
    <span>Consumer credentials are encrypted before storage. On edit, leave credential fields blank to keep the saved values.</span>
</div>
<div class="row g-3 mt-1">
    <div class="col-md-6">
        <label class="form-label fw-semibold" for="mpesa_consumer_key">Consumer Key <span class="text-danger">*</span></label>
        <input type="password" id="mpesa_consumer_key" name="mpesa_consumer_key" autocomplete="new-password"
               class="form-control @error('mpesa_consumer_key') is-invalid @enderror"
               value="" placeholder="{{ $isCreating ? 'Enter consumer key' : 'Leave blank to keep current key' }}"
               {{ $isCreating ? 'required' : '' }}>
        @error('mpesa_consumer_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold" for="mpesa_consumer_secret">Consumer Secret <span class="text-danger">*</span></label>
        <input type="password" id="mpesa_consumer_secret" name="mpesa_consumer_secret" autocomplete="new-password"
               class="form-control @error('mpesa_consumer_secret') is-invalid @enderror"
               value="" placeholder="{{ $isCreating ? 'Enter consumer secret' : 'Leave blank to keep current secret' }}"
               {{ $isCreating ? 'required' : '' }}>
        @error('mpesa_consumer_secret')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold" for="mpesa_passkey">Passkey <span class="text-danger">*</span></label>
        <input type="password" id="mpesa_passkey" name="mpesa_passkey" autocomplete="new-password"
               class="form-control @error('mpesa_passkey') is-invalid @enderror"
               value="" placeholder="{{ $isCreating ? 'Enter passkey' : 'Leave blank to keep current passkey' }}"
               {{ $isCreating ? 'required' : '' }}>
        @error('mpesa_passkey')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="cred-note mt-3 d-block">
    <div class="mb-2"><strong>Confirmation URL:</strong>
        <input class="form-control mt-1 font-monospace" type="text" readonly
               value="{{ $orgSlug ? route('mpesa.c2b.confirmation', ['orgSlug' => $orgSlug]) : '' }}">
    </div>
    <div class="mb-2"><strong>Validation URL:</strong>
        <input class="form-control mt-1 font-monospace" type="text" readonly
               value="{{ $orgSlug ? route('mpesa.c2b.validation', ['orgSlug' => $orgSlug]) : '' }}">
    </div>
    <div><strong>STK Callback URL:</strong>
        <input class="form-control mt-1 font-monospace" type="text" readonly
               value="{{ $orgSlug ? route('mpesa.stk.callback', ['orgSlug' => $orgSlug]) : '' }}">
    </div>
</div>
