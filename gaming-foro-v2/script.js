/* ═══════════════════════════════════════════════════════════════
   NEXUS//BOARD — script.js
   Interactive starfield canvas + full forum SPA logic
   Cosmos edition: cold starlight on void black
═══════════════════════════════════════════════════════════════ */

'use strict'; // Instrucción

/* ══════════════════════════════════════════════════════════════
   SECTION 1 — GALAXY CANVAS (performance-optimised)
   Pre-rendered fog · simple star dots · shooting stars · mouse dust
══════════════════════════════════════════════════════════════ */

(function initGalaxy() { // Instrucción
  const canvas = document.getElementById('starfield'); // Declara una variable
  if (!canvas) return; // Condición

  const ctx = canvas.getContext('2d'); // Declara una variable
  let CW = canvas.width  = window.innerWidth; // Declara una variable
  let CH = canvas.height = window.innerHeight; // Declara una variable

  /* ── Offscreen canvas for the static galaxy fog — drawn ONCE ── */
  let fogCanvas = document.createElement('canvas'); // Declara una variable
  let fogCtx    = fogCanvas.getContext('2d'); // Declara una variable

  function buildFog() { // Función
    fogCanvas.width  = CW; // Asigna un valor
    fogCanvas.height = CH; // Asigna un valor
    fogCtx.clearRect(0, 0, CW, CH); // Llama a una función

    /* Milky-way diagonal band — bright and visible */
    const band = fogCtx.createLinearGradient(0, CH * 0.1, CW, CH * 0.9); // Declara una variable
    band.addColorStop(0,    'rgba(255,255,255,0)'); // Llama a una función
    band.addColorStop(0.30, 'rgba(255,255,255,0.10)'); // Llama a una función
    band.addColorStop(0.5,  'rgba(255,255,255,0.22)'); // Llama a una función
    band.addColorStop(0.70, 'rgba(255,255,255,0.10)'); // Llama a una función
    band.addColorStop(1,    'rgba(255,255,255,0)'); // Llama a una función
    fogCtx.fillStyle = band; // Asigna un valor
    fogCtx.fillRect(0, 0, CW, CH); // Llama a una función

    /* Second narrower band for depth */
    const band2 = fogCtx.createLinearGradient(CW * 0.1, 0, CW * 0.9, CH); // Declara una variable
    band2.addColorStop(0,   'rgba(255,255,255,0)'); // Llama a una función
    band2.addColorStop(0.4, 'rgba(255,255,255,0.06)'); // Llama a una función
    band2.addColorStop(0.6, 'rgba(255,255,255,0.06)'); // Llama a una función
    band2.addColorStop(1,   'rgba(255,255,255,0)'); // Llama a una función
    fogCtx.fillStyle = band2; // Asigna un valor
    fogCtx.fillRect(0, 0, CW, CH); // Llama a una función

    /* Nebula clusters — radial gradients drawn once */
    const clusters = [ // Declara una variable
      { x: 0.20, y: 0.32, r: 0.30, a: 0.26 }, // Instrucción
      { x: 0.55, y: 0.50, r: 0.36, a: 0.30 }, // Instrucción
      { x: 0.80, y: 0.68, r: 0.26, a: 0.22 }, // Instrucción
      { x: 0.07, y: 0.72, r: 0.20, a: 0.18 }, // Instrucción
      { x: 0.88, y: 0.18, r: 0.18, a: 0.18 }, // Instrucción
      { x: 0.42, y: 0.18, r: 0.24, a: 0.16 }, // Instrucción
      { x: 0.68, y: 0.82, r: 0.28, a: 0.20 }, // Instrucción
      { x: 0.33, y: 0.60, r: 0.18, a: 0.14 }, // Instrucción
    ]; // Instrucción
    for (const c of clusters) { // Bucle
      const cx = c.x * CW, cy = c.y * CH, r = c.r * Math.min(CW, CH); // Declara una variable
      const g = fogCtx.createRadialGradient(cx, cy, 0, cx, cy, r); // Declara una variable
      g.addColorStop(0,   `rgba(255,255,255,${c.a})`); // Llama a una función
      g.addColorStop(0.5, `rgba(255,255,255,${c.a * 0.3})`); // Llama a una función
      g.addColorStop(1,   'rgba(0,0,0,0)'); // Llama a una función
      fogCtx.beginPath(); // Llama a una función
      fogCtx.arc(cx, cy, r, 0, Math.PI * 2); // Llama a una función
      fogCtx.fillStyle = g; // Asigna un valor
      fogCtx.fill(); // Llama a una función
    }
  }

  /* ── Stars ── */
  let stars = []; // Declara una variable

  function buildStars() { // Función
    stars = []; // Asigna un valor
    /* Fewer stars = faster. 1200px for a typical 1920×1080 = ~3700 stars */
    const total = Math.min(Math.floor((CW * CH) / 2400), 1800); // Declara una variable
    for (let i = 0; i < total; i++) { // Bucle
      /* 40 % placed in the diagonal band */
      const inBand = Math.random() < 0.40; // Declara una variable
      let x, y; // Declara una variable
      if (inBand) { // Condición
        const t = Math.random(); // Declara una variable
        x = t * CW; // Asigna un valor
        y = CH * 0.15 + t * CH * 0.70 + (Math.random() - 0.5) * CH * 0.30; // Asigna un valor
        y = Math.max(0, Math.min(CH, y)); // Asigna un valor
      } else { // Instrucción
        x = Math.random() * CW; // Asigna un valor
        y = Math.random() * CH; // Asigna un valor
      }

      /* Four brightness tiers */
      const roll = Math.random(); // Declara una variable
      let r, baseA, glow = false; // Declara una variable
      if (roll < 0.58) { // Condición
        r = Math.random() * 0.7 + 0.4;  baseA = Math.random() * 0.4 + 0.38; // Asigna un valor
      } else if (roll < 0.84) { // Instrucción
        r = Math.random() * 0.9 + 0.9;  baseA = Math.random() * 0.25 + 0.60; // Asigna un valor
      } else if (roll < 0.97) { // Instrucción
        r = Math.random() * 1.2 + 1.6;  baseA = Math.random() * 0.15 + 0.80; glow = true; // Asigna un valor
      } else { // Instrucción
        r = Math.random() * 1.5 + 2.8;  baseA = 1.0; glow = true; // Asigna un valor
      }

      if (inBand) baseA = Math.min(1.0, baseA * 1.25); // Condición

      stars.push({ // Llama a una función
        x, y, r, baseA, glow, // Instrucción
        a:     baseA, // Instrucción
        da:    (Math.random() - 0.5) * 0.004, // Instrucción
        drift: (Math.random() - 0.5) * 0.012, // Instrucción
        tick:  Math.floor(Math.random() * 2), /* stagger twinkle updates */
      });
    }
  }

  function build() { // Función
    buildFog(); // Llama a una función
    buildStars(); // Llama a una función
  }

  window.addEventListener('resize', () => { // Escucha un evento
    CW = canvas.width  = window.innerWidth; // Asigna un valor
    CH = canvas.height = window.innerHeight; // Asigna un valor
    build(); // Llama a una función
  });

  /* ── Shooting stars ── */
  let shooters = []; // Declara una variable
  let nextShoot = 300; /* frames until next shooting star */

  function spawnShooter() { // Función
    const fromTop = Math.random() < 0.6; // Declara una variable
    let x, y, vx, vy; // Declara una variable
    if (fromTop) { // Condición
      x = Math.random() * CW * 0.8 + CW * 0.1; y = -5; // Asigna un valor
      vx = (Math.random() - 0.4) * 8; vy = Math.random() * 5 + 4; // Asigna un valor
    } else { // Instrucción
      x = -5; y = Math.random() * CH * 0.55; // Asigna un valor
      vx = Math.random() * 7 + 4; vy = (Math.random() - 0.2) * 3 + 1; // Asigna un valor
    }
    const spd  = Math.sqrt(vx * vx + vy * vy); // Declara una variable
    const tail = Math.random() * 120 + 80; // Declara una variable
    shooters.push({ x, y, vx, vy, spd, tail, life: 1.0, decay: 0.012 + Math.random() * 0.01 }); // Llama a una función
    nextShoot = 200 + Math.floor(Math.random() * 250); /* ~3-7 s at 60fps */
  }

  /* ── Mouse / Touch stardust ── */
  let particles = []; // Declara una variable
  let mouseX = -999, mouseY = -999, lastMX = -999, lastMY = -999; // Declara una variable

  function spawnDust(cx, cy) { // Función
    const dx = cx - lastMX, dy = cy - lastMY; // Declara una variable
    const spd = Math.sqrt(dx * dx + dy * dy); // Declara una variable
    if (spd < 1.5) return; // Condición

    const burst = Math.min(Math.floor(spd * 0.3) + 1, 5); // Declara una variable
    const nx = dx / spd, ny = dy / spd; // Declara una variable
    for (let i = 0; i < burst; i++) { // Bucle
      const angle = Math.random() * Math.PI * 2; // Declara una variable
      const speed = Math.random() * 3.0 + 0.6; // Declara una variable
      particles.push({ // Llama a una función
        x:     cx + (Math.random() - 0.5) * 10, // Instrucción
        y:     cy + (Math.random() - 0.5) * 10, // Instrucción
        vx:    Math.cos(angle) * speed * 0.5 + nx * speed * 0.6, // Instrucción
        vy:    Math.sin(angle) * speed * 0.5 + ny * speed * 0.6, // Instrucción
        r:     Math.random() * 3.0 + 1.0, // Instrucción
        life:  1.0, // Instrucción
        decay: Math.random() * 0.018 + 0.008, // Instrucción
      });
    }
    if (particles.length > 80) particles.splice(0, particles.length - 80); // Condición
    lastMX = cx; lastMY = cy; // Asigna un valor
  }

  document.addEventListener('mousemove', e => { // Escucha un evento
    const px = mouseX, py = mouseY; // Declara una variable
    lastMX = px; lastMY = py; // Asigna un valor
    mouseX = e.clientX; mouseY = e.clientY; // Asigna un valor
    spawnDust(mouseX, mouseY); // Llama a una función
  });

  /* Touch support — doesn't block page scrolling */
  document.addEventListener('touchmove', e => { // Escucha un evento
    const t = e.touches[0]; // Declara una variable
    spawnDust(t.clientX, t.clientY); // Llama a una función
    lastMX = t.clientX; lastMY = t.clientY; // Asigna un valor
  }, { passive: true }); // Instrucción

  /* ── Draw loop ── */
  let frame = 0; // Declara una variable

  function drawFrame() { // Función
    frame++; // Instrucción

    /* 1. Black background */
    ctx.fillStyle = '#000000'; // Asigna un valor
    ctx.fillRect(0, 0, CW, CH); // Llama a una función

    /* 2. Static galaxy fog (one drawImage, nearly free) */
    ctx.drawImage(fogCanvas, 0, 0); // Llama a una función

    /* 3. Stars — simple filled arcs + glow halo for bright ones */
    ctx.fillStyle = '#ffffff'; // Asigna un valor
    const oddFrame = frame & 1; // Declara una variable
    for (let i = 0; i < stars.length; i++) { // Bucle
      const s = stars[i]; // Declara una variable
      /* Twinkle: each star updates every other frame, staggered */
      if (s.tick !== oddFrame) { // Condición
        s.a += s.da * 2; /* compensate for skipped frame */
        if (s.a > s.baseA * 1.5 || s.a < s.baseA * 0.3) s.da = -s.da; // Condición
      }
      s.x += s.drift; // Instrucción
      if (s.x > CW + 2) s.x = -2; // Condición
      if (s.x < -2)     s.x = CW + 2; // Condición

      const alpha = Math.max(0, Math.min(1, s.a)); // Declara una variable
      if (s.glow && alpha > 0.4) { // Condición
        /* Halo — only draw when visible */
        ctx.globalAlpha = alpha * 0.20; // Asigna un valor
        ctx.beginPath(); // Llama a una función
        ctx.arc(s.x, s.y, s.r * 4.0, 0, 6.283); // Llama a una función
        ctx.fill(); // Llama a una función
        ctx.globalAlpha = alpha * 0.42; // Asigna un valor
        ctx.beginPath(); // Llama a una función
        ctx.arc(s.x, s.y, s.r * 2.0, 0, 6.283); // Llama a una función
        ctx.fill(); // Llama a una función
      }
      ctx.globalAlpha = alpha; // Asigna un valor
      ctx.beginPath(); // Llama a una función
      ctx.arc(s.x, s.y, s.r, 0, 6.283); // Llama a una función
      ctx.fill(); // Llama a una función
    }
    ctx.globalAlpha = 1; // Asigna un valor

    /* 4. Shooting stars */
    if (--nextShoot <= 0) spawnShooter(); // Condición
    for (let i = shooters.length - 1; i >= 0; i--) { // Bucle
      const s = shooters[i]; // Declara una variable
      s.life -= s.decay; // Instrucción
      if (s.life <= 0) { shooters.splice(i, 1); continue; } // Condición

      const nx = s.vx / s.spd, ny = s.vy / s.spd; // Declara una variable
      const grd = ctx.createLinearGradient(s.x, s.y, s.x - nx * s.tail, s.y - ny * s.tail); // Declara una variable
      grd.addColorStop(0,   `rgba(255,255,255,${s.life.toFixed(2)})`); // Llama a una función
      grd.addColorStop(0.3, `rgba(220,220,220,${(s.life * 0.4).toFixed(2)})`); // Llama a una función
      grd.addColorStop(1,   'rgba(255,255,255,0)'); // Llama a una función
      ctx.beginPath(); // Llama a una función
      ctx.moveTo(s.x, s.y); // Llama a una función
      ctx.lineTo(s.x - nx * s.tail, s.y - ny * s.tail); // Llama a una función
      ctx.lineWidth   = 2; // Asigna un valor
      ctx.strokeStyle = grd; // Asigna un valor
      ctx.stroke(); // Llama a una función
      /* Bright head */
      ctx.globalAlpha = s.life; // Asigna un valor
      ctx.fillStyle   = '#ffffff'; // Asigna un valor
      ctx.beginPath(); // Llama a una función
      ctx.arc(s.x, s.y, 2, 0, 6.283); // Llama a una función
      ctx.fill(); // Llama a una función
      ctx.globalAlpha = 1; // Asigna un valor

      s.x += s.vx; s.y += s.vy; // Instrucción
    }

    /* 5. Mouse stardust — simple circles with globalAlpha, NO radial gradient */
    for (let i = particles.length - 1; i >= 0; i--) { // Bucle
      const p = particles[i]; // Declara una variable
      p.life -= p.decay; // Instrucción
      if (p.life <= 0) { particles.splice(i, 1); continue; } // Condición
      p.x  += p.vx; // Instrucción
      p.y  += p.vy; // Instrucción
      p.vy += 0.04; // Instrucción
      p.vx *= 0.97; // Instrucción

      ctx.globalAlpha = Math.min(1, p.life * 1.2); // Asigna un valor
      ctx.fillStyle   = '#ffffff'; // Asigna un valor
      ctx.beginPath(); // Llama a una función
      ctx.arc(p.x, p.y, p.r, 0, 6.283); // Llama a una función
      ctx.fill(); // Llama a una función
      /* Outer glow — one cheap larger circle at low alpha */
      ctx.globalAlpha = p.life * 0.25; // Asigna un valor
      ctx.beginPath(); // Llama a una función
      ctx.arc(p.x, p.y, p.r * 3.5, 0, 6.283); // Llama a una función
      ctx.fill(); // Llama a una función
    }
    ctx.globalAlpha = 1; // Asigna un valor

    rafId = requestAnimationFrame(drawFrame); // Asigna un valor
  }

  /* Respect prefers-reduced-motion: skip continuous animation */
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { // Condición
    canvas.style.opacity = '0.4'; // Asigna un valor
    build(); // Llama a una función
    /* Draw one static frame only */
    ctx.fillStyle = '#000000'; // Asigna un valor
    ctx.fillRect(0, 0, CW, CH); // Llama a una función
    ctx.drawImage(fogCanvas, 0, 0); // Llama a una función
    return; // Devuelve el resultado
  }

  build(); // Llama a una función

  /* Track RAF id so we can pause when tab is hidden */
  let rafId; // Declara una variable
  function drawLoop() { rafId = requestAnimationFrame(drawFrame); } // Función

  /* Pause animation when tab is hidden — saves battery on mobile */
  document.addEventListener('visibilitychange', () => { // Escucha un evento
    if (document.hidden) { // Condición
      cancelAnimationFrame(rafId); // Llama a una función
    } else { // Instrucción
      drawLoop(); // Llama a una función
    }
  });

  drawLoop(); // Llama a una función
})(); // Instrucción


