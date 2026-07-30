/* ============================================================
   track-order / script.js
   – Polls backend for live order status & rider GPS
   – Registers browser push notifications via Service Worker
   ============================================================ */

// ── Config ────────────────────────────────────────────────────
const API_BASE   = '/ForkFresh/backend/api';
const POLL_MS    = 10_000;   // re-fetch order status every 10 s
const GPS_MS     = 8_000;    // re-fetch rider GPS every 8 s

// Derive order number from URL: ?order=FF125679
// Falls back to the hard-coded demo order shown in the HTML
const urlParams   = new URLSearchParams(window.location.search);
const ORDER_NUM   = (urlParams.get('order') || 'FF125679').toUpperCase();

// ── Status → step index mapping ───────────────────────────────
const STATUS_STEP = {
    pending:           0,
    assigned:          1,
    preparing:         1,
    on_the_way:        2,
    out_for_delivery:  3,
    delivered:         4,
    cancelled:        -1,
};

const STATUS_LABEL = {
    pending:           'Order Placed',
    assigned:          'Rider Assigned',
    preparing:         'Preparing',
    on_the_way:        'On-the-way',
    out_for_delivery:  'Out for Delivery',
    delivered:         'Delivered',
    cancelled:         'Cancelled',
};

// ── DOM refs ──────────────────────────────────────────────────
const statusBadgeEl   = document.querySelector('.status-badge span');
const etaTimeEl       = document.querySelector('.eta-time');
const riderNameEl     = document.querySelector('.rider-name');
const riderPhoneEl    = document.querySelector('.rider-phone');
const riderAvatarEl   = document.querySelector('.rider-avatar');
const trackerFillEl   = document.querySelector('.tracker-line-fill');
const stepEls         = document.querySelectorAll('.step');

document.addEventListener('DOMContentLoaded', () => {

    // ── Animate tracker fill on load ─────────────────────────
    animateFill(75);

    // ── Animate step icons ────────────────────────────────────
    animateStepIcons();

    // ── Initial data fetch ────────────────────────────────────
    fetchOrderStatus();
    fetchRiderLocation();

    // ── Polling ───────────────────────────────────────────────
    setInterval(fetchOrderStatus,   POLL_MS);
    setInterval(fetchRiderLocation, GPS_MS);

    // ── Action buttons ────────────────────────────────────────
    const callBtn = document.querySelector('.action-btn[title="Call rider"]');
    const chatBtn = document.querySelector('.action-btn[title="Chat with rider"]');
    if (callBtn) callBtn.addEventListener('click', () => showToast('Calling rider…'));
    if (chatBtn) chatBtn.addEventListener('click', () => showToast('Opening chat…'));

    const helpLink = document.querySelector('.help-link');
    if (helpLink) helpLink.addEventListener('click', e => {
        e.preventDefault();
        showToast('Connecting to support…');
    });

    // ── Register service worker & push subscription ───────────
    registerPush();

    // ── Avatar pulse ──────────────────────────────────────────
    injectAvatarPulse();
});

// ── Fetch order status from backend ───────────────────────────
async function fetchOrderStatus() {
    try {
        const res  = await fetch(
            `${API_BASE}/orders.php?action=get&number=${ORDER_NUM}`
        );
        if (!res.ok) return;
        const json = await res.json();
        if (!json.success) return;

        const order = json.data;
        applyOrderToUI(order);

    } catch (err) {
        console.warn('[TrackOrder] fetchOrderStatus error:', err);
    }
}

// ── Apply order data to the UI ─────────────────────────────────
function applyOrderToUI(order) {
    // Status badge
    if (statusBadgeEl) {
        statusBadgeEl.textContent =
            STATUS_LABEL[order.status] ?? order.status.replace(/_/g, ' ');
    }

    // Step tracker
    const stepIndex = STATUS_STEP[order.status] ?? 0;
    updateTracker(stepIndex, order.status === 'cancelled');

    // Rider info
    if (order.rider_name && riderNameEl)  riderNameEl.textContent  = order.rider_name;
    if (order.rider_phone && riderPhoneEl) riderPhoneEl.textContent = order.rider_phone;
    if (order.rider_avatar && riderAvatarEl) {
        riderAvatarEl.src = order.rider_avatar;
    }

    // ETA
    if (etaTimeEl && order.estimated_minutes) {
        const now  = new Date();
        const from = new Date(now.getTime() + order.estimated_minutes * 60_000);
        const to   = new Date(from.getTime() + 30 * 60_000);
        etaTimeEl.textContent =
            `Today, ${fmt(from)} – ${fmt(to)}`;
    }

    // Rider location on map (update SVG overlay if GPS available)
    if (order.rider_location) {
        updateMapDot(order.rider_location);
    }
}

// ── Fetch rider GPS position ───────────────────────────────────
async function fetchRiderLocation() {
    try {
        const res  = await fetch(
            `${API_BASE}/orders.php?action=get&number=${ORDER_NUM}`
        );
        if (!res.ok) return;
        const json = await res.json();
        if (!json.success || !json.data.rider_location) return;

        updateMapDot(json.data.rider_location);
    } catch (err) {
        console.warn('[TrackOrder] fetchRiderLocation error:', err);
    }
}

