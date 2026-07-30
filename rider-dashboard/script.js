/* 
   rider-dashboard / script.js
   – Fetches live stats, orders & earnings from backend
   – POSTs GPS coordinates every GPS_INTERVAL seconds
   – Draws earnings line chart & donut chart via Canvas API
   – Handles sidebar, status dropdown, push subscription
    */

// ── Config 
const API_BASE      = '/ForkFresh/backend/api';
const API_SECRET    = 'forkfresh_dev_secret_2024';   // matches constants.php
const RIDER_ID      = 1;                              // demo: Jean Claude
const GPS_INTERVAL  = 10_000;   // ms between GPS posts
const STATS_INTERVAL= 15_000;   // ms between stats refresh

const AUTH_HEADER = { 'Authorization': `Bearer ${API_SECRET}` };

// ── DOM refs 
const sidebar       = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebarToggle');
const statValues    = document.querySelectorAll('.stat-value');
const greetingTitle = document.querySelector('.greeting-title');

document.addEventListener('DOMContentLoaded', () => {

    // ── Sidebar toggle (mobile) 
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    sidebarToggle?.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('visible');
    });
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('visible');
    });

    // ── Active nav item + page switching 
    document.querySelectorAll('.nav-item[data-page]').forEach(item => {
        item.addEventListener('click', e => {
            const href = item.getAttribute('href');
            if (href && href !== '#') return;   // allow real navigation (e.g. My Orders)
            e.preventDefault();

            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
            item.classList.add('active');

            const page = item.dataset.page;
            switchPage(page);

            if (window.innerWidth < 768) {
                sidebar.classList.remove('open');
                overlay.classList.remove('visible');
            }
        });
    });

    // ── Status dropdown 
    initStatusDropdown();

    // ── Greeting by time of day 
    updateGreeting();

    // ── Fetch initial data 
    fetchDashboardStats();
    fetchNextOrder();

    // ── Draw charts 
    drawLineChart(document.getElementById('earningsChart'));
    drawDonutChart(document.getElementById('donutChart'));

    // ── Polling 
    setInterval(fetchDashboardStats, STATS_INTERVAL);

    // ── GPS broadcasting 
    startGpsTracking();

    // ── Push subscription 
    registerPush();

    // ── Notification bell 
    document.querySelector('.notif-btn')?.addEventListener('click', () => {
        const badge = document.querySelector('.notif-badge');
        if (badge) badge.style.display = 'none';
        showToast('No new notifications.');
    });

    // ── View all orders / performance links 
    document.querySelectorAll('.view-all-link').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            showToast('Opening…');
        });
    });

    // ── Profile page init 
    initProfilePage();
});

// ── Fetch today's stats from backend 
async function fetchDashboardStats() {
    try {
        const today = new Date().toISOString().split('T')[0];

        const [ordersRes, riderRes] = await Promise.all([
            fetch(`${API_BASE}/orders.php?action=rider_orders&rider_id=${RIDER_ID}`),
            fetch(`${API_BASE}/riders.php?action=get&id=${RIDER_ID}`),
        ]);

        if (ordersRes.ok) {
            const ordersJson = await ordersRes.json();
            if (ordersJson.success) applyOrderStats(ordersJson.data);
        }

        if (riderRes.ok) {
            const riderJson = await riderRes.json();
            if (riderJson.success) applyRiderInfo(riderJson.data);
        }

    } catch (err) {
        console.warn('[Dashboard] fetchDashboardStats error:', err);
    }
}

