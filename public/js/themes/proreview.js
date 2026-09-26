/* ProReview — site scripts (no dependencies). Source: template/assets/js/main.js + project behaviour */
(function () {
  'use strict';

  /* ---------- Mobile menu ---------- */
  var menuBtn = document.querySelector('[data-menu-toggle]');
  var menuPanel = document.getElementById('mobile-menu');
  if (menuBtn && menuPanel) {
    menuBtn.addEventListener('click', function () {
      var open = menuBtn.getAttribute('aria-expanded') === 'true';
      menuBtn.setAttribute('aria-expanded', String(!open));
      menuBtn.setAttribute('aria-label', open ? 'Open menu' : 'Close menu');
      menuPanel.hidden = open;
      menuBtn.querySelector('[data-icon-open]').classList.toggle('hidden', !open);
      menuBtn.querySelector('[data-icon-close]').classList.toggle('hidden', open);
    });
    // Close the panel when switching to the desktop layout
    window.matchMedia('(min-width: 1280px)').addEventListener('change', function (e) {
      if (e.matches && menuBtn.getAttribute('aria-expanded') === 'true') menuBtn.click();
    });
  }

  /* ---------- "More" dropdown ---------- */
  document.querySelectorAll('[data-dropdown]').forEach(function (wrap) {
    var btn = wrap.querySelector('button');
    var panel = wrap.querySelector('[data-dropdown-panel]');
    if (!btn || !panel) return;
    function set(open) {
      btn.setAttribute('aria-expanded', String(open));
      panel.hidden = !open;
    }
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      set(btn.getAttribute('aria-expanded') !== 'true');
    });
    document.addEventListener('click', function (e) { if (!wrap.contains(e.target)) set(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { set(false); } });
  });

  /* ---------- Home slider ---------- */
  document.querySelectorAll('[data-slider]').forEach(function (root) {
    var track = root.querySelector('[data-track]');
    var slides = Array.prototype.slice.call(track.children);
    var prev = root.querySelector('[data-prev]');
    var next = root.querySelector('[data-next]');
    var dotsWrap = root.querySelector('[data-dots]');
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var timer = null;
    var DELAY = 5000;

    function step() {
      if (slides.length < 2) return track.clientWidth;
      return slides[1].offsetLeft - slides[0].offsetLeft;
    }
    function perView() { return Math.max(1, Math.round(track.clientWidth / step())); }
    function pageCount() { return Math.max(1, slides.length - perView() + 1); }
    function current() { return Math.round(track.scrollLeft / step()); }
    function atEnd() { return track.scrollLeft + track.clientWidth >= track.scrollWidth - 4; }

    function goTo(i) {
      var n = pageCount();
      if (i < 0) i = n - 1;
      if (i > n - 1) i = 0;
      track.scrollTo({ left: i * step(), behavior: reduce ? 'auto' : 'smooth' });
    }
    function goNext() { atEnd() ? goTo(0) : goTo(current() + 1); }
    function goPrev() { track.scrollLeft <= 4 ? goTo(pageCount() - 1) : goTo(current() - 1); }

    function buildDots() {
      if (!dotsWrap) return;
      dotsWrap.innerHTML = '';
      var n = pageCount();
      dotsWrap.hidden = n < 2;
      for (var i = 0; i < n; i++) {
        var d = document.createElement('button');
        d.type = 'button';
        d.className = 'h-2.5 rounded-full bg-line transition-all duration-300 w-2.5 aria-[current=true]:w-7 aria-[current=true]:bg-ink';
        d.setAttribute('aria-label', 'Go to slide ' + (i + 1));
        (function (idx) { d.addEventListener('click', function () { goTo(idx); restart(); }); })(i);
        dotsWrap.appendChild(d);
      }
      syncDots();
    }
    function syncDots() {
      if (!dotsWrap) return;
      var idx = atEnd() ? pageCount() - 1 : current();
      Array.prototype.forEach.call(dotsWrap.children, function (d, i) {
        d.setAttribute('aria-current', i === idx ? 'true' : 'false');
      });
      slides.forEach(function (s, i) {
        var visible = i >= idx && i < idx + perView();
        s.setAttribute('aria-hidden', visible ? 'false' : 'true');
        s.tabIndex = visible ? 0 : -1;
      });
    }

    function stop() { if (timer) { clearInterval(timer); timer = null; } }
    function start() { if (!reduce && pageCount() > 1 && !timer) timer = setInterval(goNext, DELAY); }
    function restart() { stop(); start(); }

    if (prev) prev.addEventListener('click', function () { goPrev(); restart(); });
    if (next) next.addEventListener('click', function () { goNext(); restart(); });

    var ticking = false;
    track.addEventListener('scroll', function () {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(function () { syncDots(); ticking = false; });
    }, { passive: true });

    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    root.addEventListener('focusin', stop);
    root.addEventListener('focusout', start);
    track.addEventListener('touchstart', stop, { passive: true });
    track.addEventListener('touchend', function () { setTimeout(start, DELAY); }, { passive: true });
    root.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight') { goNext(); restart(); }
      if (e.key === 'ArrowLeft') { goPrev(); restart(); }
    });
    document.addEventListener('visibilitychange', function () { document.hidden ? stop() : start(); });

    var rt;
    window.addEventListener('resize', function () {
      clearTimeout(rt);
      rt = setTimeout(function () { buildDots(); goTo(Math.min(current(), pageCount() - 1)); restart(); }, 150);
    });

    buildDots();
    start();
  });

  /* ---------- Star rating (store page) ---------- */
  document.querySelectorAll('[data-rating]').forEach(function (group) {
    var stars = group.querySelectorAll('button');
    function paint(n) {
      stars.forEach(function (s, i) {
        s.classList.toggle('text-[#F5B301]', i < n);
        s.classList.toggle('text-[#C9C1B2]', i >= n);
        s.querySelector('svg').setAttribute('fill', i < n ? 'currentColor' : 'none');
      });
    }
    var chosen = 0;
    stars.forEach(function (s, i) {
      s.addEventListener('mouseenter', function () { paint(i + 1); });
      s.addEventListener('focus', function () { paint(i + 1); });
      s.addEventListener('click', function () {
        chosen = i + 1;
        stars.forEach(function (b, j) { b.setAttribute('aria-checked', String(j === i)); });
        paint(chosen);
        var msg = group.parentNode.querySelector('[data-rating-message]');
        if (msg) { msg.textContent = 'Thanks for rating! You gave ' + chosen + (chosen > 1 ? ' stars.' : ' star.'); msg.classList.remove('hidden'); }
      });
    });
    group.addEventListener('mouseleave', function () { paint(chosen); });
  });
})();

