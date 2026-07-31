/* ================================================
   ForkFresh — Main JS
   ================================================ */
(function () {
  'use strict';

  /* ── Hamburger toggle ─────────────────────────── */
  const hamburger = document.getElementById('hamburger');
  const mainNav   = document.getElementById('mainNav');

  if (hamburger && mainNav) {
    hamburger.addEventListener('click', () => {
      const open = mainNav.classList.toggle('open');
      hamburger.setAttribute('aria-expanded', open);
    });
    // close on outside click
    document.addEventListener('click', (e) => {
      if (!hamburger.contains(e.target) && !mainNav.contains(e.target)) {
        mainNav.classList.remove('open');
        hamburger.setAttribute('aria-expanded', false);
      }
    });
  }

  /* ── Active nav link ──────────────────────────── */
  const page = window.location.pathname.split('/').pop() || 'index.php';
  document.querySelectorAll('.main-nav a').forEach((link) => {
    const href = link.getAttribute('href').split('/').pop();
    if (href === page) link.classList.add('active');
  });

  /* ── Smooth-scroll "Order Now" ────────────────── */
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener('click', function (e) {
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  /* ── Lazy-load images (IntersectionObserver) ─── */
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries, obs) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const img = entry.target;
          if (img.dataset.src) {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
          }
          obs.unobserve(img);
        }
      });
    }, { rootMargin: '200px' });

    document.querySelectorAll('img[data-src]').forEach((img) => io.observe(img));
  } else {
    // fallback: load all immediately
    document.querySelectorAll('img[data-src]').forEach((img) => {
      img.src = img.dataset.src;
    });
  }

  /* ── Category horizontal scroll arrows ───────── */
  // If you add prev/next buttons give them class .cat-prev / .cat-next
  const catGrid = document.querySelector('.cat-grid');
  const catPrev = document.querySelector('.cat-prev');
  const catNext = document.querySelector('.cat-next');
  if (catGrid) {
    if (catPrev) catPrev.addEventListener('click', () => catGrid.scrollBy({ left: -200, behavior: 'smooth' }));
    if (catNext) catNext.addEventListener('click', () => catGrid.scrollBy({ left:  200, behavior: 'smooth' }));
  }

  /* ── Stats counter animation (About page) ─────── */
  const statNums = document.querySelectorAll('.stat-num[data-target]');
  if (statNums.length) {
    const animateCount = (el) => {
      const target = parseFloat(el.dataset.target);
      const suffix = el.dataset.suffix || '';
      const duration = 1600;
      const step = 16;
      const steps = duration / step;
      let current = 0;
      const increment = target / steps;
      const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
          current = target;
          clearInterval(timer);
        }
        el.textContent = (target % 1 === 0 ? Math.floor(current) : current.toFixed(1))
                         .toLocaleString() + suffix;
      }, step);
    };

    const io2 = new IntersectionObserver((entries, obs) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          animateCount(entry.target);
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.4 });

    statNums.forEach((el) => io2.observe(el));
  }

})();
