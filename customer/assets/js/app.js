/* ==========================================================
   ForkFresh – Shared JavaScript (app.js)
   Sidebar toggle, toast helper, chip selection
   ========================================================== */

'use strict';

/* ── Sidebar toggle (mobile) ─────────────────────────────── */
(function () {
  const toggle  = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');

  if (!toggle || !sidebar) return;

  toggle.addEventListener('click', openSidebar);
  overlay && overlay.addEventListener('click', closeSidebar);

  function openSidebar() {
    sidebar.classList.add('open');
    overlay && overlay.classList.add('visible');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    sidebar.classList.remove('open');
    overlay && overlay.classList.remove('visible');
    document.body.style.overflow = '';
  }

  /* Close on Escape */
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeSidebar();
  });
})();

/* ── Toast notification helper ───────────────────────────── */
function showToast(message, type = 'info', duration = 3200) {
  const container = document.getElementById('toastContainer');
  if (!container) return;

  const toast = document.createElement('div');
  toast.className = 'toast ' + type;
  toast.textContent = message;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(30px)';
    toast.style.transition = 'opacity .3s, transform .3s';
    setTimeout(() => toast.remove(), 320);
  }, duration);
}

/* ── Chip / card toggle (diet, spice, food, allergen) ──────
   Attach via data-group="single|multi" on the wrapper.
   Children must have class chip-label, food-card-label, or allergen-label.
   ─────────────────────────────────────────────────────────── */
(function () {
  document.querySelectorAll('[data-group]').forEach(group => {
    const isMulti  = group.dataset.group === 'multi';
    const selector = '.chip-label, .food-card-label, .allergen-label';

    group.querySelectorAll(selector).forEach(label => {
      label.addEventListener('click', function () {
        if (!isMulti) {
          /* Single select – clear siblings */
          group.querySelectorAll(selector).forEach(l => {
            l.classList.remove('selected');
            const inp = l.querySelector('input');
            if (inp) inp.checked = false;
          });
        }
        this.classList.toggle('selected');
        const inp = this.querySelector('input');
        if (inp) inp.checked = this.classList.contains('selected');
      });
    });
  });
})();

/* ── Utility: CSRF token fetch from meta tag ─────────────── */
function getCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.content : '';
}

/* ── Utility: JSON POST helper ───────────────────────────── */
async function postJSON(url, data) {
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data),
  });
  return res.json();
}
