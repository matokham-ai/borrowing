import 'bootstrap';
import 'bootstrap-icons/font/bootstrap-icons.css';
import Chart from 'chart.js/auto';
import '../css/admin.css';
import '../css/applications.css';

// ═══════════════════════════════════════════════════
//  SIDEBAR — collapse / accordion / mobile drawer
// ═══════════════════════════════════════════════════
(function () {
    const sidebar = document.getElementById('sidebar');
    const main    = document.querySelector('.admin-main');
    if (!sidebar) return;

    const WIDE      = '264px';
    const SLIM      = '62px';
    const MOBILE_BP = 768;
    const isMobile  = () => window.innerWidth <= MOBILE_BP;

    // ── Desktop collapse ──────────────────────────────
    function setCollapsed(collapsed) {
        sidebar.classList.toggle('collapsed', collapsed);
        if (main) main.style.marginLeft = collapsed ? SLIM : WIDE;
        localStorage.setItem('sbCollapsed', collapsed ? '1' : '0');
    }

    if (!isMobile() && localStorage.getItem('sbCollapsed') === '1') {
        sidebar.classList.add('collapsed');
        if (main) main.style.marginLeft = SLIM;
    }
    // On mobile, always clear any SSR / inline margin so CSS rules win
    if (isMobile() && main) main.style.marginLeft = '';

    document.getElementById('sidebarCollapseBtn')?.addEventListener('click', (e) => {
        e.stopPropagation();
        if (isMobile()) return;
        setCollapsed(!sidebar.classList.contains('collapsed'));
    });

    // Click collapsed rail → expand
    sidebar.addEventListener('click', (e) => {
        if (isMobile()) return;
        if (!sidebar.classList.contains('collapsed')) return;
        if (e.target.closest('a, button')) return;
        e.stopPropagation();
        setCollapsed(false);
    });

    // ── Generic accordion helper ──────────────────────
    function setupAccordion(toggleId, itemsId, storageKey, defaultOpen = false) {
        const toggle = document.getElementById(toggleId);
        const items  = document.getElementById(itemsId);
        if (!toggle || !items) return;

        const hasActive  = !!items.querySelector('.sb-link.active');
        const saved      = localStorage.getItem(storageKey);
        const shouldOpen = hasActive || saved === '1' || (defaultOpen && saved === null);

        if (shouldOpen) {
            toggle.classList.add('open');
            toggle.setAttribute('aria-expanded', 'true');
            items.classList.remove('closed');
            // Use a real pixel height so the close animation always has a
            // starting value — avoids the maxHeight:none → 0 jump bug.
            requestAnimationFrame(() => {
                items.style.maxHeight = items.scrollHeight + 'px';
                // After the browser has painted, switch to 'none' so the
                // panel isn't clipped if child content grows later.
                setTimeout(() => { items.style.maxHeight = 'none'; }, 320);
            });
        } else {
            items.classList.add('closed');
            toggle.setAttribute('aria-expanded', 'false');
            items.style.maxHeight = '0';
        }

        toggle.addEventListener('click', () => {
            if (sidebar.classList.contains('collapsed')) return;
            const closing = toggle.classList.contains('open');
            toggle.classList.toggle('open', !closing);
            toggle.setAttribute('aria-expanded', String(!closing));

            if (closing) {
                // Snap from 'none' to a real pixel value first, then animate to 0
                items.style.maxHeight = items.scrollHeight + 'px';
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        items.classList.add('closed');
                        items.style.maxHeight = '0';
                    });
                });
            } else {
                items.classList.remove('closed');
                items.style.maxHeight = items.scrollHeight + 'px';
                setTimeout(() => { items.style.maxHeight = 'none'; }, 280);
            }
            localStorage.setItem(storageKey, closing ? '0' : '1');
        });
    }

    setupAccordion('paymentsGroupToggle', 'paymentsGroupItems', 'paymentsGroup');
    setupAccordion('financeGroupToggle',  'financeGroupItems',  'financeGroup');
    setupAccordion('accessGroupToggle',   'accessGroupItems',   'accessGroup');
    setupAccordion('orgGroupToggle',      'orgGroupItems',      'orgGroup');
    setupAccordion('systemGroupToggle',   'systemGroupItems',   'systemGroup');

    // ── Resize handler ────────────────────────────────
    window.addEventListener('resize', () => {
        if (isMobile()) {
            sidebar.classList.remove('collapsed');
            // Let CSS handle margin on mobile — clear any JS inline style
            if (main) main.style.marginLeft = '';
        } else {
            toggleMobileSidebar(true);
            // Back on desktop: restore saved collapse state
            if (localStorage.getItem('sbCollapsed') === '1') {
                sidebar.classList.add('collapsed');
                if (main) main.style.marginLeft = SLIM;
            } else {
                if (main) main.style.marginLeft = WIDE;
            }
        }
    });
})();

