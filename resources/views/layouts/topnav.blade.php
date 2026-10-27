<header class="topbar">
    <button class="mobile-toggle" id="sidebarToggle" aria-label="Open menu"><i class="bi bi-list"></i></button>

    @php
        $isSupervisor = auth()->user()->hasRole('supervisor') && !auth()->user()->hasRole(['admin', 'super-admin']);
        $isLoanOfficer = auth()->user()->hasRole('loan-officer') && !auth()->user()->hasRole(['admin', 'super-admin', 'supervisor']);
    @endphp
    @unless($isSupervisor)
    <div class="global-search" id="globalSearch">
        <i class="bi bi-search topbar-search-icon"></i>
        <input type="search" id="globalSearchInput"
               placeholder="Search borrowers, loans, payments, or reference numbers..."
               autocomplete="off" aria-label="Global search" aria-expanded="false"
               aria-controls="searchResults" role="combobox">
        <kbd class="search-kbd"><span>Ctrl</span> K</kbd>
        <div class="search-results" id="searchResults" role="listbox" hidden></div>
    </div>

    <button class="icon-btn mobile-search-btn" id="mobileSearchBtn" title="Search" aria-label="Search">
        <i class="bi bi-search"></i>
    </button>
    @endunless

    <div class="top-actions">

        {{-- Theme toggle --}}
        <button class="icon-btn theme-toggle-btn" id="themeToggleBtn" title="Toggle theme" aria-label="Toggle theme">
            <i class="bi bi-sun-fill" id="themeIcon"></i>
        </button>

        {{-- Notifications --}}
        <button class="icon-btn notification-btn" title="Notifications" aria-label="Notifications">
            <i class="bi bi-bell"></i>
            <span class="notif-badge">3</span>
        </button>

        <div class="top-divider"></div>

        {{-- Profile dropdown --}}
        <div class="profile-dropdown" id="profileDropdown">
            <button class="profile-trigger" id="profileTrigger" type="button" aria-haspopup="true" aria-expanded="false">
                <div class="avatar topbar-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
                <div class="profile-copy">
                    <strong>{{ auth()->user()->name }}</strong>
                    <small>{{ auth()->user()->roles->first()?->display_name ?? 'Administrator' }}</small>
                </div>
                <i class="bi bi-chevron-down profile-chevron" id="profileChevron"></i>
            </button>

            <div class="profile-menu" id="profileMenu" role="menu">
                <div class="profile-menu-header">
                    <div class="avatar avatar-lg">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
                    <div>
                        <strong>{{ auth()->user()->name }}</strong>
                        <small>{{ auth()->user()->email }}</small>
                    </div>
                </div>
                <div class="profile-menu-divider"></div>
                <a href="{{ $isSupervisor ? route('supervisor.profile.edit') : ($isLoanOfficer ? route('loan-officer.profile.edit') : route('profile.edit')) }}" class="profile-menu-item {{ request()->routeIs('profile.*', 'supervisor.profile.*', 'loan-officer.profile.*') ? 'active' : '' }}" role="menuitem">
                    <i class="bi bi-person-circle"></i> My Profile
                </a>
                @unless($isSupervisor || $isLoanOfficer)
                <a href="{{ route('users.index') }}" class="profile-menu-item" role="menuitem">
                    <i class="bi bi-people"></i> Manage Users
                </a>
                @endunless
                <div class="profile-menu-divider"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="profile-menu-item profile-menu-logout" role="menuitem">
                        <i class="bi bi-box-arrow-right"></i> Sign out
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

<script>
(function () {
    // ── Profile dropdown ──
    const trigger = document.getElementById('profileTrigger');
    const menu    = document.getElementById('profileMenu');
    const chevron = document.getElementById('profileChevron');
    if (trigger) {
        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            const open = menu.classList.toggle('show');
            trigger.setAttribute('aria-expanded', open);
            chevron.style.transform = open ? 'rotate(180deg)' : '';
        });
        document.addEventListener('click', function () {
            menu.classList.remove('show');
            chevron.style.transform = '';
            trigger.setAttribute('aria-expanded', 'false');
        });
    }

    // ── Mobile search expand ──
    const mobileSearchBtn = document.getElementById('mobileSearchBtn');
    const globalSearch    = document.getElementById('globalSearch');
    if (mobileSearchBtn && globalSearch) {
        mobileSearchBtn.addEventListener('click', function () {
            const expanded = globalSearch.classList.toggle('mobile-expanded');
            if (expanded) globalSearch.querySelector('input')?.focus();
        });
        document.addEventListener('click', function (e) {
            if (!globalSearch.contains(e.target) && e.target !== mobileSearchBtn) {
                globalSearch.classList.remove('mobile-expanded');
            }
        });
    }

    // ── Theme toggle ──
    const themeBtn  = document.getElementById('themeToggleBtn');
    const themeIcon = document.getElementById('themeIcon');
    if (themeBtn) {
        const apply = (dark) => {
            document.documentElement.classList.toggle('dark-mode', dark);
            themeIcon.className = dark ? 'bi bi-moon-fill' : 'bi bi-sun-fill';
            localStorage.setItem('darkMode', dark ? '1' : '0');
        };
        apply(localStorage.getItem('darkMode') === '1');
        themeBtn.addEventListener('click', () => apply(!document.documentElement.classList.contains('dark-mode')));
    }

    // ── Keyboard shortcut Ctrl/Cmd+K ──
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            document.getElementById('globalSearchInput')?.focus();
        }
    });

    // ── Global search (AJAX) ──
    const searchInput   = document.getElementById('globalSearchInput');
    const searchResults = document.getElementById('searchResults');
    const searchUrl     = '{{ route('search') }}';

    if (searchInput && searchResults) {
        let debounceTimer;

        const badgeColors = {
            success: '#027a48', warning: '#b54708', danger: '#b42318',
            info: '#3538cd',    secondary: '#344054'
        };

        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const q = this.value.trim();
            if (q.length < 2) { searchResults.hidden = true; return; }
            debounceTimer = setTimeout(() => {
                fetch(`${searchUrl}?q=${encodeURIComponent(q)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                .then(r => r.json())
                .then(data => {
                    if (!data.results?.length) { searchResults.hidden = true; return; }
                    searchResults.innerHTML = data.results.map(r => `
                        <a href="${r.url}" class="sr-item" role="option">
                            <span class="sr-icon"><i class="bi ${r.icon || 'bi-search'}"></i></span>
                            <span class="sr-body">
                                <span class="sr-title">${r.title}</span>
                                ${r.subtitle ? `<span class="sr-sub">${r.subtitle}</span>` : ''}
                            </span>
                            ${r.badge ? `<span class="sr-badge" style="color:${badgeColors[r.badge_class]||'#344054'}">${r.badge}</span>` : ''}
                        </a>`).join('');
                    searchResults.hidden = false;
                })
                .catch(() => { searchResults.hidden = true; });
            }, 250);
        });

        searchInput.addEventListener('focus', function () {
            this.setAttribute('aria-expanded', 'true');
        });
        searchInput.addEventListener('blur', function () {
            setTimeout(() => {
                searchResults.hidden = true;
                this.setAttribute('aria-expanded', 'false');
            }, 180);
        });
    }
})();
</script>