// ── Apply order stats to the 4 stat cards 
function applyOrderStats(orders) {
    const todayStr  = new Date().toISOString().split('T')[0];
    const todayOrds = orders.filter(o => o.placed_at?.startsWith(todayStr));

    const total     = todayOrds.length;
    const completed = todayOrds.filter(o => o.status === 'delivered').length;
    const inProg    = todayOrds.filter(o =>
        ['assigned','preparing','on_the_way','out_for_delivery'].includes(o.status)
    ).length;
    const earnings  = todayOrds
        .filter(o => o.status === 'delivered')
        .reduce((sum, o) => sum + parseFloat(o.total_amount || 0), 0);

    const cards = document.querySelectorAll('.stat-card');
    if (cards[0]) cards[0].querySelector('.stat-value').textContent = total;
    if (cards[1]) cards[1].querySelector('.stat-value').textContent = completed;
    if (cards[2]) cards[2].querySelector('.stat-value').textContent = inProg;
    if (cards[3]) {
        cards[3].querySelector('.stat-value').textContent =
            'FCFA ' + earnings.toLocaleString('en-US');
    }
}

// ── Apply rider profile info 
function applyRiderInfo(rider) {
    const nameEl   = document.querySelector('.profile-name');
    const idEl     = document.querySelector('.profile-id');
    const ratingEl = document.querySelector('.perf-value:last-child');
    const avatarEl = document.querySelector('.profile-avatar');

    if (nameEl)   nameEl.textContent   = rider.name;
    if (idEl)     idEl.textContent     = `Rider ID: #${rider.rider_code}`;
    if (ratingEl && rider.rating) {
        ratingEl.innerHTML = `${parseFloat(rider.rating).toFixed(1)} <span class="star">★</span>`;
    }
    if (avatarEl && rider.avatar_url) avatarEl.src = rider.avatar_url;
}

// ── Fetch & display next pending order 
async function fetchNextOrder() {
    try {
        const res  = await fetch(`${API_BASE}/orders.php?action=rider_orders&rider_id=${RIDER_ID}`);
        if (!res.ok) return;
        const json = await res.json();
        if (!json.success) return;

        const active = json.data.find(o =>
            ['assigned','preparing','on_the_way'].includes(o.status)
        );
        if (!active) return;

        const idEl   = document.querySelector('.next-order-id');
        const addrEl = document.querySelector('.route-point:last-child .route-name');
        const restEl = document.querySelector('.route-point:first-child .route-name');

        if (idEl)   idEl.textContent   = '#' + active.order_number;
        if (restEl) restEl.textContent = active.restaurant_name;
        if (addrEl) addrEl.textContent = active.dropoff_address;

    } catch (err) {
        console.warn('[Dashboard] fetchNextOrder error:', err);
    }
}

// ── GPS tracking: broadcast location to backend 
function startGpsTracking() {
    if (!('geolocation' in navigator)) {
        console.info('[GPS] Geolocation not available.');
        return;
    }

    let orderId = null;

    async function resolveActiveOrder() {
        try {
            const res  = await fetch(`${API_BASE}/orders.php?action=rider_orders&rider_id=${RIDER_ID}`);
            const json = await res.json();
            if (json.success) {
                const active = json.data.find(o =>
                    ['assigned','preparing','on_the_way','out_for_delivery'].includes(o.status)
                );
                orderId = active ? active.id : null;
            }
        } catch { /* silent */ }
    }

    async function postLocation(position) {
        const { latitude, longitude, speed, heading } = position.coords;
        await resolveActiveOrder();

        try {
            await fetch(`${API_BASE}/tracking.php?action=update`, {
                method:  'POST',
                headers: {
                    'Content-Type':  'application/json',
                    ...AUTH_HEADER,
                },
                body: JSON.stringify({
                    rider_id:  RIDER_ID,
                    order_id:  orderId,
                    latitude,
                    longitude,
                    speed_kmh: speed  ? +(speed  * 3.6).toFixed(1) : 0,
                    heading:   heading ?? 0,
                }),
            });
        } catch (err) {
            console.warn('[GPS] POST failed:', err);
        }
    }

    // Watch continuously and also post on interval for reliability
    navigator.geolocation.watchPosition(postLocation, null, {
        enableHighAccuracy: true,
        maximumAge:         5000,
    });
    setInterval(() => {
        navigator.geolocation.getCurrentPosition(postLocation);
    }, GPS_INTERVAL);
}

