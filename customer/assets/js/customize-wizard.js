/* ==========================================================
   ForkFresh – Customize Meal Plan Wizard
   Handles: step navigation, live review population,
            form validation, AJAX submit
   ========================================================== */
'use strict';

(function () {

  /* ── DOM refs ──────────────────────────────────────────── */
  const tabs    = document.querySelectorAll('.step-tab');
  const panels  = document.querySelectorAll('.step-panel');
  const btnBack = document.getElementById('btnBack');
  const btnNext = document.getElementById('btnNext');
  let   current = 1;
  const TOTAL   = 3;

  /* ── Step config ── */
  const stepConfig = {
    1: { backLabel: null,                         nextLabel: 'Next: Allergies <i class="fa fa-chevron-right"></i>' },
    2: { backLabel: '<i class="fa fa-chevron-left"></i> Back to Preferences', nextLabel: 'Next: Review <i class="fa fa-chevron-right"></i>' },
    3: { backLabel: '<i class="fa fa-chevron-left"></i> Back to Allergies',   nextLabel: '<i class="fa fa-check"></i> Create My Meal Plan' },
  };

  /* ── Render current step ───────────────────────────────── */
  function renderStep(n) {
    current = n;

    /* Panels */
    panels.forEach(p => p.classList.remove('active'));
    const panel = document.getElementById('step' + n);
    if (panel) panel.classList.add('active');

    /* Tabs */
    tabs.forEach(t => {
      const tn = parseInt(t.dataset.step, 10);
      t.classList.toggle('active',    tn === n);
      t.classList.toggle('completed', tn < n);
    });

    /* Back button */
    if (stepConfig[n].backLabel) {
      btnBack.innerHTML    = stepConfig[n].backLabel;
      btnBack.style.visibility = 'visible';
    } else {
      btnBack.style.visibility = 'hidden';
    }

    /* Next / submit button */
    btnNext.innerHTML = stepConfig[n].nextLabel;
    if (n === TOTAL) {
      btnNext.className = 'btn-create-my-plan';
    } else {
      btnNext.className = 'btn-step-next';
    }

    /* Populate review on step 3 */
    if (n === TOTAL) populateReview();
  }

  /* ── Validate step 1 ───────────────────────────────────── */
  function validateStep1() {
    const diet = document.querySelector('input[name="diet_preference"]:checked');
    if (!diet) {
      showToast('Please select a diet preference.', 'error');
      return false;
    }
    const foods = document.querySelectorAll('input[name="food_items[]"]:checked');
    if (foods.length === 0) {
      showToast('Please select at least one food preference.', 'error');
      return false;
    }
    const spice = document.querySelector('input[name="spice_level"]:checked');
    if (!spice) {
      showToast('Please select a spice level.', 'error');
      return false;
    }
    return true;
  }

  /* ── Populate review summary ───────────────────────────── */
  function populateReview() {
    /* Diet */
    const dietInput = document.querySelector('input[name="diet_preference"]:checked');
    const dietLabel = dietInput
      ? dietInput.closest('.chip-label').textContent.trim()
      : '—';
    document.getElementById('reviewDiet').textContent = dietLabel;

    /* Foods */
    const foodChecked = document.querySelectorAll('input[name="food_items[]"]:checked');
    const foodNames   = Array.from(foodChecked).map(c => {
      /* The food name is the last <span> before .check-badge */
      const spans = c.closest('.food-card-label').querySelectorAll('span');
      /* spans[0]=food-emoji, spans[1]=name text, spans[2]=check-badge */
      return spans[1] ? spans[1].textContent.trim() : '';
    }).filter(Boolean);
    document.getElementById('reviewFoods').textContent =
      foodNames.length ? foodNames.join(', ') : '—';

    /* Spice */
    const spiceInput = document.querySelector('input[name="spice_level"]:checked');
    const spiceLabel = spiceInput
      ? spiceInput.closest('.chip-label').textContent.trim()
      : '—';
    document.getElementById('reviewSpice').textContent = spiceLabel;

    /* Allergies */
    const allergyChecked = document.querySelectorAll('input[name="allergens[]"]:checked');
    const allergyNames   = Array.from(allergyChecked).map(c => {
      /* spans[0]=allergen-emoji, spans[1]=name text, spans[2]=check-badge */
      const spans = c.closest('.allergen-label').querySelectorAll('span');
      return spans[1] ? spans[1].textContent.trim() : '';
    }).filter(Boolean);
    document.getElementById('reviewAllergies').textContent =
      allergyNames.length ? allergyNames.join(', ') : 'No Allergies Selected';

    /* Additional info */
    const addInfo = (document.querySelector('textarea[name="additional_info"]')?.value || '').trim();
    document.getElementById('reviewAdditional').textContent =
      addInfo.length ? addInfo : 'None Provided';
  }

  /* ── Navigation buttons ────────────────────────────────── */
  btnNext.addEventListener('click', function () {
    if (current === 1) {
      if (!validateStep1()) return;
      renderStep(2);
    } else if (current === 2) {
      renderStep(3);
    } else if (current === TOTAL) {
      submitForm();
    }
  });

  btnBack.addEventListener('click', function () {
    if (current > 1) renderStep(current - 1);
  });

  /* Click on a completed tab to jump back */
  tabs.forEach(tab => {
    tab.addEventListener('click', function () {
      const n = parseInt(this.dataset.step, 10);
      if (n < current) renderStep(n);
    });
  });

  /* ── Click "Allergies" review row → jump to step 2 ──── */
  const allergyRow = document.getElementById('reviewAllergyRow');
  allergyRow && allergyRow.addEventListener('click', () => renderStep(2));

  /* ── Collect form data ─────────────────────────────────── */
  function collectData() {
    const form = document.getElementById('customizeForm');
    const fd   = new FormData(form);

    return {
      action:          'save_preferences',
      mode:            fd.get('mode'),
      sub_id:          fd.get('sub_id') || null,
      diet_preference: fd.get('diet_preference'),
      spice_level:     fd.get('spice_level'),
      food_items:      fd.getAll('food_items[]').map(Number),
      allergens:       fd.getAll('allergens[]').map(Number),
      additional_info: fd.get('additional_info') || '',
    };
  }

  /* ── Submit ────────────────────────────────────────────── */
  async function submitForm() {
    const btn = btnNext;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving…';

    try {
      const res = await postJSON('../api/meal-plan-handler.php', collectData());

      if (res.success) {
        showToast('Meal plan created successfully!', 'success');
        setTimeout(() => {
          window.location.href = 'manage-subscription.php';
        }, 1400);
      } else {
        showToast(res.message || 'Could not save plan. Please try again.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-check"></i> Create My Meal Plan';
      }
    } catch (err) {
      showToast('Network error. Please try again.', 'error');
      btn.disabled = false;
      btn.innerHTML = '<i class="fa fa-check"></i> Create My Meal Plan';
    }
  }

  /* ── Kick off on page load ─────────────────────────────── */
  renderStep(1);

})();
