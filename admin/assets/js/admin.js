/* ==========================================================
   Buildupnet Admin — JS
   Place at: /admin/assets/js/admin.js
   ========================================================== */

document.addEventListener('DOMContentLoaded', function () {

  // Sidebar toggle (mobile)
  var toggle  = document.getElementById('sidebarToggle');
  var sidebar = document.getElementById('sidebar');
  if (toggle && sidebar) {
    toggle.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });
    document.addEventListener('click', function (e) {
      if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    });
  }

  // Confirm delete
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!confirm(el.getAttribute('data-confirm') || 'Are you sure?')) {
        e.preventDefault();
      }
    });
  });

  // Auto-hide flash messages
  var flash = document.querySelector('.flash');
  if (flash) {
    setTimeout(function () {
      flash.style.transition = 'opacity 0.5s';
      flash.style.opacity = '0';
      setTimeout(function () { flash.remove(); }, 500);
    }, 4000);
  }

  // Auto-generate slug from title
  var titleInput = document.getElementById('post_title');
  var slugInput  = document.getElementById('post_slug');
  if (titleInput && slugInput) {
    titleInput.addEventListener('input', function () {
      if (!slugInput.dataset.edited) {
        slugInput.value = titleInput.value
          .toLowerCase()
          .replace(/[^a-z0-9\s-]/g, '')
          .replace(/\s+/g, '-')
          .replace(/-+/g, '-')
          .trim();
      }
    });
    slugInput.addEventListener('input', function () {
      slugInput.dataset.edited = '1';
    });
  }

  // Project form: show the client block or the owner block depending on who gave the project
  var srcRadios = document.querySelectorAll('input[name="source_type"]');
  if (srcRadios.length) {
    var syncSource = function () {
      var owner = document.querySelector('input[name="source_type"]:checked');
      var isOwner = owner && owner.value === 'owner_direct';
      document.querySelectorAll('.src-client').forEach(function (el) { el.style.display = isOwner ? 'none' : ''; });
      document.querySelectorAll('.src-owner').forEach(function (el) { el.style.display = isOwner ? 'grid' : 'none'; });
    };
    srcRadios.forEach(function (r) { r.addEventListener('change', syncSource); });
    syncSource();
  }

  // Live clock on the attendance page (display only - the real time is always taken from the server)
  var clock = document.getElementById('liveClock');
  if (clock) {
    setInterval(function () {
      var d = new Date(); clock.textContent = ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2);
    }, 15000);
  }

});