// ── Mobile sidebar drawer ────────────────────────────────────────
document.getElementById('sidebarToggle')?.addEventListener('click', () => toggleMobileSidebar());

function toggleMobileSidebar(forceClose) {
    const sidebar  = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (!sidebar) return;
    const open = forceClose ? false : !sidebar.classList.contains('open');
    sidebar.classList.toggle('open', open);
    backdrop?.classList.toggle('show', open);
    document.body.style.overflow = open ? 'hidden' : '';
    // On mobile the admin-main margin is controlled by CSS (margin-left:0!important)
    // so we must clear any JS-set inline style when entering mobile
    const main = document.querySelector('.admin-main');
    if (main && window.innerWidth <= 768) main.style.marginLeft = '';
}

document.getElementById('sidebarBackdrop')?.addEventListener('click', () => toggleMobileSidebar(true));

document.querySelectorAll('a.sb-link').forEach(el => {
    el.addEventListener('click', () => {
        if (window.innerWidth <= 768) toggleMobileSidebar(true);
    });
});


// ═══════════════════════════════════════════════════
//  DASHBOARD CHARTS
// ═══════════════════════════════════════════════════

// ── Palette ──────────────────────────────────────────
const C = {
    indigo : '#6366f1',
    indigoA: 'rgba(99,102,241,.12)',
    blue   : '#3b82f6',
    blueA  : 'rgba(59,130,246,.12)',
    green  : '#10b981',
    greenA : 'rgba(16,185,129,.12)',
    red    : '#ef4444',
    redA   : 'rgba(239,68,68,.12)',
    disburse: '#6366f1',
    disburseA: 'rgba(99,102,241,.15)',
    collect: '#10b981',
    collectA: 'rgba(16,185,129,.15)',
    grid   : '#f2f4f7',
    text   : '#667085',
};

// ── Sparkline helper ────────────────────────────────
function makeSparkline(canvasId, color, colorA, trend) {
    const el = document.getElementById(canvasId);
    if (!el) return;

    // Generate plausible sparkline values based on trend direction
    const base   = 40 + Math.random() * 20;
    const points = Array.from({ length: 8 }, (_, i) => {
        const noise = (Math.random() - 0.5) * 12;
        return Math.max(5, base + (trend === 'up' ? i * 3 : trend === 'down' ? -i * 2 : 0) + noise);
    });

    new Chart(el, {
        type: 'line',
        data: {
            labels: points.map(() => ''),
            datasets: [{
                data          : points,
                borderColor   : color,
                borderWidth   : 1.8,
                tension       : 0.45,
                pointRadius   : 0,
                fill          : true,
                backgroundColor: (ctx) => {
                    const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 44);
                    g.addColorStop(0,   color.replace(')', ', .25)').replace('rgb', 'rgba'));
                    g.addColorStop(1,   'rgba(255,255,255,0)');
                    return g;
                },
            }],
        },
        options: {
            responsive         : false,
            maintainAspectRatio: false,
            animation          : { duration: 600, easing: 'easeInOutQuart' },
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            scales : { x: { display: false }, y: { display: false } },
            elements: { line: { borderCapStyle: 'round', borderJoinStyle: 'round' } },
        },
    });
}

// Sparklines are initialised after the main charts so chart data is available
function initSparklines() {
    if (typeof dashboardChartData === 'undefined') return;

    // Determine trend from the last two months of each series
    const trend = (arr) => {
        if (!arr || arr.length < 2) return 'flat';
        return arr[arr.length - 1] >= arr[arr.length - 2] ? 'up' : 'down';
    };

    makeSparkline('spark-borrowers',  C.indigo, C.indigoA, 'up');
    makeSparkline('spark-portfolio',  C.blue,   C.blueA,   trend(dashboardChartData.disbursements));
    makeSparkline('spark-collection', C.green,  C.greenA,  trend(dashboardChartData.collections));
    makeSparkline('spark-atrisk',     C.red,    C.redA,    'down');
}