// ── Status dropdown 
function initStatusDropdown() {
    const dropdown = document.querySelector('.status-dropdown');
    if (!dropdown) return;

    const statuses = ['Online', 'Busy', 'Offline'];
    const colors   = { Online: '#4caf50', Busy: '#e8652a', Offline: '#9e9e9e' };
    const dot      = dropdown.querySelector('.status-online-dot');
    let   current  = 0;

    dropdown.addEventListener('click', async () => {
        current = (current + 1) % statuses.length;
        const label = statuses[current];
        dropdown.querySelector('span:nth-child(2)').textContent = label;
        if (dot) dot.style.background = colors[label];

        // Sync status to backend
        try {
            await fetch(`${API_BASE}/riders.php?action=toggle_status&id=${RIDER_ID}`, {
                method:  'PATCH',
                headers: { 'Content-Type': 'application/json', ...AUTH_HEADER },
                body:    JSON.stringify({ status: label.toLowerCase() }),
            });
        } catch { /* silent */ }
    });
}

// ── Greeting by time of day 
function updateGreeting() {
    if (!greetingTitle) return;
    const hour = new Date().getHours();
    const part = hour < 12 ? 'morning' : hour < 17 ? 'afternoon' : 'evening';
    const name = document.querySelector('.profile-name')?.textContent || 'Jean Claude';
    greetingTitle.textContent = `Good ${part}, ${name}! 👋`;
}

// ── Push subscription (rider) 
async function registerPush() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
    try {
        const reg     = await navigator.serviceWorker.register('/ForkFresh/sw.js');
        const keyRes  = await fetch(`${API_BASE}/subscribe.php?action=vapid_key`);
        const keyJson = await keyRes.json();
        if (!keyJson.success) return;

        const permission = await Notification.requestPermission();
        if (permission !== 'granted') return;

        const sub = await reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(keyJson.data.public_key),
        });

        await fetch(`${API_BASE}/subscribe.php?action=save`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                ...sub.toJSON(),
                subscriber_type: 'rider',
                subscriber_id:   RIDER_ID,
            }),
        });
    } catch (err) {
        console.warn('[Push] Rider registration failed:', err);
    }
}

// ── Counter animation 
function animateCounter(el, target) {
    if (isNaN(target)) return;
    let current = 0;
    const step  = Math.ceil(target / 20);
    const timer = setInterval(() => {
        current = Math.min(current + step, target);
        el.textContent = current;
        if (current >= target) clearInterval(timer);
    }, 40);
}

// Run on page load for visible stat values
document.querySelectorAll('.stat-value:not(.earnings-val)').forEach(el => {
    const v = parseInt(el.textContent);
    if (!isNaN(v)) animateCounter(el, v);
});