/* ══════════════════════════════════════════════════════════════
   SECTION 2 — FORUM APPLICATION
══════════════════════════════════════════════════════════════ */

(function () { // Instrucción

  const API_URL = 'api.php'; // Declara una variable

  const CAT_LABELS = { // Declara una variable
    fps: 'FPS', moba: 'MOBA', hardware: 'Hardware', // Instrucción
    noticias: 'Noticias', estrategia: 'Estrategia' // Instrucción
  };

  /* ── Avatar palette — black & white gradient pairs ── */
  const AVATAR_PALETTE = [ // Declara una variable
    ['#ffffff','#888888'], // Instrucción
    ['#d0d0d0','#404040'], // Instrucción
    ['#e8e8e8','#606060'], // Instrucción
    ['#b0b0b0','#282828'], // Instrucción
    ['#f0f0f0','#707070'], // Instrucción
    ['#c0c0c0','#303030'], // Instrucción
    ['#a8a8a8','#181818'], // Instrucción
    ['#dcdcdc','#505050'], // Instrucción
    ['#e0e0e0','#383838'], // Instrucción
    ['#c8c8c8','#484848'] // Instrucción
  ]; // Instrucción

  /* ═══════════════════════════════════════════════════════════
     HELPERS
  ═══════════════════════════════════════════════════════════ */

  function avatarStyle(str) { // Función
    let h = 0; // Declara una variable
    for (let i = 0; i < (str || '').length; i++) h = str.charCodeAt(i) + ((h << 5) - h); // Bucle
    const [c1, c2] = AVATAR_PALETTE[Math.abs(h) % AVATAR_PALETTE.length]; // Declara una variable
    return `background:linear-gradient(135deg,${c1},${c2})`; // Devuelve el resultado
  }

  function avatarInitial(str) { // Función
    return (str || '?').charAt(0).toUpperCase(); // Devuelve el resultado
  }

  function esc(str) { // Función
    return String(str ?? '') // Devuelve el resultado
      .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') // Instrucción
      .replace(/"/g,'&quot;').replace(/'/g,'&#039;'); // Instrucción
  }

  function fmtDate(ds) { // Función
    return new Date(ds).toLocaleDateString('es-ES', { // Devuelve el resultado
      day:'2-digit', month:'short', year:'numeric', // Instrucción
      hour:'2-digit', minute:'2-digit' // Instrucción
    });
  }

  function fmtNumber(n) { // Función
    n = Number(n) || 0; // Asigna un valor
    return n >= 1000 ? (n / 1000).toFixed(1) + 'k' : String(n); // Devuelve el resultado
  }

  function catClass(cat) { // Función
    return ['fps','moba','hardware','noticias','estrategia'].includes(cat) ? `cat-${cat}` : 'cat-default'; // Devuelve el resultado
  }

  /* ═══════════════════════════════════════════════════════════
     TOAST NOTIFICATIONS
  ═══════════════════════════════════════════════════════════ */

  function toast(msg, type = 'info') { // Función
    const wrap = document.querySelector('[data-toast-wrap]'); // Declara una variable
    if (!wrap) return; // Condición
    const el = document.createElement('div'); // Declara una variable
    el.className = `toast toast-${type}`; // Asigna un valor
    el.textContent = msg; // Fija el texto del elemento
    wrap.appendChild(el); // Añade el elemento al DOM
    setTimeout(() => { // Temporizador
      el.classList.add('toast-exit'); // Cambia clases CSS
      setTimeout(() => el.remove(), 380); // Elimina el elemento del DOM
    }, 3400); // Instrucción
  }

  /* ═══════════════════════════════════════════════════════════
     API
  ═══════════════════════════════════════════════════════════ */

  async function api(action, method = 'GET', body = null, params = {}) { // Función
    const qs   = new URLSearchParams({ action, ...params }); // Declara una variable
    const opts = { method, headers: {} }; // Declara una variable
    if (method === 'POST' && body) { // Condición
      opts.headers['Content-Type'] = 'application/json'; // Instrucción
      opts.body = JSON.stringify(body); // Asigna un valor
    }
    try { // Instrucción
      const res = await fetch(`${API_URL}?${qs}`, opts); // Declara una variable
      return await res.json(); // Devuelve el resultado
    } catch { // Instrucción
      return null; // Devuelve el resultado
    }
  }

  /* ═══════════════════════════════════════════════════════════
     CURRENT USER
  ═══════════════════════════════════════════════════════════ */

  function getUser() { // Función
    const u = window.NEXUS_CURRENT_USER; // Declara una variable
    if (!u) return null; // Condición
    return { ...u, name: u.nombre || 'Usuario', favoriteGame: u.favorite_game || 'Gaming' }; // Devuelve el resultado
  }

  /* ═══════════════════════════════════════════════════════════
     SCROLL REVEAL
  ═══════════════════════════════════════════════════════════ */

  function setupScrollReveal() { // Función
    if (!('IntersectionObserver' in window)) { // Condición
      document.querySelectorAll('.reveal-on-scroll').forEach(el => el.classList.add('revealed')); // Busca elemento(s) en el DOM
      return; // Devuelve el resultado
    }
    const io = new IntersectionObserver((entries) => { // Función flecha
      entries.forEach(entry => { // Recorre la lista
        if (entry.isIntersecting) { // Condición
          entry.target.classList.add('revealed'); // Cambia clases CSS
          io.unobserve(entry.target); // Llama a una función
        }
      });
    }, { threshold: 0.08 }); // Instrucción
    document.querySelectorAll('.reveal-on-scroll').forEach(el => io.observe(el)); // Busca elemento(s) en el DOM
  }

  /* ═══════════════════════════════════════════════════════════
     MOBILE NAV
  ═══════════════════════════════════════════════════════════ */

  function setupMobileNav() { // Función
    const btn = document.querySelector('[data-hamburger]'); // Declara una variable
    const nav = document.querySelector('[data-mobile-nav]'); // Declara una variable
    if (!btn || !nav) return; // Condición
    btn.addEventListener('click', () => { // Escucha un evento
      const open = nav.classList.toggle('is-open'); // Declara una variable
      btn.classList.toggle('is-open', open); // Cambia clases CSS
    });
    nav.querySelectorAll('a').forEach(a => a.addEventListener('click', () => { // Escucha un evento
      nav.classList.remove('is-open'); // Cambia clases CSS
      btn.classList.remove('is-open'); // Cambia clases CSS
    })); // Instrucción
  }

  /* ═══════════════════════════════════════════════════════════
     AUTH SLOT (header right side)
  ═══════════════════════════════════════════════════════════ */

  function renderAuthSlot() { // Función
    const slot = document.querySelector('[data-auth-slot]'); // Declara una variable
    if (!slot) return; // Condición
    const user = getUser(); // Declara una variable

    if (!user) { // Condición
      slot.innerHTML = `<a class="btn-nav-login" href="login.php">Iniciar sesión</a>`; // Inyecta HTML en el elemento
      return; // Devuelve el resultado
    }

    const roleLabel = user.role === 'admin' ? 'Admin' : 'Miembro'; // Declara una variable
    slot.innerHTML = `
      <div class="user-chip">
        <span class="chip-ava" style="${avatarStyle(user.username || user.name)}">${avatarInitial(user.username || user.name)}</span>
        <div>
          <span class="chip-name">${esc(user.username || user.name)}</span>
          <span class="chip-role">${roleLabel}</span>
        </div>
        <button class="btn-chip-out" type="button" data-logout>Salir</button>
      </div>
    `; // Instrucción
    slot.querySelector('[data-logout]')?.addEventListener('click', () => { // Escucha un evento
      window.location.href = 'logout.php'; // Asigna un valor
    });
  }

  /* ═══════════════════════════════════════════════════════════
     STATS BAR
  ═══════════════════════════════════════════════════════════ */

  async function updateStats() { // Función
    const stats = await api('get_stats'); // Declara una variable
    if (!stats) return; // Condición

    const set = (sel, val) => { // Función flecha
      const el = document.querySelector(sel); // Declara una variable
      if (el) el.textContent = fmtNumber(val || 0); // Condición
    };

    set('[data-stat-posts]',    stats.posts); // Llama a una función
    set('[data-stat-users]',    stats.users); // Llama a una función
    set('[data-stat-online]',   Math.max(1, Math.min(stats.users || 0, (stats.posts || 0) + 2))); // Llama a una función
    set('[data-approved-count]',stats.posts); // Llama a una función
    set('[data-pending-count]', stats.pending); // Llama a una función
    set('[data-likes-count]',   stats.likes); // Llama a una función
  }

  /* ═══════════════════════════════════════════════════════════
     SESSION PANEL (sidebar)
  ═══════════════════════════════════════════════════════════ */

  function renderSessionPanel() { // Función
    const panel = document.querySelector('[data-session-panel]'); // Declara una variable
    if (!panel) return; // Condición
    const user = getUser(); // Declara una variable

    if (!user) { // Condición
      panel.innerHTML = `
        <p style="font-size:0.84rem;color:var(--white-dim);margin:0 0 14px;line-height:1.65;">
          Inicia sesión para publicar temas, dar likes y acceder a tu perfil.
        </p>
        <a class="btn btn-primary btn-sm" href="login.php" style="width:100%;justify-content:center;margin-bottom:8px;">Iniciar sesión</a>
        <a class="btn btn-ghost btn-sm" href="register.php" style="width:100%;justify-content:center;">Crear cuenta gratis</a>
      `; // Instrucción
      return; // Devuelve el resultado
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
    `; // Instrucción
  }

  /* ═══════════════════════════════════════════════════════════
     POSTS FEED
  ═══════════════════════════════════════════════════════════ */

  async function renderPosts() { // Función
    const list  = document.querySelector('[data-post-list]'); // Declara una variable
    const empty = document.querySelector('[data-empty-state]'); // Declara una variable
    if (!list) return; // Condición

    const user     = getUser(); // Declara una variable
    const category = document.querySelector('[data-category-filters] .cat-btn.is-active')?.dataset.category || 'all'; // Declara una variable
    const search   = (document.querySelector('[data-search-input]')?.value || '').trim(); // Declara una variable
    const sort     = document.querySelector('[data-sort-select]')?.value || 'recent'; // Declara una variable

    const params = { sort }; // Declara una variable
    if (category !== 'all') params.category = category; // Condición
    if (search) params.search = search; // Condición

    const result = await api('get_posts', 'GET', null, params); // Declara una variable
    if (!result) return; // Condición
    const posts = Array.isArray(result) ? result : (result.data ?? []); // Declara una variable

    if (!posts.length) { // Condición
      list.innerHTML = ''; // Inyecta HTML en el elemento
      empty?.classList.remove('hidden'); // Cambia clases CSS
      return; // Devuelve el resultado
    }

    empty?.classList.add('hidden'); // Cambia clases CSS

    const isAuthor = p => user && Number(user.id) === Number(p.author_id); // Función flecha

    list.innerHTML = posts.map(p => `
      <article class="post-card">
        <div class="post-vote">
          <button class="vote-btn" type="button" data-like-post="${p.id}" title="Me gusta esta publicación">+1</button>
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
            <div class="post-meta">
              <div class="post-author">
                <span class="avatar ava-sm" style="${avatarStyle(p.username)}">${avatarInitial(p.username)}</span>
                <span class="post-author-name">@${esc(p.username || 'anon')}</span>
              </div>
              <span class="post-dot" aria-hidden="true">·</span>
              <span class="post-game">${esc(p.favorite_game || 'Gaming')}</span>
              <span class="post-dot" aria-hidden="true">·</span>
              <span class="post-date">${fmtDate(p.created_at)}</span>
            </div>
            <div class="post-actions">
              ${user
                ? `<button class="btn btn-ghost btn-sm" type="button" data-open-composer>Responder</button>`
                : `<a class="btn btn-ghost btn-sm" href="login.php">Responder</a>`}
            </div>
          </div>
        </div>
      </article>
    `).join(''); // Instrucción

    list.querySelectorAll('[data-like-post]').forEach(btn => // Busca elemento(s) en el DOM
      btn.addEventListener('click', () => likePost(btn.dataset.likePost, btn)) // Escucha un evento
    ); // Instrucción
    list.querySelectorAll('[data-open-composer]').forEach(btn => // Busca elemento(s) en el DOM
      btn.addEventListener('click', () => toggleComposer(true)) // Escucha un evento
    ); // Instrucción
  }

  async function likePost(id, btn) { // Función
    if (!getUser()) { toast('Inicia sesión para dar likes', 'info'); return; } // Condición
    if (btn) { // Condición
      btn.style.color        = 'var(--accent)'; // Asigna un valor
      btn.style.borderColor  = 'var(--accent)'; // Asigna un valor
      btn.style.textShadow   = '0 0 8px rgba(200,216,240,0.5)'; // Asigna un valor
    }
    await api('like_post', 'POST', { post_id: parseInt(id) }); // Llama a la API y espera la respuesta
    renderPosts(); // Llama a una función
    updateStats(); // Llama a una función
  }

  /* ═══════════════════════════════════════════════════════════
     POST COMPOSER
  ═══════════════════════════════════════════════════════════ */

  function toggleComposer(forceOpen) { // Función
    const panel = document.querySelector('[data-composer-panel]'); // Declara una variable
    if (!panel) return; // Condición
    if (!getUser()) { window.location.href = 'login.php'; return; } // Condición
    if (forceOpen === true) panel.classList.remove('hidden'); // Condición
    else panel.classList.toggle('hidden'); // Caso alternativo
  }

  function setNotice(el, msg, type) { // Función
    if (!el) return; // Condición
    el.textContent = msg; // Fija el texto del elemento
    el.className   = `notice-inline is-${type}`; // Asigna un valor
  }

  function setupComposer() { // Función
    document.querySelector('[data-open-composer]')?.addEventListener('click', () => toggleComposer()); // Escucha un evento
    document.querySelector('[data-close-composer]')?.addEventListener('click', () => { // Escucha un evento
      document.querySelector('[data-composer-panel]')?.classList.add('hidden'); // Busca elemento(s) en el DOM
    });

    const form = document.querySelector('[data-post-form]'); // Declara una variable
    if (!form) return; // Condición

    form.addEventListener('submit', async e => { // Escucha un evento
      e.preventDefault(); // Cancela el comportamiento por defecto
      const notice = form.querySelector('[data-post-notice]'); // Declara una variable
      const user   = getUser(); // Declara una variable
      if (!user) { window.location.href = 'login.php'; return; } // Condición

      const fd = new FormData(form); // Declara una variable
      const payload = { // Declara una variable
        title:    String(fd.get('title')   || '').trim(), // Instrucción
        content:  String(fd.get('content') || '').trim(), // Instrucción
        category: String(fd.get('category')|| 'fps') // Instrucción
      };

      if (payload.title.length < 6) { // Condición
        setNotice(notice, 'El título debe tener al menos 6 caracteres.', 'error'); // Llama a una función
        return; // Devuelve el resultado
      }
      if (payload.content.length < 12) { // Condición
        setNotice(notice, 'El contenido debe tener al menos 12 caracteres.', 'error'); // Llama a una función
        return; // Devuelve el resultado
      }

      const submitBtn = form.querySelector('[type="submit"]'); // Declara una variable
      submitBtn.disabled    = true; // Asigna un valor
      submitBtn.textContent = 'Publicando…'; // Fija el texto del elemento

      const res = await api('add_post', 'POST', payload); // Declara una variable
      submitBtn.disabled    = false; // Asigna un valor
      submitBtn.textContent = 'Publicar tema'; // Fija el texto del elemento

      if (res?.success) { // Condición
        form.reset(); // Llama a una función
        const msg = user.role === 'admin' // Declara una variable
          ? 'Tema publicado correctamente.' // Instrucción
          : 'Tema enviado — pendiente de aprobación.'; // Instrucción
        setNotice(notice, msg, 'success'); // Llama a una función
        toast(user.role === 'admin' ? 'Tema publicado' : 'Enviado — pendiente de aprobación', 'success'); // Muestra una notificación
        renderPosts(); // Llama a una función
        updateStats(); // Llama a una función
        setTimeout(() => { notice.className = 'notice-inline'; notice.textContent = ''; }, 5000); // Fija el texto del elemento
      } else { // Instrucción
        setNotice(notice, res?.error || 'No se pudo publicar el tema.', 'error'); // Llama a una función
      }
    });
  }

  /* ═══════════════════════════════════════════════════════════
     CATEGORY FILTERS
  ═══════════════════════════════════════════════════════════ */

  function setupFilters() { // Función
    document.querySelectorAll('[data-category-filters] .cat-btn').forEach(btn => { // Busca elemento(s) en el DOM
      btn.addEventListener('click', () => { // Escucha un evento
        document.querySelectorAll('[data-category-filters] .cat-btn').forEach(b => b.classList.remove('is-active')); // Busca elemento(s) en el DOM
        btn.classList.add('is-active'); // Cambia clases CSS
        renderPosts(); // Llama a una función
      });
    });
    document.querySelector('[data-search-input]')?.addEventListener('input', renderPosts); // Escucha un evento
    document.querySelector('[data-sort-select]')?.addEventListener('change', renderPosts); // Escucha un evento
  }

  /* ═══════════════════════════════════════════════════════════
     CONTACT FORM
  ═══════════════════════════════════════════════════════════ */

  function setupContactForm() { // Función
    const form = document.querySelector('[data-contact-form]'); // Declara una variable
    if (!form) return; // Condición

    form.addEventListener('submit', async e => { // Escucha un evento
      e.preventDefault(); // Cancela el comportamiento por defecto
      const notice = form.querySelector('[data-contact-notice]'); // Declara una variable
      const fd     = new FormData(form); // Declara una variable
      const name    = String(fd.get('name')    || '').trim(); // Declara una variable
      const email   = String(fd.get('email')   || '').trim(); // Declara una variable
      const subject = String(fd.get('subject') || '').trim(); // Declara una variable
      const message = String(fd.get('message') || '').trim(); // Declara una variable

      if (!name || !email || !subject || !message) { // Condición
        setNotice(notice, 'Por favor, completa todos los campos.', 'error'); // Llama a una función
        return; // Devuelve el resultado
      }
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { // Condición
        setNotice(notice, 'Introduce un email válido.', 'error'); // Llama a una función
        return; // Devuelve el resultado
      }

      const submitBtn = form.querySelector('[type="submit"]'); // Declara una variable
      submitBtn.disabled    = true; // Asigna un valor
      submitBtn.textContent = 'Enviando…'; // Fija el texto del elemento

      try { // Instrucción
        const res  = await fetch('process_contact.php', { // Declara una variable
          method:  'POST', // Instrucción
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, // Instrucción
          body:    new URLSearchParams({ name, email, subject, message }) // Instrucción
        });
        const data = await res.json(); // Declara una variable
        submitBtn.disabled    = false; // Asigna un valor
        submitBtn.textContent = 'Enviar mensaje'; // Fija el texto del elemento

        if (data.success) { // Condición
          form.reset(); // Llama a una función
          setNotice(notice, '¡Mensaje recibido! Te responderemos pronto.', 'success'); // Llama a una función
          toast('Mensaje enviado — guardado en base de datos', 'success'); // Muestra una notificación
          setTimeout(() => { notice.className = 'notice-inline'; notice.textContent = ''; }, 6000); // Fija el texto del elemento
        } else { // Instrucción
          setNotice(notice, data.error || 'No se pudo enviar el mensaje.', 'error'); // Llama a una función
        }
      } catch { // Instrucción
        submitBtn.disabled    = false; // Asigna un valor
        submitBtn.textContent = 'Enviar mensaje'; // Fija el texto del elemento
        setNotice(notice, 'Error de conexión. Inténtalo de nuevo.', 'error'); // Llama a una función
      }
    });
  }

  /* ═══════════════════════════════════════════════════════════
     MEMBERS SECTION (public list on index page)
  ═══════════════════════════════════════════════════════════ */

  async function renderMembersList() { // Función
    const wrap = document.querySelector('[data-members-list]'); // Declara una variable
    if (!wrap) return; // Condición

    const users = await api('get_users'); // Declara una variable
    if (!Array.isArray(users) || !users.length) return; // Condición

    wrap.innerHTML = users.slice(0, 12).map(u => `
      <div class="member-card reveal-on-scroll">
        <span class="avatar ava-lg" style="${avatarStyle(u.username)}">${avatarInitial(u.username)}</span>
        <div class="member-card-info">
          <strong class="member-card-name">${esc(u.username)}</strong>
          <span class="member-card-game">${esc(u.favorite_game || 'Gaming')}</span>
          ${u.bio ? `<p class="member-card-bio">${esc(u.bio)}</p>` : ''}
        </div>
        <span class="badge ${u.role === 'admin' ? 'badge-admin' : 'badge-member'}">${u.role === 'admin' ? 'Admin' : 'Miembro'}</span>
      </div>
    `).join(''); // Instrucción

    setupScrollReveal(); // Llama a una función
  }

  async function renderAdminPosts() { // Función
    const c   = document.querySelector('[data-admin-posts]'); // Declara una variable
    const cnt = document.querySelector('[data-posts-count]'); // Declara una variable
    if (!c) return; // Condición

    const result = await api('get_all_posts'); // Declara una variable
    const posts  = Array.isArray(result) ? result : (result?.data ?? []); // Declara una variable
    if (!posts.length) { // Condición
      c.innerHTML = '<div style="padding:20px 18px;color:var(--white-faint);font-size:0.86rem;font-family:var(--font-ui);">No hay temas todavía.</div>'; // Inyecta HTML en el elemento
      return; // Devuelve el resultado
    }

    if (cnt) cnt.textContent = `${posts.length} temas`; // Condición

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
    `).join(''); // Instrucción

    c.querySelectorAll('[data-approve-post]').forEach(btn => // Busca elemento(s) en el DOM
      btn.addEventListener('click', async () => { // Escucha un evento
        await api('approve_post', 'POST', { post_id: parseInt(btn.dataset.approvePost), approved: 1 }); // Llama a la API y espera la respuesta
        toast('Tema aprobado — visible en el foro', 'success'); // Muestra una notificación
        renderAdminPosts(); updateAdminMetrics(); // Llama a una función
      }) // Instrucción
    ); // Instrucción
    c.querySelectorAll('[data-reject-post]').forEach(btn => // Busca elemento(s) en el DOM
      btn.addEventListener('click', async () => { // Escucha un evento
        await api('approve_post', 'POST', { post_id: parseInt(btn.dataset.rejectPost), approved: 0 }); // Llama a la API y espera la respuesta
        toast('Tema rechazado', 'info'); // Muestra una notificación
        renderAdminPosts(); updateAdminMetrics(); // Llama a una función
      }) // Instrucción
    ); // Instrucción
    c.querySelectorAll('[data-delete-post]').forEach(btn => // Busca elemento(s) en el DOM
      btn.addEventListener('click', async () => { // Escucha un evento
        if (!confirm('¿Eliminar este tema definitivamente?')) return; // Condición
        await api('delete_post', 'POST', { post_id: parseInt(btn.dataset.deletePost) }); // Llama a la API y espera la respuesta
        toast('Tema eliminado', 'error'); // Muestra una notificación
        renderAdminPosts(); updateAdminMetrics(); // Llama a una función
      }) // Instrucción
    ); // Instrucción
  }

  /* ═══════════════════════════════════════════════════════════
     LEADERBOARD — extended with API leaderboard endpoint
  ═══════════════════════════════════════════════════════════ */

  async function renderLeaderboardExtended() { // Función
    const list = document.querySelector('[data-user-list]'); // Declara una variable
    if (!list) return; // Condición

    const data = await api('get_leaderboard', 'GET', null, { limit: 5 }); // Declara una variable
    const rows = Array.isArray(data) ? data : []; // Declara una variable

    if (!rows.length) { // Condición
      list.innerHTML = '<p style="font-size:0.82rem;color:var(--white-faint);padding:4px 0;">Sin datos aún.</p>'; // Inyecta HTML en el elemento
      return; // Devuelve el resultado
    }

    const rankClass = i => i === 0 ? 'r1' : i === 1 ? 'r2' : i === 2 ? 'r3' : ''; // Función flecha

    list.innerHTML = rows.map((u, i) => `
      <div class="leader-row">
        <span class="leader-num ${rankClass(i)}">${i + 1}</span>
        <span class="avatar ava-sm" style="${avatarStyle(u.username)}">${avatarInitial(u.username)}</span>
        <div class="leader-info">
          <div class="leader-name">${esc(u.username)}</div>
          <div class="leader-sub">${u.post_count} tema${u.post_count !== 1 ? 's' : ''}</div>
        </div>
        <span class="leader-badge">${fmtNumber(u.total_likes)} likes</span>
      </div>
    `).join(''); // Instrucción
  }

  /* ═══════════════════════════════════════════════════════════
     SEARCH — debounced live search
  ═══════════════════════════════════════════════════════════ */

  let searchTimer = null; // Declara una variable

  function debounce(fn, delay) { // Función
    return function (...args) { // Devuelve el resultado
      clearTimeout(searchTimer); // Llama a una función
      searchTimer = setTimeout(() => fn.apply(this, args), delay); // Temporizador
    };
  }

  function setupSearchDebounce() { // Función
    const input = document.querySelector('[data-search-input]'); // Declara una variable
    if (!input) return; // Condición
    const debouncedRender = debounce(renderPosts, 380); // Declara una variable
    input.removeEventListener('input', renderPosts); // Llama a una función
    input.addEventListener('input', debouncedRender); // Escucha un evento

    input.addEventListener('keydown', e => { // Escucha un evento
      if (e.key === 'Escape') { // Condición
        input.value = ''; // Asigna un valor
        renderPosts(); // Llama a una función
        input.blur(); // Llama a una función
      }
    });
  }

  /* ═══════════════════════════════════════════════════════════
     CHAR COUNTER — textarea live counter
  ═══════════════════════════════════════════════════════════ */

  function setupCharCounters() { // Función
    document.querySelectorAll('[data-char-counter]').forEach(wrap => { // Busca elemento(s) en el DOM
      const textarea = wrap.querySelector('textarea, input'); // Declara una variable
      const counter  = wrap.querySelector('[data-char-count-display]'); // Declara una variable
      if (!textarea || !counter) return; // Condición
      const max = parseInt(textarea.getAttribute('maxlength') || '0', 10); // Declara una variable
      const update = () => { // Función flecha
        const len = textarea.value.length; // Declara una variable
        counter.textContent = max ? `${len} / ${max}` : String(len); // Fija el texto del elemento
        counter.classList.toggle('is-near-limit', max > 0 && len >= max * 0.85); // Cambia clases CSS
        counter.classList.toggle('is-over-limit',  max > 0 && len >= max); // Cambia clases CSS
      };
      textarea.addEventListener('input', update); // Escucha un evento
      update(); // Llama a una función
    });
  }

  /* ═══════════════════════════════════════════════════════════
     BACK TO TOP BUTTON
  ═══════════════════════════════════════════════════════════ */

  function setupBackToTop() { // Función
    const btn = document.querySelector('[data-back-to-top]'); // Declara una variable
    if (!btn) return; // Condición
    const toggleVisibility = () => { // Función flecha
      btn.classList.toggle('is-visible', window.scrollY > 400); // Cambia clases CSS
    };
    window.addEventListener('scroll', toggleVisibility, { passive: true }); // Escucha un evento
    btn.addEventListener('click', () => { // Escucha un evento
      window.scrollTo({ top: 0, behavior: 'smooth' }); // Llama a una función
    });
  }

  /* ═══════════════════════════════════════════════════════════
     STICKY HEADER — hide on scroll down, show on scroll up
  ═══════════════════════════════════════════════════════════ */

  function setupStickyHeader() { // Función
    const header = document.querySelector('.site-header'); // Declara una variable
    if (!header) return; // Condición
    let lastScroll = 0; // Declara una variable
    let ticking    = false; // Declara una variable

    window.addEventListener('scroll', () => { // Escucha un evento
      if (!ticking) { // Condición
        requestAnimationFrame(() => { // Función de callback
          const cur = window.scrollY; // Declara una variable
          if (cur > lastScroll && cur > 120) { // Condición
            header.classList.add('header-hidden'); // Cambia clases CSS
          } else { // Instrucción
            header.classList.remove('header-hidden'); // Cambia clases CSS
          }
          lastScroll = cur; // Asigna un valor
          ticking    = false; // Asigna un valor
        });
        ticking = true; // Asigna un valor
      }
    }, { passive: true }); // Instrucción
  }

  /* ═══════════════════════════════════════════════════════════
     SMOOTH SCROLL — anchor links (#section)
  ═══════════════════════════════════════════════════════════ */

  function setupSmoothScroll() { // Función
    document.querySelectorAll('a[href^="#"]').forEach(link => { // Busca elemento(s) en el DOM
      link.addEventListener('click', e => { // Escucha un evento
        const id = link.getAttribute('href').slice(1); // Declara una variable
        if (!id) return; // Condición
        const target = document.getElementById(id); // Declara una variable
        if (!target) return; // Condición
        e.preventDefault(); // Cancela el comportamiento por defecto
        target.scrollIntoView({ behavior: 'smooth', block: 'start' }); // Llama a una función
        history.replaceState(null, '', `#${id}`); // Llama a una función
      });
    });
  }

  /* ═══════════════════════════════════════════════════════════
     KEYBOARD SHORTCUTS
  ═══════════════════════════════════════════════════════════ */

  function setupKeyboardShortcuts() { // Función
    document.addEventListener('keydown', e => { // Escucha un evento
      const tag = document.activeElement?.tagName?.toLowerCase(); // Declara una variable
      const inInput = ['input', 'textarea', 'select'].includes(tag); // Declara una variable

      /* Ctrl/Cmd + K → focus search */
      if ((e.ctrlKey || e.metaKey) && e.key === 'k') { // Condición
        e.preventDefault(); // Cancela el comportamiento por defecto
        const searchEl = document.querySelector('[data-search-input]'); // Declara una variable
        if (searchEl) { searchEl.focus(); searchEl.select(); } // Condición
        return; // Devuelve el resultado
      }

      /* Escape → close any open panels / composer */
      if (e.key === 'Escape' && !inInput) { // Condición
        document.querySelector('[data-composer-panel]:not(.hidden)')?.classList.add('hidden'); // Busca elemento(s) en el DOM
        document.querySelector('.modal-overlay.is-open')?.classList.remove('is-open'); // Busca elemento(s) en el DOM
        return; // Devuelve el resultado
      }

      /* N → open composer (not in input) */
      if (e.key === 'n' && !inInput && !e.ctrlKey && !e.metaKey) { // Condición
        const page = document.body.dataset.page; // Declara una variable
        if (page === 'home') { // Condición
          toggleComposer(); // Llama a una función
        }
        return; // Devuelve el resultado
      }

      /* T → back to top */
      if (e.key === 't' && !inInput && !e.ctrlKey && !e.metaKey) { // Condición
        window.scrollTo({ top: 0, behavior: 'smooth' }); // Llama a una función
        return; // Devuelve el resultado
      }

      /* F → focus search */
      if (e.key === 'f' && !inInput && !e.ctrlKey && !e.metaKey) { // Condición
        const searchEl = document.querySelector('[data-search-input]'); // Declara una variable
        if (searchEl) { searchEl.focus(); e.preventDefault(); } // Condición
        return; // Devuelve el resultado
      }
    });
  }

  /* ═══════════════════════════════════════════════════════════
     LOCAL STORAGE PREFERENCES
  ═══════════════════════════════════════════════════════════ */

  const PREFS_KEY = 'nexus_prefs_v1'; // Declara una variable

  function loadPrefs() { // Función
    try { // Instrucción
      return JSON.parse(localStorage.getItem(PREFS_KEY) || '{}'); // Devuelve el resultado
    } catch { // Instrucción
      return {}; // Devuelve el resultado
    }
  }

  function savePrefs(delta) { // Función
    try { // Instrucción
      const prefs = { ...loadPrefs(), ...delta }; // Declara una variable
      localStorage.setItem(PREFS_KEY, JSON.stringify(prefs)); // Llama a una función
    } catch { /* storage unavailable */ }
  }

  function applyPrefs() { // Función
    const prefs = loadPrefs(); // Declara una variable

    /* Restore last selected category */
    if (prefs.category) { // Condición
      const btn = document.querySelector(`[data-category-filters] [data-category="${prefs.category}"]`); // Declara una variable
      if (btn) { // Condición
        document.querySelectorAll('[data-category-filters] .cat-btn').forEach(b => b.classList.remove('is-active')); // Busca elemento(s) en el DOM
        btn.classList.add('is-active'); // Cambia clases CSS
      }
    }

    /* Restore last selected sort */
    const sortEl = document.querySelector('[data-sort-select]'); // Declara una variable
    if (sortEl && prefs.sort) sortEl.value = prefs.sort; // Condición
  }

  function persistFilterPrefs() { // Función
    document.querySelectorAll('[data-category-filters] .cat-btn').forEach(btn => { // Busca elemento(s) en el DOM
      btn.addEventListener('click', () => savePrefs({ category: btn.dataset.category })); // Escucha un evento
    });
    const sortEl = document.querySelector('[data-sort-select]'); // Declara una variable
    if (sortEl) sortEl.addEventListener('change', () => savePrefs({ sort: sortEl.value })); // Condición
  }

  /* ═══════════════════════════════════════════════════════════
     ADMIN — delete contact messages
  ═══════════════════════════════════════════════════════════ */

  async function renderAdminContactsExtended() { // Función
    const c   = document.querySelector('[data-admin-contacts-list]'); // Declara una variable
    const cnt = document.querySelector('[data-contacts-count]'); // Declara una variable
    if (!c) return; // Condición

    const result = await api('get_contacts'); // Declara una variable
    const contacts = Array.isArray(result) ? result : (result?.data ?? []); // Declara una variable

    if (!contacts.length) { // Condición
      c.innerHTML = '<div style="padding:20px 18px;color:var(--white-faint);font-size:0.86rem;font-family:var(--font-ui);">No hay mensajes de contacto.</div>'; // Inyecta HTML en el elemento
      return; // Devuelve el resultado
    }

    if (cnt) cnt.textContent = `${contacts.length} mensajes`; // Condición

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
    `).join(''); // Instrucción

    c.querySelectorAll('[data-read-contact]').forEach(btn => // Busca elemento(s) en el DOM
      btn.addEventListener('click', async () => { // Escucha un evento
        await api('mark_contact_read', 'POST', { contact_id: parseInt(btn.dataset.readContact) }); // Llama a la API y espera la respuesta
        toast('Mensaje marcado como leído', 'info'); // Muestra una notificación
        renderAdminContactsExtended(); // Llama a una función
        updateAdminMetrics(); // Llama a una función
      }) // Instrucción
    ); // Instrucción

    c.querySelectorAll('[data-delete-contact]').forEach(btn => // Busca elemento(s) en el DOM
      btn.addEventListener('click', async () => { // Escucha un evento
        if (!confirm('¿Eliminar este mensaje de contacto definitivamente?')) return; // Condición
        const row = c.querySelector(`[data-contact-row="${btn.dataset.deleteContact}"]`); // Declara una variable
        if (row) { row.style.opacity = '0.4'; row.style.pointerEvents = 'none'; } // Condición
        const res = await api('delete_contact', 'POST', { contact_id: parseInt(btn.dataset.deleteContact) }); // Declara una variable
        if (res?.success) { // Condición
          toast('Mensaje eliminado', 'error'); // Muestra una notificación
          renderAdminContactsExtended(); // Llama a una función
          updateAdminMetrics(); // Llama a una función
        } else { // Instrucción
          if (row) { row.style.opacity = ''; row.style.pointerEvents = ''; } // Condición
          toast('Error al eliminar', 'error'); // Muestra una notificación
        }
      }) // Instrucción
    ); // Instrucción
  }

  /* ═══════════════════════════════════════════════════════════
     ADMIN — user role management
  ═══════════════════════════════════════════════════════════ */

  async function renderAdminUsersExtended() { // Función
    const c   = document.querySelector('[data-admin-users-list]'); // Declara una variable
    const cnt = document.querySelector('[data-users-count]'); // Declara una variable
    if (!c) return; // Condición

    const users = await api('get_users'); // Declara una variable
    if (!Array.isArray(users) || !users.length) { // Condición
      c.innerHTML = '<div style="padding:20px 18px;color:var(--white-faint);font-size:0.86rem;font-family:var(--font-ui);">Sin usuarios.</div>'; // Inyecta HTML en el elemento
      return; // Devuelve el resultado
    }

    if (cnt) cnt.textContent = `${users.length} usuarios`; // Condición
    const me = getUser(); // Declara una variable

    c.innerHTML = users.map(u => { // Inyecta HTML en el elemento
      const isSelf  = me && String(me.id) === String(u.id); // Declara una variable
      const isAdmin = u.role === 'admin'; // Declara una variable
      return `
        <div class="admin-row" data-user-row="${u.id}">
          <span class="avatar ava-sm" style="${avatarStyle(u.username)}">${avatarInitial(u.username)}</span>
          <div class="admin-row-main">
            <strong>${esc(u.username)}</strong>
            <div class="admin-meta">
              <span style="color:var(--white-dim);">${esc(u.nombre || u.username)}</span>
              <span style="color:var(--white-ghost);font-size:0.72rem;">${esc(u.email || '')}</span>
              <span class="badge ${isAdmin ? 'badge-admin' : 'badge-member'}">${esc(u.role)}</span>
              <span style="font-size:0.72rem;color:var(--white-faint);">${esc(u.favorite_game || 'Gaming')}</span>
              <span style="font-size:0.72rem;color:var(--white-faint);">${fmtNumber(u.total_likes || 0)} likes · ${u.post_count || 0} posts</span>
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
      `; // Instrucción
    }).join(''); // Instrucción

    c.querySelectorAll('[data-promote-user]').forEach(btn => // Busca elemento(s) en el DOM
      btn.addEventListener('click', async () => { // Escucha un evento
        if (!confirm('¿Promover a este usuario a administrador?')) return; // Condición
        const res = await api('update_user_role', 'POST', { user_id: parseInt(btn.dataset.promoteUser), role: 'admin' }); // Declara una variable
        if (res?.success) { toast('Usuario promovido a admin', 'success'); renderAdminUsersExtended(); } // Condición
        else toast(res?.error || 'Error al promover', 'error'); // Caso alternativo
      }) // Instrucción
    ); // Instrucción

    c.querySelectorAll('[data-demote-user]').forEach(btn => // Busca elemento(s) en el DOM
      btn.addEventListener('click', async () => { // Escucha un evento
        if (!confirm('¿Degradar a este administrador a miembro?')) return; // Condición
        const res = await api('update_user_role', 'POST', { user_id: parseInt(btn.dataset.demoteUser), role: 'member' }); // Declara una variable
        if (res?.success) { toast('Rol actualizado a miembro', 'info'); renderAdminUsersExtended(); } // Condición
        else toast(res?.error || 'Error al degradar', 'error'); // Caso alternativo
      }) // Instrucción
    ); // Instrucción

    c.querySelectorAll('[data-delete-user]').forEach(btn => // Busca elemento(s) en el DOM
      btn.addEventListener('click', async () => { // Escucha un evento
        if (!confirm('¿Eliminar este usuario y todos sus posts? Esta acción no se puede deshacer.')) return; // Condición
        const row = c.querySelector(`[data-user-row="${btn.dataset.deleteUser}"]`); // Declara una variable
        if (row) { row.style.opacity = '0.4'; row.style.pointerEvents = 'none'; } // Condición
        const res = await api('delete_user', 'POST', { user_id: parseInt(btn.dataset.deleteUser) }); // Declara una variable
        if (res?.success) { // Condición
          toast('Usuario eliminado', 'error'); // Muestra una notificación
          renderAdminUsersExtended(); // Llama a una función
          updateAdminMetrics(); // Llama a una función
        } else { // Instrucción
          if (row) { row.style.opacity = ''; row.style.pointerEvents = ''; } // Condición
          toast(res?.error || 'Error al eliminar', 'error'); // Muestra una notificación
        }
      }) // Instrucción
    ); // Instrucción
  }

  /* ═══════════════════════════════════════════════════════════
     ADMIN — update metrics panel
  ═══════════════════════════════════════════════════════════ */

  async function updateAdminMetrics() { // Función
    const stats = await api('get_stats'); // Declara una variable
    if (!stats) return; // Condición
    const s = (sel, v) => { // Función flecha
      const el = document.querySelector(sel); // Declara una variable
      if (el) el.textContent = fmtNumber(v || 0); // Condición
    };
    s('[data-admin-users]',    stats.users); // Llama a una función
    s('[data-admin-approved]', stats.posts); // Llama a una función
    s('[data-admin-pending]',  stats.pending); // Llama a una función
    s('[data-admin-contacts]', stats.contacts); // Llama a una función
    s('[data-admin-likes]',    stats.likes); // Llama a una función
  }

  /* ═══════════════════════════════════════════════════════════
     ADMIN PAGE — extended version using new render functions
  ═══════════════════════════════════════════════════════════ */

  async function renderAdminPageFull() { // Función
    const user    = getUser(); // Declara una variable
    const blocked = document.querySelector('[data-admin-blocked]'); // Declara una variable
    const content = document.querySelector('[data-admin-content]'); // Declara una variable

    if (!user || user.role !== 'admin') { // Condición
      blocked?.classList.remove('hidden'); // Cambia clases CSS
      content?.classList.add('hidden'); // Cambia clases CSS
      return; // Devuelve el resultado
    }

    content?.classList.remove('hidden'); // Cambia clases CSS
    blocked?.classList.add('hidden'); // Cambia clases CSS

    initAdminTabs(); // Llama a una función
    await updateAdminMetrics(); // Instrucción
  }

  /* ═══════════════════════════════════════════════════════════
     ADMIN — tab navigation
  ═══════════════════════════════════════════════════════════ */
  function initAdminTabs() { // Función
    const nav = document.querySelector('[data-admin-tab-nav]'); // Declara una variable
    if (!nav || nav.dataset.initialized) return; // Condición
    nav.dataset.initialized = 'true'; // Asigna un valor

    nav.addEventListener('click', async e => { // Escucha un evento
      const btn = e.target.closest('[data-admin-tab]'); // Declara una variable
      if (!btn) return; // Condición
      const tab = btn.dataset.adminTab; // Declara una variable

      nav.querySelectorAll('[data-admin-tab]').forEach(b => b.classList.remove('is-active')); // Busca elemento(s) en el DOM
      btn.classList.add('is-active'); // Cambia clases CSS

      document.querySelectorAll('[data-admin-pane]').forEach(p => { // Busca elemento(s) en el DOM
        p.classList.toggle('hidden', p.dataset.adminPane !== tab); // Cambia clases CSS
      });

      if (tab === 'moderation') { // Condición
        await Promise.all([renderAdminPosts(), renderAdminUsersExtended(), renderAdminContactsExtended()]); // Instrucción
      }
      if (tab === 'users')    initAdminUsersTab(); // Condición
      if (tab === 'backups')  initAdminBackupsTab(); // Condición
      if (tab === 'danger')   initAdminDangerTab(); // Condición
    });
  }

  /* ═══════════════════════════════════════════════════════════
     ADMIN — full users table with search + filter
  ═══════════════════════════════════════════════════════════ */
  let _usersCache = []; // Declara una variable

  function initAdminUsersTab() { // Función
    const pane = document.querySelector('[data-admin-pane="users"]'); // Declara una variable
    if (!pane || pane.dataset.initialized) return; // Condición
    pane.dataset.initialized = 'true'; // Asigna un valor

    let timer; // Declara una variable
    const searchInput = pane.querySelector('[data-user-search]'); // Declara una variable
    const roleFilter  = pane.querySelector('[data-user-role-filter]'); // Declara una variable

    searchInput?.addEventListener('input', () => { // Escucha un evento
      clearTimeout(timer); // Llama a una función
      timer = setTimeout(renderAdminUsersTable, 280); // Temporizador
    });
    roleFilter?.addEventListener('change', renderAdminUsersTable); // Escucha un evento

    renderAdminUsersTable(); // Llama a una función
  }

  async function renderAdminUsersTable() { // Función
    const container = document.querySelector('[data-admin-users-table]'); // Declara una variable
    if (!container) return; // Condición

    if (!_usersCache.length) { // Condición
      container.innerHTML = '<div style="padding:24px 20px;color:var(--white-faint);font-family:var(--font-ui);font-size:0.86rem;">Cargando usuarios…</div>'; // Inyecta HTML en el elemento
      const res = await api('get_users'); // Declara una variable
      _usersCache = Array.isArray(res) ? res : []; // Asigna un valor
    }

    const pane       = document.querySelector('[data-admin-pane="users"]'); // Declara una variable
    const searchVal  = (pane?.querySelector('[data-user-search]')?.value || '').toLowerCase(); // Declara una variable
    const roleVal    = pane?.querySelector('[data-user-role-filter]')?.value || ''; // Declara una variable
    const me         = getUser(); // Declara una variable

    const filtered = _usersCache.filter(u => { // Función flecha
      if (roleVal && u.role !== roleVal) return false; // Condición
      if (searchVal) { // Condición
        const hay = `${u.username} ${u.nombre} ${u.email || ''}`.toLowerCase(); // Declara una variable
        if (!hay.includes(searchVal)) return false; // Condición
      }
      return true; // Devuelve el resultado
    });

    if (!filtered.length) { // Condición
      container.innerHTML = '<div style="padding:24px 20px;color:var(--white-faint);font-family:var(--font-ui);font-size:0.86rem;">Sin resultados.</div>'; // Inyecta HTML en el elemento
      return; // Devuelve el resultado
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
                  <td class="tbl-num">${fmtNumber(u.total_likes || 0)}</td>
                  <td><span class="tbl-date">${fmtDate(u.fecha_registro || '')}</span></td>
                  <td>
                    ${isSelf
                      ? '<span class="tbl-self">(Tú)</span>'
                      : `<div class="tbl-actions">
                          ${!isAdmin
                            ? `<button class="btn btn-ghost btn-xs" data-promote-user="${u.id}" title="Promover a admin">Promover</button>`
                            : `<button class="btn btn-ghost btn-xs" data-demote-user="${u.id}" title="Degradar a miembro">Degradar</button>`
                          }
                          <button class="btn btn-danger btn-xs" data-delete-user="${u.id}" title="Eliminar">Eliminar</button>
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
    `; // Instrucción

    container.querySelectorAll('[data-promote-user]').forEach(btn => // Busca elemento(s) en el DOM
      btn.addEventListener('click', async () => { // Escucha un evento
        if (!confirm('¿Promover a este usuario a administrador?')) return; // Condición
        const res = await api('update_user_role', 'POST', { user_id: parseInt(btn.dataset.promoteUser), role: 'admin' }); // Declara una variable
        if (res?.success) { toast('Usuario promovido a admin', 'success'); _usersCache = []; renderAdminUsersTable(); updateAdminMetrics(); } // Condición
        else toast(res?.error || 'Error', 'error'); // Caso alternativo
      }) // Instrucción
    ); // Instrucción
    container.querySelectorAll('[data-demote-user]').forEach(btn => // Busca elemento(s) en el DOM
      btn.addEventListener('click', async () => { // Escucha un evento
        if (!confirm('¿Degradar a miembro?')) return; // Condición
        const res = await api('update_user_role', 'POST', { user_id: parseInt(btn.dataset.demoteUser), role: 'member' }); // Declara una variable
        if (res?.success) { toast('Rol actualizado a miembro', 'info'); _usersCache = []; renderAdminUsersTable(); } // Condición
        else toast(res?.error || 'Error', 'error'); // Caso alternativo
      }) // Instrucción
    ); // Instrucción
    container.querySelectorAll('[data-delete-user]').forEach(btn => // Busca elemento(s) en el DOM
      btn.addEventListener('click', async () => { // Escucha un evento
        if (!confirm('¿Eliminar este usuario y todos sus posts? Esta acción es irreversible.')) return; // Condición
        const row = container.querySelector(`[data-user-row="${btn.dataset.deleteUser}"]`); // Declara una variable
        if (row) { row.style.opacity = '0.4'; row.style.pointerEvents = 'none'; } // Condición
        const res = await api('delete_user', 'POST', { user_id: parseInt(btn.dataset.deleteUser) }); // Declara una variable
        if (res?.success) { // Condición
          toast('Usuario eliminado', 'error'); // Muestra una notificación
          _usersCache = []; // Asigna un valor
          renderAdminUsersTable(); // Llama a una función
          updateAdminMetrics(); // Llama a una función
        } else { // Instrucción
          if (row) { row.style.opacity = ''; row.style.pointerEvents = ''; } // Condición
          toast(res?.error || 'Error al eliminar', 'error'); // Muestra una notificación
        }
      }) // Instrucción
    ); // Instrucción
  }

  /* ═══════════════════════════════════════════════════════════
     ADMIN — backups tab
  ═══════════════════════════════════════════════════════════ */
  function initAdminBackupsTab() { // Función
    const pane = document.querySelector('[data-admin-pane="backups"]'); // Declara una variable
    if (!pane || pane.dataset.initialized) return; // Condición
    pane.dataset.initialized = 'true'; // Asigna un valor

    async function triggerDownload(url, btn, label) { // Función
      const original = btn.textContent; // Declara una variable
      btn.disabled = true; // Asigna un valor
      btn.textContent = 'Generando…'; // Fija el texto del elemento
      toast(label, 'info'); // Muestra una notificación
      try { // Instrucción
        const res = await fetch(url); // Declara una variable
        if (!res.ok) { // Condición
          const err = await res.json().catch(() => ({})); // Función flecha
          toast(err.error || 'Error al generar el backup', 'error'); // Muestra una notificación
          return; // Devuelve el resultado
        }
        const blob = await res.blob(); // Declara una variable
        const cd       = res.headers.get('Content-Disposition') || ''; // Declara una variable
        const match    = cd.match(/filename="?([^";\n]+)"?/); // Declara una variable
        const filename = match ? match[1] : 'nexus_backup'; // Declara una variable
        const a = document.createElement('a'); // Declara una variable
        a.href = URL.createObjectURL(blob); // Asigna un valor
        a.download = filename; // Asigna un valor
        a.style.display = 'none'; // Asigna un valor
        document.body.appendChild(a); // Añade el elemento al DOM
        a.click(); // Llama a una función
        document.body.removeChild(a); // Llama a una función
        setTimeout(() => URL.revokeObjectURL(a.href), 10000); // Temporizador
        toast('Descarga completada', 'success'); // Muestra una notificación
      } catch { // Instrucción
        toast('Error de conexión al generar el backup', 'error'); // Muestra una notificación
      } finally { // Instrucción
        btn.disabled = false; // Asigna un valor
        btn.textContent = original; // Fija el texto del elemento
      }
    }

    pane.querySelector('[data-backup-db]')?.addEventListener('click', function () { // Escucha un evento
      triggerDownload('api.php?action=backup_database', this, 'Generando dump SQL — un momento…'); // Llama a una función
    });

    pane.querySelector('[data-backup-files]')?.addEventListener('click', function () { // Escucha un evento
      triggerDownload('api.php?action=backup_files', this, 'Generando ZIP de la web — puede tardar unos segundos…'); // Llama a una función
    });

    /* ── Restore section ── */
    const zipInput   = pane.querySelector('[data-restore-zip-input]'); // Declara una variable
    const restoreBtn = pane.querySelector('[data-restore-btn]'); // Declara una variable
    const fileInfo   = pane.querySelector('[data-restore-file-info]'); // Declara una variable
    const restorePin = pane.querySelector('[data-restore-pin]'); // Declara una variable
    const statusEl   = pane.querySelector('[data-restore-status]'); // Declara una variable
    const dropArea   = pane.querySelector('[data-restore-drop]'); // Declara una variable

    function setRestoreFile(file) { // Función
      if (!file || file.type !== 'application/zip' && !file.name.endsWith('.zip')) { // Condición
        toast('Selecciona un archivo .zip válido', 'error'); // Muestra una notificación
        return; // Devuelve el resultado
      }
      fileInfo.textContent = `${file.name}  ·  ${(file.size / 1024 / 1024).toFixed(2)} MB`; // Fija el texto del elemento
      fileInfo.classList.remove('hidden'); // Cambia clases CSS
      restoreBtn.disabled = false; // Asigna un valor
      /* Replace the input's file list by reassigning via DataTransfer */
      const dt = new DataTransfer(); // Declara una variable
      dt.items.add(file); // Llama a una función
      zipInput.files = dt.files; // Asigna un valor
    }

    zipInput?.addEventListener('change', () => { // Escucha un evento
      if (zipInput.files[0]) setRestoreFile(zipInput.files[0]); // Condición
    });

    /* Drag & drop onto the drop area */
    dropArea?.addEventListener('dragover', e => { e.preventDefault(); dropArea.classList.add('is-dragging'); }); // Escucha un evento
    dropArea?.addEventListener('dragleave', () => dropArea.classList.remove('is-dragging')); // Escucha un evento
    dropArea?.addEventListener('drop', e => { // Escucha un evento
      e.preventDefault(); // Cancela el comportamiento por defecto
      dropArea.classList.remove('is-dragging'); // Cambia clases CSS
      const file = e.dataTransfer?.files[0]; // Declara una variable
      if (file) setRestoreFile(file); // Condición
    });

    restoreBtn?.addEventListener('click', async () => { // Escucha un evento
      const file = zipInput?.files[0]; // Declara una variable
      if (!file) { toast('Selecciona un archivo ZIP primero', 'error'); return; } // Condición
      if (!(restorePin?.value || '').trim()) { toast('Introduce el PIN', 'error'); return; } // El servidor valida el PIN real
      if (!confirm('¿Restaurar la web desde este ZIP?\n\nLos archivos actuales serán sobreescritos. Esta acción no se puede deshacer.')) return; // Condición

      restoreBtn.disabled = true; // Asigna un valor
      restoreBtn.textContent = 'Restaurando…'; // Fija el texto del elemento
      statusEl.textContent = 'Subiendo y procesando el ZIP…'; // Fija el texto del elemento
      statusEl.className = 'restore-status restore-status--loading'; // Asigna un valor
      statusEl.classList.remove('hidden'); // Cambia clases CSS

      const fd = new FormData(); // Declara una variable
      fd.append('zipfile', file); // Añade el elemento al DOM
      fd.append('pin', restorePin.value); // Añade el elemento al DOM

      try { // Instrucción
        const res  = await fetch('api.php?action=restore_files', { method: 'POST', body: fd }); // Declara una variable
        const data = await res.json(); // Declara una variable
        if (data.success) { // Condición
          statusEl.textContent = data.message; // Fija el texto del elemento
          statusEl.className = 'restore-status restore-status--ok'; // Asigna un valor
          toast(data.message, 'success'); // Muestra una notificación
        } else { // Instrucción
          statusEl.textContent = (data.error || 'Error desconocido'); // Fija el texto del elemento
          statusEl.className = 'restore-status restore-status--error'; // Asigna un valor
          toast(data.error || 'Error al restaurar', 'error'); // Muestra una notificación
        }
      } catch { // Instrucción
        statusEl.textContent = 'Error de conexión. Comprueba que el servidor está activo.'; // Fija el texto del elemento
        statusEl.className = 'restore-status restore-status--error'; // Asigna un valor
        toast('Error de conexión', 'error'); // Muestra una notificación
      } finally { // Instrucción
        restoreBtn.disabled = false; // Asigna un valor
        restoreBtn.textContent = 'Restaurar web'; // Fija el texto del elemento
      }
    });
  }

  /* ═══════════════════════════════════════════════════════════
     ADMIN — danger zone tab
  ═══════════════════════════════════════════════════════════ */
  function initAdminDangerTab() { // Función
    const pane = document.querySelector('[data-admin-pane="danger"]'); // Declara una variable
    if (!pane || pane.dataset.initialized) return; // Condición
    pane.dataset.initialized = 'true'; // Asigna un valor

    const pinInput  = pane.querySelector('[data-danger-pin]'); // Declara una variable
    const verifyBtn = pane.querySelector('[data-danger-verify]'); // Declara una variable
    const actions   = pane.querySelector('[data-danger-actions]'); // Declara una variable
    const pinStatus = pane.querySelector('[data-pin-status]'); // Declara una variable
    let dangerPin   = ''; // Guarda el PIN introducido para enviarlo al servidor (el servidor lo valida)

    verifyBtn?.addEventListener('click', () => { // Escucha un evento
      if ((pinInput.value || '').trim()) { // Si se ha introducido un PIN no vacío...
        dangerPin = pinInput.value; // Guarda el PIN para las acciones (el servidor comprueba si es correcto)
        pinStatus.textContent  = 'PIN introducido — se verificará en cada acción'; // Fija el texto del elemento
        pinStatus.className    = 'pin-status pin-ok'; // Asigna un valor
        actions?.classList.remove('hidden'); // Cambia clases CSS
        pinInput.disabled  = true; // Asigna un valor
        verifyBtn.disabled = true; // Asigna un valor
      } else { // Instrucción
        pinStatus.textContent = 'Introduce el PIN.'; // Fija el texto del elemento
        pinStatus.className   = 'pin-status pin-error'; // Asigna un valor
        pinInput.value = ''; // Asigna un valor
        pinInput.focus(); // Llama a una función
      }
    });

    pinInput?.addEventListener('keydown', e => { // Escucha un evento
      if (e.key === 'Enter') verifyBtn?.click(); // Condición
    });

    pane.querySelectorAll('[data-reset-target]').forEach(btn => { // Busca elemento(s) en el DOM
      btn.addEventListener('click', async () => { // Escucha un evento
        const target = btn.dataset.resetTarget; // Declara una variable
        const label  = btn.dataset.resetLabel || target; // Declara una variable
        if (!confirm(`¿Estás SEGURO de que quieres ${label}?\n\nEsta acción es IRREVERSIBLE. No se puede deshacer.`)) return; // Condición
        if (target === 'database' && !confirm('ÚLTIMA ADVERTENCIA: Se eliminará la base de datos completa. ¿Continuar?')) return; // Condición

        const orig = btn.textContent; // Declara una variable
        btn.disabled    = true; // Asigna un valor
        btn.textContent = 'Procesando…'; // Fija el texto del elemento

        const res = await api('reset_site', 'POST', { pin: dangerPin, target }); // Envía el PIN introducido; el servidor lo valida
        if (res?.success) { // Condición
          toast(res.message || 'Acción completada', target === 'database' ? 'error' : 'success'); // Muestra una notificación
          _usersCache = []; // Asigna un valor
          updateAdminMetrics(); // Llama a una función
          if (target === 'users' || target === 'all') { // Condición
            _usersCache = []; // Asigna un valor
            const usersPane = document.querySelector('[data-admin-pane="users"]'); // Declara una variable
            if (usersPane) { delete usersPane.dataset.initialized; } // Condición
          }
        } else { // Instrucción
          toast(res?.error || 'Error al ejecutar la acción', 'error'); // Muestra una notificación
          btn.disabled    = false; // Asigna un valor
          btn.textContent = orig; // Fija el texto del elemento
        }
      });
    });
  }

  /* ═══════════════════════════════════════════════════════════
     COPY TO CLIPBOARD utility
  ═══════════════════════════════════════════════════════════ */

  async function copyToClipboard(text) { // Función
    try { // Instrucción
      await navigator.clipboard.writeText(text); // Instrucción
      toast('Copiado al portapapeles', 'success'); // Muestra una notificación
    } catch { // Instrucción
      const el = document.createElement('textarea'); // Declara una variable
      el.value = text; // Asigna un valor
      el.style.position = 'fixed'; // Asigna un valor
      el.style.opacity  = '0'; // Asigna un valor
      document.body.appendChild(el); // Añade el elemento al DOM
      el.select(); // Llama a una función
      document.execCommand('copy'); // Llama a una función
      document.body.removeChild(el); // Llama a una función
      toast('Copiado al portapapeles', 'success'); // Muestra una notificación
    }
  }

  /* ═══════════════════════════════════════════════════════════
     RELATIVE TIME — "hace 2 horas" format
  ═══════════════════════════════════════════════════════════ */

  function relativeTime(dateStr) { // Función
    const now  = Date.now(); // Declara una variable
    const then = new Date(dateStr).getTime(); // Declara una variable
    const diff = Math.floor((now - then) / 1000); // Declara una variable

    if (diff < 60)           return 'hace unos segundos'; // Condición
    if (diff < 3600)         return `hace ${Math.floor(diff / 60)} min`; // Condición
    if (diff < 86400)        return `hace ${Math.floor(diff / 3600)} h`; // Condición
    if (diff < 86400 * 7)    return `hace ${Math.floor(diff / 86400)} d`; // Condición
    if (diff < 86400 * 30)   return `hace ${Math.floor(diff / (86400 * 7))} sem`; // Condición
    if (diff < 86400 * 365)  return `hace ${Math.floor(diff / (86400 * 30))} mes`; // Condición
    return `hace ${Math.floor(diff / (86400 * 365))} año(s)`; // Devuelve el resultado
  }

  /* ═══════════════════════════════════════════════════════════
     LIVE POST COUNTER — shows count next to forum heading
  ═══════════════════════════════════════════════════════════ */

  async function updatePostCounter() { // Función
    const badge = document.querySelector('[data-post-count-badge]'); // Declara una variable
    if (!badge) return; // Condición
    const stats = await api('get_stats'); // Declara una variable
    if (stats?.posts !== undefined) { // Condición
      badge.textContent = fmtNumber(stats.posts) + ' tema' + (stats.posts !== 1 ? 's' : ''); // Fija el texto del elemento
    }
  }

  /* ═══════════════════════════════════════════════════════════
     SCROLL TO FORUM — for CTA buttons linking to #foro
  ═══════════════════════════════════════════════════════════ */

  function setupCTAScrollLinks() { // Función
    document.querySelectorAll('[data-scroll-to]').forEach(btn => { // Busca elemento(s) en el DOM
      btn.addEventListener('click', () => { // Escucha un evento
        const id     = btn.dataset.scrollTo; // Declara una variable
        const target = document.getElementById(id); // Declara una variable
        if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' }); // Condición
      });
    });
  }

  /* ═══════════════════════════════════════════════════════════
     AUTO-REFRESH POSTS — poll every 90 seconds
  ═══════════════════════════════════════════════════════════ */

  function setupAutoRefresh() { // Función
    const page = document.body.dataset.page; // Declara una variable
    if (page !== 'home') return; // Condición
    setInterval(() => { // Temporizador
      renderPosts(); // Llama a una función
      updateStats(); // Llama a una función
    }, 90_000); // Instrucción
  }

  /* ═══════════════════════════════════════════════════════════
     PAGE TRANSITION FADE — quick opacity fade on navigation
  ═══════════════════════════════════════════════════════════ */

  function setupPageTransitions() { // Función
    document.querySelectorAll('a[href]').forEach(link => { // Busca elemento(s) en el DOM
      const href = link.getAttribute('href') || ''; // Declara una variable
      if ( // Condición
        href.startsWith('#') || // Llama a una función
        href.startsWith('javascript') || // Llama a una función
        href.startsWith('mailto') || // Llama a una función
        link.target === '_blank' // Asigna un valor
      ) return; // Instrucción

      link.addEventListener('click', e => { // Escucha un evento
        const wrap = document.querySelector('.page-wrap'); // Declara una variable
        if (!wrap) return; // Condición
        e.preventDefault(); // Cancela el comportamiento por defecto
        wrap.style.transition = 'opacity 0.22s ease'; // Asigna un valor
        wrap.style.opacity    = '0'; // Asigna un valor
        setTimeout(() => { window.location.href = href; }, 200); // Temporizador
      });
    });
  }

  /* ═══════════════════════════════════════════════════════════
     FORUM STATS ANIMATED COUNTER
  ═══════════════════════════════════════════════════════════ */

  function animateCounter(el, target, duration = 900) { // Función
    const start    = performance.now(); // Declara una variable
    const startVal = 0; // Declara una variable
    const step = ts => { // Función flecha
      const elapsed  = ts - start; // Declara una variable
      const progress = Math.min(elapsed / duration, 1); // Declara una variable
      const eased    = 1 - Math.pow(1 - progress, 3); // Declara una variable
      el.textContent = fmtNumber(Math.round(startVal + (target - startVal) * eased)); // Fija el texto del elemento
      if (progress < 1) requestAnimationFrame(step); // Condición
    };
    requestAnimationFrame(step); // Llama a una función
  }

  async function animateStatsOnLoad() { // Función
    const stats = await api('get_stats'); // Declara una variable
    if (!stats) return; // Condición
    const targets = { // Declara una variable
      '[data-stat-posts]':    stats.posts  || 0, // Instrucción
      '[data-stat-users]':    stats.users  || 0, // Instrucción
      '[data-stat-online]':   Math.max(1, Math.min(stats.users || 0, 12)), // Instrucción
      '[data-approved-count]':stats.posts  || 0, // Instrucción
      '[data-pending-count]': stats.pending || 0, // Instrucción
      '[data-likes-count]':   stats.likes  || 0, // Instrucción
    };
    Object.entries(targets).forEach(([sel, val]) => { // Recorre la lista
      const el = document.querySelector(sel); // Declara una variable
      if (el) animateCounter(el, val, 1200); // Condición
    });
  }

  /* ═══════════════════════════════════════════════════════════
     INIT
  ═══════════════════════════════════════════════════════════ */

  function init() { // Función
    const page = document.body.dataset.page; // Declara una variable

    setupMobileNav(); // Llama a una función
    renderAuthSlot(); // Llama a una función
    setupScrollReveal(); // Llama a una función
    setupKeyboardShortcuts(); // Llama a una función
    setupSmoothScroll(); // Llama a una función
    setupBackToTop(); // Llama a una función
    setupStickyHeader(); // Llama a una función
    setupCTAScrollLinks(); // Llama a una función
    setupPageTransitions(); // Llama a una función

    if (page === 'home') { // Condición
      applyPrefs(); // Llama a una función
      setupComposer(); // Llama a una función
      setupFilters(); // Llama a una función
      setupContactForm(); // Llama a una función
      setupSearchDebounce(); // Llama a una función
      setupCharCounters(); // Llama a una función
      persistFilterPrefs(); // Llama a una función
      animateStatsOnLoad(); // Llama a una función
      renderLeaderboardExtended(); // Llama a una función
      renderSessionPanel(); // Llama a una función
      renderPosts(); // Llama a una función
      renderMembersList(); // Llama a una función
      updatePostCounter(); // Llama a una función
      setupAutoRefresh(); // Llama a una función
    }

    if (page === 'admin') { // Condición
      renderAdminPageFull(); // Llama a una función
    }
  }

  document.addEventListener('DOMContentLoaded', init); // Escucha un evento

})(); // Instrucción