// ── Update the tracker steps ───────────────────────────────────
function updateTracker(activeIndex, isCancelled) {
    const fillPct = isCancelled ? 0 : Math.round((activeIndex / 4) * 100);
    animateFill(fillPct);

    stepEls.forEach((step, i) => {
        step.classList.remove('completed', 'active');
        if (isCancelled) {
            step.querySelector('.step-icon').style.opacity = '0.35';
            return;
        }
        if (i < activeIndex)  step.classList.add('completed');
        if (i === activeIndex) step.classList.add('active');
    });
}

// ── Animate the tracker fill bar ──────────────────────────────
function animateFill(pct) {
    if (!trackerFillEl) return;
    trackerFillEl.style.transition = 'width 0.8s ease';
    trackerFillEl.style.width      = `${pct}%`;
}

// ── Animate step icons on load ────────────────────────────────
function animateStepIcons() {
    stepEls.forEach((step, i) => {
        const icon = step.querySelector('.step-icon');
        if (!icon) return;
        if (step.classList.contains('completed') || step.classList.contains('active')) {
            icon.style.opacity   = '0';
            icon.style.transform = 'scale(0.5)';
            setTimeout(() => {
                icon.style.transition = 'all 0.4s ease';
                icon.style.opacity    = '1';
                icon.style.transform  = 'scale(1)';
            }, 400 + i * 150);
        }
    });
}

// ── Update map rider dot (simple SVG overlay) ──────────────────
function updateMapDot(gps) {
    // The map is a static SVG illustration so we can't use real coords.
    // Instead, animate the rider icon smoothly within the SVG viewBox.
    // In a real app swap this SVG for a Leaflet/Google Maps instance.
    const riderGroup = document.querySelector('.map-svg g:last-of-type');
    if (riderGroup) {
        // Tiny pulse animation to indicate live update
        riderGroup.style.transition = 'opacity 0.5s';
        riderGroup.style.opacity    = '0.6';
        setTimeout(() => { riderGroup.style.opacity = '1'; }, 500);
    }
    // Log for dev visibility
    console.debug('[GPS]', gps.latitude, gps.longitude,
                  '–', gps.recorded_at);
}

// ── Push Notification registration ────────────────────────────
async function registerPush() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        console.info('[Push] Not supported in this browser.');
        return;
    }

    try {
        // Register SW from project root
        const reg = await navigator.serviceWorker.register('/ForkFresh/sw.js');

        // Fetch VAPID public key
        const keyRes  = await fetch(`${API_BASE}/subscribe.php?action=vapid_key`);
        const keyJson = await keyRes.json();
        if (!keyJson.success) return;

        const vapidKey = urlBase64ToUint8Array(keyJson.data.public_key);

        // Ask permission
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') return;

        // Subscribe
        const sub = await reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: vapidKey,
        });

        // Save to backend
        await fetch(`${API_BASE}/subscribe.php?action=save`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                ...sub.toJSON(),
                subscriber_type: 'customer',
            }),
        });

        console.info('[Push] Subscribed successfully.');
    } catch (err) {
        console.warn('[Push] Registration failed:', err);
    }
}

// ── Helpers ───────────────────────────────────────────────────
function fmt(date) {
    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}

function urlBase64ToUint8Array(base64String) {
    const padding  = '='.repeat((4 - base64String.length % 4) % 4);
    const base64   = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw      = window.atob(base64);
    return Uint8Array.from([...raw].map(c => c.charCodeAt(0)));
}

// ── Toast ─────────────────────────────────────────────────────
function showToast(msg) {
    document.querySelector('.toast-notify')?.remove();

    const toast = Object.assign(document.createElement('div'), {
        className: 'toast-notify',
        textContent: msg,
    });
    Object.assign(toast.style, {
        position:  'fixed',
        bottom:    '28px',
        left:      '50%',
        transform: 'translateX(-50%) translateY(20px)',
        background:'#1a5c1a',
        color:     '#fff',
        padding:   '12px 24px',
        borderRadius: '50px',
        fontSize:  '0.9rem',
        fontWeight:'500',
        boxShadow: '0 4px 16px rgba(0,0,0,0.18)',
        opacity:   '0',
        transition:'all 0.3s ease',
        zIndex:    '9999',
        whiteSpace:'nowrap',
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

// ── Avatar pulse ─────────────────────────────────────────────
function injectAvatarPulse() {
    const avatar = document.querySelector('.rider-avatar');
    if (avatar) avatar.style.animation = 'avatarPulse 2.5s ease-in-out infinite';

    const s = document.createElement('style');
    s.textContent = `
        @keyframes avatarPulse {
            0%,100% { box-shadow: 0 0 0 0   rgba(26,92,26,0.3); }
            50%      { box-shadow: 0 0 0 8px rgba(26,92,26,0);   }
        }`;
    document.head.appendChild(s);
}