// ── Earnings line chart 
function drawLineChart(canvas) {
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    const labels = ['12 AM','4 AM','8 AM','12 PM','4 PM','8 PM','11 PM'];
    const data   = [3000, 4500, 6000, 12000, 10000, 18000, 20000];

    function resize() {
        const parent = canvas.parentElement;
        const dpr    = window.devicePixelRatio || 1;
        const W      = parent.clientWidth;
        const H      = parent.clientHeight || 180;
        canvas.width  = W * dpr;
        canvas.height = H * dpr;
        canvas.style.width  = W + 'px';
        canvas.style.height = H + 'px';
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        render(W, H);
    }

    function render(W, H) {
        ctx.clearRect(0, 0, W, H);
        const pL = 40, pR = 10, pT = 10, pB = 30;
        const cW  = W - pL - pR;
        const cH  = H - pT - pB;
        const max = 22000;

        // Grid lines & Y labels
        [0, 5000, 10000, 15000, 20000].forEach(v => {
            const y = pT + cH - (v / max) * cH;
            ctx.strokeStyle = '#f0f0f0';
            ctx.lineWidth   = 1;
            ctx.beginPath(); ctx.moveTo(pL, y); ctx.lineTo(pL + cW, y); ctx.stroke();
            ctx.fillStyle   = '#999';
            ctx.font        = '10px Segoe UI, Arial';
            ctx.textAlign   = 'right';
            ctx.fillText(v === 0 ? '0' : (v / 1000) + 'K', pL - 6, y + 3);
        });

        const pts = data.map((v, i) => ({
            x: pL + (i / (data.length - 1)) * cW,
            y: pT + cH - (v / max) * cH,
        }));

        // Fill gradient
        const grad = ctx.createLinearGradient(0, pT, 0, pT + cH);
        grad.addColorStop(0,   'rgba(26,92,26,0.18)');
        grad.addColorStop(1,   'rgba(26,92,26,0)');
        ctx.beginPath();
        ctx.moveTo(pts[0].x, pT + cH);
        pts.forEach(p => ctx.lineTo(p.x, p.y));
        ctx.lineTo(pts[pts.length - 1].x, pT + cH);
        ctx.closePath();
        ctx.fillStyle = grad;
        ctx.fill();

        // Line
        ctx.beginPath();
        pts.forEach((p, i) => i === 0 ? ctx.moveTo(p.x, p.y) : ctx.lineTo(p.x, p.y));
        ctx.strokeStyle = '#1a5c1a';
        ctx.lineWidth   = 2.5;
        ctx.lineJoin    = 'round';
        ctx.stroke();

        // Dots
        pts.forEach(p => {
            ctx.beginPath(); ctx.arc(p.x, p.y, 4, 0, Math.PI * 2);
            ctx.fillStyle = '#1a5c1a'; ctx.fill();
            ctx.beginPath(); ctx.arc(p.x, p.y, 2.5, 0, Math.PI * 2);
            ctx.fillStyle = '#fff'; ctx.fill();
        });

        // X labels
        ctx.fillStyle = '#999';
        ctx.font      = '10px Segoe UI, Arial';
        ctx.textAlign = 'center';
        labels.forEach((lbl, i) => {
            ctx.fillText(lbl, pL + (i / (labels.length - 1)) * cW, H - pB + 16);
        });
    }

    resize();
    window.addEventListener('resize', resize);
}

// ── Donut chart 
function drawDonutChart(canvas) {
    if (!canvas) return;
    const ctx  = canvas.getContext('2d');
    const dpr  = window.devicePixelRatio || 1;
    const size = 160;
    canvas.width  = size * dpr;
    canvas.height = size * dpr;
    canvas.style.width  = size + 'px';
    canvas.style.height = size + 'px';
    ctx.scale(dpr, dpr);

    const cx = size / 2, cy = size / 2;
    const outerR = 70, innerR = 50;

    // Grey ring
    ctx.beginPath();
    ctx.arc(cx, cy, outerR,    0, Math.PI * 2);
    ctx.arc(cx, cy, outerR-14, 0, Math.PI * 2, true);
    ctx.fillStyle = '#ebebeb'; ctx.fill();

    // Segments: 75% green, 25% orange
    const segs = [
        { value: 0.75, color: '#1a5c1a' },
        { value: 0.25, color: '#e8652a' },
    ];
    let start = -Math.PI / 2;
    segs.forEach(seg => {
        const end = start + seg.value * Math.PI * 2;
        ctx.beginPath();
        ctx.moveTo(cx, cy);
        ctx.arc(cx, cy, outerR, start, end);
        ctx.arc(cx, cy, innerR, end, start, true);
        ctx.closePath();
        ctx.fillStyle = seg.color; ctx.fill();
        start = end;
    });

    // White hole
    ctx.beginPath();
    ctx.arc(cx, cy, innerR, 0, Math.PI * 2);
    ctx.fillStyle = '#fff'; ctx.fill();
}

// ── Helpers 
function urlBase64ToUint8Array(b64) {
    const pad = '='.repeat((4 - b64.length % 4) % 4);
    const raw = atob((b64 + pad).replace(/-/g, '+').replace(/_/g, '/'));
    return Uint8Array.from([...raw].map(c => c.charCodeAt(0)));
}

