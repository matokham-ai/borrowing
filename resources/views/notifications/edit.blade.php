@extends('layouts.app')
@section('title', 'Edit Notification Template')

@push('styles')
<style>
.nf-wrap{max-width:760px;margin:0 auto;padding:0 var(--pg) 52px}
.nf-breadcrumb{display:flex;align-items:center;gap:6px;font-size:11px;color:#667085;margin-bottom:20px}
.nf-breadcrumb a{color:#667085;text-decoration:none;font-weight:500;transition:color .13s}
.nf-breadcrumb a:hover{color:#5b5ce2}
.nf-breadcrumb-sep{color:#d0d5dd}
.nf-card{background:#fff;border:1px solid var(--line);border-radius:18px;overflow:hidden;box-shadow:0 4px 20px rgba(16,24,40,.07)}
.nf-card-head{padding:22px 28px 18px;border-bottom:1px solid var(--line);background:linear-gradient(to bottom,#f8f9ff,#fff);display:flex;align-items:flex-start;gap:14px}
.nf-head-icon{width:44px;height:44px;border-radius:13px;display:grid;place-items:center;font-size:19px;flex-shrink:0}
.nf-head-icon--blue  {background:#eff6ff;color:#2563eb}
.nf-head-icon--indigo{background:#eeefff;color:#5b5ce2}
.nf-head-title{font-size:15px;font-weight:700;color:#101828;margin:0 0 3px}
.nf-head-sub  {font-size:12px;color:#667085;margin:0}
.nf-card-body {padding:28px}
.nf-card-foot {padding:16px 28px;border-top:1px solid var(--line);display:flex;gap:10px;justify-content:flex-end;align-items:center;background:#fafafa}
.nf-sec{display:flex;align-items:center;gap:8px;font-size:9.5px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#98a2b3;margin:24px 0 14px;padding-bottom:8px;border-bottom:1px dashed var(--line)}
.nf-sec i{font-size:12px;color:#c3c8f0}
.form-label{font-size:12px;font-weight:600;color:#344054;margin-bottom:5px}
.form-control,.form-select{height:36px;border:1px solid #d0d5dd;border-radius:9px;font-size:12px;color:#101828;padding:0 12px;background:#fff;transition:border-color .14s,box-shadow .14s}
.form-control:focus,.form-select:focus{border-color:#5b5ce2;box-shadow:0 0 0 3px rgba(91,92,226,.14);outline:none}
textarea.form-control{height:auto;padding:10px 12px;line-height:1.6;resize:vertical}
.form-control.is-invalid,.form-select.is-invalid{border-color:#f04438}
.invalid-feedback{font-size:11px;color:#f04438;margin-top:4px}
.nf-hint{font-size:11px;color:#98a2b3;margin-top:5px;display:flex;align-items:flex-start;gap:4px;line-height:1.4}
.nf-hint i{margin-top:1px;color:#c3c8f0;flex-shrink:0}

/* meta strip */
.nf-meta{
    display:flex;align-items:center;gap:16px;flex-wrap:wrap;
    padding:10px 28px;border-bottom:1px solid var(--line);
    background:#fafafa;font-size:11.5px;
}
.nf-meta-item{display:flex;align-items:center;gap:6px;color:#667085}
.nf-meta-item strong{color:#101828;font-weight:600}
.nf-meta-item .bi{color:#98a2b3;font-size:12px}
.nf-meta-divider{width:1px;height:16px;background:var(--line)}
/* channel badge in meta */
.ch-badge-sm{display:inline-flex;align-items:center;gap:4px;font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:20px}
.ch-badge-sm--sms   {background:#fffbeb;color:#92400e;border:1px solid #fde68a}
.ch-badge-sm--email {background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe}
.ch-badge-sm--in_app{background:#f5f3ff;color:#5b21b6;border:1px solid #ddd6fe}

/* channel selector */
.ch-selector{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.ch-opt{position:relative}
.ch-opt input[type=radio]{position:absolute;opacity:0;width:0;height:0}
.ch-opt-label{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:7px;padding:14px 10px;border:1.5px solid var(--line);border-radius:12px;cursor:pointer;transition:all .14s;text-align:center;background:#fff}
.ch-opt-label:hover{border-color:#5b5ce2;background:#f5f5ff}
.ch-opt input:checked + .ch-opt-label{border-color:#5b5ce2;background:#eeefff;box-shadow:0 0 0 3px rgba(91,92,226,.12)}
.ch-opt-icon{width:36px;height:36px;border-radius:10px;display:grid;place-items:center;font-size:16px}
.ch-opt-icon--sms   {background:#fffbeb;color:#d97706}
.ch-opt-icon--email {background:#eff6ff;color:#2563eb}
.ch-opt-icon--in_app{background:#f5f3ff;color:#7c3aed}
.ch-opt input:checked + .ch-opt-label .ch-opt-icon--sms   {background:#fde68a}
.ch-opt input:checked + .ch-opt-label .ch-opt-icon--email {background:#bfdbfe}
.ch-opt input:checked + .ch-opt-label .ch-opt-icon--in_app{background:#ddd6fe}
.ch-opt-name{font-size:12px;font-weight:600;color:#344054}

/* variable chips */
.var-chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
.var-chip{font-size:11px;font-family:'SF Mono','Fira Code',monospace;background:#f5f5f5;border:1px solid #e5e7eb;border-radius:6px;padding:3px 9px;cursor:pointer;color:#555;transition:all .13s;user-select:none;display:inline-flex;align-items:center;gap:4px}
.var-chip:hover{background:#eeefff;border-color:#c7c8fa;color:#5b5ce2}
.var-chip i{font-size:9px;opacity:.6}

/* toggle row */
.nf-toggle-row{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border:1px solid var(--line);border-radius:10px;background:#fafafa;transition:background .12s}
.nf-toggle-row:hover{background:#f5f5ff}
.nf-toggle-label{font-size:12px;font-weight:600;color:#344054}
.nf-toggle-hint {font-size:11px;color:#98a2b3;margin-top:1px}
.form-switch .form-check-input{width:38px;height:20px;cursor:pointer}
.form-switch .form-check-input:checked{background-color:#5b5ce2;border-color:#5b5ce2}

/* buttons */
.nf-btn{height:36px;padding:0 20px;border-radius:9px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:7px;cursor:pointer;border:none;text-decoration:none;transition:all .14s}
.nf-btn--primary{background:#5b5ce2;color:#fff;box-shadow:0 2px 8px rgba(91,92,226,.3)}
.nf-btn--primary:hover{background:#4a4bcf;box-shadow:0 4px 14px rgba(91,92,226,.4);transform:translateY(-1px);color:#fff}
.nf-btn--ghost{background:#fff;color:#344054;border:1px solid var(--line)}
.nf-btn--ghost:hover{background:#f9fafb;border-color:#d0d5dd}
.nf-btn--danger{background:#fff;color:#dc2626;border:1px solid #fecaca}
.nf-btn--danger:hover{background:#fef2f2;border-color:#f87171}
.nf-btn--test{background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe}
.nf-btn--test:hover{background:#dbeafe;border-color:#93c5fd}
</style>
@endpush

@section('content')
<div class="nf-wrap">

    <div class="nf-breadcrumb">
        <a href="{{ route('settings.index') }}"><i class="bi bi-grid-fill me-1"></i>Settings</a>
        <span class="nf-breadcrumb-sep">/</span>
        <a href="{{ route('notifications.index') }}">Notifications</a>
        <span class="nf-breadcrumb-sep">/</span>
        <span>Edit</span>
    </div>

    <div class="nf-card">

        {{-- card head --}}
        <div class="nf-card-head">
            <div class="nf-head-icon nf-head-icon--indigo"><i class="bi bi-pencil-fill"></i></div>
            <div style="flex:1">
                <div class="nf-head-title">{{ $template->name }}</div>
                <div class="nf-head-sub">Edit message content, event trigger and delivery channel.</div>
            </div>
            <form method="POST" action="{{ route('notifications.test', $template) }}" class="d-inline">
                @csrf
                <button type="submit" class="nf-btn nf-btn--test" style="height:34px;font-size:11px">
                    <i class="bi bi-send-fill"></i> Send Test
                </button>
            </form>
        </div>

        {{-- meta strip --}}
        <div class="nf-meta">
            <div class="nf-meta-item">
                <i class="bi bi-lightning-fill"></i>
                Event: <strong>{{ $template->event }}</strong>
            </div>
            <div class="nf-meta-divider"></div>
            <div class="nf-meta-item">
                <i class="bi bi-broadcast-pin"></i>
                Channel:
                <span class="ch-badge-sm ch-badge-sm--{{ $template->channel }}">
                    @if($template->channel==='sms')<i class="bi bi-chat-dots-fill" style="font-size:9px"></i>
                    @elseif($template->channel==='email')<i class="bi bi-envelope-fill" style="font-size:9px"></i>
                    @else<i class="bi bi-bell-fill" style="font-size:9px"></i>@endif
                    {{ $template->channelLabel() }}
                </span>
            </div>
            @if($template->last_tested_at)
            <div class="nf-meta-divider"></div>
            <div class="nf-meta-item">
                <i class="bi bi-clock-fill"></i>
                Last tested: <strong>{{ $template->last_tested_at->diffForHumans() }}</strong>
            </div>
            @endif
            <div class="nf-meta-item ms-auto">
                <span style="font-size:10.5px;color:#98a2b3">
                    Updated {{ $template->updated_at->diffForHumans() }}
                </span>
            </div>
        </div>

        <form method="POST" action="{{ route('notifications.update', $template) }}">
            @csrf @method('PUT')
            <div class="nf-card-body">

                <div class="nf-sec"><i class="bi bi-tag-fill"></i> Identity</div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="name">Template Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $template->name) }}">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="event">Trigger Event <span class="text-danger">*</span></label>
                        <select id="event" name="event" class="form-select @error('event') is-invalid @enderror">
                            @foreach($events as $key => $label)
                                <option value="{{ $key }}" {{ old('event',$template->event)===$key?'selected':'' }}>{{ $label }}</option>
                            @endforeach
                            @if(!array_key_exists($template->event,$events))
                                <option value="{{ $template->event }}" {{ old('event',$template->event)===$template->event?'selected':'' }}>{{ $template->event }} (custom)</option>
                            @endif
                        </select>
                        @error('event')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="nf-sec"><i class="bi bi-broadcast-pin"></i> Delivery Channel</div>
                <div class="ch-selector">
                    @foreach(['sms'=>['SMS','bi-chat-dots-fill'],'email'=>['Email','bi-envelope-fill'],'in_app'=>['In-App','bi-bell-fill']] as $cv=>[$cl,$ci])
                    <div class="ch-opt">
                        <input type="radio" name="channel" id="ch_{{ $cv }}" value="{{ $cv }}"
                               {{ old('channel',$template->channel)===$cv ? 'checked' : '' }}>
                        <label class="ch-opt-label" for="ch_{{ $cv }}">
                            <div class="ch-opt-icon ch-opt-icon--{{ $cv }}"><i class="bi {{ $ci }}"></i></div>
                            <span class="ch-opt-name">{{ $cl }}</span>
                        </label>
                    </div>
                    @endforeach
                </div>
                @error('channel')<div class="invalid-feedback d-block mt-2">{{ $message }}</div>@enderror

                <div class="nf-sec"><i class="bi bi-file-text-fill"></i> Message Content</div>
                <div class="row g-3">
                    <div class="col-12" id="subject-row">
                        <label class="form-label" for="subject">Subject Line</label>
                        <input type="text" id="subject" name="subject"
                               class="form-control @error('subject') is-invalid @enderror"
                               value="{{ old('subject', $template->subject) }}"
                               placeholder="Email subject line…">
                        @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="body">
                            Body <span class="text-danger">*</span>
                            <span id="char-count" style="font-size:10.5px;font-weight:400;color:#98a2b3;margin-left:8px">0 chars</span>
                        </label>
                        <textarea id="body" name="body" rows="6"
                                  class="form-control @error('body') is-invalid @enderror">{{ old('body', $template->body) }}</textarea>
                        @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="nf-hint mt-2">
                            <i class="bi bi-info-circle"></i>
                            Click a chip to insert a variable at cursor position.
                        </div>
                        <div class="var-chips">
                            @foreach(['borrower_name','loan_amount','due_date','payment_amount','balance','org_name','loan_ref','phone_number'] as $v)
                            <span class="var-chip" data-var="{{ $v }}">
                                <i class="bi bi-plus-circle"></i>{{"{{"}}{{ $v }}{{"}}"}}
                            </span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="nf-sec"><i class="bi bi-toggles"></i> Options</div>
                <div class="nf-toggle-row">
                    <div>
                        <div class="nf-toggle-label">Active</div>
                        <div class="nf-toggle-hint">Template fires when the trigger event occurs</div>
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch"
                               id="is_active" name="is_active" value="1"
                               {{ old('is_active',$template->is_active) ? 'checked' : '' }}>
                    </div>
                </div>

            </div>

            <div class="nf-card-foot">
                <form method="POST" action="{{ route('notifications.destroy', $template) }}" class="me-auto"
                      onsubmit="return confirm('Delete template «{{ addslashes($template->name) }}»?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="nf-btn nf-btn--danger">
                        <i class="bi bi-trash3"></i> Delete
                    </button>
                </form>
                <a href="{{ route('notifications.index') }}" class="nf-btn nf-btn--ghost">Cancel</a>
                <button type="submit" class="nf-btn nf-btn--primary">
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
    const channels   = document.querySelectorAll('input[name=channel]');
    const subjectRow = document.getElementById('subject-row');
    const body       = document.getElementById('body');
    const charCount  = document.getElementById('char-count');

    function syncChannel(){
        const checked = document.querySelector('input[name=channel]:checked');
        subjectRow.style.display = (checked && checked.value === 'email') ? '' : 'none';
    }
    channels.forEach(r => r.addEventListener('change', syncChannel));
    syncChannel();

    function updateCount(){ charCount.textContent = body.value.length + ' chars'; }
    body.addEventListener('input', updateCount);
    updateCount();

    document.querySelectorAll('.var-chip').forEach(chip => {
        chip.addEventListener('click', () => {
            const v = '{{' + chip.dataset.var + '}}';
            const s = body.selectionStart, e = body.selectionEnd;
            body.value = body.value.slice(0,s) + v + body.value.slice(e);
            body.focus();
            body.selectionStart = body.selectionEnd = s + v.length;
            updateCount();
        });
    });
})();
</script>
@endpush
