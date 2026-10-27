@extends('layouts.app')
@section('title', 'Edit System Setting')

@push('styles')
<style>
.fc-wrap{max-width:740px;margin:0 auto;padding:0 var(--pg) 52px}
.fc-breadcrumb{display:flex;align-items:center;gap:6px;font-size:11px;color:#667085;margin-bottom:20px}
.fc-breadcrumb a{color:#667085;text-decoration:none;font-weight:500}
.fc-breadcrumb a:hover{color:#5b5ce2}
.fc-breadcrumb-sep{color:#d0d5dd}
.fc-card{background:#fff;border:1px solid var(--line);border-radius:18px;overflow:hidden;box-shadow:0 4px 20px rgba(16,24,40,.07)}
.fc-card-head{padding:24px 28px 20px;border-bottom:1px solid var(--line);background:linear-gradient(to bottom,#fafbff,#fff);display:flex;align-items:flex-start;gap:14px}
.fc-card-head-icon{width:44px;height:44px;border-radius:13px;background:#eeefff;color:#5b5ce2;display:grid;place-items:center;font-size:19px;flex-shrink:0}
.fc-card-head-copy h2{font-size:15px;font-weight:700;color:#101828;margin:0 0 3px}
.fc-card-head-copy p{font-size:12px;color:#667085;margin:0}
.fc-card-body{padding:28px}
.fc-card-foot{padding:16px 28px;border-top:1px solid var(--line);display:flex;gap:10px;justify-content:flex-end;align-items:center;background:#fafafa}
.fc-section{font-size:9.5px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#98a2b3;margin:24px 0 14px;padding-bottom:8px;border-bottom:1px dashed var(--line);display:flex;align-items:center;gap:8px}
.fc-section i{font-size:12px;color:#c3c8f0}
.form-label{font-size:12px;font-weight:600;color:#344054;margin-bottom:5px}
.form-control,.form-select{height:36px;border:1px solid #d0d5dd;border-radius:9px;font-size:12px;color:#101828;padding:0 12px;background:#fff;transition:border-color .14s,box-shadow .14s}
.form-control:focus,.form-select:focus{border-color:#5b5ce2;box-shadow:0 0 0 3px rgba(91,92,226,.14);outline:none}
textarea.form-control{height:auto;padding:10px 12px;line-height:1.5}
.form-control.is-invalid,.form-select.is-invalid{border-color:#f04438}
.invalid-feedback{font-size:11px;color:#f04438;margin-top:4px}
.fc-hint{font-size:11px;color:#98a2b3;margin-top:5px;display:flex;align-items:flex-start;gap:4px}
.fc-hint i{margin-top:1px;color:#c3c8f0;flex-shrink:0}
.fc-toggle-row{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border:1px solid var(--line);border-radius:10px;background:#fafafa;transition:background .12s}
.fc-toggle-row:hover{background:#f5f5ff}
.fc-toggle-label{font-size:12px;font-weight:600;color:#344054}
.fc-toggle-hint{font-size:11px;color:#98a2b3;margin-top:1px}
.form-switch .form-check-input{width:38px;height:20px;cursor:pointer}
.form-switch .form-check-input:checked{background-color:#5b5ce2;border-color:#5b5ce2}
.fc-type-badge{display:inline-flex;align-items:center;gap:5px;font-size:10px;font-weight:700;padding:3px 9px;border-radius:6px;letter-spacing:.04em;text-transform:uppercase;background:#eeefff;color:#5b5ce2;border:1px solid #d4d5fb;margin-left:8px}
.fc-btn{height:36px;padding:0 20px;border-radius:9px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:7px;cursor:pointer;border:none;text-decoration:none;transition:all .14s}
.fc-btn--primary{background:#5b5ce2;color:#fff;box-shadow:0 2px 8px rgba(91,92,226,.3)}
.fc-btn--primary:hover{background:#4a4bcf;box-shadow:0 4px 14px rgba(91,92,226,.4);transform:translateY(-1px)}
.fc-btn--ghost{background:#fff;color:#344054;border:1px solid var(--line)}
.fc-btn--ghost:hover{background:#f9fafb;border-color:#d0d5dd}
.fc-btn--danger{background:#fff;color:#dc2626;border:1px solid #fecaca}
.fc-btn--danger:hover{background:#fef2f2;border-color:#f87171}
/* key chip */
.fc-key-chip{display:inline-flex;align-items:center;gap:6px;background:#f5f5f5;border:1px solid #e8e8e8;border-radius:7px;padding:4px 10px;font-family:'SF Mono','Fira Code',monospace;font-size:11.5px;color:#555}
</style>
@endpush

@section('content')
<div class="fc-wrap">

    <div class="fc-breadcrumb">
        <a href="{{ route('settings.index') }}"><i class="bi bi-grid-fill me-1"></i>Settings</a>
        <span class="fc-breadcrumb-sep">/</span>
        <a href="{{ route('system.index') }}">System Settings</a>
        <span class="fc-breadcrumb-sep">/</span>
        <span>Edit</span>
    </div>

    <div class="fc-card">

        <div class="fc-card-head">
            <div class="fc-card-head-icon"><i class="bi bi-pencil-fill"></i></div>
            <div class="fc-card-head-copy">
                <h2>{{ $setting->label }}</h2>
                <p>
                    <span class="fc-key-chip">
                        <i class="bi bi-hash"></i>{{ $setting->group }}.{{ $setting->key }}
                    </span>
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('system.update', $setting) }}">
            @csrf @method('PUT')
            <div class="fc-card-body">

                <div class="fc-section"><i class="bi bi-tag-fill"></i> Identification</div>
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label" for="group">Group <span class="text-danger">*</span></label>
                        <input type="text" id="group" name="group"
                               class="form-control @error('group') is-invalid @enderror"
                               value="{{ old('group', $setting->group) }}"
                               list="existing-groups" autocomplete="off">
                        <datalist id="existing-groups">
                            @foreach($groups as $g)
                                <option value="{{ $g }}">
                            @endforeach
                        </datalist>
                        @error('group')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-7">
                        <label class="form-label" for="key">Key <span class="text-danger">*</span></label>
                        <input type="text" id="key" name="key"
                               class="form-control @error('key') is-invalid @enderror"
                               value="{{ old('key', $setting->key) }}" autocomplete="off">
                        @error('key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="label">Label <span class="text-danger">*</span></label>
                        <input type="text" id="label" name="label"
                               class="form-control @error('label') is-invalid @enderror"
                               value="{{ old('label', $setting->label) }}">
                        @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" rows="2"
                                  class="form-control">{{ old('description', $setting->description) }}</textarea>
                    </div>
                </div>

                <div class="fc-section"><i class="bi bi-code-slash"></i> Value</div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="type">
                            Type <span class="text-danger">*</span>
                            <span class="fc-type-badge" id="type-badge">{{ $setting->type }}</span>
                        </label>
                        <select id="type" name="type" class="form-select @error('type') is-invalid @enderror">
                            @foreach(['string'=>'String (text)','boolean'=>'Boolean (true/false)','integer'=>'Integer (number)','json'=>'JSON (object/array)','select'=>'Select (options)'] as $val => $lbl)
                                <option value="{{ $val }}" {{ old('type', $setting->type) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="value">Value</label>
                        <input type="text" id="value" name="value"
                               class="form-control @error('value') is-invalid @enderror"
                               value="{{ old('value', $setting->value) }}"
                               placeholder="null">
                        @error('value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12" id="options-row">
                        <label class="form-label" for="options">
                            Options <span style="font-weight:400;color:#98a2b3">(JSON array)</span>
                        </label>
                        <textarea id="options" name="options" rows="3"
                                  class="form-control @error('options') is-invalid @enderror"
                                  style="font-family:'SF Mono','Fira Code',monospace;font-size:11px"
                                  placeholder='[{"value":"a","label":"Option A"}]'>{{ old('options', $setting->options ? json_encode($setting->options, JSON_PRETTY_PRINT) : '') }}</textarea>
                        @error('options')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="fc-section"><i class="bi bi-toggles"></i> Flags</div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" for="sort_order">Sort Order</label>
                        <input type="number" id="sort_order" name="sort_order"
                               class="form-control" value="{{ old('sort_order', $setting->sort_order) }}" min="0">
                    </div>
                    <div class="col-md-9">
                        <label class="form-label d-block">&nbsp;</label>
                        <div class="d-flex flex-column gap-2">
                            <div class="fc-toggle-row">
                                <div>
                                    <div class="fc-toggle-label">Active</div>
                                    <div class="fc-toggle-hint">Setting is applied at runtime</div>
                                </div>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="is_active" name="is_active" value="1"
                                           {{ old('is_active', $setting->is_active) ? 'checked' : '' }}>
                                </div>
                            </div>
                            <div class="fc-toggle-row">
                                <div>
                                    <div class="fc-toggle-label">Expose to front-end</div>
                                    <div class="fc-toggle-hint">Available in JavaScript via window.appConfig</div>
                                </div>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="is_public" name="is_public" value="1"
                                           {{ old('is_public', $setting->is_public) ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="fc-card-foot">
                {{-- Danger zone: delete button left-aligned --}}
                <form method="POST" action="{{ route('system.destroy', $setting) }}" class="me-auto"
                      onsubmit="return confirm('Permanently delete «{{ addslashes($setting->label) }}»?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="fc-btn fc-btn--danger">
                        <i class="bi bi-trash3"></i> Delete
                    </button>
                </form>
                <a href="{{ route('system.index') }}" class="fc-btn fc-btn--ghost">Cancel</a>
                <button type="submit" class="fc-btn fc-btn--primary">
                    <i class="bi bi-check-lg"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
    const type   = document.getElementById('type');
    const optRow = document.getElementById('options-row');
    const badge  = document.getElementById('type-badge');
    const map    = {string:'String',boolean:'Boolean',integer:'Integer',json:'JSON',select:'Select'};
    function sync(){ optRow.style.display = type.value === 'select' ? '' : 'none'; badge.textContent = map[type.value]||type.value; }
    type.addEventListener('change', sync);
    sync();
})();
</script>
@endpush
