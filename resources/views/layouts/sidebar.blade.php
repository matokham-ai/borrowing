<aside class="sidebar" id="sidebar">

    {{-- ① BRAND --}}
    <div class="sb-brand">
        <div class="sb-brand-mark"><i class="bi bi-layers-fill"></i></div>
        <div class="sb-brand-text">
            <span class="sb-brand-name">LendFlow</span>
            <span class="sb-brand-sub">Lending Possibilities</span>
        </div>
        <button class="sb-pin-btn" id="sidebarCollapseBtn" title="Toggle sidebar" aria-label="Toggle sidebar">
            <i class="bi bi-layout-sidebar-reverse"></i>
        </button>
    </div>

    {{-- ② NAVIGATION --}}
    <nav class="sb-nav" id="sidebarNav" aria-label="Main navigation">
        @php $isSupervisor = auth()->user()->hasRole('supervisor') && !auth()->user()->hasRole(['admin', 'super-admin']); @endphp
        @php $isLoanOfficer = auth()->user()->hasRole('loan-officer') && !auth()->user()->hasRole(['admin', 'super-admin', 'supervisor']); @endphp

        {{-- Dashboard --}}
        <a class="sb-link {{ request()->routeIs('dashboard', 'supervisor.dashboard', 'loan-officer.dashboard') ? 'active' : '' }}"
           href="{{ $isSupervisor ? route('supervisor.dashboard') : ($isLoanOfficer ? route('loan-officer.dashboard') : route('dashboard')) }}">
            <span class="sb-link-icon"><i class="bi bi-grid-1x2-fill"></i></span>
            <span class="sb-link-label">Dashboard</span>
        </a>

        @if($isSupervisor)
            <div class="sb-nav-label">Branch operations</div>
            <a class="sb-link {{ request()->routeIs('applications.*') ? 'active' : '' }}" href="{{ route('applications.index') }}">
                <span class="sb-link-icon"><i class="bi bi-file-earmark-check-fill"></i></span>
                <span class="sb-link-label">Loan Applications</span>
            </a>
            <a class="sb-link {{ request()->routeIs('supervisor.guarantors.*') ? 'active' : '' }}" href="{{ route('supervisor.guarantors.index') }}">
                <span class="sb-link-icon"><i class="bi bi-person-badge-fill"></i></span>
                <span class="sb-link-label">Guarantors</span>
            </a>
            <a class="sb-link {{ request()->routeIs('borrowers.index', 'borrowers.show', 'borrowers.edit') ? 'active' : '' }}" href="{{ route('borrowers.index') }}">
                <span class="sb-link-icon"><i class="bi bi-people-fill"></i></span>
                <span class="sb-link-label">Borrowers</span>
            </a>
            @if(auth()->user()->hasPermission('create-borrowers'))
                <a class="sb-link {{ request()->routeIs('borrowers.create') ? 'active' : '' }}" href="{{ route('borrowers.create') }}">
                    <span class="sb-link-icon"><i class="bi bi-person-plus-fill"></i></span>
                    <span class="sb-link-label">New Borrower</span>
                </a>
            @endif
            <a class="sb-link {{ request()->routeIs('loans.active') ? 'active' : '' }}" href="{{ route('loans.active') }}">
                <span class="sb-link-icon"><i class="bi bi-bank2"></i></span>
                <span class="sb-link-label">Active Loans</span>
            </a>
        @elseif($isLoanOfficer)
            <div class="sb-nav-label">My lending work</div>
            <a class="sb-link {{ request()->routeIs('applications.index', 'applications.show', 'applications.edit') ? 'active' : '' }}" href="{{ route('applications.index') }}">
                <span class="sb-link-icon"><i class="bi bi-file-earmark-text-fill"></i></span>
                <span class="sb-link-label">My Applications</span>
            </a>
            <a class="sb-link {{ request()->routeIs('loan-officer.guarantors.*') ? 'active' : '' }}" href="{{ route('loan-officer.guarantors.index') }}">
                <span class="sb-link-icon"><i class="bi bi-person-badge-fill"></i></span>
                <span class="sb-link-label">Guarantors</span>
            </a>
            @if(auth()->user()->hasPermission('create-loans'))
                <a class="sb-link {{ request()->routeIs('applications.create') ? 'active' : '' }}" href="{{ route('applications.create') }}">
                    <span class="sb-link-icon"><i class="bi bi-file-earmark-plus-fill"></i></span>
                    <span class="sb-link-label">New Application</span>
                </a>
            @endif
            <a class="sb-link {{ request()->routeIs('borrowers.index', 'borrowers.show', 'borrowers.edit') ? 'active' : '' }}" href="{{ route('borrowers.index') }}">
                <span class="sb-link-icon"><i class="bi bi-people-fill"></i></span>
                <span class="sb-link-label">Borrowers</span>
            </a>
            @if(auth()->user()->hasPermission('create-borrowers'))
                <a class="sb-link {{ request()->routeIs('borrowers.create') ? 'active' : '' }}" href="{{ route('borrowers.create') }}">
                    <span class="sb-link-icon"><i class="bi bi-person-plus-fill"></i></span>
                    <span class="sb-link-label">New Borrower</span>
                </a>
            @endif
            <a class="sb-link {{ request()->routeIs('loans.active') ? 'active' : '' }}" href="{{ route('loans.active') }}">
                <span class="sb-link-icon"><i class="bi bi-bank2"></i></span>
                <span class="sb-link-label">Active Loans</span>
            </a>
        @else
        {{-- ── BORROWERS ── --}}
        <div class="sb-nav-label">Borrowers</div>

        <a class="sb-link {{ request()->routeIs('borrowers.index') ? 'active' : '' }}"
           href="{{ route('borrowers.index') }}">
            <span class="sb-link-icon"><i class="bi bi-people-fill"></i></span>
            <span class="sb-link-label">All Borrowers</span>
        </a>

        <a class="sb-link {{ request()->routeIs('borrowers.create') ? 'active' : '' }}"
           href="{{ route('borrowers.create') }}">
            <span class="sb-link-icon"><i class="bi bi-person-plus-fill"></i></span>
            <span class="sb-link-label">New Borrower</span>
        </a>

        <a class="sb-link {{ request()->routeIs('groups.*') ? 'active' : '' }}" href="#">
            <span class="sb-link-icon"><i class="bi bi-collection-fill"></i></span>
            <span class="sb-link-label">Groups</span>
        </a>

        {{-- ── LOANS ── --}}
        <div class="sb-nav-label sb-nav-label-spaced">Loans</div>

        <a class="sb-link {{ request()->routeIs('applications.*') && !request()->routeIs('applications.create') ? 'active' : '' }}"
           href="{{ route('applications.index') }}">
            <span class="sb-link-icon"><i class="bi bi-file-earmark-text-fill"></i></span>
            <span class="sb-link-label">Loan Applications</span>
        </a>

        @if(!auth()->user()->hasRole('supervisor') || auth()->user()->hasRole(['admin', 'super-admin']))
            <a class="sb-link {{ request()->routeIs('applications.create') ? 'active' : '' }}"
               href="{{ route('applications.create') }}">
                <span class="sb-link-icon"><i class="bi bi-file-earmark-plus-fill"></i></span>
                <span class="sb-link-label">New Loan Application</span>
            </a>
        @endif

        <a class="sb-link {{ request()->routeIs('loans.active') ? 'active' : '' }}"
           href="{{ route('loans.active') }}">
            <span class="sb-link-icon"><i class="bi bi-bank2"></i></span>
            <span class="sb-link-label">Active Loans</span>
        </a>

        <a class="sb-link {{ request()->routeIs('loans.repayments') || (request()->routeIs('payments.index') && request('view') === 'incoming') ? 'active' : '' }}"
           href="{{ route('loans.repayments') }}">
            <span class="sb-link-icon"><i class="bi bi-arrow-repeat"></i></span>
            <span class="sb-link-label">Repayments</span>
        </a>

        <a class="sb-link {{ request()->routeIs('loan-products.*') ? 'active' : '' }}"
           href="{{ route('loan-products.index') }}">
            <span class="sb-link-icon"><i class="bi bi-box-seam-fill"></i></span>
            <span class="sb-link-label">Loan Products</span>
        </a>

        {{-- ── PAYMENTS ── --}}
        <div class="sb-nav-label sb-nav-label-spaced">Payments</div>

        {{-- Payments group --}}
        @php $paymentsOpen = request()->routeIs('payments.*') || request()->routeIs('mpesa.stk.*'); @endphp
        <button class="sb-link sb-group-toggle {{ $paymentsOpen ? 'active open' : '' }}"
                id="paymentsGroupToggle"
                aria-expanded="{{ $paymentsOpen ? 'true' : 'false' }}"
                aria-controls="paymentsGroupItems">
            <span class="sb-link-icon"><i class="bi bi-credit-card-fill"></i></span>
            <span class="sb-link-label">Payments</span>
            <i class="bi bi-chevron-right sb-link-arrow" id="paymentsGroupChevron"></i>
        </button>

        <div class="sb-group-items {{ $paymentsOpen ? 'open' : 'closed' }}" id="paymentsGroupItems">
            <a class="sb-link sb-link-sub {{ request()->routeIs('payments.index') && !request('view') ? 'active' : '' }}"
               href="{{ route('payments.index') }}">
                <span class="sb-link-icon"><i class="bi bi-grid-1x2"></i></span>
                <span class="sb-link-label">All Payments</span>
            </a>

            <a class="sb-link sb-link-sub {{ request()->routeIs('mpesa.stk.*') ? 'active' : '' }}"
               href="{{ route('mpesa.stk.index') }}">
                <span class="sb-link-icon"><i class="bi bi-phone-vibrate-fill"></i></span>
                <span class="sb-link-label">M-Pesa STK</span>
            </a>

            <a class="sb-link sb-link-sub {{ request()->routeIs('mpesa.operations.*') ? 'active' : '' }}"
               href="{{ route('mpesa.operations.index') }}">
                <span class="sb-link-icon"><i class="bi bi-arrow-left-right"></i></span>
                <span class="sb-link-label">Daraja API Operations</span>
            </a>

            <a class="sb-link sb-link-sub"
               href="{{ route('payments.index') }}">
                <span class="sb-link-icon"><i class="bi bi-speedometer2"></i></span>
                <span class="sb-link-label">Payment Dashboard</span>
            </a>

            <a class="sb-link sb-link-sub {{ request('view') === 'incoming' ? 'active' : '' }}"
               href="{{ route('payments.index', ['view' => 'incoming']) }}">
                <span class="sb-link-icon"><i class="bi bi-box-arrow-in-down"></i></span>
                <span class="sb-link-label">Incoming Payments</span>
            </a>

            <a class="sb-link sb-link-sub {{ request('view') === 'unmatched' ? 'active' : '' }}"
               href="{{ route('payments.index', ['view' => 'unmatched']) }}">
                <span class="sb-link-icon"><i class="bi bi-question-circle"></i></span>
                <span class="sb-link-label">Unmatched Payments</span>
            </a>

            <a class="sb-link sb-link-sub {{ request('view') === 'allocations' ? 'active' : '' }}"
               href="{{ route('payments.index', ['view' => 'allocations']) }}">
                <span class="sb-link-icon"><i class="bi bi-link-45deg"></i></span>
                <span class="sb-link-label">Allocations</span>
            </a>

            <a class="sb-link sb-link-sub {{ request()->routeIs('payments.reversals') ? 'active' : '' }}"
               href="{{ route('payments.reversals') }}">
                <span class="sb-link-icon"><i class="bi bi-arrow-counterclockwise"></i></span>
                <span class="sb-link-label">Payment Reversals</span>
            </a>
        </div>

        {{-- ── FINANCE ── --}}
        <div class="sb-nav-label sb-nav-label-spaced">Finance</div>

        @php $financeOpen = request()->routeIs('finance.*'); @endphp
        <button class="sb-link sb-group-toggle {{ $financeOpen ? 'active open' : '' }}"
                id="financeGroupToggle"
                aria-expanded="{{ $financeOpen ? 'true' : 'false' }}"
                aria-controls="financeGroupItems">
            <span class="sb-link-icon"><i class="bi bi-bank2"></i></span>
            <span class="sb-link-label">Finance</span>
            <i class="bi bi-chevron-right sb-link-arrow" id="financeGroupChevron"></i>
        </button>

        <div class="sb-group-items {{ $financeOpen ? 'open' : 'closed' }}" id="financeGroupItems">
            <a class="sb-link sb-link-sub {{ request()->routeIs('finance.overview') ? 'active' : '' }}"
               href="{{ route('finance.overview') }}">
                <span class="sb-link-icon"><i class="bi bi-grid-fill"></i></span>
                <span class="sb-link-label">Overview</span>
            </a>

            <a class="sb-link sb-link-sub {{ request()->routeIs('finance.transactions') ? 'active' : '' }}"
               href="{{ route('finance.transactions') }}">
                <span class="sb-link-icon"><i class="bi bi-list-ul"></i></span>
                <span class="sb-link-label">All Transactions</span>
            </a>

            <a class="sb-link sb-link-sub {{ request()->routeIs('finance.reconciliation') ? 'active' : '' }}"
               href="{{ route('finance.reconciliation') }}">
                <span class="sb-link-icon"><i class="bi bi-check2-all"></i></span>
                <span class="sb-link-label">Reconciliation</span>
            </a>

            <a class="sb-link sb-link-sub {{ request()->routeIs('finance.reports') ? 'active' : '' }}"
               href="{{ route('finance.reports') }}">
                <span class="sb-link-icon"><i class="bi bi-bar-chart-line-fill"></i></span>
                <span class="sb-link-label">Reports</span>
            </a>

            <a class="sb-link sb-link-sub {{ request()->routeIs('finance.reversals') ? 'active' : '' }}"
               href="{{ route('finance.reversals') }}">
                <span class="sb-link-icon"><i class="bi bi-arrow-counterclockwise"></i></span>
                <span class="sb-link-label">Reversals</span>
            </a>

            <a class="sb-link sb-link-sub {{ request()->routeIs('finance.disbursed') ? 'active' : '' }}"
               href="{{ route('finance.disbursed') }}">
                <span class="sb-link-icon"><i class="bi bi-cash-coin"></i></span>
                <span class="sb-link-label">Disbursed Loans</span>
            </a>
        </div>

        {{-- ── SETTINGS ── --}}
        <div class="sb-nav-label sb-nav-label-spaced">Settings</div>

        {{-- Settings hub --}}
        <a class="sb-link {{ request()->routeIs('settings.index') ? 'active' : '' }}"
           href="{{ route('settings.index') }}">
            <span class="sb-link-icon"><i class="bi bi-grid-fill"></i></span>
            <span class="sb-link-label">Overview</span>
        </a>

        {{-- Users & Access --}}
        @php $accessOpen = request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('permissions.*') || request()->routeIs('activities.*'); @endphp
        <div class="sb-access-row">
            <a class="sb-link sb-access-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
               href="{{ route('users.index') }}">
                <span class="sb-link-icon"><i class="bi bi-people-fill"></i></span>
                <span class="sb-link-label">Users &amp; Access</span>
            </a>
            <button class="sb-access-toggle sb-group-toggle {{ $accessOpen ? 'open' : '' }}"
                id="accessGroupToggle"
                aria-expanded="{{ $accessOpen ? 'true' : 'false' }}"
                aria-controls="accessGroupItems"
                aria-label="Toggle Users &amp; Access submenu">
                <i class="bi bi-chevron-right sb-link-arrow"></i>
            </button>
        </div>
        <div class="sb-group-items {{ $accessOpen ? 'open' : 'closed' }}" id="accessGroupItems">
            <a class="sb-link sb-link-sub {{ request()->routeIs('roles.*') ? 'active' : '' }}"
               href="{{ route('roles.index') }}">
                <span class="sb-link-icon"><i class="bi bi-shield-lock-fill"></i></span>
                <span class="sb-link-label">Roles</span>
            </a>
            <a class="sb-link sb-link-sub {{ request()->routeIs('permissions.*') ? 'active' : '' }}"
               href="{{ route('permissions.index') }}">
                <span class="sb-link-icon"><i class="bi bi-key-fill"></i></span>
                <span class="sb-link-label">Permissions</span>
            </a>
            <a class="sb-link sb-link-sub {{ request()->routeIs('activities.*') ? 'active' : '' }}"
               href="{{ route('activities.index') }}">
                <span class="sb-link-icon"><i class="bi bi-clock-history"></i></span>
                <span class="sb-link-label">Audit Logs</span>
            </a>
        </div>

        {{-- Organisation --}}
        @php $orgOpen = request()->routeIs('organisation.*') || request()->routeIs('branches.*'); @endphp
        <button class="sb-link sb-group-toggle {{ $orgOpen ? 'active open' : '' }}"
                id="orgGroupToggle"
                aria-expanded="{{ $orgOpen ? 'true' : 'false' }}"
                aria-controls="orgGroupItems">
            <span class="sb-link-icon"><i class="bi bi-building-fill"></i></span>
            <span class="sb-link-label">Organisation</span>
            <i class="bi bi-chevron-right sb-link-arrow"></i>
        </button>
        @if($orgOpen)
        <div class="sb-context-card">
            <span class="sb-context-kicker">Current workspace</span>
            <strong>{{ auth()->user()->organisation?->name ?? 'Organisation' }}</strong>
            <span><i class="bi bi-circle-fill"></i> Workspace settings</span>
        </div>
        @endif
        <div class="sb-group-items {{ $orgOpen ? 'open' : 'closed' }}" id="orgGroupItems">
            @if(auth()->user()->hasPermission('branches.manage'))
            <a class="sb-link sb-link-sub {{ request()->routeIs('branches.*') ? 'active' : '' }}"
               href="{{ route('branches.index') }}">
                <span class="sb-link-icon"><i class="bi bi-diagram-3"></i></span>
                <span class="sb-link-label">Branches</span>
            </a>
            @endif
            <a class="sb-link sb-link-sub {{ request()->routeIs('organisation.index') ? 'active' : '' }}"
               href="{{ route('organisation.index') }}">
                <span class="sb-link-icon"><i class="bi bi-speedometer2"></i></span>
                <span class="sb-link-label">Overview</span>
            </a>
            <a class="sb-link sb-link-sub {{ request()->routeIs('organisation.edit') ? 'active' : '' }}"
               href="{{ route('organisation.edit') }}">
                <span class="sb-link-icon"><i class="bi bi-pencil-square"></i></span>
                <span class="sb-link-label">Edit Profile</span>
            </a>
            <a class="sb-link sb-link-sub {{ request()->routeIs('organisation.general') ? 'active' : '' }}"
               href="{{ route('organisation.general') }}">
                <span class="sb-link-icon"><i class="bi bi-gear"></i></span>
                <span class="sb-link-label">General Config</span>
            </a>
            <a class="sb-link sb-link-sub {{ request()->routeIs('organisation.lending') ? 'active' : '' }}"
               href="{{ route('organisation.lending') }}">
                <span class="sb-link-icon"><i class="bi bi-bank"></i></span>
                <span class="sb-link-label">Lending Config</span>
            </a>
            <a class="sb-link sb-link-sub {{ request()->routeIs('organisation.branding') ? 'active' : '' }}"
               href="{{ route('organisation.branding') }}">
                <span class="sb-link-icon"><i class="bi bi-palette"></i></span>
                <span class="sb-link-label">Branding</span>
            </a>
        </div>

        {{-- System --}}
        @php $systemOpen = request()->routeIs('system.*') || request()->routeIs('notifications.*') || request()->routeIs('integrations.*') || request()->routeIs('security.*'); @endphp
        <button class="sb-link sb-group-toggle {{ $systemOpen ? 'active open' : '' }}"
                id="systemGroupToggle"
                aria-expanded="{{ $systemOpen ? 'true' : 'false' }}"
                aria-controls="systemGroupItems">
            <span class="sb-link-icon"><i class="bi bi-gear-wide-connected"></i></span>
            <span class="sb-link-label">System</span>
            <i class="bi bi-chevron-right sb-link-arrow"></i>
        </button>
        <div class="sb-group-items {{ $systemOpen ? 'open' : 'closed' }}" id="systemGroupItems">
            <a class="sb-link sb-link-sub {{ request()->routeIs('system.*') ? 'active' : '' }}"
               href="{{ route('system.index') }}">
                <span class="sb-link-icon"><i class="bi bi-toggles"></i></span>
                <span class="sb-link-label">Settings</span>
            </a>
            <a class="sb-link sb-link-sub {{ request()->routeIs('notifications.*') ? 'active' : '' }}"
               href="{{ route('notifications.index') }}">
                <span class="sb-link-icon"><i class="bi bi-bell-fill"></i></span>
                <span class="sb-link-label">Notifications</span>
            </a>
            <a class="sb-link sb-link-sub {{ request()->routeIs('integrations.*') ? 'active' : '' }}"
               href="{{ route('integrations.index') }}">
                <span class="sb-link-icon"><i class="bi bi-plug-fill"></i></span>
                <span class="sb-link-label">Integrations</span>
            </a>
            <a class="sb-link sb-link-sub {{ request()->routeIs('security.*') ? 'active' : '' }}"
               href="{{ route('security.index') }}">
                <span class="sb-link-icon"><i class="bi bi-shield-shaded"></i></span>
                <span class="sb-link-label">Security</span>
            </a>
        </div>
        @endif

    </nav>

    {{-- ③ BOTTOM CTA --}}
    <div class="sb-footer">
        <div class="sb-footer-cta">
            <i class="bi bi-lightning-charge-fill sb-footer-icon"></i>
            <div class="sb-footer-body">
                <strong>Grow more lives</strong>
                <span>Inclusive Finance for a Brighter Tomorrow.</span>
            </div>
            <a href="#" class="sb-footer-arrow"><i class="bi bi-arrow-right"></i></a>
        </div>
    </div>

</aside>
