@extends('layouts.app')
@section('title', 'Edit Integration')

@push('styles')
<style>
.form-page{max-width:760px;margin:0 auto;padding:0 var(--pg) 48px}
.form-card{background:var(--clr-card);border:1px solid var(--clr-border);border-radius:16px;overflow:hidden}
.form-card-head{padding:22px 28px 18px;border-bottom:1px solid var(--clr-border)}
.form-card-head h2{font-size:1.1rem;font-weight:700;margin:0;color:var(--clr-text)}
.form-card-head p{margin:4px 0 0;font-size:.85rem;color:var(--clr-muted)}
.form-card-body{padding:28px}
.form-sec{font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--clr-muted);margin:20px 0 12px;padding-bottom:6px;border-bottom:1px solid var(--clr-border)}
.form-card-footer{padding:16px 28px;border-top:1px solid var(--clr-border);display:flex;gap:10px;justify-content:flex-end;background:var(--clr-surface-2)}
.hint{font-size:.78rem;color:var(--clr-muted);margin-top:4px}
.cred-note{background:var(--clr-surface-2);border:1px solid var(--clr-border);border-radius:8px;padding:10px 14px;font-size:.8rem;color:var(--clr-muted);display:flex;gap:8px;align-items:flex-start}
.meta-strip{display:flex;gap:16px;flex-wrap:wrap;align-items:center;background:var(--clr-surface-2);padding:10px 28px;border-bottom:1px solid var(--clr-border);font-size:.78rem;color:var(--clr-muted)}
.meta-strip strong{color:var(--clr-text)}
/* status badge (inline) */
.badge-status{display:inline-flex;align-items:center;gap:5px;font-size:.7rem;font-weight:700;padding:4px 10px;border-radius:20px}
.badge-status--active{background:#d1fae5;color:#065f46}
.badge-status--inactive{background:#f3f4f6;color:#6b7280}
.badge-status--error{background:#fee2e2;color:#991b1b}
.badge-status .dot{width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block}
</style>
@endpush

@section('content')
<div class="form-page">

    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('integrations.index') }}">Integrations</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </ol>
    </nav>

    <div class="form-card">
        <div class="form-card-head">
            <h2>
                <i class="bi {{ $integration->icon() }} me-2 text-primary"></i>
                {{ $integration->label }}
            </h2>
            <p>{{ ucfirst($integration->provider) }} · {{ $integration->typeLabel() }} · {{ ucfirst($integration->environment) }}</p>
        </div>

        {{-- Meta strip --}}
        <div class="meta-strip">
            <span>Status:
                <span class="badge-status {{ $integration->statusBadgeClass() }}">
                    <span class="dot"></span>{{ ucfirst($integration->status) }}
                </span>
            </span>
            @if($integration->last_tested_at)
            <span>Last tested: <strong>{{ $integration->last_tested_at->diffForHumans() }}</strong></span>
            @else
            <span style="color:#dc2626">Never tested</span>
            @endif
            <span class="ms-auto">
                <form method="POST" action="{{ route('integrations.test', $integration) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-wifi me-1"></i> Test Connection
                    </button>
                </form>
            </span>
        </div>

        @if($integration->last_error)
        <div class="alert alert-danger m-4 mb-0 py-2 px-3" style="font-size:.82rem">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <strong>Last error:</strong> {{ $integration->last_error }}
        </div>
        @endif

        <form id="delete-integration" method="POST" action="{{ route('integrations.destroy', $integration) }}"
              class="d-none"
              onsubmit="return confirm('Remove integration «{{ addslashes($integration->label) }}»?')">
            @csrf @method('DELETE')
        </form>

        <form method="POST" action="{{ route('integrations.update', $integration) }}">
            @csrf @method('PUT')
            <div class="form-card-body">

                <div class="form-sec">Provider</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="provider">Provider Key <span class="text-danger">*</span></label>
                        <input type="text" id="provider" name="provider"
                               class="form-control @error('provider') is-invalid @enderror"
                               value="{{ old('provider', $integration->provider) }}">
                        @error('provider')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="label">Display Label <span class="text-danger">*</span></label>
                        <input type="text" id="label" name="label"
                               class="form-control @error('label') is-invalid @enderror"
                               value="{{ old('label', $integration->label) }}">
                        @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="type">Type <span class="text-danger">*</span></label>
                        <select id="type" name="type" class="form-select @error('type') is-invalid @enderror">
                            @foreach($types as $key => $lbl)
                                <option value="{{ $key }}" {{ old('type', $integration->type) === $key ? 'selected' : '' }}>
                                    {{ $lbl }}
                                </option>
                            @endforeach
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="environment">Environment <span class="text-danger">*</span></label>
                        <select id="environment" name="environment" class="form-select @error('environment') is-invalid @enderror">
                            <option value="sandbox"    {{ old('environment', $integration->environment) === 'sandbox'    ? 'selected' : '' }}>Sandbox</option>
                            <option value="production" {{ old('environment', $integration->environment) === 'production' ? 'selected' : '' }}>Production</option>
                        </select>
                        @error('environment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                @if($integration->provider === 'mpesa')
                    @include('integrations._mpesa_fields', ['integration' => $integration])
                @else
                    <div class="form-sec">Credentials</div>
                    <div class="cred-note mb-3">
                        <i class="bi bi-shield-lock-fill text-primary mt-1"></i>
                        <span>Leave blank to keep current credentials. Provide a new JSON object to replace them. Values are encrypted at rest.</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="credentials">
                                New Credentials <span class="text-muted fw-normal">(JSON — leave blank to keep existing)</span>
                            </label>
                            <textarea id="credentials" name="credentials" rows="4"
                                      class="form-control font-monospace @error('credentials') is-invalid @enderror"
                                      placeholder='{"api_key":"…","api_secret":"…"}'>{{ old('credentials') }}</textarea>
                            @error('credentials')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                                      placeholder='{"callback_url":"https://…"}'>{{ old('config', $integration->config ? json_encode($integration->config, JSON_PRETTY_PRINT) : '') }}</textarea>
                            @error('config')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                @endif
                <div class="form-sec">Status</div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch"
                           id="is_active" name="is_active" value="1"
                           {{ old('is_active', $integration->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>

            </div>
            <div class="form-card-footer">
                <button type="submit" form="delete-integration" class="btn btn-outline-danger btn-sm me-auto">
                    <i class="bi bi-trash3 me-1"></i> Remove
                </button>
                <a href="{{ route('integrations.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-lg me-1"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
