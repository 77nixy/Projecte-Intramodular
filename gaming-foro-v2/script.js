/* ═══════════════════════════════════════════════════════════════
   NEXUS//BOARD — script.js
   Interactive starfield canvas + full forum SPA logic
   Cosmos edition: cold starlight on void black
═══════════════════════════════════════════════════════════════ */

'use strict';

/* ══════════════════════════════════════════════════════════════
   SECTION 1 — GALAXY CANVAS (performance-optimised)
   Pre-rendered fog · simple star dots · shooting stars · mouse dust
══════════════════════════════════════════════════════════════ */

(function initGalaxy() {
  const canvas = document.getElementById('starfield');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  let CW = canvas.width  = window.innerWidth;
  let CH = canvas.height = window.innerHeight;

  /* ── Offscreen canvas for the static galaxy fog — drawn ONCE ── */
  let fogCanvas = document.createElement('canvas');
  let fogCtx    = fogCanvas.getContext('2d');

  function buildFog() {
    fogCanvas.width  = CW;
    fogCanvas.height = CH;
    fogCtx.clearRect(0, 0, CW, CH);

    /* Milky-way diagonal band — bright and visible */
    const band = fogCtx.createLinearGradient(0, CH * 0.1, CW, CH * 0.9);
    band.addColorStop(0,    'rgba(255,255,255,0)');
    band.addColorStop(0.30, 'rgba(255,255,255,0.10)');
    band.addColorStop(0.5,  'rgba(255,255,255,0.22)');
    band.addColorStop(0.70, 'rgba(255,255,255,0.10)');
    band.addColorStop(1,    'rgba(255,255,255,0)');
    fogCtx.fillStyle = band;
    fogCtx.fillRect(0, 0, CW, CH);

    /* Second narrower band for depth */
    const band2 = fogCtx.createLinearGradient(CW * 0.1, 0, CW * 0.9, CH);
    band2.addColorStop(0,   'rgba(255,255,255,0)');
    band2.addColorStop(0.4, 'rgba(255,255,255,0.06)');
    band2.addColorStop(0.6, 'rgba(255,255,255,0.06)');
    band2.addColorStop(1,   'rgba(255,255,255,0)');
    fogCtx.fillStyle = band2;
    fogCtx.fillRect(0, 0, CW, CH);

    /* Nebula clusters — radial gradients drawn once */
    const clusters = [
      { x: 0.20, y: 0.32, r: 0.30, a: 0.26 },
      { x: 0.55, y: 0.50, r: 0.36, a: 0.30 },
      { x: 0.80, y: 0.68, r: 0.26, a: 0.22 },
      { x: 0.07, y: 0.72, r: 0.20, a: 0.18 },
      { x: 0.88, y: 0.18, r: 0.18, a: 0.18 },
      { x: 0.42, y: 0.18, r: 0.24, a: 0.16 },
      { x: 0.68, y: 0.82, r: 0.28, a: 0.20 },
      { x: 0.33, y: 0.60, r: 0.18, a: 0.14 },
    ];
    for (const c of clusters) {
      const cx = c.x * CW, cy = c.y * CH, r = c.r * Math.min(CW, CH);
      const g = fogCtx.createRadialGradient(cx, cy, 0, cx, cy, r);
      g.addColorStop(0,   `rgba(255,255,255,${c.a})`);
      g.addColorStop(0.5, `rgba(255,255,255,${c.a * 0.3})`);
      g.addColorStop(1,   'rgba(0,0,0,0)');
      fogCtx.beginPath();
      fogCtx.arc(cx, cy, r, 0, Math.PI * 2);
      fogCtx.fillStyle = g;
      fogCtx.fill();
    }
  }

  /* ── Stars ── */
  let stars = [];

  function buildStars() {
    stars = [];
    /* Fewer stars = faster. 1200px for a typical 1920×1080 = ~3700 stars */
    const total = Math.min(Math.floor((CW * CH) / 2400), 1800);
    for (let i = 0; i < total; i++) {
      /* 40 % placed in the diagonal band */
      const inBand = Math.random() < 0.40;
      let x, y;
      if (inBand) {
        const t = Math.random();
        x = t * CW;
        y = CH * 0.15 + t * CH * 0.70 + (Math.random() - 0.5) * CH * 0.30;
        y = Math.max(0, Math.min(CH, y));
      } else {
        x = Math.random() * CW;
        y = Math.random() * CH;
      }

      /* Four brightness tiers */
      const roll = Math.random();
      let r, baseA, glow = false;
      if (roll < 0.58) {
        r = Math.random() * 0.7 + 0.4;  baseA = Math.random() * 0.4 + 0.38;
      } else if (roll < 0.84) {
        r = Math.random() * 0.9 + 0.9;  baseA = Math.random() * 0.25 + 0.60;
      } else if (roll < 0.97) {
        r = Math.random() * 1.2 + 1.6;  baseA = Math.random() * 0.15 + 0.80; glow = true;
      } else {
        r = Math.random() * 1.5 + 2.8;  baseA = 1.0; glow = true;
      }

      if (inBand) baseA = Math.min(1.0, baseA * 1.25);

      stars.push({
        x, y, r, baseA, glow,
        a:     baseA,
        da:    (Math.random() - 0.5) * 0.004,
        drift: (Math.random() - 0.5) * 0.012,
        tick:  Math.floor(Math.random() * 2), /* stagger twinkle updates */
      });
    }
  }

  function build() {
    buildFog();
    buildStars();
  }

  window.addEventListener('resize', () => {
    CW = canvas.width  = window.innerWidth;
    CH = canvas.height = window.innerHeight;
    build();
  });

  /* ── Shooting stars ── */
  let shooters = [];
  let nextShoot = 300; /* frames until next shooting star */

  function spawnShooter() {
    const fromTop = Math.random() < 0.6;
    let x, y, vx, vy;
    if (fromTop) {
      x = Math.random() * CW * 0.8 + CW * 0.1; y = -5;
      vx = (Math.random() - 0.4) * 8; vy = Math.random() * 5 + 4;
    } else {
      x = -5; y = Math.random() * CH * 0.55;
      vx = Math.random() * 7 + 4; vy = (Math.random() - 0.2) * 3 + 1;
    }
    const spd  = Math.sqrt(vx * vx + vy * vy);
    const tail = Math.random() * 120 + 80;
    shooters.push({ x, y, vx, vy, spd, tail, life: 1.0, decay: 0.012 + Math.random() * 0.01 });
    nextShoot = 200 + Math.floor(Math.random() * 250); /* ~3-7 s at 60fps */
  }

  /* ── Mouse / Touch stardust ── */
  let particles = [];
  let mouseX = -999, mouseY = -999, lastMX = -999, lastMY = -999;

  function spawnDust(cx, cy) {
    const dx = cx - lastMX, dy = cy - lastMY;
    const spd = Math.sqrt(dx * dx + dy * dy);
    if (spd < 1.5) return;

    const burst = Math.min(Math.floor(spd * 0.3) + 1, 5);
    const nx = dx / spd, ny = dy / spd;
    for (let i = 0; i < burst; i++) {
      const angle = Math.random() * Math.PI * 2;
      const speed = Math.random() * 3.0 + 0.6;
      particles.push({
        x:     cx + (Math.random() - 0.5) * 10,
        y:     cy + (Math.random() - 0.5) * 10,
        vx:    Math.cos(angle) * speed * 0.5 + nx * speed * 0.6,
        vy:    Math.sin(angle) * speed * 0.5 + ny * speed * 0.6,
        r:     Math.random() * 3.0 + 1.0,
        life:  1.0,
        decay: Math.random() * 0.018 + 0.008,
      });
    }
    if (particles.length > 80) particles.splice(0, particles.length - 80);
    lastMX = cx; lastMY = cy;
  }

  document.addEventListener('mousemove', e => {
    const px = mouseX, py = mouseY;
    lastMX = px; lastMY = py;
    mouseX = e.clientX; mouseY = e.clientY;
    spawnDust(mouseX, mouseY);
  });

  /* Touch support — doesn't block page scrolling */
  document.addEventListener('touchmove', e => {
    const t = e.touches[0];
    spawnDust(t.clientX, t.clientY);
    lastMX = t.clientX; lastMY = t.clientY;
  }, { passive: true });

  /* ── Draw loop ── */
  let frame = 0;

  function drawFrame() {
    frame++;

    /* 1. Black background */
    ctx.fillStyle = '#000000';
    ctx.fillRect(0, 0, CW, CH);

    /* 2. Static galaxy fog (one drawImage, nearly free) */
    ctx.drawImage(fogCanvas, 0, 0);

    /* 3. Stars — simple filled arcs + glow halo for bright ones */
    ctx.fillStyle = '#ffffff';
    const oddFrame = frame & 1;
    for (let i = 0; i < stars.length; i++) {
      const s = stars[i];
      /* Twinkle: each star updates every other frame, staggered */
      if (s.tick !== oddFrame) {
        s.a += s.da * 2; /* compensate for skipped frame */
        if (s.a > s.baseA * 1.5 || s.a < s.baseA * 0.3) s.da = -s.da;
      }
      s.x += s.drift;
      if (s.x > CW + 2) s.x = -2;
      if (s.x < -2)     s.x = CW + 2;

      const alpha = Math.max(0, Math.min(1, s.a));
      if (s.glow && alpha > 0.4) {
        /* Halo — only draw when visible */
        ctx.globalAlpha = alpha * 0.20;
        ctx.beginPath();
        ctx.arc(s.x, s.y, s.r * 4.0, 0, 6.283);
        ctx.fill();
        ctx.globalAlpha = alpha * 0.42;
        ctx.beginPath();
        ctx.arc(s.x, s.y, s.r * 2.0, 0, 6.283);
        ctx.fill();
      }
      ctx.globalAlpha = alpha;
      ctx.beginPath();
      ctx.arc(s.x, s.y, s.r, 0, 6.283);
      ctx.fill();
    }
    ctx.globalAlpha = 1;

    /* 4. Shooting stars */
    if (--nextShoot <= 0) spawnShooter();
    for (let i = shooters.length - 1; i >= 0; i--) {
      const s = shooters[i];
      s.life -= s.decay;
      if (s.life <= 0) { shooters.splice(i, 1); continue; }

      const nx = s.vx / s.spd, ny = s.vy / s.spd;
      const grd = ctx.createLinearGradient(s.x, s.y, s.x - nx * s.tail, s.y - ny * s.tail);
      grd.addColorStop(0,   `rgba(255,255,255,${s.life.toFixed(2)})`);
      grd.addColorStop(0.3, `rgba(220,220,220,${(s.life * 0.4).toFixed(2)})`);
      grd.addColorStop(1,   'rgba(255,255,255,0)');
      ctx.beginPath();
      ctx.moveTo(s.x, s.y);
      ctx.lineTo(s.x - nx * s.tail, s.y - ny * s.tail);
      ctx.lineWidth   = 2;
      ctx.strokeStyle = grd;
      ctx.stroke();
      /* Bright head */
      ctx.globalAlpha = s.life;
      ctx.fillStyle   = '#ffffff';
      ctx.beginPath();
      ctx.arc(s.x, s.y, 2, 0, 6.283);
      ctx.fill();
      ctx.globalAlpha = 1;

      s.x += s.vx; s.y += s.vy;
    }

    /* 5. Mouse stardust — simple circles with globalAlpha, NO radial gradient */
    for (let i = particles.length - 1; i >= 0; i--) {
      const p = particles[i];
      p.life -= p.decay;
      if (p.life <= 0) { particles.splice(i, 1); continue; }
      p.x  += p.vx;
      p.y  += p.vy;
      p.vy += 0.04;
      p.vx *= 0.97;

      ctx.globalAlpha = Math.min(1, p.life * 1.2);
      ctx.fillStyle   = '#ffffff';
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, 6.283);
      ctx.fill();
      /* Outer glow — one cheap larger circle at low alpha */
      ctx.globalAlpha = p.life * 0.25;
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r * 3.5, 0, 6.283);
      ctx.fill();
    }
    ctx.globalAlpha = 1;

    rafId = requestAnimationFrame(drawFrame);
  }

  /* Respect prefers-reduced-motion: skip continuous animation */
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    canvas.style.opacity = '0.4';
    build();
    /* Draw one static frame only */
    ctx.fillStyle = '#000000';
    ctx.fillRect(0, 0, CW, CH);
    ctx.drawImage(fogCanvas, 0, 0);
    return;
  }

  build();

  /* Track RAF id so we can pause when tab is hidden */
  let rafId;
  function drawLoop() { rafId = requestAnimationFrame(drawFrame); }

  /* Pause animation when tab is hidden — saves battery on mobile */
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      cancelAnimationFrame(rafId);
    } else {
      drawLoop();
    }
  });

  drawLoop();
})();