/* ---------- Project behaviour: store search, offer click, offer popup ---------- */
(function () {
  'use strict';

  /* Store search autocomplete – POST /search returns <li> items (frontend/home/search_result) */
  var csrf = document.querySelector('meta[name="csrf-token"]');
  csrf = csrf ? csrf.getAttribute('content') : '';

  document.querySelectorAll('[data-search]').forEach(function (form) {
    var input = form.querySelector('input[type="search"]');
    var box = form.querySelector('[data-search-results]');
    if (!input || !box) return;
    var timer = null;
    var lastKeyword = '';

    function close() { box.hidden = true; box.innerHTML = ''; }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var first = box.querySelector('a[href]');
      if (first) window.location.href = first.href;
    });

    input.addEventListener('input', function () {
      var keyword = input.value.trim();
      clearTimeout(timer);
      if (keyword.length < 3) { lastKeyword = ''; close(); return; }
      timer = setTimeout(function () {
        if (keyword === lastKeyword) return;
        lastKeyword = keyword;
        fetch((window.baseUrl || '') + '/search', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
          body: JSON.stringify({ keyword: keyword })
        })
          .then(function (res) { if (!res.ok) throw new Error(res.status); return res.text(); })
          .then(function (html) {
            if (input.value.trim() !== keyword) return;
            box.innerHTML = html;
            box.hidden = false;
          })
          .catch(function () {
            box.innerHTML = '<li class="px-4 py-3 text-sm text-muted">No results</li>';
            box.hidden = false;
          });
      }, 250);
    });

    input.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
    document.addEventListener('click', function (e) { if (!form.contains(e.target)) box.hidden = true; });
    input.addEventListener('focus', function () { if (box.innerHTML.trim()) box.hidden = false; });
  });

  /* Offer click: open the store page (with ?offer=id popup) in a new tab, send this tab to the affiliate link */
  document.addEventListener('click', function (e) {
    var target = e.target.closest('[data-affiliate_url][data-store_url]');
    if (!target) return;
    var affiliateUrl = target.getAttribute('data-affiliate_url');
    var storeUrl = target.getAttribute('data-store_url');
    if (!affiliateUrl || !storeUrl) return;
    e.preventDefault();
    window.open(storeUrl, '_blank');
    window.location.href = affiliateUrl;
  });

  /* Offer popup (store page ?offer=id) */
  var popup = document.querySelector('[data-offer-popup]');
  if (popup) {
    var lastFocus = document.activeElement;
    function closePopup() {
      popup.hidden = true;
      document.body.classList.remove('overflow-hidden');
      if (lastFocus && lastFocus.focus) lastFocus.focus();
    }
    popup.hidden = false;
    document.body.classList.add('overflow-hidden');
    var closeBtn = popup.querySelector('[data-popup-close]');
    if (closeBtn) { closeBtn.addEventListener('click', closePopup); closeBtn.focus(); }
    popup.addEventListener('click', function (e) { if (!e.target.closest('[data-popup-panel]')) closePopup(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !popup.hidden) closePopup(); });

    var copyBtn = popup.querySelector('[data-copy]');
    if (copyBtn) {
      copyBtn.addEventListener('click', function () {
        var code = copyBtn.getAttribute('data-copy');
        var label = copyBtn.textContent;
        var done = function () {
          copyBtn.textContent = 'Copied!';
          copyBtn.classList.add('bg-mint', 'text-mint-ink');
          setTimeout(function () {
            copyBtn.textContent = label;
            copyBtn.classList.remove('bg-mint', 'text-mint-ink');
          }, 2000);
        };
        if (navigator.clipboard && window.isSecureContext) {
          navigator.clipboard.writeText(code).then(done);
        } else {
          var t = document.createElement('textarea');
          t.value = code; document.body.appendChild(t); t.select();
          try { document.execCommand('copy'); done(); } catch (err) {}
          document.body.removeChild(t);
        }
      });
    }
  }
})();
