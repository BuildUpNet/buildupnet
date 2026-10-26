/* Buildupnet — main.js */

document.addEventListener('DOMContentLoaded', function () {

  /* ---- Mobile Nav ---- */
  var toggle = document.getElementById('navToggle');
  var nav    = document.getElementById('mainNav');

  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      nav.classList.toggle('open');
      toggle.classList.toggle('open');
    });
    nav.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () {
        nav.classList.remove('open');
        toggle.classList.remove('open');
      });
    });
  }

  /* ---- Sticky Header Shadow ---- */
  var header = document.getElementById('siteHeader');
  if (header) {
    window.addEventListener('scroll', function () {
      header.style.boxShadow = window.scrollY > 10
        ? '0 4px 16px rgba(15,34,54,0.06)'
        : 'none';
    });
  }

  /* ---- FAQ Accordion ---- */
  document.querySelectorAll('.faq-item').forEach(function (item) {
    var q = item.querySelector('.faq-question');
    if (!q) return;
    q.addEventListener('click', function () {
      var isOpen = item.classList.contains('open');
      document.querySelectorAll('.faq-item.open').forEach(function (o) {
        o.classList.remove('open');
      });
      if (!isOpen) item.classList.add('open');
    });
  });

  /* ---- Quote Modal ---- */
  var overlay = document.getElementById('quoteModalOverlay');
  if (overlay) {
    var closeBtn = document.getElementById('quoteModalClose');

    function openModal()  { overlay.classList.add('open');    document.body.classList.add('modal-open'); }
    function closeModal() { overlay.classList.remove('open'); document.body.classList.remove('modal-open'); }

    document.querySelectorAll('[data-open-quote]').forEach(function (el) {
      el.addEventListener('click', function (e) { e.preventDefault(); openModal(); });
    });

    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) closeModal();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeModal();
    });

    if (window.location.search.indexOf('sent=1') !== -1) openModal();
  }

  /* ---- Careers: auto-select job dropdown ---- */
  var positionSelect = document.getElementById('app-position');
  document.querySelectorAll('.apply-trigger').forEach(function (el) {
    el.addEventListener('click', function () {
      if (positionSelect) positionSelect.value = el.getAttribute('data-job') || '';
    });
  });

  /* ---- Scroll Reveal ---- */
  if ('IntersectionObserver' in window) {
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('revealed');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });

    document.querySelectorAll('[data-reveal]').forEach(function (el) {
      observer.observe(el);
    });
  }

});