// ── Portfolio Performance Chart ─────────────────────
const portfolioEl = document.getElementById('portfolioChart');
if (portfolioEl) {
    const portfolioChart = new Chart(portfolioEl, {
        type: 'line',
        data: {
            labels  : dashboardChartData.labels,
            datasets: [
                {
                    label          : 'Disbursements',
                    data           : dashboardChartData.disbursements,
                    borderColor    : C.disburse,
                    backgroundColor: (ctx) => {
                        const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 240);
                        g.addColorStop(0,   'rgba(99,102,241,.18)');
                        g.addColorStop(1,   'rgba(99,102,241,0)');
                        return g;
                    },
                    borderWidth : 2.5,
                    tension     : 0.42,
                    pointRadius : 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: C.disburse,
                    pointBorderWidth: 2,
                    fill        : true,
                },
                {
                    label          : 'Collections',
                    data           : dashboardChartData.collections,
                    borderColor    : C.collect,
                    backgroundColor: (ctx) => {
                        const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 240);
                        g.addColorStop(0,   'rgba(16,185,129,.14)');
                        g.addColorStop(1,   'rgba(16,185,129,0)');
                        return g;
                    },
                    borderWidth : 2.5,
                    tension     : 0.42,
                    pointRadius : 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: C.collect,
                    pointBorderWidth: 2,
                    fill        : true,
                },
            ],
        },
        options: {
            responsive         : true,
            maintainAspectRatio: false,
            interaction        : { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels  : {
                        usePointStyle: true,
                        pointStyle   : 'circle',
                        boxWidth     : 7,
                        padding      : 18,
                        font         : { size: 11, family: 'Inter' },
                        color        : '#667085',
                    },
                },
                tooltip: {
                    backgroundColor  : '#fff',
                    borderColor      : '#eaecf0',
                    borderWidth      : 1,
                    titleColor       : '#101828',
                    bodyColor        : '#667085',
                    titleFont        : { size: 12, weight: '700', family: 'Inter' },
                    bodyFont         : { size: 11, family: 'Inter' },
                    padding          : 12,
                    cornerRadius     : 10,
                    boxPadding       : 5,
                    displayColors    : true,
                    usePointStyle    : true,
                    callbacks: {
                        title : (items) => items[0].label,
                        label : (item)  => `  ${item.dataset.label}: KES ${item.parsed.y}M`,
                    },
                },
            },
            scales: {
                y: {
                    grid : { color: C.grid, drawBorder: false },
                    ticks: {
                        font    : { size: 10, family: 'Inter' },
                        color   : C.text,
                        callback: v => 'KES ' + v + 'M',
                        maxTicksLimit: 6,
                    },
                    border: { display: false },
                },
                x: {
                    grid : { display: false },
                    ticks: {
                        font : { size: 10, family: 'Inter' },
                        color: C.text,
                    },
                    border: { display: false },
                },
            },
        },
    });

    // Period tab switching
    document.querySelectorAll('.cpt-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.cpt-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            // In a real app, fetch new data for the period — here just re-render with same data
            portfolioChart.update();
        });
    });

    initSparklines();
}


// ── Portfolio Health (Doughnut) ─────────────────────
const healthEl = document.getElementById('healthChart');
if (healthEl) {
    const healthColors = ['#12b76a', '#f79009', '#f04438', '#98a2b3'];
    const healthLabels = ['Current', 'Watch', 'Delinquent', 'Default'];

    // Compute total from raw health object if available
    let totalLoans = 0;
    if (typeof dashboardHealthRaw !== 'undefined') {
        // dashboardHealthRaw is {current: %, watch: %, ...}
        // The controller passes percentages; total loans is set on the center element via PHP
        const el = document.getElementById('healthTotalLoans');
        if (el && el.textContent === '—') {
            // Sum of percentages divided by average gives a proxy — instead read from DOM
            const legendCounts = document.querySelectorAll('.health-legend-count');
            legendCounts.forEach(c => { totalLoans += parseInt(c.textContent) || 0; });
            if (el) el.textContent = totalLoans || '—';
        }
    }

    // Also update from the count spans in the legend
    const countEls = document.querySelectorAll('.health-legend-count');
    countEls.forEach(c => { totalLoans += parseInt(c.textContent) || 0; });
    const centerEl = document.getElementById('healthTotalLoans');
    if (centerEl) centerEl.textContent = totalLoans || centerEl.textContent;

    const healthChart = new Chart(healthEl, {
        type: 'doughnut',
        data: {
            labels  : healthLabels,
            datasets: [{
                data           : dashboardHealthData,
                backgroundColor: healthColors,
                borderWidth    : 0,
                hoverOffset    : 6,
            }],
        },
        options: {
            cutout   : '75%',
            responsive: true,
            maintainAspectRatio: false,
            animation: { animateRotate: true, duration: 800 },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#fff',
                    borderColor    : '#eaecf0',
                    borderWidth    : 1,
                    titleColor     : '#101828',
                    bodyColor      : '#667085',
                    titleFont      : { size: 11, weight: '700', family: 'Inter' },
                    bodyFont       : { size: 11, family: 'Inter' },
                    padding        : 10,
                    cornerRadius   : 8,
                    callbacks      : {
                        label: (item) => `  ${item.label}: ${item.parsed}%`,
                    },
                },
            },
        },
    });
}
