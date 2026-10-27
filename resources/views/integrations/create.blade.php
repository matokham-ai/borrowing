@extends('layouts.app')
@section('title', 'New Integration')

@push('styles')
<style>
.form-page{max-width:920px;margin:0 auto;padding:0 var(--pg) 56px}.form-page .breadcrumb{font-size:.75rem;margin:20px 0 14px}.form-page .breadcrumb a{color:var(--clr-primary);text-decoration:none;font-weight:600}
.form-card{background:var(--clr-card);border:1px solid var(--clr-border);border-radius:18px;overflow:hidden;box-shadow:0 16px 38px rgba(16,24,40,.08)}
.form-card-head{display:block;padding:30px 32px 28px;background:linear-gradient(120deg,#121b36 0%,#1d2a55 58%,#283e73 100%);position:relative;overflow:hidden;color:#fff}.form-card-head:after{content:'';position:absolute;width:260px;height:260px;border:1px solid rgba(255,255,255,.11);border-radius:50%;right:-72px;top:-150px;box-shadow:0 0 0 20px rgba(255,255,255,.025),0 0 0 40px rgba(255,255,255,.018)}
.form-card-head h2{font-size:1.45rem;font-weight:750;letter-spacing:-.025em;margin:0 0 7px;color:#fff;position:relative;z-index:1}.form-card-head h2 i{color:#aab8ff!important}.form-card-head p{display:block;margin:0;max-width:520px;font-size:.84rem;line-height:1.55;color:#bdc8e4;position:relative;z-index:1}
.form-card-body{padding:28px 32px 32px}.form-sec{display:flex;align-items:center;gap:10px;font-size:.68rem;font-weight:750;letter-spacing:.13em;text-transform:uppercase;color:var(--clr-primary);margin:24px 0 14px;padding-bottom:10px;border-bottom:1px solid var(--clr-border)}.form-sec:first-child{margin-top:0}.form-sec:after{content:'';height:4px;width:4px;border-radius:50%;background:currentColor;opacity:.55}
.form-card .form-label{font-size:.77rem;color:var(--clr-text);margin-bottom:6px}.form-card .form-control,.form-card .form-select{border-color:var(--clr-border);border-radius:9px;min-height:40px;font-size:.82rem;background:#fff;box-shadow:0 1px 2px rgba(16,24,40,.025);transition:border-color .15s,box-shadow .15s}.form-card textarea.form-control{min-height:0;line-height:1.6}.form-card .form-control:focus,.form-card .form-select:focus{border-color:var(--clr-primary);box-shadow:0 0 0 3px rgba(91,92,226,.12)}
.form-card-footer{padding:16px 32px;border-top:1px solid var(--clr-border);display:flex;gap:10px;justify-content:flex-end;background:var(--clr-surface-2)}.form-card-footer .btn{min-height:38px;border-radius:9px;font-size:.78rem;font-weight:650}.form-card-footer .btn-primary{box-shadow:0 6px 14px rgba(91,92,226,.2)}
.hint{font-size:.72rem;color:var(--clr-muted);margin-top:5px}.cred-note{background:linear-gradient(110deg,#f5f6ff,#fafbff);border:1px solid #dfe2ff;border-radius:10px;padding:12px 14px;font-size:.77rem;color:var(--clr-muted);display:flex;gap:9px;align-items:flex-start;margin-top:8px}.cred-note i{color:var(--clr-primary)!important}.provider-quick{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:20px}.pq-btn{position:relative;min-height:88px;padding:12px 8px 10px;border:1px solid var(--clr-border);border-radius:12px;background:linear-gradient(180deg,#fff 0%,#fbfcff 100%);cursor:pointer;font-size:.76rem;font-weight:700;color:var(--clr-text);transition:transform .15s,box-shadow .15s,border-color .15s,background .15s;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px}.pq-btn i{width:32px;height:32px;border-radius:10px;display:grid;place-items:center;background:#f0f1ff;color:var(--clr-primary);font-size:1rem;box-shadow:inset 0 0 0 1px rgba(91,92,226,.08)}.pq-btn:after{content:'Select';position:absolute;right:8px;top:7px;font-size:.55rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#98a2b3;opacity:0;transition:opacity .15s}.pq-btn:hover{transform:translateY(-2px);border-color:#b8b9f3;color:var(--clr-primary);box-shadow:0 8px 16px rgba(16,24,40,.08)}.pq-btn:hover:after{opacity:1}.pq-btn:hover,.pq-btn.active{border-color:var(--clr-primary);color:var(--clr-primary);background:linear-gradient(180deg,#f7f7ff 0%,#eef0ff 100%)}.pq-btn.active{box-shadow:0 0 0 3px rgba(91,92,226,.1),0 8px 16px rgba(91,92,226,.12)}.pq-btn.active:after{content:'Selected';opacity:1;color:var(--clr-primary)}
.form-check{padding:12px 14px;border:1px solid var(--clr-border);border-radius:10px;background:var(--clr-surface-2)}.form-check-label{font-size:.8rem;font-weight:600;color:var(--clr-text)}
@media(max-width:640px){.form-card-head,.form-card-body{padding-left:20px;padding-right:20px}.form-card-footer{padding:14px 20px}.provider-quick{grid-template-columns:repeat(2,1fr);gap:8px}.pq-btn{min-height:82px}.form-card-footer .btn{flex:1;justify-content:center}}
</style>
@endpush

@section('content')
<div class="form-page">

    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('integrations.index') }}">Integrations</a></li>
            <li class="breadcrumb-item active">New Integration</li>
        </ol>
    </nav>

    <div class="form-card">
        <div class="form-card-head">
            <div class="text-uppercase small fw-semibold mb-2" style="letter-spacing:.14em;color:#aab8ff;position:relative;z-index:1">System connection</div>
            <h2><i class="bi bi-plug-fill me-2 text-primary"></i>New Integration</h2>
            <p>Connect an external service provider to your organisation.</p>
        </div>

        <form method="POST" action="{{ route('integrations.store') }}">
            @csrf
            <div class="form-card-body">

                {{-- Quick-pick provider buttons --}}
                <div class="form-sec">Provider</div>
                <div class="provider-quick" id="provider-quick">
                    @foreach([
                        ['mpesa',           'bi-phone-vibrate-fill', 'M-Pesa'],
                        ['africas_talking', 'bi-chat-dots-fill',     "Africa's Talking"],
                        ['sendgrid',        'bi-envelope-fill',      'SendGrid'],
                        ['mailgun',         'bi-envelope-at-fill',   'Mailgun'],
                        ['twilio',          'bi-telephone-fill',     'Twilio'],
                        ['webhook',         'bi-broadcast',          'Webhook'],
                        ['custom',          'bi-plug-fill',          'Custom'],
                    ] as [$pv, $pi, $pl])
                    <button type="button" class="pq-btn" data-provider="{{ $pv }}" data-type="{{ match($pv) {
                        'mpesa'           => 'payment',
                        'africas_talking' => 'sms',
                        'sendgrid', 'mailgun' => 'email',
                        'twilio'          => 'sms',
                        'webhook'         => 'webhook',
                        default           => ''
                    } }}">
                        <i class="bi {{ $pi }}"></i> {{ $pl }}
                    </button>
                    @endforeach
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="provider">Provider Key <span class="text-danger">*</span></label>
                        <input type="text" id="provider" name="provider"
                               class="form-control @error('provider') is-invalid @enderror"
                               value="{{ old('provider') }}"
                               placeholder="e.g. mpesa, sendgrid">
                        @error('provider')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="hint">Lowercase, no spaces.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="label">Display Label <span class="text-danger">*</span></label>
                        <input type="text" id="label" name="label"
                               class="form-control @error('label') is-invalid @enderror"
                               value="{{ old('label') }}"
                               placeholder="e.g. M-Pesa STK Production">
                        @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="type">Type <span class="text-danger">*</span></label>
                        <select id="type" name="type" class="form-select @error('type') is-invalid @enderror">
                            <option value="">— Select type —</option>
                            @foreach($types as $key => $lbl)
                                <option value="{{ $key }}" {{ old('type') === $key ? 'selected' : '' }}>
                                    {{ $lbl }}
                                </option>
                            @endforeach
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="environment">Environment <span class="text-danger">*</span></label>
                        <select id="environment" name="environment" class="form-select @error('environment') is-invalid @enderror">
                            <option value="sandbox"    {{ old('environment','sandbox') === 'sandbox'    ? 'selected' : '' }}>Sandbox</option>
                            <option value="production" {{ old('environment') === 'production' ? 'selected' : '' }}>Production</option>
                        </select>
                        @error('environment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div id="mpesa-fields" hidden>
                    @include('integrations._mpesa_fields', ['integration' => null])
                </div>

                <div id="generic-fields">
                    <div class="form-sec">Credentials</div>
                    <div class="cred-note">
                        <i class="bi bi-shield-lock-fill text-primary mt-1"></i>
                        <span>Credentials are encrypted before storage using AES-256. They are never displayed in plain text after saving.</span>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="credentials">
                                Credentials <span class="text-muted fw-normal">(JSON)</span>
                            </label>
                            <textarea id="credentials" name="credentials" rows="5"
                                      class="form-control font-monospace @error('credentials') is-invalid @enderror"
                                      placeholder='{"api_key":"…","api_secret":"…"}'>{{ old('credentials') }}</textarea>
                            @error('credentials')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="hint">JSON object with provider-specific keys.</div>
                        </div>
                    </div>

                    <div class="form-sec">Configuration (non-sensitive)</div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="config">
                                Config <span class="text-muted fw-normal">(JSON)</span>
                            </label>
                            <textarea id="config" name="config" rows="4"
                                      class="form-control font-monospace @error('config') is-invalid @enderror"
                                      placeholder='{"callback_url":"https://…"}'>{{ old('config') }}</textarea>
                            @error('config')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="hint">Provider-specific non-sensitive configuration.</div>
                        </div>
                    </div>
                </div>

                {{-- Status --}}
                <div class="form-sec">Status</div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch"
                           id="is_active" name="is_active" value="1"
                           {{ old('is_active') ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Activate immediately</label>
                </div>

            </div>
            <div class="form-card-footer">
                <a href="{{ route('integrations.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-lg me-1"></i> Save Integration
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
    const providerInput = document.getElementById('provider');
    const typeSelect    = document.getElementById('type');
    const mpesaFields   = document.getElementById('mpesa-fields');
    const genericFields = document.getElementById('generic-fields');

    function toggleProviderFields(){
        const isMpesa = providerInput.value.trim().toLowerCase() === 'mpesa';
        mpesaFields.hidden = !isMpesa;
        genericFields.hidden = isMpesa;
        mpesaFields.querySelectorAll('input, select').forEach(field => field.disabled = !isMpesa);
        genericFields.querySelectorAll('input, select, textarea').forEach(field => field.disabled = isMpesa);
    }

    document.querySelectorAll('.pq-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.pq-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            providerInput.value = btn.dataset.provider;
            if(btn.dataset.type) typeSelect.value = btn.dataset.type;
            toggleProviderFields();
        });
    });
    providerInput.addEventListener('input', toggleProviderFields);

    // Pre-select from old input
    const old = providerInput.value;
    if(old){
        document.querySelectorAll('.pq-btn').forEach(b => {
            if(b.dataset.provider === old) b.classList.add('active');
        });
    }
    toggleProviderFields();
})();
</script>
@endpush
