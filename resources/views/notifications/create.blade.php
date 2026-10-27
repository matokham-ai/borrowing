@extends('layouts.app')
@section('title', 'New Notification Template')

@push('styles')
<style>
/* ── shared form shell ── */
.nf-wrap{max-width:760px;margin:0 auto;padding:0 var(--pg) 52px}
.nf-breadcrumb{display:flex;align-items:center;gap:6px;font-size:11px;color:#667085;margin-bottom:20px}
.nf-breadcrumb a{color:#667085;text-decoration:none;font-weight:500;transition:color .13s}
.nf-breadcrumb a:hover{color:#5b5ce2}
.nf-breadcrumb-sep{color:#d0d5dd}

/* ── card ── */
.nf-card{background:#fff;border:1px solid var(--line);border-radius:18px;overflow:hidden;box-shadow:0 4px 20px rgba(16,24,40,.07)}
.nf-card-head{
    padding:22px 28px 18px;border-bottom:1px solid var(--line);
    background:linear-gradient(to bottom,#f8f9ff,#fff);
    display:flex;align-items:flex-start;gap:14px;
}
.nf-head-icon{width:44px;height:44px;border-radius:13px;display:grid;place-items:center;font-size:19px;flex-shrink:0}
.nf-head-icon--blue  {background:#eff6ff;color:#2563eb}
.nf-head-icon--indigo{background:#eeefff;color:#5b5ce2}
.nf-head-title{font-size:15px;font-weight:700;color:#101828;margin:0 0 3px}
.nf-head-sub  {font-size:12px;color:#667085;margin:0}
.nf-card-body {padding:28px}
.nf-card-foot {
    padding:16px 28px;border-top:1px solid var(--line);
    display:flex;gap:10px;justify-content:flex-end;align-items:center;
    background:#fafafa;
}

/* ── section dividers ── */
.nf-sec{
    display:flex;align-items:center;gap:8px;
    font-size:9.5px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;
    color:#98a2b3;margin:24px 0 14px;padding-bottom:8px;
    border-bottom:1px dashed var(--line);
}
.nf-sec i{font-size:12px;color:#c3c8f0}

/* ── form controls ── */
.form-label{font-size:12px;font-weight:600;color:#344054;margin-bottom:5px}
.form-control,.form-select{
    height:36px;border:1px solid #d0d5dd;border-radius:9px;
    font-size:12px;color:#101828;padding:0 12px;background:#fff;
    transition:border-color .14s,box-shadow .14s;
}
.form-control:focus,.form-select:focus{border-color:#5b5ce2;box-shadow:0 0 0 3px rgba(91,92,226,.14);outline:none}
textarea.form-control{height:auto;padding:10px 12px;line-height:1.6;resize:vertical}
.form-control.is-invalid,.form-select.is-invalid{border-color:#f04438}
.invalid-feedback{font-size:11px;color:#f04438;margin-top:4px}
.nf-hint{font-size:11px;color:#98a2b3;margin-top:5px;display:flex;align-items:flex-start;gap:4px;line-height:1.4}
.nf-hint i{margin-top:1px;color:#c3c8f0;flex-shrink:0}

/* ── channel selector cards ── */
.ch-selector{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.ch-opt{position:relative}
.ch-opt input[type=radio]{position:absolute;opacity:0;width:0;height:0}
.ch-opt-label{
    display:flex;flex-direction:column;align-items:center;justify-content:center;gap:7px;
    padding:14px 10px;border:1.5px solid var(--line);border-radius:12px;
    cursor:pointer;transition:all .14s;text-align:center;background:#fff;
}
.ch-opt-label:hover{border-color:#5b5ce2;background:#f5f5ff}
.ch-opt input:checked + .ch-opt-label{
    border-color:#5b5ce2;background:#eeefff;
    box-shadow:0 0 0 3px rgba(91,92,226,.12);
}
.ch-opt-icon{
    width:36px;height:36px;border-radius:10px;
    display:grid;place-items:center;font-size:16px;
}
.ch-opt-icon--sms   {background:#fffbeb;color:#d97706}
.ch-opt-icon--email {background:#eff6ff;color:#2563eb}
.ch-opt-icon--in_app{background:#f5f3ff;color:#7c3aed}
.ch-opt input:checked + .ch-opt-label .ch-opt-icon--sms   {background:#fde68a}
.ch-opt input:checked + .ch-opt-label .ch-opt-icon--email {background:#bfdbfe}
.ch-opt input:checked + .ch-opt-label .ch-opt-icon--in_app{background:#ddd6fe}
.ch-opt-name{font-size:12px;font-weight:600;color:#344054}

/* ── variable chips ── */
.var-chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
.var-chip{
    font-size:11px;font-family:'SF Mono','Fira Code',monospace;
    background:#f5f5f5;border:1px solid #e5e7eb;
    border-radius:6px;padding:3px 9px;cursor:pointer;
    color:#555;transition:all .13s;user-select:none;
    display:inline-flex;align-items:center;gap:4px;
}
.var-chip:hover{background:#eeefff;border-color:#c7c8fa;color:#5b5ce2}
.var-chip i{font-size:9px;opacity:.6}

/* ── active toggle row ── */
.nf-toggle-row{
    display:flex;align-items:center;justify-content:space-between;
    padding:12px 16px;border:1px solid var(--line);border-radius:10px;
    background:#fafafa;transition:background .12s;
}
.nf-toggle-row:hover{background:#f5f5ff}
.nf-toggle-label{font-size:12px;font-weight:600;color:#344054}
.nf-toggle-hint {font-size:11px;color:#98a2b3;margin-top:1px}
.form-switch .form-check-input{width:38px;height:20px;cursor:pointer}
.form-switch .form-check-input:checked{background-color:#5b5ce2;border-color:#5b5ce2}

/* ── buttons ── */
.nf-btn{height:36px;padding:0 20px;border-radius:9px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:7px;cursor:pointer;border:none;text-decoration:none;transition:all .14s}
.nf-btn--primary{background:#5b5ce2;color:#fff;box-shadow:0 2px 8px rgba(91,92,226,.3)}
.nf-btn--primary:hover{background:#4a4bcf;box-shadow:0 4px 14px rgba(91,92,226,.4);transform:translateY(-1px);color:#fff}
.nf-btn--ghost{background:#fff;color:#344054;border:1px solid var(--line)}
.nf-btn--ghost:hover{background:#f9fafb;border-color:#d0d5dd}
</style>
@endpush

@section('content')
<div class="nf-wrap">

    <div class="nf-breadcrumb">
        <a href="{{ route('settings.index') }}"><i class="bi bi-grid-fill me-1"></i>Settings</a>
        <span class="nf-breadcrumb-sep">/</span>
        <a href="{{ route('notifications.index') }}">Notifications</a>
        <span class="nf-breadcrumb-sep">/</span>
        <span>New Template</span>
    </div>

    <div class="nf-card">

        <div class="nf-card-head">
            <div class="nf-head-icon nf-head-icon--blue"><i class="bi bi-bell-plus-fill"></i></div>
            <div>
                <div class="nf-head-title">New Notification Template</div>
                <div class="nf-head-sub">Create a reusable message for a specific system event and delivery channel.</div>
            </div>
        </div>

        <form method="POST" action="{{ route('notifications.store') }}">
            @csrf
            <div class="nf-card-body">

                {{-- Identity --}}
                <div class="nf-sec"><i class="bi bi-tag-fill"></i> Identity</div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="name">Template Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" placeholder="e.g. Loan Approved — SMS">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="event">Trigger Event <span class="text-danger">*</span></label>
                        <select id="event" name="event" class="form-select @error('event') is-invalid @enderror">
                            <option value="">— Select event —</option>
                            @foreach($events as $key => $label)
                                <option value="{{ $key }}" {{ old('event') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                            <option value="__custom__" {{ !array_key_exists(old('event',''), $events) && old('event') ? 'selected' : '' }}>Custom event…</option>
                        </select>
                        @error('event')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6" id="custom-event-row" style="display:none">
                        <label class="form-label" for="custom_event">Custom Event Key</label>
                        <input type="text" id="custom_event" name="custom_event"
                               class="form-control" placeholder="e.g. tier_upgraded"
                               value="{{ old('custom_event') }}">
                        <div class="nf-hint"><i class="bi bi-info-circle"></i>Lowercase and underscores only.</div>
                    </div>
                </div>

                {{-- Channel picker --}}
                <div class="nf-sec"><i class="bi bi-broadcast-pin"></i> Delivery Channel</div>
                <div class="ch-selector">
                    @foreach(['sms'=>['SMS','bi-chat-dots-fill'],'email'=>['Email','bi-envelope-fill'],'in_app'=>['In-App','bi-bell-fill']] as $cv=>[$cl,$ci])
                    <div class="ch-opt">
                        <input type="radio" name="channel" id="ch_{{ $cv }}" value="{{ $cv }}"
                               {{ old('channel') === $cv ? 'checked' : '' }}>
                        <label class="ch-opt-label" for="ch_{{ $cv }}">
                            <div class="ch-opt-icon ch-opt-icon--{{ $cv }}">
                                <i class="bi {{ $ci }}"></i>
                            </div>
                            <span class="ch-opt-name">{{ $cl }}</span>
                        </label>
                    </div>
                    @endforeach
                </div>
                @error('channel')<div class="invalid-feedback d-block mt-2">{{ $message }}</div>@enderror

                {{-- Content --}}
                <div class="nf-sec"><i class="bi bi-file-text-fill"></i> Message Content</div>
                <div class="row g-3">
                    <div class="col-12" id="subject-row" style="display:none">
                        <label class="form-label" for="subject">Subject Line</label>
                        <input type="text" id="subject" name="subject"
                               class="form-control @error('subject') is-invalid @enderror"
                               value="{{ old('subject') }}" placeholder="Email subject line…">
                        @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="body">
                            Body <span class="text-danger">*</span>
                            <span id="char-count" style="font-size:10.5px;font-weight:400;color:#98a2b3;margin-left:8px">0 chars</span>
                        </label>
                        <textarea id="body" name="body" rows="6"
                                  class="form-control @error('body') is-invalid @enderror"
                                  placeholder="Hi &#123;&#123;borrower_name&#125;&#125;, your loan of &#123;&#123;loan_amount&#125;&#125; has been approved…">{{ old('body') }}</textarea>
                        @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="nf-hint mt-2"><i class="bi bi-info-circle"></i>
                            Use <code style="font-size:10.5px;background:#f5f5f5;padding:1px 5px;border-radius:4px">&#123;&#123;variable&#125;&#125;</code> for dynamic content. Click a chip to insert at cursor.
                        </div>
                        <div class="var-chips">
                            @foreach(['borrower_name','loan_amount','due_date','payment_amount','balance','org_name','loan_ref','phone_number'] as $v)
                            <span class="var-chip" data-var="{{ $v }}">
                                <i class="bi bi-plus-circle"></i>&#123;&#123;{{ $v }}&#125;&#125;
                            </span>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Active --}}
                <div class="nf-sec"><i class="bi bi-toggles"></i> Options</div>
                <div class="nf-toggle-row">
                    <div>
                        <div class="nf-toggle-label">Activate template</div>
                        <div class="nf-toggle-hint">Template fires when the trigger event occurs</div>
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch"
                               id="is_active" name="is_active" value="1"
                               {{ old('is_active','1') ? 'checked' : '' }}>
                    </div>
                </div>

            </div>
            <div class="nf-card-foot">
                <a href="{{ route('notifications.index') }}" class="nf-btn nf-btn--ghost">Cancel</a>
                <button type="submit" class="nf-btn nf-btn--primary">
                    <i class="bi bi-check-lg"></i> Create Template
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
    const eventSel   = document.getElementById('event');
    const custRow    = document.getElementById('custom-event-row');
    const custInput  = document.getElementById('custom_event');
    const body       = document.getElementById('body');
    const charCount  = document.getElementById('char-count');

    // Show subject for email channel
    function syncChannel(){
        const checked = document.querySelector('input[name=channel]:checked');
        subjectRow.style.display = (checked && checked.value === 'email') ? '' : 'none';
    }
    channels.forEach(r => r.addEventListener('change', syncChannel));
    syncChannel();

    // Custom event key
    function syncCustom(){
        const show = eventSel.value === '__custom__';
        custRow.style.display = show ? '' : 'none';
        if(!show) custInput.value = '';
    }
    eventSel.addEventListener('change', syncCustom);
    syncCustom();

    // Swap event value on submit
    document.querySelector('form').addEventListener('submit', () => {
        if(eventSel.value === '__custom__' && custInput.value.trim())
            eventSel.value = custInput.value.trim();
    });

    // Char counter
    function updateCount(){ charCount.textContent = body.value.length + ' chars'; }
    body.addEventListener('input', updateCount);
    updateCount();

    // Variable chip insertion
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