// ── Toast 
function showToast(msg) {
    document.querySelector('.dash-toast')?.remove();
    const toast = Object.assign(document.createElement('div'), {
        className:   'dash-toast',
        textContent: msg,
    });
    Object.assign(toast.style, {
        position:     'fixed',
        bottom:       '28px',
        left:         '50%',
        transform:    'translateX(-50%) translateY(20px)',
        background:   '#1a5c1a',
        color:        '#fff',
        padding:      '12px 24px',
        borderRadius: '50px',
        fontSize:     '0.88rem',
        fontWeight:   '500',
        boxShadow:    '0 4px 16px rgba(0,0,0,0.2)',
        opacity:      '0',
        transition:   'all 0.3s ease',
        zIndex:       '9999',
        whiteSpace:   'nowrap',
        pointerEvents:'none',
    });
    document.body.appendChild(toast);
    requestAnimationFrame(() => {
        toast.style.opacity   = '1';
        toast.style.transform = 'translateX(-50%) translateY(0)';
    });
    setTimeout(() => {
        toast.style.opacity   = '0';
        toast.style.transform = 'translateX(-50%) translateY(20px)';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ── Page switcher 
function switchPage(page) {
    const allSections = document.querySelectorAll('.page-section');
    allSections.forEach(s => s.style.display = 'none');

    const target = document.getElementById(`page-${page}`);
    if (target) {
        target.style.display = 'flex';
    } else {
        // Pages not yet built (Earnings, Wallet, etc.) → stay on dashboard
        const dashboard = document.getElementById('page-dashboard');
        if (dashboard) dashboard.style.display = 'flex';
        showToast(`${page.charAt(0).toUpperCase() + page.slice(1)} page coming soon.`);
    }

    // Update topbar greeting text to reflect current page
    const titles = {
        dashboard:   null,                  // use time-of-day greeting
        profile:     'My Profile',
        orders:      'My Orders',
        earnings:    'Earnings',
        wallet:      'Wallet',
        performance: 'Performance',
        settings:    'Settings',
        help:        'Help & Support',
    };
    const greetingEl = document.querySelector('.greeting-title');
    const subEl      = document.querySelector('.greeting-sub');
    if (greetingEl) {
        if (titles[page]) {
            greetingEl.textContent = titles[page];
            if (subEl) subEl.textContent = '';
        } else {
            updateGreeting();
            if (subEl) subEl.textContent = "Here's your overview for today.";
        }
    }
}

// ── Profile page: init, avatar preview, form submit 
function initProfilePage() {

    // ── Avatar live preview 
    const picInput   = document.getElementById('profilePicInput');
    const picPreview = document.getElementById('profilePicPreview');

    picInput?.addEventListener('change', () => {
        const file = picInput.files[0];
        if (!file) return;
        if (!file.type.startsWith('image/')) {
            showToast('Please select an image file.');
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            showToast('Image must be under 5 MB.');
            return;
        }
        const reader = new FileReader();
        reader.onload = e => {
            picPreview.src = e.target.result;
            // Also update the sidebar avatar
            const sidebarAvatar = document.querySelector('.profile-avatar');
            if (sidebarAvatar) sidebarAvatar.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });

    // ── Populate form from backend on first open 
    document.querySelector('.nav-item[data-page="profile"]')
        ?.addEventListener('click', loadProfileData);

    // ── Cancel button → back to dashboard 
    document.getElementById('btnCancelProfile')?.addEventListener('click', () => {
        switchPage('dashboard');
        document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
        document.querySelector('.nav-item[data-page="dashboard"]')?.classList.add('active');
        updateGreeting();
        const subEl = document.querySelector('.greeting-sub');
        if (subEl) subEl.textContent = "Here's your overview for today.";
    });

    // ── Form submit 
    document.getElementById('profileForm')?.addEventListener('submit', handleProfileSave);
}

// ── Load rider data into profile form 
async function loadProfileData() {
    try {
        const res  = await fetch(`${API_BASE}/riders.php?action=get&id=${RIDER_ID}`);
        const json = await res.json();
        if (!json.success) return;
        const r = json.data;

        setValue('profileFullName', r.name);
        setValue('profileEmail',    r.email);
        setValue('profilePhone',    r.phone);
        setValue('profileRiderCode','#' + r.rider_code);
        setValue('profileRating',   parseFloat(r.rating).toFixed(1) + ' ★');
        if (r.vehicle_type) {
            const sel = document.getElementById('profileVehicle');
            if (sel) sel.value = r.vehicle_type;
        }
        if (r.avatar_url) {
            const prev = document.getElementById('profilePicPreview');
            if (prev) prev.src = r.avatar_url;
        }
    } catch { /* show form with defaults on error */ }
}

function setValue(id, val) {
    const el = document.getElementById(id);
    if (el && val !== undefined && val !== null) el.value = val;
}

// ── Validate & save profile 
async function handleProfileSave(e) {
    e.preventDefault();
    if (!validateProfileForm()) return;

    const btn = document.getElementById('btnSaveProfile');
    btn.classList.add('saving');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';

    const payload = {
        name:         document.getElementById('profileFullName').value.trim(),
        email:        document.getElementById('profileEmail').value.trim(),
        phone:        document.getElementById('profilePhone').value.trim(),
        vehicle_type: document.getElementById('profileVehicle').value,
    };

    // Handle avatar upload if a new file was chosen
    const picInput = document.getElementById('profilePicInput');
    if (picInput?.files[0]) {
        // In production: upload to server and get back a URL.
        // For now we store the data-URL as avatar_url (demo only).
        payload.avatar_url = document.getElementById('profilePicPreview').src;
    }

    try {
        const res  = await fetch(
            `${API_BASE}/riders.php?action=update&id=${RIDER_ID}`,
            {
                method:  'PATCH',
                headers: { 'Content-Type': 'application/json', ...AUTH_HEADER },
                body:    JSON.stringify(payload),
            }
        );
        const json = await res.json();

        if (json.success) {
            // Sync sidebar name
            const nameEl = document.querySelector('.profile-name');
            if (nameEl) nameEl.textContent = payload.name;
            showToast('Profile saved successfully!');
        } else {
            showToast(json.error || 'Save failed. Please try again.');
        }
    } catch {
        showToast('Network error. Check your connection.');
    } finally {
        btn.classList.remove('saving');
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save Changes';
    }
}

// ── Client-side form validation 
function validateProfileForm() {
    let valid = true;

    function setError(inputId, errorId, msg) {
        const input = document.getElementById(inputId);
        const err   = document.getElementById(errorId);
        if (msg) {
            input?.classList.add('has-error');
            if (err) err.textContent = msg;
            valid = false;
        } else {
            input?.classList.remove('has-error');
            if (err) err.textContent = '';
        }
    }

    const name  = document.getElementById('profileFullName')?.value.trim();
    const email = document.getElementById('profileEmail')?.value.trim();
    const phone = document.getElementById('profilePhone')?.value.trim();

    setError('profileFullName', 'errName',
        !name                         ? 'Full name is required.' :
        name.length < 2               ? 'Name is too short.'     : '');

    setError('profileEmail', 'errEmail',
        !email                                        ? 'Email is required.' :
        !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)    ? 'Enter a valid email address.' : '');

    setError('profilePhone', 'errPhone',
        !phone                          ? 'Phone number is required.' :
        phone.replace(/\D/g, '').length < 8 ? 'Enter a valid phone number.' : '');

    return valid;
}

// Clear errors on input
['profileFullName','profileEmail','profilePhone'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', () => {
        document.getElementById(id)?.classList.remove('has-error');
        const errMap = { profileFullName:'errName', profileEmail:'errEmail', profilePhone:'errPhone' };
        const errEl  = document.getElementById(errMap[id]);
        if (errEl) errEl.textContent = '';
    });
});
