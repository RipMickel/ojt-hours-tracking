/* assets/js/app.js — OJT Tracker */

(function () {
  'use strict';

  /* ── Mobile sidebar toggle ──────────────────────── */
  const menuBtn  = document.getElementById('menuBtn');
  const sidebar  = document.querySelector('.sidebar');
  if (menuBtn && sidebar) {
    menuBtn.addEventListener('click', () => sidebar.classList.toggle('open'));
    document.addEventListener('click', (e) => {
      if (!sidebar.contains(e.target) && !menuBtn.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    });
  }

  /* ── Progress bar animate-in ────────────────────── */
  document.querySelectorAll('.progress-bar-fill[data-pct]').forEach(bar => {
    const target = bar.dataset.pct;
    bar.style.width = '0%';
    requestAnimationFrame(() =>
      setTimeout(() => { bar.style.width = target + '%'; }, 80)
    );
  });

  /* ── Live hours calculator ──────────────────────── */
  function setupHoursCalc(scope) {
    const inEl  = scope.querySelector('[data-time-in]')  || scope.querySelector('#time_in');
    const outEl = scope.querySelector('[data-time-out]') || scope.querySelector('#time_out');
    const prev  = scope.querySelector('.hours-preview');
    if (!inEl || !outEl || !prev) return;

    function recalc() {
      const tIn  = inEl.value;
      const tOut = outEl.value;
      if (!tIn || !tOut) {
        prev.textContent = '— hrs';
        prev.className = 'hours-preview';
        return;
      }
      const [ih, im] = tIn.split(':').map(Number);
      const [oh, om] = tOut.split(':').map(Number);
      const mins = (oh * 60 + om) - (ih * 60 + im);
      if (mins <= 0) {
        prev.textContent = 'Time-out must be after time-in';
        prev.className = 'hours-preview error';
      } else {
        const h = Math.floor(mins / 60);
        const m = mins % 60;
        prev.textContent = (m > 0 ? `${h}h ${m}m` : `${h}h`) + ' rendered';
        prev.className = 'hours-preview has-value';
      }
    }
    inEl.addEventListener('change', recalc);
    outEl.addEventListener('change', recalc);
    recalc();
  }

  document.querySelectorAll('.log-form').forEach(setupHoursCalc);
  setupHoursCalc(document); // fallback for single-form pages

  /* ── Auto-dismiss success alerts ───────────────── */
  document.querySelectorAll('.alert-success').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity .4s';
      el.style.opacity    = '0';
      setTimeout(() => el.remove(), 450);
    }, 4500);
  });

  /* ── Confirm-before-submit ──────────────────────── */
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
  });
})();