@extends('layouts.app')
@section('title', 'Security Policies')

@push('styles')
<style>
/* ─────────────────────────────────────────────────
   CSS TOKENS
───────────────────────────────────────────────── */
:root {
  --clr-card:       #ffffff;
  --clr-surface:    #f8f9fc;
  --clr-surface-2:  #f3f4f8;
  --clr-border:     #e8eaf0;
  --clr-text:       #0f172a;
  --clr-muted:      #64748b;
  --clr-primary:    #5b5ce2;
  --clr-primary-lt: rgba(91,92,226,.08);
  --rad-card:       16px;
  --shadow-card:    0 1px 3px rgba(15,23,42,.06), 0 4px 16px rgba(15,23,42,.04);
  --shadow-hover:   0 8px 28px rgba(15,23,42,.10);
  --transition:     .18s cubic-bezier(.22,1,.36,1);
}

/* ─────────────────────────────────────────────────
   HERO HEADER BANNER
───────────────────────────────────────────────── */
.sec-hero {
  position: relative;
  overflow: hidden;
  margin: 0 var(--pg) 28px;
  border-radius: 20px;
  padding: 32px 36px;
  background: linear-gradient(130deg, #0f1e3d 0%, #1a2f5e 45%, #1e3a6e 70%, #243d7a 100%);
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 24px;
  flex-wrap: wrap;
  box-shadow: 0 20px 48px rgba(15,30,61,.22), 0 0 0 1px rgba(255,255,255,.05);
}
/* Decorative rings */
.sec-hero::before,
.sec-hero::after {
  content: '';
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
}
.sec-hero::before {
  width: 420px; height: 420px;
  right: -100px; top: -180px;
  border: 1px solid rgba(255,255,255,.07);
  box-shadow: 0 0 0 40px rgba(255,255,255,.03), 0 0 0 80px rgba(255,255,255,.018);
}
.sec-hero::after {
  width: 220px; height: 220px;
  left: 38%; bottom: -120px;
  background: radial-gradient(circle, rgba(99,102,241,.18) 0%, transparent 70%);
}

.sec-hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(165,180,252,.12);
  border: 1px solid rgba(165,180,252,.2);
  border-radius: 30px;
  padding: 4px 12px 4px 8px;
  font-size: .7rem;
  font-weight: 700;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: #a5b4fc;
  margin-bottom: 12px;
  width: fit-content;
}
.sec-hero-badge i { font-size: .75rem; }

.sec-hero-copy {
  position: relative;
  z-index: 1;
  flex: 1;
  min-width: 220px;
}
.sec-hero-copy h1 {
  font-size: 1.7rem;
  font-weight: 750;
  letter-spacing: -.03em;
  margin: 0 0 6px;
  color: #fff;
  line-height: 1.2;
}
.sec-hero-copy p {
  margin: 0;
  color: #94a9c9;
  font-size: .87rem;
  line-height: 1.55;
  max-width: 440px;
}

