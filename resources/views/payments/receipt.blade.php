<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Receipt · {{ $payment->mpesa_receipt_number ?: $payment->external_reference ?: 'PMT-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.0/font/bootstrap-icons.css">
    <style>
        /* ── Reset & Base ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { font-size: 13px; }
        body {
            font-family: Inter, system-ui, -apple-system, sans-serif;
            background: #f4f5f8;
            color: #101828;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px 60px;
        }

        /* ── Toolbar (screen only) ── */
        .receipt-toolbar {
            width: 100%;
            max-width: 720px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .receipt-toolbar a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            font-weight: 600;
            color: #667085;
            text-decoration: none;
            border: 1px solid #e4e7ec;
            border-radius: 8px;
            padding: 7px 13px;
            background: #fff;
            transition: border-color .15s, color .15s;
        }
        .receipt-toolbar a:hover { border-color: #a5b4fc; color: #5b5ce2; }
        .receipt-toolbar .toolbar-actions { display: flex; gap: 8px; }
        .btn-print {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 11.5px;
            font-weight: 650;
            color: #fff;
            background: #5b5ce2;
            border: 1px solid #5b5ce2;
            border-radius: 8px;
            padding: 7px 16px;
            cursor: pointer;
            transition: background .15s;
        }
        .btn-print:hover { background: #4a4bcf; }

        /* ── Receipt card ── */
        .receipt-card {
            width: 100%;
            max-width: 720px;
            background: #fff;
            border-radius: 18px;
            border: 1px solid #e4e7ec;
            box-shadow: 0 4px 24px rgba(10, 16, 30, .07);
            overflow: hidden;
        }

        /* ── Header band ── */
        .receipt-header {
            background: linear-gradient(135deg, #5b5ce2 0%, #4338ca 100%);
            padding: 36px 40px 30px;
            position: relative;
            overflow: hidden;
        }
        .receipt-header::after {
            content: '';
            position: absolute;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            right: -80px;
            top: -120px;
            background: rgba(255, 255, 255, .07);
            pointer-events: none;
        }
        .receipt-header::before {
            content: '';
            position: absolute;
            width: 160px;
            height: 160px;
            border-radius: 50%;
            right: 60px;
            bottom: -60px;
            background: rgba(255, 255, 255, .05);
            pointer-events: none;
        }
        .rh-brand {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
            position: relative;
            z-index: 1;
        }
        .rh-brand-name {
            font-size: 17px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -.02em;
        }
        .rh-brand-org {
            font-size: 10px;
            color: rgba(255,255,255,.6);
            margin-top: 3px;
        }
        .rh-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,.15);
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 20px;
            padding: 6px 13px;
            font-size: 11px;
            font-weight: 700;
            color: #fff;
            letter-spacing: .03em;
            backdrop-filter: blur(4px);
        }
        .rh-status-badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #4ade80;
        }
        .rh-status-badge.reversed::before { background: #fca5a5; }
        .rh-status-badge.pending::before  { background: #fde68a; }

        .rh-amount {
            position: relative;
            z-index: 1;
        }
        .rh-amount-label {
            font-size: 10.5px;
            font-weight: 600;
            color: rgba(255,255,255,.6);
            letter-spacing: .06em;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .rh-amount-value {
            font-size: 40px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -.04em;
            line-height: 1;
        }
        .rh-amount-currency {
            font-size: 20px;
            font-weight: 700;
            opacity: .75;
            margin-right: 4px;
        }
        .rh-amount-note {
            font-size: 10.5px;
            color: rgba(255,255,255,.55);
            margin-top: 8px;
        }

        /* ── Reference strip ── */
        .receipt-ref-strip {
            background: #f9fafb;
            border-bottom: 1px solid #eaecf0;
            padding: 14px 40px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .ref-item {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 10.5px;
            color: #667085;
        }
        .ref-item i { font-size: 13px; color: #b0bbc8; }
        .ref-item strong { color: #101828; font-weight: 650; }
        .ref-divider {
            width: 1px;
            height: 14px;
            background: #e4e7ec;
        }

        /* ── Body ── */
        .receipt-body { padding: 32px 40px; }

        /* Parties */
        .receipt-parties {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 20px;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 28px;
            border-bottom: 1px solid #f0f2f5;
        }
        .party-block {}
        .party-label {
            font-size: 9.5px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #98a2b3;
            margin-bottom: 10px;
        }
        .party-avatar {
            width: 40px;
            height: 40px;
            border-radius: 11px;
            background: linear-gradient(135deg, #e0e1ff, #c7c8f6);
            color: #4b4fc4;
            font-size: 14px;
            font-weight: 750;
            display: grid;
            place-items: center;
            margin-bottom: 9px;
        }
        .party-avatar.org {
            background: linear-gradient(135deg, #5b5ce2, #4338ca);
            color: #fff;
            font-size: 13px;
        }
        .party-name {
            font-size: 13px;
            font-weight: 700;
            color: #0d1320;
            display: block;
            margin-bottom: 3px;
        }
        .party-detail {
            font-size: 10.5px;
            color: #667085;
            display: block;
            line-height: 1.6;
        }
        .party-arrow {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #f0f2f5;
            display: grid;
            place-items: center;
            color: #98a2b3;
            font-size: 16px;
        }

        /* Section heading */
        .receipt-section-title {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .09em;
            text-transform: uppercase;
            color: #98a2b3;
            margin-bottom: 14px;
        }

        /* Details grid */
        .receipt-details-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 28px;
            padding-bottom: 28px;
            border-bottom: 1px solid #f0f2f5;
        }
        .detail-item label {
            font-size: 9.5px;
            font-weight: 700;
            letter-spacing: .07em;
            text-transform: uppercase;
            color: #98a2b3;
            display: block;
            margin-bottom: 5px;
        }
        .detail-item span {
            font-size: 12.5px;
            font-weight: 650;
            color: #0d1320;
            display: block;
        }
        .detail-item small {
            font-size: 10px;
            color: #667085;
            display: block;
            margin-top: 2px;
        }

        /* Allocation table */
        .alloc-section {
            margin-bottom: 28px;
            padding-bottom: 28px;
            border-bottom: 1px solid #f0f2f5;
        }
        .alloc-breakdown-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #eaecf0;
            border-radius: 10px;
            overflow: hidden;
        }
        .alloc-breakdown-table thead tr {
            background: #f9fafb;
            border-bottom: 1px solid #eaecf0;
        }
        .alloc-breakdown-table th {
            padding: 9px 14px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #98a2b3;
            text-align: left;
        }
        .alloc-breakdown-table th:last-child { text-align: right; }
        .alloc-breakdown-table td {
            padding: 12px 14px;
            font-size: 12px;
            border-bottom: 1px solid #f5f6f8;
            vertical-align: middle;
        }
        .alloc-breakdown-table tbody tr:last-child td { border-bottom: none; }
        .alloc-breakdown-table td:last-child {
            text-align: right;
            font-weight: 700;
            color: #0d1320;
        }
        .alloc-breakdown-table td:nth-child(3) {
            text-align: right;
            color: #667085;
            font-size: 11px;
        }
        .alloc-breakdown-table tfoot tr {
            background: #f9fafb;
            border-top: 2px solid #eaecf0;
        }
        .alloc-breakdown-table tfoot td {
            padding: 11px 14px;
            font-size: 12.5px;
            font-weight: 750;
            color: #0d1320;
        }
        .alloc-breakdown-table tfoot td:last-child {
            text-align: right;
            color: #5b5ce2;
        }
        .type-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .type-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .alloc-bar-cell {
            width: 100px;
        }
        .alloc-mini-bar {
            height: 5px;
            background: #f0f2f5;
            border-radius: 10px;
            overflow: hidden;
        }
        .alloc-mini-bar span {
            display: block;
            height: 100%;
            border-radius: inherit;
        }

        /* Loan summary card */
        .loan-summary-card {
            display: flex;
            align-items: center;
            gap: 14px;
            background: #f9faff;
            border: 1px solid #e4e7f2;
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 28px;
        }
        .loan-icon {
            width: 40px;
            height: 40px;
            border-radius: 11px;
            background: linear-gradient(135deg, #eef4ff, #dde9ff);
            color: #175cd3;
            font-size: 17px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }
        .loan-info { flex: 1; }
        .loan-info strong {
            display: block;
            font-size: 12.5px;
            font-weight: 700;
            color: #0d1320;
        }
        .loan-info small {
            display: block;
            font-size: 10.5px;
            color: #667085;
            margin-top: 3px;
        }
        .loan-balance { text-align: right; }
        .loan-balance strong {
            display: block;
            font-size: 13px;
            font-weight: 750;
            color: #0d1320;
            letter-spacing: -.01em;
        }
        .loan-balance span {
            display: block;
            font-size: 9.5px;
            color: #98a2b3;
            margin-top: 3px;
        }

        /* Footer */
        .receipt-footer {
            background: #f9fafb;
            border-top: 1px solid #eaecf0;
            padding: 20px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        .receipt-footer-left {
            font-size: 10px;
            color: #98a2b3;
            line-height: 1.7;
        }
        .receipt-footer-left strong { color: #667085; }
        .receipt-footer-seal {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 10px;
            font-weight: 600;
            color: #667085;
        }
        .seal-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: #ecfdf3;
            border: 1px solid #a9efc5;
            display: grid;
            place-items: center;
            color: #067647;
            font-size: 13px;
        }

        /* ── Print styles ── */
        @media print {
            body {
                background: #fff;
                padding: 0;
                display: block;
            }
            .receipt-toolbar { display: none; }
            .receipt-card {
                max-width: none;
                width: 100%;
                border-radius: 0;
                border: none;
                box-shadow: none;
            }
            .receipt-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .receipt-header::after, .receipt-header::before { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .alloc-breakdown-table thead tr,
            .alloc-breakdown-table tfoot tr,
            .loan-summary-card,
            .receipt-footer { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            a { color: inherit !important; text-decoration: none !important; }
            @page { margin: 0; size: A4; }
        }

        @media (max-width: 600px) {
            body { padding: 20px 12px 40px; }
            .receipt-header { padding: 28px 22px 24px; }
            .receipt-body { padding: 24px 22px; }
            .receipt-footer { padding: 16px 22px; }
            .receipt-ref-strip { padding: 12px 22px; }
            .receipt-parties { grid-template-columns: 1fr; }
            .party-arrow { display: none; }
            .receipt-details-grid { grid-template-columns: 1fr 1fr; }
            .rh-amount-value { font-size: 30px; }
        }
    </style>
</head>
<body>

    {{-- ── Toolbar (screen only) ── --}}
    <div class="receipt-toolbar">
        <a href="{{ url()->previous() }}">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <div class="toolbar-actions">
            <a href="{{ route('payments.edit', $payment) }}">
                <i class="bi bi-pencil"></i> Edit payment
            </a>
            <button class="btn-print" onclick="window.print()">
                <i class="bi bi-printer"></i> Print receipt
            </button>
        </div>
    </div>

    {{-- ── Receipt card ── --}}
    <div class="receipt-card">

        {{-- Header band --}}
        <div class="receipt-header">
            <div class="rh-brand">
                <div>
                    <div class="rh-brand-name">LendFlow</div>
                    <div class="rh-brand-org">{{ $organisation?->name ?? 'Lending Organisation' }}</div>
                </div>
                @php
                    $statusBadgeClass = match($payment->status) {
                        'posted'     => '',
                        'reversed'   => 'reversed',
                        'pending',
                        'processing' => 'pending',
                        default      => '',
                    };
                    $statusLabel = ucfirst($payment->status);
                @endphp
                <span class="rh-status-badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
            </div>

            <div class="rh-amount">
                <div class="rh-amount-label">Payment amount</div>
                <div class="rh-amount-value">
                    <span class="rh-amount-currency">KSh</span>{{ number_format($payment->amount_cents / 100, 2) }}
                </div>
                <div class="rh-amount-note">
                    @if($payment->unapplied_cents > 0)
                        KSh {{ number_format($payment->unapplied_cents / 100, 2) }} unapplied balance
                    @else
                        Fully applied to loan
                    @endif
                </div>
            </div>
        </div>

        {{-- Reference strip --}}
        <div class="receipt-ref-strip">
            <div class="ref-item">
                <i class="bi bi-hash"></i>
                <span>Receipt no. <strong>{{ $payment->mpesa_receipt_number ?: $payment->external_reference ?: 'PMT-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</strong></span>
            </div>
            <div class="ref-divider"></div>
            <div class="ref-item">
                <i class="bi bi-calendar3"></i>
                <span>Received <strong>{{ optional($payment->received_at)->format('d M Y, H:i') ?? '—' }}</strong></span>
            </div>
            @if($payment->posted_at)
                <div class="ref-divider"></div>
                <div class="ref-item">
                    <i class="bi bi-check2-circle"></i>
                    <span>Posted <strong>{{ optional($payment->posted_at)->format('d M Y, H:i') }}</strong></span>
                </div>
            @endif
        </div>

        {{-- Body --}}
        <div class="receipt-body">

            {{-- Parties --}}
            <div class="receipt-parties">
                {{-- Borrower --}}
                <div class="party-block">
                    <div class="party-label">From (Borrower)</div>
                    <div class="party-avatar">{{ strtoupper(substr($borrower?->user?->name ?? 'U', 0, 1)) }}</div>
                    <span class="party-name">{{ $borrower?->user?->name ?? 'Unknown borrower' }}</span>
                    <span class="party-detail">
                        {{ $borrower?->user?->email ?? '' }}
                        @if($borrower?->user?->phone ?? false)
                            <br>{{ $borrower->user->phone }}
                        @endif
                    </span>
                </div>

                <div class="party-arrow"><i class="bi bi-arrow-right"></i></div>

                {{-- Organisation --}}
                <div class="party-block" style="text-align:right">
                    <div class="party-label" style="text-align:right">To (Lender)</div>
                    <div class="party-avatar org" style="margin-left:auto">
                        <i class="bi bi-building"></i>
                    </div>
                    <span class="party-name">{{ $organisation?->name ?? 'LendFlow' }}</span>
                    <span class="party-detail">
                        {{ $organisation?->company_reg_number ? 'Reg: ' . $organisation->company_reg_number : '' }}
                    </span>
                </div>
            </div>

            {{-- Payment details grid --}}
            <div class="receipt-section-title">Payment details</div>
            <div class="receipt-details-grid">
                <div class="detail-item">
                    <label>Payment method</label>
                    <span>
                        @php
                            $methodIcon = match($payment->payment_method) {
                                'mpesa'          => 'bi-phone',
                                'bank_transfer'  => 'bi-bank',
                                'cash'           => 'bi-cash',
                                'credit_balance' => 'bi-wallet2',
                                default          => 'bi-credit-card',
                            };
                            $methodName = match($payment->payment_method) {
                                'mpesa'          => 'M-Pesa',
                                'bank_transfer'  => 'Bank transfer',
                                'cash'           => 'Cash',
                                'credit_balance' => 'Credit balance',
                                default          => ucfirst($payment->payment_method),
                            };
                        @endphp
                        <i class="bi {{ $methodIcon }}" style="margin-right:5px;color:#5b5ce2"></i>{{ $methodName }}
                    </span>
                </div>
                <div class="detail-item">
                    <label>Total amount</label>
                    <span>KSh {{ number_format($payment->amount_cents / 100, 2) }}</span>
                </div>
                <div class="detail-item">
                    <label>Unapplied balance</label>
                    <span style="color:{{ $payment->unapplied_cents > 0 ? '#dc6803' : '#067647' }}">
                        KSh {{ number_format($payment->unapplied_cents / 100, 2) }}
                    </span>
                </div>
                <div class="detail-item">
                    <label>External reference</label>
                    <span>{{ $payment->mpesa_receipt_number ?: $payment->external_reference ?: '—' }}</span>
                </div>
                <div class="detail-item">
                    <label>Processed by</label>
                    <span>System</span>
                </div>
                <div class="detail-item">
                    <label>Status</label>
                    <span>{{ $statusLabel }}</span>
                </div>
            </div>

            {{-- Allocation breakdown --}}
            @if($allocGroups->isNotEmpty())
                <div class="alloc-section">
                    <div class="receipt-section-title">Allocation breakdown</div>
                    @php
                        $typeColors = [
                            'interest'  => '#f79009',
                            'principal' => '#5b5ce2',
                            'penalty'   => '#f04438',
                            'fees'      => '#9e77ed',
                            'other'     => '#12b76a',
                        ];
                    @endphp
                    <table class="alloc-breakdown-table">
                        <thead>
                            <tr>
                                <th>Allocation type</th>
                                <th style="width:130px">Distribution</th>
                                <th style="text-align:right">Percentage</th>
                                <th style="text-align:right">Amount (KSh)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allocGroups as $type => $cents)
                                @php
                                    $pct   = $totalAllocated > 0 ? round(($cents / $totalAllocated) * 100, 2) : 0;
                                    $color = $typeColors[$type] ?? '#667085';
                                @endphp
                                <tr>
                                    <td>
                                        <span class="type-indicator">
                                            <span class="type-dot" style="background:{{ $color }}"></span>
                                            {{ ucfirst($type) }}
                                        </span>
                                    </td>
                                    <td class="alloc-bar-cell">
                                        <div class="alloc-mini-bar">
                                            <span style="width:{{ $pct }}%;background:{{ $color }}"></span>
                                        </div>
                                    </td>
                                    <td>{{ number_format($pct, 2) }}%</td>
                                    <td>{{ number_format($cents / 100, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2">Total allocated</td>
                                <td style="text-align:right;color:#667085">100.00%</td>
                                <td style="text-align:right;color:#5b5ce2">{{ number_format($totalAllocated / 100, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif

            {{-- Loan summary --}}
            @if($loan)
                <div class="receipt-section-title">Linked loan</div>
                <div class="loan-summary-card">
                    <div class="loan-icon"><i class="bi bi-bank2"></i></div>
                    <div class="loan-info">
                        <strong>Loan #{{ str_pad($loan->id, 7, '0', STR_PAD_LEFT) }}</strong>
                        <small>
                            {{ $loan->loanProduct?->name ?? 'Loan' }}
                            &nbsp;·&nbsp;
                            Term: {{ $loan->term_days }} days
                            &nbsp;·&nbsp;
                            Principal: KSh {{ number_format($loan->principal_cents / 100, 2) }}
                        </small>
                    </div>
                    @if($outstandingCents !== null)
                        <div class="loan-balance">
                            <strong>KSh {{ number_format($outstandingCents / 100, 2) }}</strong>
                            <span>Outstanding balance</span>
                        </div>
                    @endif
                </div>
            @endif

        </div>{{-- end receipt-body --}}

        {{-- Footer --}}
        <div class="receipt-footer">
            <div class="receipt-footer-left">
                Generated on <strong>{{ now()->format('d M Y, H:i') }}</strong> &nbsp;·&nbsp;
                Document ID: <strong>PMT-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</strong><br>
                This is a system-generated receipt and does not require a physical signature.
            </div>
            <div class="receipt-footer-seal">
                <div class="seal-icon"><i class="bi bi-shield-check"></i></div>
                Verified payment
            </div>
        </div>

    </div>{{-- end receipt-card --}}

</body>
</html>