/* ══════════════════════════════════════════════════════════════
   SECTION 2 — FORUM APPLICATION
══════════════════════════════════════════════════════════════ */

(function () {

  const API_URL = 'api.php';

  const CAT_LABELS = {
    fps: 'FPS', moba: 'MOBA', hardware: 'Hardware',
    noticias: 'Noticias', estrategia: 'Estrategia'
  };

  /* ── Avatar palette — black & white gradient pairs ── */
  const AVATAR_PALETTE = [
    ['#ffffff','#888888'],
    ['#d0d0d0','#404040'],
    ['#e8e8e8','#606060'],
    ['#b0b0b0','#282828'],
    ['#f0f0f0','#707070'],
    ['#c0c0c0','#303030'],
    ['#a8a8a8','#181818'],
    ['#dcdcdc','#505050'],
    ['#e0e0e0','#383838'],
    ['#c8c8c8','#484848']
  ];

  /* ═══════════════════════════════════════════════════════════
     HELPERS
  ═══════════════════════════════════════════════════════════ */

  function avatarStyle(str) {
    let h = 0;
    for (let i = 0; i < (str || '').length; i++) h = str.charCodeAt(i) + ((h << 5) - h);
    const [c1, c2] = AVATAR_PALETTE[Math.abs(h) % AVATAR_PALETTE.length];
    return `background:linear-gradient(135deg,${c1},${c2})`;
  }

  function avatarInitial(str) {
    return (str || '?').charAt(0).toUpperCase();
  }

  function esc(str) {
    return String(str ?? '')
      .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
  }

  function fmtDate(ds) {
    return new Date(ds).toLocaleDateString('es-ES', {
      day:'2-digit', month:'short', year:'numeric',
      hour:'2-digit', minute:'2-digit'
    });
  }

  function fmtNumber(n) {
    n = Number(n) || 0;
    return n >= 1000 ? (n / 1000).toFixed(1) + 'k' : String(n);
  }

  function catClass(cat) {
    return ['fps','moba','hardware','noticias','estrategia'].includes(cat) ? `cat-${cat}` : 'cat-default';
  }

  /* ═══════════════════════════════════════════════════════════
     TOAST NOTIFICATIONS
  ═══════════════════════════════════════════════════════════ */

  function toast(msg, type = 'info') {
    const wrap = document.querySelector('[data-toast-wrap]');
    if (!wrap) return;
    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    el.textContent = msg;
    wrap.appendChild(el);
    setTimeout(() => {
      el.classList.add('toast-exit');
      setTimeout(() => el.remove(), 380);
    }, 3400);
  }

  /* ═══════════════════════════════════════════════════════════
     API
  ═══════════════════════════════════════════════════════════ */

  async function api(action, method = 'GET', body = null, params = {}) {
    const qs   = new URLSearchParams({ action, ...params });
    const opts = { method, headers: {} };
    if (method === 'POST' && body) {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(body);
    }
    try {
      const res = await fetch(`${API_URL}?${qs}`, opts);
      return await res.json();
    } catch {
      return null;
    }
  }

  /* ═══════════════════════════════════════════════════════════
     CURRENT USER
  ═══════════════════════════════════════════════════════════ */

  function getUser() {
    const u = window.NEXUS_CURRENT_USER;
    if (!u) return null;
    return { ...u, name: u.nombre || 'Usuario', favoriteGame: u.favorite_game || 'Gaming' };
  }

  /* ═══════════════════════════════════════════════════════════
     SCROLL REVEAL
  ═══════════════════════════════════════════════════════════ */

  function setupScrollReveal() {
    if (!('IntersectionObserver' in window)) {
      document.querySelectorAll('.reveal-on-scroll').forEach(el => el.classList.add('revealed'));
      return;
    }
    const io = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('revealed');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.08 });
    document.querySelectorAll('.reveal-on-scroll').forEach(el => io.observe(el));
  }

  /* ═══════════════════════════════════════════════════════════
     MOBILE NAV
  ═══════════════════════════════════════════════════════════ */

  function setupMobileNav() {
    const btn = document.querySelector('[data-hamburger]');
    const nav = document.querySelector('[data-mobile-nav]');
    if (!btn || !nav) return;
    btn.addEventListener('click', () => {
      const open = nav.classList.toggle('is-open');
      btn.classList.toggle('is-open', open);
    });
    nav.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
      nav.classList.remove('is-open');
      btn.classList.remove('is-open');
    }));
  }

  /* ═══════════════════════════════════════════════════════════
     AUTH SLOT (header right side)
  ═══════════════════════════════════════════════════════════ */

  function renderAuthSlot() {
    const slot = document.querySelector('[data-auth-slot]');
    if (!slot) return;
    const user = getUser();

    if (!user) {
      slot.innerHTML = `<a class="btn-nav-login" href="login.php">Iniciar sesión</a>`;
      return;
    }

    const roleLabel = user.role === 'admin' ? 'Admin' : 'Miembro';
    slot.innerHTML = `
      <div class="user-chip">
        <span class="chip-ava" style="${avatarStyle(user.username || user.name)}">${avatarInitial(user.username || user.name)}</span>
        <div>
          <span class="chip-name">${esc(user.username || user.name)}</span>
          <span class="chip-role">${roleLabel}</span>
        </div>
        <button class="btn-chip-out" type="button" data-logout>Salir</button>
      </div>
    `;
    slot.querySelector('[data-logout]')?.addEventListener('click', () => {
      window.location.href = 'logout.php';
    });
  }

  /* ═══════════════════════════════════════════════════════════
     STATS BAR
  ═══════════════════════════════════════════════════════════ */

  async function updateStats() {
    const stats = await api('get_stats');
    if (!stats) return;

    const set = (sel, val) => {
      const el = document.querySelector(sel);
      if (el) el.textContent = fmtNumber(val || 0);
    };

    set('[data-stat-posts]',    stats.posts);
    set('[data-stat-users]',    stats.users);
    set('[data-stat-online]',   Math.max(1, Math.min(stats.users || 0, (stats.posts || 0) + 2)));
    set('[data-approved-count]',stats.posts);
    set('[data-pending-count]', stats.pending);
    set('[data-likes-count]',   stats.likes);
  }

  /* ═══════════════════════════════════════════════════════════
     SESSION PANEL (sidebar)
  ═══════════════════════════════════════════════════════════ */

  function renderSessionPanel() {
    const panel = document.querySelector('[data-session-panel]');
    if (!panel) return;
    const user = getUser();

    if (!user) {
      panel.innerHTML = `
        <p style="font-size:0.84rem;color:var(--white-dim);margin:0 0 14px;line-height:1.65;">
          Inicia sesión para publicar temas, dar likes y acceder a tu perfil.
        </p>
        <a class="btn btn-primary btn-sm" href="login.php" style="width:100%;justify-content:center;margin-bottom:8px;">Iniciar sesión</a>
        <a class="btn btn-ghost btn-sm" href="register.php" style="width:100%;justify-content:center;">Crear cuenta gratis</a>
      `;
      return;
    }

    panel.innerHTML = `
      <div class="session-user-row">
        <span class="avatar ava-md" style="${avatarStyle(user.username || user.name)}">${avatarInitial(user.username || user.name)}</span>
        <div class="session-user-info">
          <strong>${esc(user.name)}</strong>
          <span>@${esc(user.username || 'user')} · ${esc(user.favoriteGame)}</span>
        </div>
      </div>
      ${user.bio ? `<p class="session-bio">${esc(user.bio)}</p>` : ''}
    `;
  }

  /* ═══════════════════════════════════════════════════════════
     POSTS FEED
  ═══════════════════════════════════════════════════════════ */

  async function renderPosts() {
    const list  = document.querySelector('[data-post-list]');
    const empty = document.querySelector('[data-empty-state]');
    if (!list) return;

    const user     = getUser();
    const category = document.querySelector('[data-category-filters] .cat-btn.is-active')?.dataset.category || 'all';
    const search   = (document.querySelector('[data-search-input]')?.value || '').trim();
    const sort     = document.querySelector('[data-sort-select]')?.value || 'recent';

    const params = { sort };
    if (category !== 'all') params.category = category;
    if (search) params.search = search;

    const result = await api('get_posts', 'GET', null, params);
    if (!result) return;
    const posts = Array.isArray(result) ? result : (result.data ?? []);

    if (!posts.length) {
      list.innerHTML = '';
      empty?.classList.remove('hidden');
      return;
    }

    empty?.classList.add('hidden');

    const isAuthor = p => user && Number(user.id) === Number(p.author_id);

    list.innerHTML = posts.map(p => `
      <article class="post-card">
        <div class="post-vote">
          <button class="vote-btn" type="button" data-like-post="${p.id}" title="Me gusta esta publicación">▲</button>
          <span class="vote-count">${fmtNumber(p.likes || 0)}</span>
        </div>
        <div class="post-body">
          <div class="post-top">
            <span class="cat-badge ${catClass(p.category)}">${esc(CAT_LABELS[p.category] || p.category)}</span>
            ${isAuthor(p) ? '<span class="badge-mine">Tuyo</span>' : ''}
          </div>
          <h3 class="post-title">${esc(p.title)}</h3>
          <p class="post-excerpt">${esc(p.content)}</p>
          <div class="post-footer">
            <div class="post-author">
              <span class="avatar ava-sm" style="${avatarStyle(p.username)}">${avatarInitial(p.username)}</span>
              <span class="post-author-name">@${esc(p.username || 'anon')}</span>
            </div>
            <span class="post-game">🎮 ${esc(p.favorite_game || 'Gaming')}</span>
            <div class="post-actions">
              ${user
                ? `<button class="btn btn-ghost btn-sm" type="button" data-open-composer>Responder</button>`
                : `<a class="btn btn-ghost btn-sm" href="login.php">Responder</a>`}
            </div>
            <span class="post-date">${fmtDate(p.created_at)}</span>
          </div>
        </div>
      </article>
    `).join('');

    list.querySelectorAll('[data-like-post]').forEach(btn =>
      btn.addEventListener('click', () => likePost(btn.dataset.likePost, btn))
    );
    list.querySelectorAll('[data-open-composer]').forEach(btn =>
      btn.addEventListener('click', () => toggleComposer(true))
    );
  }

  async function likePost(id, btn) {
    if (!getUser()) { toast('Inicia sesión para dar likes', 'info'); return; }
    if (btn) {
      btn.style.color        = 'var(--accent)';
      btn.style.borderColor  = 'var(--accent)';
      btn.style.textShadow   = '0 0 8px rgba(200,216,240,0.5)';
    }
    await api('like_post', 'POST', { post_id: parseInt(id) });
    renderPosts();
    updateStats();
  }

  /* ═══════════════════════════════════════════════════════════
     POST COMPOSER
  ═══════════════════════════════════════════════════════════ */

  function toggleComposer(forceOpen) {
    const panel = document.querySelector('[data-composer-panel]');
    if (!panel) return;
    if (!getUser()) { window.location.href = 'login.php'; return; }
    if (forceOpen === true) panel.classList.remove('hidden');
    else panel.classList.toggle('hidden');
  }

  function setNotice(el, msg, type) {
    if (!el) return;
    el.textContent = msg;
    el.className   = `notice-inline is-${type}`;
  }

  function setupComposer() {
    document.querySelector('[data-open-composer]')?.addEventListener('click', () => toggleComposer());
    document.querySelector('[data-close-composer]')?.addEventListener('click', () => {
      document.querySelector('[data-composer-panel]')?.classList.add('hidden');
    });

    const form = document.querySelector('[data-post-form]');
    if (!form) return;

    form.addEventListener('submit', async e => {
      e.preventDefault();
      const notice = form.querySelector('[data-post-notice]');
      const user   = getUser();
      if (!user) { window.location.href = 'login.php'; return; }

      const fd = new FormData(form);
      const payload = {
        title:    String(fd.get('title')   || '').trim(),
        content:  String(fd.get('content') || '').trim(),
        category: String(fd.get('category')|| 'fps')
      };

      if (payload.title.length < 6) {
        setNotice(notice, 'El título debe tener al menos 6 caracteres.', 'error');
        return;
      }
      if (payload.content.length < 12) {
        setNotice(notice, 'El contenido debe tener al menos 12 caracteres.', 'error');
        return;
      }

      const submitBtn = form.querySelector('[type="submit"]');
      submitBtn.disabled    = true;
      submitBtn.textContent = 'Publicando…';

      const res = await api('add_post', 'POST', payload);
      submitBtn.disabled    = false;
      submitBtn.textContent = 'Publicar tema';

      if (res?.success) {
        form.reset();
        const msg = user.role === 'admin'
          ? '✦ Tema publicado correctamente.'
          : '⏳ Tema enviado — pendiente de aprobación.';
        setNotice(notice, msg, 'success');
        toast(user.role === 'admin' ? 'Tema publicado' : 'Enviado — pendiente de aprobación', 'success');
        renderPosts();
        updateStats();
        setTimeout(() => { notice.className = 'notice-inline'; notice.textContent = ''; }, 5000);
      } else {
        setNotice(notice, res?.error || 'No se pudo publicar el tema.', 'error');
      }
    });
  }

  /* ═══════════════════════════════════════════════════════════
     CATEGORY FILTERS
  ═══════════════════════════════════════════════════════════ */

  function setupFilters() {
    document.querySelectorAll('[data-category-filters] .cat-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('[data-category-filters] .cat-btn').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
        renderPosts();
      });
    });
    document.querySelector('[data-search-input]')?.addEventListener('input', renderPosts);
    document.querySelector('[data-sort-select]')?.addEventListener('change', renderPosts);
  }

  /* ═══════════════════════════════════════════════════════════
     CONTACT FORM
  ═══════════════════════════════════════════════════════════ */

  function setupContactForm() {
    const form = document.querySelector('[data-contact-form]');
    if (!form) return;

    form.addEventListener('submit', async e => {
      e.preventDefault();
      const notice = form.querySelector('[data-contact-notice]');
      const fd     = new FormData(form);
      const name    = String(fd.get('name')    || '').trim();
      const email   = String(fd.get('email')   || '').trim();
      const subject = String(fd.get('subject') || '').trim();
      const message = String(fd.get('message') || '').trim();

      if (!name || !email || !subject || !message) {
        setNotice(notice, 'Por favor, completa todos los campos.', 'error');
        return;
      }
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        setNotice(notice, 'Introduce un email válido.', 'error');
        return;
      }

      const submitBtn = form.querySelector('[type="submit"]');
      submitBtn.disabled    = true;
      submitBtn.textContent = 'Enviando…';

      try {
        const res  = await fetch('process_contact.php', {
          method:  'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body:    new URLSearchParams({ name, email, subject, message })
        });
        const data = await res.json();
        submitBtn.disabled    = false;
        submitBtn.textContent = 'Enviar mensaje';

        if (data.success) {
          form.reset();
          setNotice(notice, '✦ ¡Mensaje recibido! Te responderemos pronto.', 'success');
          toast('Mensaje enviado — guardado en base de datos', 'success');
          setTimeout(() => { notice.className = 'notice-inline'; notice.textContent = ''; }, 6000);
        } else {
          setNotice(notice, data.error || 'No se pudo enviar el mensaje.', 'error');
        }
      } catch {
        submitBtn.disabled    = false;
        submitBtn.textContent = 'Enviar mensaje';
        setNotice(notice, 'Error de conexión. Inténtalo de nuevo.', 'error');
      }
    });
  }

  /* ═══════════════════════════════════════════════════════════
     MEMBERS SECTION (public list on index page)
  ═══════════════════════════════════════════════════════════ */

  async function renderMembersList() {
    const wrap = document.querySelector('[data-members-list]');
    if (!wrap) return;

    const users = await api('get_users');
    if (!Array.isArray(users) || !users.length) return;

    wrap.innerHTML = users.slice(0, 12).map(u => `
      <div class="member-card reveal-on-scroll">
        <span class="avatar ava-lg" style="${avatarStyle(u.username)}">${avatarInitial(u.username)}</span>
        <div class="member-card-info">
          <strong class="member-card-name">${esc(u.username)}</strong>
          <span class="member-card-game">🎮 ${esc(u.favorite_game || 'Gaming')}</span>
          ${u.bio ? `<p class="member-card-bio">${esc(u.bio)}</p>` : ''}
        </div>
        <span class="badge ${u.role === 'admin' ? 'badge-admin' : 'badge-member'}">${u.role === 'admin' ? 'Admin' : 'Miembro'}</span>
      </div>
    `).join('');

    setupScrollReveal();
  }

  async function renderAdminPosts() {
    const c   = document.querySelector('[data-admin-posts]');
    const cnt = document.querySelector('[data-posts-count]');
    if (!c) return;

    const result = await api('get_all_posts');
    const posts  = Array.isArray(result) ? result : (result?.data ?? []);
    if (!posts.length) {
      c.innerHTML = '<div style="padding:20px 18px;color:var(--white-faint);font-size:0.86rem;font-family:var(--font-ui);">No hay temas todavía.</div>';
      return;
    }

    if (cnt) cnt.textContent = `${posts.length} temas`;

    c.innerHTML = posts.map(p => `
      <div class="admin-row">
        <div class="admin-row-main">
          <strong>${esc(p.title)}</strong>
          <div class="admin-meta">
            <span>@${esc(p.username)}</span>
            <span class="cat-badge ${catClass(p.category)}" style="font-size:0.66rem;padding:2px 7px;">${esc(CAT_LABELS[p.category] || p.category)}</span>
            <span class="badge ${p.approved == 1 ? 'badge-ok' : 'badge-warn'}">${p.approved == 1 ? 'Aprobado' : 'Pendiente'}</span>
            <span style="font-size:0.72rem;color:var(--white-ghost);">${fmtDate(p.created_at)}</span>
          </div>
        </div>
        <div class="admin-actions">
          ${p.approved == 0
            ? `<button class="btn btn-ghost btn-sm" data-approve-post="${p.id}">Aprobar</button>`
            : `<button class="btn btn-ghost btn-sm" data-reject-post="${p.id}">Rechazar</button>`
          }
          <button class="btn btn-danger btn-sm" data-delete-post="${p.id}">Eliminar</button>
        </div>
      </div>
    `).join('');

    c.querySelectorAll('[data-approve-post]').forEach(btn =>
      btn.addEventListener('click', async () => {
        await api('approve_post', 'POST', { post_id: parseInt(btn.dataset.approvePost), approved: 1 });
        toast('Tema aprobado — visible en el foro', 'success');
        renderAdminPosts(); updateAdminMetrics();
      })
    );
    c.querySelectorAll('[data-reject-post]').forEach(btn =>
      btn.addEventListener('click', async () => {
        await api('approve_post', 'POST', { post_id: parseInt(btn.dataset.rejectPost), approved: 0 });
        toast('Tema rechazado', 'info');
        renderAdminPosts(); updateAdminMetrics();
      })
    );
    c.querySelectorAll('[data-delete-post]').forEach(btn =>
      btn.addEventListener('click', async () => {
        if (!confirm('¿Eliminar este tema definitivamente?')) return;
        await api('delete_post', 'POST', { post_id: parseInt(btn.dataset.deletePost) });
        toast('Tema eliminado', 'error');
        renderAdminPosts(); updateAdminMetrics();
      })
    );
  }

  /* ═══════════════════════════════════════════════════════════
     LEADERBOARD — extended with API leaderboard endpoint
  ═══════════════════════════════════════════════════════════ */

  async function renderLeaderboardExtended() {
    const list = document.querySelector('[data-user-list]');
    if (!list) return;

    const data = await api('get_leaderboard', 'GET', null, { limit: 5 });
    const rows = Array.isArray(data) ? data : [];

    if (!rows.length) {
      list.innerHTML = '<p style="font-size:0.82rem;color:var(--white-faint);padding:4px 0;">Sin datos aún.</p>';
      return;
    }

    const rankClass = i => i === 0 ? 'r1' : i === 1 ? 'r2' : i === 2 ? 'r3' : '';

    list.innerHTML = rows.map((u, i) => `
      <div class="leader-row">
        <span class="leader-num ${rankClass(i)}">${i + 1}</span>
        <span class="avatar ava-sm" style="${avatarStyle(u.username)}">${avatarInitial(u.username)}</span>
        <div class="leader-info">
          <div class="leader-name">${esc(u.username)}</div>
          <div class="leader-sub">${u.post_count} tema${u.post_count !== 1 ? 's' : ''}</div>
        </div>
        <span class="leader-badge">♥ ${fmtNumber(u.total_likes)}</span>
      </div>
    `).join('');
  }

  /* ═══════════════════════════════════════════════════════════
     SEARCH — debounced live search
  ═══════════════════════════════════════════════════════════ */

  let searchTimer = null;

  function debounce(fn, delay) {
    return function (...args) {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => fn.apply(this, args), delay);
    };
  }

  function setupSearchDebounce() {
    const input = document.querySelector('[data-search-input]');
    if (!input) return;
    const debouncedRender = debounce(renderPosts, 380);
    input.removeEventListener('input', renderPosts);
    input.addEventListener('input', debouncedRender);

    input.addEventListener('keydown', e => {
      if (e.key === 'Escape') {
        input.value = '';
        renderPosts();
        input.blur();
      }
    });
  }

  /* ═══════════════════════════════════════════════════════════
     CHAR COUNTER — textarea live counter
  ═══════════════════════════════════════════════════════════ */

  function setupCharCounters() {
    document.querySelectorAll('[data-char-counter]').forEach(wrap => {
      const textarea = wrap.querySelector('textarea, input');
      const counter  = wrap.querySelector('[data-char-count-display]');
      if (!textarea || !counter) return;
      const max = parseInt(textarea.getAttribute('maxlength') || '0', 10);
      const update = () => {
        const len = textarea.value.length;
        counter.textContent = max ? `${len} / ${max}` : String(len);
        counter.classList.toggle('is-near-limit', max > 0 && len >= max * 0.85);
        counter.classList.toggle('is-over-limit',  max > 0 && len >= max);
      };
      textarea.addEventListener('input', update);
      update();
    });
  }

  /* ═══════════════════════════════════════════════════════════
     BACK TO TOP BUTTON
  ═══════════════════════════════════════════════════════════ */

  function setupBackToTop() {
    const btn = document.querySelector('[data-back-to-top]');
    if (!btn) return;
    const toggleVisibility = () => {
      btn.classList.toggle('is-visible', window.scrollY > 400);
    };
    window.addEventListener('scroll', toggleVisibility, { passive: true });
    btn.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* ═══════════════════════════════════════════════════════════
     STICKY HEADER — hide on scroll down, show on scroll up
  ═══════════════════════════════════════════════════════════ */

  function setupStickyHeader() {
    const header = document.querySelector('.site-header');
    if (!header) return;
    let lastScroll = 0;
    let ticking    = false;

    window.addEventListener('scroll', () => {
      if (!ticking) {
        requestAnimationFrame(() => {
          const cur = window.scrollY;
          if (cur > lastScroll && cur > 120) {
            header.classList.add('header-hidden');
          } else {
            header.classList.remove('header-hidden');
          }
          lastScroll = cur;
          ticking    = false;
        });
        ticking = true;
      }
    }, { passive: true });
  }

  /* ═══════════════════════════════════════════════════════════
     SMOOTH SCROLL — anchor links (#section)
  ═══════════════════════════════════════════════════════════ */

  function setupSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(link => {
      link.addEventListener('click', e => {
        const id = link.getAttribute('href').slice(1);
        if (!id) return;
        const target = document.getElementById(id);
        if (!target) return;
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        history.replaceState(null, '', `#${id}`);
      });
    });
  }

  /* ═══════════════════════════════════════════════════════════
     KEYBOARD SHORTCUTS
  ═══════════════════════════════════════════════════════════ */

  function setupKeyboardShortcuts() {
    document.addEventListener('keydown', e => {
      const tag = document.activeElement?.tagName?.toLowerCase();
      const inInput = ['input', 'textarea', 'select'].includes(tag);

      /* Ctrl/Cmd + K → focus search */
      if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchEl = document.querySelector('[data-search-input]');
        if (searchEl) { searchEl.focus(); searchEl.select(); }
        return;
      }

      /* Escape → close any open panels / composer */
      if (e.key === 'Escape' && !inInput) {
        document.querySelector('[data-composer-panel]:not(.hidden)')?.classList.add('hidden');
        document.querySelector('.modal-overlay.is-open')?.classList.remove('is-open');
        return;
      }

      /* N → open composer (not in input) */
      if (e.key === 'n' && !inInput && !e.ctrlKey && !e.metaKey) {
        const page = document.body.dataset.page;
        if (page === 'home') {
          toggleComposer();
        }
        return;
      }

      /* T → back to top */
      if (e.key === 't' && !inInput && !e.ctrlKey && !e.metaKey) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
      }

      /* F → focus search */
      if (e.key === 'f' && !inInput && !e.ctrlKey && !e.metaKey) {
        const searchEl = document.querySelector('[data-search-input]');
        if (searchEl) { searchEl.focus(); e.preventDefault(); }
        return;
      }
    });
  }

  /* ═══════════════════════════════════════════════════════════
     LOCAL STORAGE PREFERENCES
  ═══════════════════════════════════════════════════════════ */

  const PREFS_KEY = 'nexus_prefs_v1';

  function loadPrefs() {
    try {
      return JSON.parse(localStorage.getItem(PREFS_KEY) || '{}');
    } catch {
      return {};
    }
  }

  function savePrefs(delta) {
    try {
      const prefs = { ...loadPrefs(), ...delta };
      localStorage.setItem(PREFS_KEY, JSON.stringify(prefs));
    } catch { /* storage unavailable */ }
  }

  function applyPrefs() {
    const prefs = loadPrefs();

    /* Restore last selected category */
    if (prefs.category) {
      const btn = document.querySelector(`[data-category-filters] [data-category="${prefs.category}"]`);
      if (btn) {
        document.querySelectorAll('[data-category-filters] .cat-btn').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
      }
    }

    /* Restore last selected sort */
    const sortEl = document.querySelector('[data-sort-select]');
    if (sortEl && prefs.sort) sortEl.value = prefs.sort;
  }

  function persistFilterPrefs() {
    document.querySelectorAll('[data-category-filters] .cat-btn').forEach(btn => {
      btn.addEventListener('click', () => savePrefs({ category: btn.dataset.category }));
    });
    const sortEl = document.querySelector('[data-sort-select]');
    if (sortEl) sortEl.addEventListener('change', () => savePrefs({ sort: sortEl.value }));
  }

  /* ═══════════════════════════════════════════════════════════
     ADMIN — delete contact messages
  ═══════════════════════════════════════════════════════════ */

  async function renderAdminContactsExtended() {
    const c   = document.querySelector('[data-admin-contacts-list]');
    const cnt = document.querySelector('[data-contacts-count]');
    if (!c) return;

    const result = await api('get_contacts');
    const contacts = Array.isArray(result) ? result : (result?.data ?? []);

    if (!contacts.length) {
      c.innerHTML = '<div style="padding:20px 18px;color:var(--white-faint);font-size:0.86rem;font-family:var(--font-ui);">No hay mensajes de contacto.</div>';
      return;
    }

    if (cnt) cnt.textContent = `${contacts.length} mensajes`;

    c.innerHTML = contacts.map(ct => `
      <div class="admin-row" data-contact-row="${ct.id}">
        <div class="admin-row-main">
          <strong>${esc(ct.subject)}</strong>
          <div class="admin-meta">
            <span>${esc(ct.name)}</span>
            <span style="color:var(--white-ghost);font-size:0.72rem;">${esc(ct.email)}</span>
            ${ct.read == 0 ? '<span class="badge badge-unread">No leído</span>' : '<span class="badge badge-member">Leído</span>'}
            <span style="font-size:0.72rem;color:var(--white-ghost);">${fmtDate(ct.created_at)}</span>
          </div>
          <p style="margin:8px 0 0;font-size:0.82rem;color:var(--white-dim);line-height:1.6;font-family:var(--font-ui);font-weight:300;">${esc(ct.message)}</p>
        </div>
        <div class="admin-actions">
          ${ct.read == 0
            ? `<button class="btn btn-ghost btn-sm" data-read-contact="${ct.id}">Marcar leído</button>`
            : ''}
          <button class="btn btn-danger btn-sm" data-delete-contact="${ct.id}">Eliminar</button>
        </div>
      </div>
    `).join('');

    c.querySelectorAll('[data-read-contact]').forEach(btn =>
      btn.addEventListener('click', async () => {
        await api('mark_contact_read', 'POST', { contact_id: parseInt(btn.dataset.readContact) });
        toast('Mensaje marcado como leído', 'info');
        renderAdminContactsExtended();
        updateAdminMetrics();
      })
    );

    c.querySelectorAll('[data-delete-contact]').forEach(btn =>
      btn.addEventListener('click', async () => {
        if (!confirm('¿Eliminar este mensaje de contacto definitivamente?')) return;
        const row = c.querySelector(`[data-contact-row="${btn.dataset.deleteContact}"]`);
        if (row) { row.style.opacity = '0.4'; row.style.pointerEvents = 'none'; }
        const res = await api('delete_contact', 'POST', { contact_id: parseInt(btn.dataset.deleteContact) });
        if (res?.success) {
          toast('Mensaje eliminado', 'error');
          renderAdminContactsExtended();
          updateAdminMetrics();
        } else {
          if (row) { row.style.opacity = ''; row.style.pointerEvents = ''; }
          toast('Error al eliminar', 'error');
        }
      })
    );
  }

  /* ═══════════════════════════════════════════════════════════
     ADMIN — user role management
  ═══════════════════════════════════════════════════════════ */

  async function renderAdminUsersExtended() {
    const c   = document.querySelector('[data-admin-users-list]');
    const cnt = document.querySelector('[data-users-count]');
    if (!c) return;

    const users = await api('get_users');
    if (!Array.isArray(users) || !users.length) {
      c.innerHTML = '<div style="padding:20px 18px;color:var(--white-faint);font-size:0.86rem;font-family:var(--font-ui);">Sin usuarios.</div>';
      return;
    }

    if (cnt) cnt.textContent = `${users.length} usuarios`;
    const me = getUser();

    c.innerHTML = users.map(u => {
      const isSelf  = me && String(me.id) === String(u.id);
      const isAdmin = u.role === 'admin';
      return `
        <div class="admin-row" data-user-row="${u.id}">
          <span class="avatar ava-sm" style="${avatarStyle(u.username)}">${avatarInitial(u.username)}</span>
          <div class="admin-row-main">
            <strong>${esc(u.username)}</strong>
            <div class="admin-meta">
              <span style="color:var(--white-dim);">${esc(u.nombre || u.username)}</span>
              <span style="color:var(--white-ghost);font-size:0.72rem;">${esc(u.email || '')}</span>
              <span class="badge ${isAdmin ? 'badge-admin' : 'badge-member'}">${esc(u.role)}</span>
              <span style="font-size:0.72rem;color:var(--white-faint);">🎮 ${esc(u.favorite_game || 'Gaming')}</span>
              <span style="font-size:0.72rem;color:var(--white-faint);">♥ ${fmtNumber(u.total_likes || 0)} likes · ${u.post_count || 0} posts</span>
            </div>
          </div>
          ${!isSelf ? `
          <div class="admin-actions">
            ${!isAdmin
              ? `<button class="btn btn-ghost btn-sm" data-promote-user="${u.id}">Promover</button>`
              : `<button class="btn btn-ghost btn-sm" data-demote-user="${u.id}">Degradar</button>`}
            <button class="btn btn-danger btn-sm" data-delete-user="${u.id}">Eliminar</button>
          </div>` : '<span style="font-size:0.75rem;color:var(--white-ghost);padding-right:8px;">(Tú)</span>'}
        </div>
      `;
    }).join('');

    c.querySelectorAll('[data-promote-user]').forEach(btn =>
      btn.addEventListener('click', async () => {
        if (!confirm('¿Promover a este usuario a administrador?')) return;
        const res = await api('update_user_role', 'POST', { user_id: parseInt(btn.dataset.promoteUser), role: 'admin' });
        if (res?.success) { toast('Usuario promovido a admin', 'success'); renderAdminUsersExtended(); }
        else toast(res?.error || 'Error al promover', 'error');
      })
    );

    c.querySelectorAll('[data-demote-user]').forEach(btn =>
      btn.addEventListener('click', async () => {
        if (!confirm('¿Degradar a este administrador a miembro?')) return;
        const res = await api('update_user_role', 'POST', { user_id: parseInt(btn.dataset.demoteUser), role: 'member' });
        if (res?.success) { toast('Rol actualizado a miembro', 'info'); renderAdminUsersExtended(); }
        else toast(res?.error || 'Error al degradar', 'error');
      })
    );

    c.querySelectorAll('[data-delete-user]').forEach(btn =>
      btn.addEventListener('click', async () => {
        if (!confirm('¿Eliminar este usuario y todos sus posts? Esta acción no se puede deshacer.')) return;
        const row = c.querySelector(`[data-user-row="${btn.dataset.deleteUser}"]`);
        if (row) { row.style.opacity = '0.4'; row.style.pointerEvents = 'none'; }
        const res = await api('delete_user', 'POST', { user_id: parseInt(btn.dataset.deleteUser) });
        if (res?.success) {
          toast('Usuario eliminado', 'error');
          renderAdminUsersExtended();
          updateAdminMetrics();
        } else {
          if (row) { row.style.opacity = ''; row.style.pointerEvents = ''; }
          toast(res?.error || 'Error al eliminar', 'error');
        }
      })
    );
  }

  /* ═══════════════════════════════════════════════════════════
     ADMIN — update metrics panel
  ═══════════════════════════════════════════════════════════ */

  async function updateAdminMetrics() {
    const stats = await api('get_stats');
    if (!stats) return;
    const s = (sel, v) => {
      const el = document.querySelector(sel);
      if (el) el.textContent = fmtNumber(v || 0);
    };
    s('[data-admin-users]',    stats.users);
    s('[data-admin-approved]', stats.posts);
    s('[data-admin-pending]',  stats.pending);
    s('[data-admin-contacts]', stats.contacts);
    s('[data-admin-likes]',    stats.likes);
  }

  /* ═══════════════════════════════════════════════════════════
     ADMIN PAGE — extended version using new render functions
  ═══════════════════════════════════════════════════════════ */

  async function renderAdminPageFull() {
    const user    = getUser();
    const blocked = document.querySelector('[data-admin-blocked]');
    const content = document.querySelector('[data-admin-content]');

    if (!user || user.role !== 'admin') {
      blocked?.classList.remove('hidden');
      content?.classList.add('hidden');
      return;
    }

    content?.classList.remove('hidden');
    blocked?.classList.add('hidden');

    initAdminTabs();
    await updateAdminMetrics();
  }

  /* ═══════════════════════════════════════════════════════════
     ADMIN — tab navigation
  ═══════════════════════════════════════════════════════════ */
  function initAdminTabs() {
    const nav = document.querySelector('[data-admin-tab-nav]');
    if (!nav || nav.dataset.initialized) return;
    nav.dataset.initialized = 'true';

    nav.addEventListener('click', async e => {
      const btn = e.target.closest('[data-admin-tab]');
      if (!btn) return;
      const tab = btn.dataset.adminTab;

      nav.querySelectorAll('[data-admin-tab]').forEach(b => b.classList.remove('is-active'));
      btn.classList.add('is-active');

      document.querySelectorAll('[data-admin-pane]').forEach(p => {
        p.classList.toggle('hidden', p.dataset.adminPane !== tab);
      });

      if (tab === 'moderation') {
        await Promise.all([renderAdminPosts(), renderAdminUsersExtended(), renderAdminContactsExtended()]);
      }
      if (tab === 'users')    initAdminUsersTab();
      if (tab === 'backups')  initAdminBackupsTab();
      if (tab === 'danger')   initAdminDangerTab();
    });
  }

  /* ═══════════════════════════════════════════════════════════
     ADMIN — full users table with search + filter
  ═══════════════════════════════════════════════════════════ */
  let _usersCache = [];

  function initAdminUsersTab() {
    const pane = document.querySelector('[data-admin-pane="users"]');
    if (!pane || pane.dataset.initialized) return;
    pane.dataset.initialized = 'true';

    let timer;
    const searchInput = pane.querySelector('[data-user-search]');
    const roleFilter  = pane.querySelector('[data-user-role-filter]');

    searchInput?.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(renderAdminUsersTable, 280);
    });
    roleFilter?.addEventListener('change', renderAdminUsersTable);

    renderAdminUsersTable();
  }

  async function renderAdminUsersTable() {
    const container = document.querySelector('[data-admin-users-table]');
    if (!container) return;

    if (!_usersCache.length) {
      container.innerHTML = '<div style="padding:24px 20px;color:var(--white-faint);font-family:var(--font-ui);font-size:0.86rem;">Cargando usuarios…</div>';
      const res = await api('get_users');
      _usersCache = Array.isArray(res) ? res : [];
    }

    const pane       = document.querySelector('[data-admin-pane="users"]');
    const searchVal  = (pane?.querySelector('[data-user-search]')?.value || '').toLowerCase();
    const roleVal    = pane?.querySelector('[data-user-role-filter]')?.value || '';
    const me         = getUser();

    const filtered = _usersCache.filter(u => {
      if (roleVal && u.role !== roleVal) return false;
      if (searchVal) {
        const hay = `${u.username} ${u.nombre} ${u.email || ''}`.toLowerCase();
        if (!hay.includes(searchVal)) return false;
      }
      return true;
    });

    if (!filtered.length) {
      container.innerHTML = '<div style="padding:24px 20px;color:var(--white-faint);font-family:var(--font-ui);font-size:0.86rem;">Sin resultados.</div>';
      return;
    }

    container.innerHTML = `
      <div class="users-table-wrap">
        <table class="users-table">
          <thead>
            <tr>
              <th>Usuario</th>
              <th>Nombre</th>
              <th>Email</th>
              <th>Juego</th>
              <th>Rol</th>
              <th>Posts</th>
              <th>Likes</th>
              <th>Registro</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            ${filtered.map(u => {
              const isSelf  = me && String(me.id) === String(u.id);
              const isAdmin = u.role === 'admin';
              return `
                <tr data-user-row="${u.id}" class="${isSelf ? 'tr-self' : ''}">
                  <td>
                    <div class="tbl-user-cell">
                      <span class="avatar ava-xs" style="${avatarStyle(u.username)}">${avatarInitial(u.username)}</span>
                      <strong>@${esc(u.username)}</strong>
                    </div>
                  </td>
                  <td>${esc(u.nombre || '—')}</td>
                  <td><span class="tbl-email">${esc(u.email || '—')}</span></td>
                  <td><span class="tbl-game">${esc(u.favorite_game || '—')}</span></td>
                  <td><span class="badge ${isAdmin ? 'badge-admin' : 'badge-member'}">${isAdmin ? 'Admin' : 'Miembro'}</span></td>
                  <td class="tbl-num">${u.post_count || 0}</td>
                  <td class="tbl-num">♥ ${fmtNumber(u.total_likes || 0)}</td>
                  <td><span class="tbl-date">${fmtDate(u.fecha_registro || '')}</span></td>
                  <td>
                    ${isSelf
                      ? '<span class="tbl-self">(Tú)</span>'
                      : `<div class="tbl-actions">
                          ${!isAdmin
                            ? `<button class="btn btn-ghost btn-xs" data-promote-user="${u.id}" title="Promover a admin">▲ Admin</button>`
                            : `<button class="btn btn-ghost btn-xs" data-demote-user="${u.id}" title="Degradar a miembro">▼ Miembro</button>`
                          }
                          <button class="btn btn-danger btn-xs" data-delete-user="${u.id}" title="Eliminar">✕</button>
                        </div>`
                    }
                  </td>
                </tr>
              `;
            }).join('')}
          </tbody>
        </table>
      </div>
      <div class="tbl-footer">Mostrando ${filtered.length} de ${_usersCache.length} usuarios</div>
    `;

    container.querySelectorAll('[data-promote-user]').forEach(btn =>
      btn.addEventListener('click', async () => {
        if (!confirm('¿Promover a este usuario a administrador?')) return;
        const res = await api('update_user_role', 'POST', { user_id: parseInt(btn.dataset.promoteUser), role: 'admin' });
        if (res?.success) { toast('Usuario promovido a admin', 'success'); _usersCache = []; renderAdminUsersTable(); updateAdminMetrics(); }
        else toast(res?.error || 'Error', 'error');
      })
    );
    container.querySelectorAll('[data-demote-user]').forEach(btn =>
      btn.addEventListener('click', async () => {
        if (!confirm('¿Degradar a miembro?')) return;
        const res = await api('update_user_role', 'POST', { user_id: parseInt(btn.dataset.demoteUser), role: 'member' });
        if (res?.success) { toast('Rol actualizado a miembro', 'info'); _usersCache = []; renderAdminUsersTable(); }
        else toast(res?.error || 'Error', 'error');
      })
    );
    container.querySelectorAll('[data-delete-user]').forEach(btn =>
      btn.addEventListener('click', async () => {
        if (!confirm('¿Eliminar este usuario y todos sus posts? Esta acción es irreversible.')) return;
        const row = container.querySelector(`[data-user-row="${btn.dataset.deleteUser}"]`);
        if (row) { row.style.opacity = '0.4'; row.style.pointerEvents = 'none'; }
        const res = await api('delete_user', 'POST', { user_id: parseInt(btn.dataset.deleteUser) });
        if (res?.success) {
          toast('Usuario eliminado', 'error');
          _usersCache = [];
          renderAdminUsersTable();
          updateAdminMetrics();
        } else {
          if (row) { row.style.opacity = ''; row.style.pointerEvents = ''; }
          toast(res?.error || 'Error al eliminar', 'error');
        }
      })
    );
  }

  /* ═══════════════════════════════════════════════════════════
     ADMIN — backups tab
  ═══════════════════════════════════════════════════════════ */
  function initAdminBackupsTab() {
    const pane = document.querySelector('[data-admin-pane="backups"]');
    if (!pane || pane.dataset.initialized) return;
    pane.dataset.initialized = 'true';

    async function triggerDownload(url, btn, label) {
      const original = btn.textContent;
      btn.disabled = true;
      btn.textContent = 'Generando…';
      toast(label, 'info');
      try {
        const res = await fetch(url);
        if (!res.ok) {
          const err = await res.json().catch(() => ({}));
          toast(err.error || 'Error al generar el backup', 'error');
          return;
        }
        const blob = await res.blob();
        const cd       = res.headers.get('Content-Disposition') || '';
        const match    = cd.match(/filename="?([^";\n]+)"?/);
        const filename = match ? match[1] : 'nexus_backup';
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = filename;
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        setTimeout(() => URL.revokeObjectURL(a.href), 10000);
        toast('Descarga completada ✓', 'success');
      } catch {
        toast('Error de conexión al generar el backup', 'error');
      } finally {
        btn.disabled = false;
        btn.textContent = original;
      }
    }

    pane.querySelector('[data-backup-db]')?.addEventListener('click', function () {
      triggerDownload('api.php?action=backup_database', this, 'Generando dump SQL — un momento…');
    });

    pane.querySelector('[data-backup-files]')?.addEventListener('click', function () {
      triggerDownload('api.php?action=backup_files', this, 'Generando ZIP de la web — puede tardar unos segundos…');
    });

    /* ── Restore section ── */
    const zipInput   = pane.querySelector('[data-restore-zip-input]');
    const restoreBtn = pane.querySelector('[data-restore-btn]');
    const fileInfo   = pane.querySelector('[data-restore-file-info]');
    const restorePin = pane.querySelector('[data-restore-pin]');
    const statusEl   = pane.querySelector('[data-restore-status]');
    const dropArea   = pane.querySelector('[data-restore-drop]');

    function setRestoreFile(file) {
      if (!file || file.type !== 'application/zip' && !file.name.endsWith('.zip')) {
        toast('Selecciona un archivo .zip válido', 'error');
        return;
      }
      fileInfo.textContent = `📦 ${file.name}  ·  ${(file.size / 1024 / 1024).toFixed(2)} MB`;
      fileInfo.classList.remove('hidden');
      restoreBtn.disabled = false;
      /* Replace the input's file list by reassigning via DataTransfer */
      const dt = new DataTransfer();
      dt.items.add(file);
      zipInput.files = dt.files;
    }

    zipInput?.addEventListener('change', () => {
      if (zipInput.files[0]) setRestoreFile(zipInput.files[0]);
    });

    /* Drag & drop onto the drop area */
    dropArea?.addEventListener('dragover', e => { e.preventDefault(); dropArea.classList.add('is-dragging'); });
    dropArea?.addEventListener('dragleave', () => dropArea.classList.remove('is-dragging'));
    dropArea?.addEventListener('drop', e => {
      e.preventDefault();
      dropArea.classList.remove('is-dragging');
      const file = e.dataTransfer?.files[0];
      if (file) setRestoreFile(file);
    });

    restoreBtn?.addEventListener('click', async () => {
      const file = zipInput?.files[0];
      if (!file) { toast('Selecciona un archivo ZIP primero', 'error'); return; }
      if ((restorePin?.value || '') !== '565656') { toast('PIN incorrecto', 'error'); return; }
      if (!confirm('¿Restaurar la web desde este ZIP?\n\nLos archivos actuales serán sobreescritos. Esta acción no se puede deshacer.')) return;

      restoreBtn.disabled = true;
      restoreBtn.textContent = 'Restaurando…';
      statusEl.textContent = 'Subiendo y procesando el ZIP…';
      statusEl.className = 'restore-status restore-status--loading';
      statusEl.classList.remove('hidden');

      const fd = new FormData();
      fd.append('zipfile', file);
      fd.append('pin', restorePin.value);

      try {
        const res  = await fetch('api.php?action=restore_files', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
          statusEl.textContent = '✓ ' + data.message;
          statusEl.className = 'restore-status restore-status--ok';
          toast(data.message, 'success');
        } else {
          statusEl.textContent = '✗ ' + (data.error || 'Error desconocido');
          statusEl.className = 'restore-status restore-status--error';
          toast(data.error || 'Error al restaurar', 'error');
        }
      } catch {
        statusEl.textContent = '✗ Error de conexión. Comprueba que el servidor está activo.';
        statusEl.className = 'restore-status restore-status--error';
        toast('Error de conexión', 'error');
      } finally {
        restoreBtn.disabled = false;
        restoreBtn.textContent = 'Restaurar web';
      }
    });

    pane.querySelector('[data-backup-full]')?.addEventListener('click', function () {
      triggerDownload('api.php?action=backup_full', this,
        'Generando backup completo (web + base de datos) — puede tardar varios segundos…');
    });
  }

  /* ═══════════════════════════════════════════════════════════
     ADMIN — danger zone tab
  ═══════════════════════════════════════════════════════════ */
  function initAdminDangerTab() {
    const pane = document.querySelector('[data-admin-pane="danger"]');
    if (!pane || pane.dataset.initialized) return;
    pane.dataset.initialized = 'true';

    const pinInput  = pane.querySelector('[data-danger-pin]');
    const verifyBtn = pane.querySelector('[data-danger-verify]');
    const actions   = pane.querySelector('[data-danger-actions]');
    const pinStatus = pane.querySelector('[data-pin-status]');

    verifyBtn?.addEventListener('click', () => {
      if (pinInput.value === '565656') {
        pinStatus.textContent  = '✓ PIN correcto — acciones desbloqueadas';
        pinStatus.className    = 'pin-status pin-ok';
        actions?.classList.remove('hidden');
        pinInput.disabled  = true;
        verifyBtn.disabled = true;
      } else {
        pinStatus.textContent = '✕ PIN incorrecto. Inténtalo de nuevo.';
        pinStatus.className   = 'pin-status pin-error';
        pinInput.value = '';
        pinInput.focus();
      }
    });

    pinInput?.addEventListener('keydown', e => {
      if (e.key === 'Enter') verifyBtn?.click();
    });

    pane.querySelectorAll('[data-reset-target]').forEach(btn => {
      btn.addEventListener('click', async () => {
        const target = btn.dataset.resetTarget;
        const label  = btn.dataset.resetLabel || target;
        if (!confirm(`⚠️ ¿Estás SEGURO de que quieres ${label}?\n\nEsta acción es IRREVERSIBLE. No se puede deshacer.`)) return;
        if (target === 'database' && !confirm('ÚLTIMA ADVERTENCIA: Se eliminará la base de datos completa. ¿Continuar?')) return;

        const orig = btn.textContent;
        btn.disabled    = true;
        btn.textContent = 'Procesando…';

        const res = await api('reset_site', 'POST', { pin: '565656', target });
        if (res?.success) {
          toast(res.message || 'Acción completada', target === 'database' ? 'error' : 'success');
          _usersCache = [];
          updateAdminMetrics();
          if (target === 'users' || target === 'all') {
            _usersCache = [];
            const usersPane = document.querySelector('[data-admin-pane="users"]');
            if (usersPane) { delete usersPane.dataset.initialized; }
          }
        } else {
          toast(res?.error || 'Error al ejecutar la acción', 'error');
          btn.disabled    = false;
          btn.textContent = orig;
        }
      });
    });
  }

  /* ═══════════════════════════════════════════════════════════
     COPY TO CLIPBOARD utility
  ═══════════════════════════════════════════════════════════ */

  async function copyToClipboard(text) {
    try {
      await navigator.clipboard.writeText(text);
      toast('Copiado al portapapeles', 'success');
    } catch {
      const el = document.createElement('textarea');
      el.value = text;
      el.style.position = 'fixed';
      el.style.opacity  = '0';
      document.body.appendChild(el);
      el.select();
      document.execCommand('copy');
      document.body.removeChild(el);
      toast('Copiado al portapapeles', 'success');
    }
  }

  /* ═══════════════════════════════════════════════════════════
     RELATIVE TIME — "hace 2 horas" format
  ═══════════════════════════════════════════════════════════ */

  function relativeTime(dateStr) {
    const now  = Date.now();
    const then = new Date(dateStr).getTime();
    const diff = Math.floor((now - then) / 1000);

    if (diff < 60)           return 'hace unos segundos';
    if (diff < 3600)         return `hace ${Math.floor(diff / 60)} min`;
    if (diff < 86400)        return `hace ${Math.floor(diff / 3600)} h`;
    if (diff < 86400 * 7)    return `hace ${Math.floor(diff / 86400)} d`;
    if (diff < 86400 * 30)   return `hace ${Math.floor(diff / (86400 * 7))} sem`;
    if (diff < 86400 * 365)  return `hace ${Math.floor(diff / (86400 * 30))} mes`;
    return `hace ${Math.floor(diff / (86400 * 365))} año(s)`;
  }

  /* ═══════════════════════════════════════════════════════════
     LIVE POST COUNTER — shows count next to forum heading
  ═══════════════════════════════════════════════════════════ */

  async function updatePostCounter() {
    const badge = document.querySelector('[data-post-count-badge]');
    if (!badge) return;
    const stats = await api('get_stats');
    if (stats?.posts !== undefined) {
      badge.textContent = fmtNumber(stats.posts) + ' tema' + (stats.posts !== 1 ? 's' : '');
    }
  }

  /* ═══════════════════════════════════════════════════════════
     SCROLL TO FORUM — for CTA buttons linking to #foro
  ═══════════════════════════════════════════════════════════ */

  function setupCTAScrollLinks() {
    document.querySelectorAll('[data-scroll-to]').forEach(btn => {
      btn.addEventListener('click', () => {
        const id     = btn.dataset.scrollTo;
        const target = document.getElementById(id);
        if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });
  }

  /* ═══════════════════════════════════════════════════════════
     AUTO-REFRESH POSTS — poll every 90 seconds
  ═══════════════════════════════════════════════════════════ */

  function setupAutoRefresh() {
    const page = document.body.dataset.page;
    if (page !== 'home') return;
    setInterval(() => {
      renderPosts();
      updateStats();
    }, 90_000);
  }

  /* ═══════════════════════════════════════════════════════════
     PAGE TRANSITION FADE — quick opacity fade on navigation
  ═══════════════════════════════════════════════════════════ */

  function setupPageTransitions() {
    document.querySelectorAll('a[href]').forEach(link => {
      const href = link.getAttribute('href') || '';
      if (
        href.startsWith('#') ||
        href.startsWith('javascript') ||
        href.startsWith('mailto') ||
        link.target === '_blank'
      ) return;

      link.addEventListener('click', e => {
        const wrap = document.querySelector('.page-wrap');
        if (!wrap) return;
        e.preventDefault();
        wrap.style.transition = 'opacity 0.22s ease';
        wrap.style.opacity    = '0';
        setTimeout(() => { window.location.href = href; }, 200);
      });
    });
  }

  /* ═══════════════════════════════════════════════════════════
     FORUM STATS ANIMATED COUNTER
  ═══════════════════════════════════════════════════════════ */

  function animateCounter(el, target, duration = 900) {
    const start    = performance.now();
    const startVal = 0;
    const step = ts => {
      const elapsed  = ts - start;
      const progress = Math.min(elapsed / duration, 1);
      const eased    = 1 - Math.pow(1 - progress, 3);
      el.textContent = fmtNumber(Math.round(startVal + (target - startVal) * eased));
      if (progress < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  }

  async function animateStatsOnLoad() {
    const stats = await api('get_stats');
    if (!stats) return;
    const targets = {
      '[data-stat-posts]':    stats.posts  || 0,
      '[data-stat-users]':    stats.users  || 0,
      '[data-stat-online]':   Math.max(1, Math.min(stats.users || 0, 12)),
      '[data-approved-count]':stats.posts  || 0,
      '[data-pending-count]': stats.pending || 0,
      '[data-likes-count]':   stats.likes  || 0,
    };
    Object.entries(targets).forEach(([sel, val]) => {
      const el = document.querySelector(sel);
      if (el) animateCounter(el, val, 1200);
    });
  }

  /* ═══════════════════════════════════════════════════════════
     INIT
  ═══════════════════════════════════════════════════════════ */

  function init() {
    const page = document.body.dataset.page;

    setupMobileNav();
    renderAuthSlot();
    setupScrollReveal();
    setupKeyboardShortcuts();
    setupSmoothScroll();
    setupBackToTop();
    setupStickyHeader();
    setupCTAScrollLinks();
    setupPageTransitions();

    if (page === 'home') {
      applyPrefs();
      setupComposer();
      setupFilters();
      setupContactForm();
      setupSearchDebounce();
      setupCharCounters();
      persistFilterPrefs();
      animateStatsOnLoad();
      renderLeaderboardExtended();
      renderSessionPanel();
      renderPosts();
      renderMembersList();
      updatePostCounter();
      setupAutoRefresh();
    }

    if (page === 'admin') {
      renderAdminPageFull();
    }
  }

  document.addEventListener('DOMContentLoaded', init);

})();