/* Hero right — stat pills */
.sec-hero-kpis {
  position: relative;
  z-index: 1;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  flex-shrink: 0;
}
.sec-kpi {
  background: rgba(255,255,255,.065);
  border: 1px solid rgba(255,255,255,.1);
  border-radius: 12px;
  padding: 12px 18px;
  text-align: center;
  min-width: 88px;
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  transition: background var(--transition);
}
.sec-kpi:hover { background: rgba(255,255,255,.1); }
.sec-kpi-val {
  display: block;
  font-size: 1.25rem;
  font-weight: 750;
  color: #fff;
  letter-spacing: -.02em;
  line-height: 1;
  margin-bottom: 4px;
}
.sec-kpi-val.on  { color: #34d399; }
.sec-kpi-val.off { color: #f87171; }
.sec-kpi-lbl {
  font-size: .65rem;
  font-weight: 600;
  letter-spacing: .07em;
  text-transform: uppercase;
  color: #7d96b8;
  white-space: nowrap;
}

/* ─────────────────────────────────────────────────
   FLASH MESSAGES
───────────────────────────────────────────────── */
.sec-flash {
  margin: 0 var(--pg) 20px;
  padding: 12px 18px;
  border-radius: 12px;
  display: flex;
  align-items: flex-start;
  gap: 10px;
  font-size: .84rem;
  font-weight: 500;
  animation: flashIn .3s ease both;
}
@keyframes flashIn {
  from { opacity:0; transform:translateY(-6px); }
  to   { opacity:1; transform:translateY(0); }
}
.sec-flash--success {
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
  color: #065f46;
}
.sec-flash--danger {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #991b1b;
}
.sec-flash i { flex-shrink: 0; font-size: 1rem; margin-top: .05rem; }
.sec-flash ul { margin: 0; padding-left: 18px; }

/* ─────────────────────────────────────────────────
   LAYOUT GRID
───────────────────────────────────────────────── */
.sec-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 18px;
  padding: 0 var(--pg) 48px;
}
@media(max-width:960px) { .sec-grid { grid-template-columns: 1fr; } }
.sec-grid--wide { grid-column: 1 / -1; }

/* ─────────────────────────────────────────────────
   POLICY CARD
───────────────────────────────────────────────── */
.pol {
  background: var(--clr-card);
  border: 1px solid var(--clr-border);
  border-radius: var(--rad-card);
  box-shadow: var(--shadow-card);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: box-shadow var(--transition), transform var(--transition);
}
.pol:hover { box-shadow: var(--shadow-hover); }

/* card header */
.pol-hd {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px 22px;
  border-bottom: 1px solid var(--clr-border);
  background: var(--clr-surface-2);
}
.pol-hd-icon {
  width: 40px; height: 40px;
  border-radius: 11px;
  display: grid;
  place-items: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}
.pol-hd-icon--red    { background: linear-gradient(135deg,#fee2e2,#fecaca); color: #b91c1c; }
.pol-hd-icon--blue   { background: linear-gradient(135deg,#dbeafe,#bfdbfe); color: #1d4ed8; }
.pol-hd-icon--amber  { background: linear-gradient(135deg,#fef3c7,#fde68a); color: #b45309; }
.pol-hd-icon--violet { background: linear-gradient(135deg,#ede9fe,#ddd6fe); color: #7c3aed; }

.pol-hd-text { flex: 1; min-width: 0; }
.pol-hd-title {
  font-size: .9rem;
  font-weight: 700;
  color: var(--clr-text);
  margin: 0;
  letter-spacing: -.01em;
}
.pol-hd-sub {
  font-size: .75rem;
  color: var(--clr-muted);
  margin: 2px 0 0;
}

/* card body */
.pol-body { padding: 6px 22px 4px; flex: 1; }

/* row item */
.pol-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  padding: 14px 0;
  border-bottom: 1px solid var(--clr-border);
}
.pol-item:last-child { border-bottom: none; }

.pol-item-info {}
.pol-item-label {
  font-size: .84rem;
  font-weight: 600;
  color: var(--clr-text);
  line-height: 1.3;
}
.pol-item-hint {
  font-size: .73rem;
  color: var(--clr-muted);
  margin-top: 3px;
}

.pol-item-ctrl {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* numeric stepper */
.sec-num {
  width: 78px;
  text-align: center;
  border: 1.5px solid var(--clr-border);
  border-radius: 9px;
  padding: 7px 10px;
  font-size: .82rem;
  font-weight: 600;
  color: var(--clr-text);
  background: var(--clr-card);
  transition: border-color .15s, box-shadow .15s;
  outline: none;
}
.sec-num:focus {
  border-color: var(--clr-primary);
  box-shadow: 0 0 0 3px rgba(91,92,226,.12);
}
.sec-num-unit {
  font-size: .72rem;
  color: var(--clr-muted);
  font-weight: 500;
}

/* toggle switch */
.sec-toggle {
  appearance: none;
  -webkit-appearance: none;
  width: 42px;
  height: 24px;
  border-radius: 12px;
  background: #e2e8f0;
  border: 2px solid transparent;
  cursor: pointer;
  position: relative;
  transition: background .2s, border-color .2s, box-shadow .15s;
  flex-shrink: 0;
}
.sec-toggle::after {
  content: '';
  position: absolute;
  left: 2px; top: 2px;
  width: 16px; height: 16px;
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 1px 3px rgba(0,0,0,.18);
  transition: transform .22s cubic-bezier(.22,1,.36,1);
}
.sec-toggle:checked {
  background: var(--clr-primary);
  border-color: transparent;
  box-shadow: 0 0 0 3px rgba(91,92,226,.15);
}
.sec-toggle:checked::after { transform: translateX(18px); }
.sec-toggle:focus { box-shadow: 0 0 0 3px rgba(91,92,226,.18); }

/* select */
.sec-select {
  border: 1.5px solid var(--clr-border);
  border-radius: 9px;
  padding: 7px 32px 7px 11px;
  font-size: .82rem;
  font-weight: 500;
  color: var(--clr-text);
  background: var(--clr-card) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 16 16'%3E%3Cpath fill='%2364748b' d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E") no-repeat right 10px center;
  appearance: none;
  -webkit-appearance: none;
  cursor: pointer;
  transition: border-color .15s, box-shadow .15s;
  outline: none;
}
.sec-select:focus {
  border-color: var(--clr-primary);
  box-shadow: 0 0 0 3px rgba(91,92,226,.12);
}

/* card footer */
.pol-ft {
  padding: 14px 22px;
  border-top: 1px solid var(--clr-border);
  background: var(--clr-surface-2);
  display: flex;
  justify-content: flex-end;
}

/* ─────────────────────────────────────────────────
   SAVE BUTTON
───────────────────────────────────────────────── */
.btn-sec-save {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  background: var(--clr-primary);
  color: #fff;
  border: none;
  border-radius: 9px;
  padding: 8px 20px;
  font-size: .8rem;
  font-weight: 650;
  cursor: pointer;
  transition: background var(--transition), transform var(--transition), box-shadow var(--transition);
  box-shadow: 0 4px 12px rgba(91,92,226,.3);
  letter-spacing: -.005em;
}
.btn-sec-save:hover {
  background: #4a4bcf;
  box-shadow: 0 6px 18px rgba(91,92,226,.38);
  transform: translateY(-1px);
}
.btn-sec-save:active { transform: translateY(0); }

/* ─────────────────────────────────────────────────
   IP ALLOW-LIST
───────────────────────────────────────────────── */
.ip-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  padding: 14px 0 4px;
}
.ip-chip {
  font-size: .73rem;
  font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', monospace;
  background: var(--clr-surface-2);
  border: 1px solid var(--clr-border);
  border-radius: 6px;
  padding: 3px 10px;
  color: #1d4ed8;
  font-weight: 600;
  letter-spacing: .01em;
}
.ip-chip--none {
  color: var(--clr-muted);
  font-style: italic;
  font-family: inherit;
  font-weight: 400;
  background: transparent;
  border-color: transparent;
  padding-left: 0;
}
.ip-field-label {
  font-size: .75rem;
  font-weight: 700;
  color: var(--clr-muted);
  letter-spacing: .04em;
  text-transform: uppercase;
  display: block;
  margin-bottom: 7px;
}
.sec-textarea {
  width: 100%;
  border: 1.5px solid var(--clr-border);
  border-radius: 10px;
  padding: 10px 14px;
  font-size: .8rem;
  font-family: 'SFMono-Regular', Consolas, monospace;
  color: var(--clr-text);
  background: var(--clr-card);
  resize: vertical;
  min-height: 100px;
  transition: border-color .15s, box-shadow .15s;
  line-height: 1.7;
  outline: none;
}
.sec-textarea:focus {
  border-color: var(--clr-primary);
  box-shadow: 0 0 0 3px rgba(91,92,226,.12);
}
.sec-textarea.is-invalid { border-color: #ef4444; }
.sec-textarea-hint {
  font-size: .73rem;
  color: var(--clr-muted);
  margin-top: 6px;
  display: flex;
  align-items: center;
  gap: 5px;
}

/* ─────────────────────────────────────────────────
   AUDIT / EVENTS TABLE
───────────────────────────────────────────────── */
.audit-card {
  background: var(--clr-card);
  border: 1px solid var(--clr-border);
  border-radius: var(--rad-card);
  box-shadow: var(--shadow-card);
  overflow: hidden;
}
.audit-card-hd {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 22px;
  border-bottom: 1px solid var(--clr-border);
  background: var(--clr-surface-2);
  gap: 12px;
}
.audit-card-hd-left {
  display: flex;
  align-items: center;
  gap: 12px;
}
.audit-hd-icon {
  width: 36px; height: 36px;
  border-radius: 10px;
  background: linear-gradient(135deg,#e0e7ff,#c7d2fe);
  color: #3730a3;
  display: grid;
  place-items: center;
  font-size: 1rem;
  flex-shrink: 0;
}
.audit-card-hd h3 {
  font-size: .9rem;
  font-weight: 700;
  margin: 0;
  color: var(--clr-text);
}
.audit-card-hd p {
  font-size: .73rem;
  color: var(--clr-muted);
  margin: 2px 0 0;
}
.btn-audit-all {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: transparent;
  border: 1.5px solid var(--clr-border);
  border-radius: 9px;
  padding: 7px 14px;
  font-size: .77rem;
  font-weight: 600;
  color: var(--clr-muted);
  text-decoration: none;
  white-space: nowrap;
  transition: border-color .15s, color .15s, background .15s;
}
.btn-audit-all:hover {
  border-color: var(--clr-primary);
  color: var(--clr-primary);
  background: var(--clr-primary-lt);
}

.audit-table {
  width: 100%;
  border-collapse: collapse;
}
.audit-table thead th {
  padding: 10px 20px;
  font-size: .68rem;
  font-weight: 700;
  letter-spacing: .07em;
  text-transform: uppercase;
  color: var(--clr-muted);
  border-bottom: 1px solid var(--clr-border);
  white-space: nowrap;
  background: var(--clr-surface);
}
.audit-table tbody tr {
  border-bottom: 1px solid var(--clr-border);
  transition: background .12s;
}
.audit-table tbody tr:last-child { border-bottom: none; }
.audit-table tbody tr:hover { background: var(--clr-surface); }
.audit-table tbody td {
  padding: 12px 20px;
  font-size: .82rem;
  color: var(--clr-text);
  vertical-align: middle;
}

/* event badge */
.ev-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: .71rem;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 20px;
  white-space: nowrap;
  letter-spacing: .02em;
}
.ev-badge i { font-size: .78rem; }
.ev-badge--login          { background: #dbeafe; color: #1e40af; }
.ev-badge--login_failed   { background: #fee2e2; color: #991b1b; }
.ev-badge--user_locked    { background: #fee2e2; color: #991b1b; }
.ev-badge--logout         { background: #f1f5f9; color: #475569; }
.ev-badge--password_changed { background: #dcfce7; color: #166534; }

.audit-user {
  display: flex;
  align-items: center;
  gap: 8px;
}
.audit-user-avatar {
  width: 28px; height: 28px;
  border-radius: 50%;
  background: linear-gradient(135deg, #a5b4fc, #818cf8);
  display: grid;
  place-items: center;
  font-size: .68rem;
  font-weight: 700;
  color: #fff;
  flex-shrink: 0;
  text-transform: uppercase;
}
.audit-user-name { font-weight: 600; font-size: .82rem; }
.audit-ip {
  font-family: 'SFMono-Regular', Consolas, monospace;
  font-size: .75rem;
  color: var(--clr-muted);
  background: var(--clr-surface-2);
  border: 1px solid var(--clr-border);
  border-radius: 5px;
  padding: 2px 7px;
  display: inline-block;
}
.audit-time {
  font-size: .77rem;
  color: var(--clr-muted);
  white-space: nowrap;
}
.audit-empty {
  text-align: center;
  padding: 52px 20px;
  color: var(--clr-muted);
}
.audit-empty i {
  display: block;
  font-size: 2.4rem;
  opacity: .25;
  margin-bottom: 12px;
}
.audit-empty p { margin: 0; font-size: .84rem; }

/* ─────────────────────────────────────────────────
   TFA DISABLED-ROW EFFECT
───────────────────────────────────────────────── */
.pol-item--tfa-method {
  transition: opacity .2s, filter .2s;
}
.pol-item--tfa-method.tfa-off {
  opacity: .45;
  pointer-events: none;
}

/* ─────────────────────────────────────────────────
   SECTION DIVIDER LABEL
───────────────────────────────────────────────── */
.sec-section-label {
  padding: 0 var(--pg);
  margin-bottom: 14px;
  display: flex;
  align-items: center;
  gap: 10px;
}
.sec-section-label span {
  font-size: .72rem;
  font-weight: 700;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: var(--clr-muted);
}
.sec-section-label::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--clr-border);
}

/* ─────────────────────────────────────────────────
   RESPONSIVE
───────────────────────────────────────────────── */
@media(max-width:680px) {
  .sec-hero { padding: 24px 22px; }
  .sec-hero-copy h1 { font-size: 1.35rem; }
  .sec-hero-kpis { gap: 7px; }
  .sec-kpi { padding: 10px 14px; min-width: 76px; }
  .sec-kpi-val { font-size: 1.05rem; }
}
</style>
@endpush

@section('content')

{{-- ══════════════════════════════════════════
     HERO HEADER
══════════════════════════════════════════ --}}
<div class="sec-hero">
  <div class="sec-hero-copy">
    <div class="sec-hero-badge">
      <i class="bi bi-shield-fill-check"></i>
      System · Security
    </div>
    <h1>Security Policies</h1>
    <p>Manage authentication rules, session controls, brute-force protection and network access for your organisation.</p>
  </div>

  <div class="sec-hero-kpis">
    <div class="sec-kpi">
      <span class="sec-kpi-val {{ $stats['twoFactor'] ? 'on' : 'off' }}">
        {{ $stats['twoFactor'] ? 'ON' : 'OFF' }}
      </span>
      <span class="sec-kpi-lbl">2FA</span>
    </div>
    <div class="sec-kpi">
      <span class="sec-kpi-val">{{ $stats['idleMinutes'] }}<small style="font-size:.55em;font-weight:500;opacity:.7">m</small></span>
      <span class="sec-kpi-lbl">Idle Timeout</span>
    </div>
    <div class="sec-kpi">
      <span class="sec-kpi-val">{{ $stats['maxAttempts'] }}</span>
      <span class="sec-kpi-lbl">Max Attempts</span>
    </div>
    <div class="sec-kpi">
      <span class="sec-kpi-val {{ $stats['ipRestricted'] ? 'on' : 'off' }}">
        {{ $stats['ipRestricted'] ? 'YES' : 'NO' }}
      </span>
      <span class="sec-kpi-lbl">IP Restricted</span>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════
     FLASH MESSAGES
══════════════════════════════════════════ --}}
@if(session('success'))
<div class="sec-flash sec-flash--success">
  <i class="bi bi-check-circle-fill"></i>
  <span>{{ session('success') }}</span>
</div>
@endif
@if($errors->any())
<div class="sec-flash sec-flash--danger">
  <i class="bi bi-exclamation-triangle-fill"></i>
  <div>
    <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
  </div>
</div>
@endif

{{-- ══════════════════════════════════════════
     POLICY GRID
══════════════════════════════════════════ --}}
<div class="sec-section-label"><span>Policy Configuration</span></div>

<div class="sec-grid">

  {{-- ── PASSWORD POLICY ── --}}
  <div class="pol" id="password">
    <div class="pol-hd">
      <div class="pol-hd-icon pol-hd-icon--red">
        <i class="bi bi-lock-fill"></i>
      </div>
      <div class="pol-hd-text">
        <div class="pol-hd-title">Password Policy</div>
        <div class="pol-hd-sub">Complexity rules and expiry settings</div>
      </div>
    </div>

    <form method="POST" action="{{ route('security.update.password') }}">
      @csrf @method('PUT')
      <div class="pol-body">

        <div class="pol-item">
          <div class="pol-item-info">
            <div class="pol-item-label">Minimum Length</div>
            <div class="pol-item-hint">Characters required (6 – 128)</div>
          </div>
          <div class="pol-item-ctrl">
            <input type="number" name="password_min_length"
                   class="sec-num"
                   value="{{ old('password_min_length', $policy->password_min_length) }}"
                   min="6" max="128">
          </div>
        </div>

        <div class="pol-item">
          <div class="pol-item-info">
            <div class="pol-item-label">Require Uppercase</div>
            <div class="pol-item-hint">At least one capital letter</div>
          </div>
          <div class="pol-item-ctrl">
            <input type="checkbox" role="switch" class="sec-toggle"
                   name="password_require_uppercase" value="1"
                   {{ old('password_require_uppercase', $policy->password_require_uppercase) ? 'checked' : '' }}>
          </div>
        </div>

        <div class="pol-item">
          <div class="pol-item-info">
            <div class="pol-item-label">Require Number</div>
            <div class="pol-item-hint">At least one digit</div>
          </div>
          <div class="pol-item-ctrl">
            <input type="checkbox" role="switch" class="sec-toggle"
                   name="password_require_number" value="1"
                   {{ old('password_require_number', $policy->password_require_number) ? 'checked' : '' }}>
          </div>
        </div>

        <div class="pol-item">
          <div class="pol-item-info">
            <div class="pol-item-label">Require Symbol</div>
            <div class="pol-item-hint">At least one special character</div>
          </div>
          <div class="pol-item-ctrl">
            <input type="checkbox" role="switch" class="sec-toggle"
                   name="password_require_symbol" value="1"
                   {{ old('password_require_symbol', $policy->password_require_symbol) ? 'checked' : '' }}>
          </div>
        </div>

        <div class="pol-item">
          <div class="pol-item-info">
            <div class="pol-item-label">Expiry Period</div>
            <div class="pol-item-hint">Days until password expires (0 = never)</div>
          </div>
          <div class="pol-item-ctrl">
            <input type="number" name="password_expiry_days"
                   class="sec-num"
                   value="{{ old('password_expiry_days', $policy->password_expiry_days) }}"
                   min="0" max="365">
            <span class="sec-num-unit">days</span>
          </div>
        </div>

        <div class="pol-item">
          <div class="pol-item-info">
            <div class="pol-item-label">Password History</div>
            <div class="pol-item-hint">Block reuse of last N passwords</div>
          </div>
          <div class="pol-item-ctrl">
            <input type="number" name="password_history_count"
                   class="sec-num"
                   value="{{ old('password_history_count', $policy->password_history_count) }}"
                   min="0" max="24">
          </div>
        </div>

      </div>
      <div class="pol-ft">
        <button type="submit" class="btn-sec-save">
          <i class="bi bi-floppy2-fill"></i> Save Password Policy
        </button>
      </div>
    </form>
  </div>

  {{-- ── SESSION MANAGEMENT ── --}}
  <div class="pol" id="session">
    <div class="pol-hd">
      <div class="pol-hd-icon pol-hd-icon--blue">
        <i class="bi bi-clock-fill"></i>
      </div>
      <div class="pol-hd-text">
        <div class="pol-hd-title">Session Management</div>
        <div class="pol-hd-sub">Idle timeouts and concurrent session limits</div>
      </div>
    </div>

    <form method="POST" action="{{ route('security.update.session') }}">
      @csrf @method('PUT')
      <div class="pol-body">

        <div class="pol-item">
          <div class="pol-item-info">
            <div class="pol-item-label">Idle Timeout</div>
            <div class="pol-item-hint">Minutes before auto-logout (5 – 480)</div>
          </div>
          <div class="pol-item-ctrl">
            <input type="number" name="session_idle_minutes"
                   class="sec-num"
                   value="{{ old('session_idle_minutes', $policy->session_idle_minutes) }}"
                   min="5" max="480">
            <span class="sec-num-unit">min</span>
          </div>
        </div>

        <div class="pol-item">
          <div class="pol-item-info">
            <div class="pol-item-label">Max Concurrent Sessions</div>
            <div class="pol-item-hint">Per user — 0 means unlimited</div>
          </div>
          <div class="pol-item-ctrl">
            <input type="number" name="session_max_per_user"
                   class="sec-num"
                   value="{{ old('session_max_per_user', $policy->session_max_per_user) }}"
                   min="0" max="10">
          </div>
        </div>

        <div class="pol-item">
          <div class="pol-item-info">
            <div class="pol-item-label">Single Device Only</div>
            <div class="pol-item-hint">Invalidate other sessions on new login</div>
          </div>
          <div class="pol-item-ctrl">
            <input type="checkbox" role="switch" class="sec-toggle"
                   name="session_single_device" value="1"
                   {{ old('session_single_device', $policy->session_single_device) ? 'checked' : '' }}>
          </div>
        </div>

      </div>
      <div class="pol-ft">
        <button type="submit" class="btn-sec-save">
          <i class="bi bi-floppy2-fill"></i> Save Session Policy
        </button>
      </div>
    </form>
  </div>

  {{-- ── LOGIN SECURITY & 2FA ── --}}
  <div class="pol" id="two-factor">
    <div class="pol-hd">
      <div class="pol-hd-icon pol-hd-icon--amber">
        <i class="bi bi-shield-lock-fill"></i>
      </div>
      <div class="pol-hd-text">
        <div class="pol-hd-title">Login Security &amp; 2FA</div>
        <div class="pol-hd-sub">Brute-force protection and two-factor authentication</div>
      </div>
    </div>

    <form method="POST" action="{{ route('security.update.login') }}">
      @csrf @method('PUT')
      <div class="pol-body">

        <div class="pol-item">
          <div class="pol-item-info">
            <div class="pol-item-label">Max Login Attempts</div>
            <div class="pol-item-hint">Failed attempts before account lockout</div>
          </div>
          <div class="pol-item-ctrl">
            <input type="number" name="max_login_attempts"
                   class="sec-num"
                   value="{{ old('max_login_attempts', $policy->max_login_attempts) }}"
                   min="1" max="20">
          </div>
        </div>

        <div class="pol-item">
          <div class="pol-item-info">
            <div class="pol-item-label">Lockout Duration</div>
            <div class="pol-item-hint">Minutes the account stays locked</div>
          </div>
          <div class="pol-item-ctrl">
            <input type="number" name="lockout_duration_minutes"
                   class="sec-num"
                   value="{{ old('lockout_duration_minutes', $policy->lockout_duration_minutes) }}"
                   min="1" max="1440">
            <span class="sec-num-unit">min</span>
          </div>
        </div>

        <div class="pol-item">
          <div class="pol-item-info">
            <div class="pol-item-label">Require 2FA</div>
            <div class="pol-item-hint">Enforce two-factor for all staff</div>
          </div>
          <div class="pol-item-ctrl">
            <input type="checkbox" role="switch" class="sec-toggle"
                   id="two_factor_required" name="two_factor_required" value="1"
                   {{ old('two_factor_required', $policy->two_factor_required) ? 'checked' : '' }}>
          </div>
        </div>

        <div class="pol-item pol-item--tfa-method" id="tfa-method-row">
          <div class="pol-item-info">
            <div class="pol-item-label">2FA Method</div>
            <div class="pol-item-hint">How the second factor is delivered</div>
          </div>
          <div class="pol-item-ctrl">
            <select name="two_factor_method" class="sec-select">
              @foreach(['totp' => 'Authenticator App (TOTP)', 'sms' => 'SMS Code', 'email' => 'Email Code'] as $val => $lbl)
              <option value="{{ $val }}" {{ old('two_factor_method', $policy->two_factor_method) === $val ? 'selected' : '' }}>
                {{ $lbl }}
              </option>
              @endforeach
            </select>
          </div>
        </div>

      </div>
      <div class="pol-ft">
        <button type="submit" class="btn-sec-save">
          <i class="bi bi-floppy2-fill"></i> Save Login Policy
        </button>
      </div>
    </form>
  </div>

  {{-- ── IP ALLOW-LIST ── --}}
  <div class="pol" id="ip-allowlist">
    <div class="pol-hd">
      <div class="pol-hd-icon pol-hd-icon--violet">
        <i class="bi bi-diagram-3-fill"></i>
      </div>
      <div class="pol-hd-text">
        <div class="pol-hd-title">IP Allow-list</div>
        <div class="pol-hd-sub">Restrict admin access to specific IPs or CIDRs</div>
      </div>
    </div>

    <form method="POST" action="{{ route('security.update.ip-allowlist') }}">
      @csrf @method('PUT')
      <div class="pol-body">

        {{-- current IPs display --}}
        <div class="ip-chips">
          @if(!empty($policy->ip_allowlist))
            @foreach($policy->ip_allowlist as $ip)
              <span class="ip-chip">{{ $ip }}</span>
            @endforeach
          @else
            <span class="ip-chip ip-chip--none">
              <i class="bi bi-globe2" style="margin-right:4px"></i>No restrictions — all IPs allowed
            </span>
          @endif
        </div>

        <div class="pol-item" style="display:block;padding-top:16px">
          <label class="ip-field-label" for="ip_allowlist">Edit Allowed IPs / CIDRs</label>
          <textarea id="ip_allowlist" name="ip_allowlist" rows="5"
                    class="sec-textarea @error('ip_allowlist') is-invalid @enderror"
                    placeholder="192.168.1.0/24&#10;10.0.0.1&#10;41.90.64.0/18">{{ old('ip_allowlist', $policy->ipAllowlistText()) }}</textarea>
          @error('ip_allowlist')
            <div style="font-size:.75rem;color:#dc2626;margin-top:5px;display:flex;align-items:center;gap:5px">
              <i class="bi bi-exclamation-circle-fill"></i> {{ $message }}
            </div>
          @enderror
          <div class="sec-textarea-hint">
            <i class="bi bi-info-circle" style="color:var(--clr-primary)"></i>
            One entry per line. Leave blank to allow all IPs. Supports IPv4, IPv6 and CIDR.
          </div>
        </div>

      </div>
      <div class="pol-ft">
        <button type="submit" class="btn-sec-save">
          <i class="bi bi-floppy2-fill"></i> Save IP Allow-list
        </button>
      </div>
    </form>
  </div>

  {{-- ══════════════════════════════════════════
       RECENT SECURITY EVENTS (full-width)
  ══════════════════════════════════════════ --}}
  <div class="audit-card sec-grid--wide">
    <div class="audit-card-hd">
      <div class="audit-card-hd-left">
        <div class="audit-hd-icon"><i class="bi bi-clock-history"></i></div>
        <div>
          <h3>Recent Security Events</h3>
          <p>Last 10 login, logout and account activity entries</p>
        </div>
      </div>
      <a href="{{ route('activities.index') }}" class="btn-audit-all">
        View all logs <i class="bi bi-arrow-right"></i>
      </a>
    </div>

    @if($recentEvents->count())
    <div style="overflow-x:auto">
      <table class="audit-table">
        <thead>
          <tr>
            <th>Event</th>
            <th>User</th>
            <th>IP Address</th>
            <th>When</th>
          </tr>
        </thead>
        <tbody>
          @foreach($recentEvents as $ev)
          <tr>
            <td>
              <span class="ev-badge ev-badge--{{ $ev->action }}">
                <i class="bi bi-{{ match($ev->action) {
                  'login'            => 'box-arrow-in-right',
                  'login_failed'     => 'x-circle-fill',
                  'logout'           => 'box-arrow-right',
                  'password_changed' => 'key-fill',
                  'user_locked'      => 'lock-fill',
                  default            => 'activity'
                } }}"></i>
                {{ str_replace('_', ' ', ucfirst($ev->action)) }}
              </span>
            </td>
            <td>
              @php $name = $ev->causer_name ?? ($ev->causer_id ? '#'.$ev->causer_id : null); @endphp
              @if($name)
              <div class="audit-user">
                <div class="audit-user-avatar">{{ strtoupper(substr($name, 0, 2)) }}</div>
                <span class="audit-user-name">{{ $name }}</span>
              </div>
              @else
              <span style="color:var(--clr-muted)">—</span>
              @endif
            </td>
            <td>
              @if($ev->ip_address)
                <span class="audit-ip">{{ $ev->ip_address }}</span>
              @else
                <span style="color:var(--clr-muted)">—</span>
              @endif
            </td>
            <td>
              <span class="audit-time" title="{{ $ev->created_at->format('d M Y, H:i') }}">
                {{ $ev->created_at->diffForHumans() }}
              </span>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @else
    <div class="audit-empty">
      <i class="bi bi-shield-check"></i>
      <p>No security events recorded yet.</p>
    </div>
    @endif
  </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
  // ── 2FA method row visibility ─────────────────
  const tfaToggle = document.getElementById('two_factor_required');
  const tfaRow    = document.getElementById('tfa-method-row');
  function syncTfa() {
    if (!tfaRow) return;
    tfaRow.classList.toggle('tfa-off', !tfaToggle.checked);
  }
  if (tfaToggle) {
    tfaToggle.addEventListener('change', syncTfa);
    syncTfa();
  }

  // ── Smooth-scroll to hash anchor on load ──────
  if (location.hash) {
    const el = document.querySelector(location.hash);
    if (el) setTimeout(() => el.scrollIntoView({ behavior: 'smooth', block: 'start' }), 200);
  }
})();
</script>
@endpush
