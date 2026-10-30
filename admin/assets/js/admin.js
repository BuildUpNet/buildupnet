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

  // Confirm delete (delegated, so it also works on blocks that were refreshed without a page reload)
  document.addEventListener('click', function (e) {
    var el = e.target.closest ? e.target.closest('[data-confirm]') : null;
    if (el && !confirm(el.getAttribute('data-confirm') || 'Are you sure?')) { e.preventDefault(); e.stopPropagation(); }
  }, true);

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

  // ------------------------------------------------------------------
  // No-reload pages ("AJAX"): blocks marked data-region are replaced in place.
  //   form[data-ajax]  -> sent with fetch, the page's blocks refresh, a small message pops up
  //   a[data-nav]      -> month / tab links load into the blocks and update the address bar
  //   [data-refresh=N] -> that block reloads itself every N seconds (live team board)
  // Everything also works without JavaScript (normal links and forms).
  // ------------------------------------------------------------------
  var REGION = '[data-region]';

  function toast(msg, type) {
    if (!msg) return;
    var box = document.getElementById('toasts');
    if (!box) { box = document.createElement('div'); box.id = 'toasts'; box.setAttribute('aria-live', 'polite'); document.body.appendChild(box); }
    var t = document.createElement('div'); t.className = 'toast toast-' + (type || 'success'); t.textContent = msg;
    box.appendChild(t);
    setTimeout(function () { t.style.opacity = '0'; setTimeout(function () { t.remove(); }, 400); }, 4200);
  }
  function parse(html) { return new DOMParser().parseFromString(html, 'text/html'); }
  function loggedOut(doc) { return !!doc.querySelector('#username'); }

  /** Replace every region of this page with the same-named region of `doc`. False = not possible (do a normal page load). */
  function swap(doc) {
    var cur = document.querySelectorAll(REGION), i, name, nw;
    if (!cur.length) return false;
    for (i = 0; i < cur.length; i++) if (!doc.querySelector('[data-region="' + cur[i].getAttribute('data-region') + '"]')) return false;
    for (i = 0; i < cur.length; i++) {
      name = cur[i].getAttribute('data-region'); nw = doc.querySelector('[data-region="' + name + '"]');
      cur[i].innerHTML = nw.innerHTML;
      if (nw.hasAttribute('data-refresh')) cur[i].setAttribute('data-refresh', nw.getAttribute('data-refresh')); else cur[i].removeAttribute('data-refresh');
    }
    if (doc.title) document.title = doc.title;
    tick();
    return true;
  }
  function flashFrom(doc) {
    var f = doc.querySelector('.flash');
    if (f) toast(f.textContent.trim(), /error/.test(f.className) ? 'error' : 'success');
  }
  function request(url, opts) {
    opts = opts || {}; opts.credentials = 'same-origin'; opts.headers = { 'X-Requested-With': 'XMLHttpRequest' };
    return fetch(url, opts).then(function (r) {
      if ((r.headers.get('content-type') || '').indexOf('json') > -1) return r.json().then(function (j) { return { json: j, url: r.url }; });
      return r.text().then(function (t) { return { doc: parse(t), url: r.url }; });
    });
  }
  function reload(quiet) {                      // refresh the blocks of the current page
    return request(location.href).then(function (res) {
      if (!res.doc || loggedOut(res.doc) || !swap(res.doc)) { if (!quiet) location.reload(); }
    }).catch(function () { if (!quiet) location.reload(); });
  }
  function navigate(url, push) {
    request(url).then(function (res) {
      if (!res.doc || loggedOut(res.doc) || !swap(res.doc)) { location.href = url; return; }
      if (push) history.pushState({ ajax: 1 }, '', url);
    }).catch(function () { location.href = url; });
  }

  document.addEventListener('click', function (e) {
    var a = e.target.closest ? e.target.closest('a[data-nav]') : null;
    if (!a || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || !document.querySelector(REGION)) return;
    e.preventDefault(); navigate(a.href, true);
  });
  document.addEventListener('change', function (e) {          // date picker of the team board
    var i = e.target;
    if (i.matches && i.matches('input[data-navform]') && i.form && document.querySelector(REGION))
      navigate(location.pathname + '?' + new URLSearchParams(new FormData(i.form)).toString(), true);
  });
  window.addEventListener('popstate', function () { if (document.querySelector(REGION)) navigate(location.href, false); });

  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f.matches || !f.matches('form[data-ajax]')) return;
    e.preventDefault();
    var fd = new FormData(f);
    if (e.submitter && e.submitter.name) fd.append(e.submitter.name, e.submitter.value);
    var btns = f.querySelectorAll('button, input[type=submit]'), i;
    for (i = 0; i < btns.length; i++) btns[i].disabled = true;
    request(f.getAttribute('action') || location.href, { method: 'POST', body: fd }).then(function (res) {
      if (res.json) { toast(res.json.message, res.json.ok ? 'success' : 'error'); return reload(false); }
      if (!res.doc || loggedOut(res.doc)) { location.href = res.url; return; }
      flashFrom(res.doc);
      if (!swap(res.doc)) location.href = res.url;
      else if (res.url !== location.href) history.replaceState({ ajax: 1 }, '', res.url);
    }).catch(function () {
      toast('Connection problem - please try again.', 'error');
      for (i = 0; i < btns.length; i++) btns[i].disabled = false;
    });
  });

  // live blocks (team board) refresh by themselves while the tab is visible and nobody is typing in them
  setInterval(function () {
    var r = document.querySelector('[data-region][data-refresh]');
    if (!r || document.hidden) return;
    var ae = document.activeElement;
    if (ae && r.contains(ae) && /^(INPUT|TEXTAREA|SELECT)$/.test(ae.tagName)) return;
    reload(true);
  }, 30000);

  // running timers ("out for 12:41") and the clock - display only, the real times always come from the server
  function tick() {
    var t = document.querySelectorAll('.timer[data-elapsed]'), i, el, s, h, m;
    for (i = 0; i < t.length; i++) {
      el = t[i]; if (!el._t0) el._t0 = Date.now() - parseInt(el.getAttribute('data-elapsed'), 10) * 1000;
      s = Math.max(0, Math.floor((Date.now() - el._t0) / 1000)); h = Math.floor(s / 3600); m = Math.floor(s % 3600 / 60);
      el.textContent = (h ? h + 'h ' : '') + ('0' + m).slice(-2) + 'm ' + ('0' + (s % 60)).slice(-2) + 's';
    }
    var c = document.getElementById('liveClock');
    if (c && c.getAttribute('data-secs')) {
      if (!c._t0) c._t0 = Date.now() - parseInt(c.getAttribute('data-secs'), 10) * 1000;
      var d = Math.floor((Date.now() - c._t0) / 1000) % 86400;
      c.textContent = ('0' + Math.floor(d / 3600)).slice(-2) + ':' + ('0' + Math.floor(d % 3600 / 60)).slice(-2);
    }
  }
  setInterval(tick, 1000); tick();

});
