<?php require_once __DIR__ . "/page_top.php"; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <title>NEXUS · Foro Gaming Competitivo</title>
  <meta name="description" content="El foro gaming más serio en español. Debates de estrategia, hardware, FPS, MOBA y mucho más." />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css?v=9" />
</head>
<body data-page="home">

  <canvas id="starfield"></canvas>
  <div class="nebula-overlay"></div>

  <div class="page-wrap">

    <!-- ═══════ HEADER ═══════ -->
    <header class="site-header">
      <div class="container topbar">

        <a class="brand" href="index.php">
          <span class="brand-icon">
            <svg class="brand-logo-svg" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M20 2L35.6 11V29L20 38L4.4 29V11Z" stroke="rgba(255,255,255,0.65)" stroke-width="1.3" fill="rgba(255,255,255,0.07)"/>
              <path d="M11 28V12L29 28V12" stroke="white" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="20" cy="2" r="1.9" fill="white"/>
              <circle cx="35.6" cy="11" r="1.2" fill="rgba(255,255,255,0.75)"/>
              <circle cx="35.6" cy="29" r="1.2" fill="rgba(255,255,255,0.75)"/>
              <circle cx="4.4" cy="11" r="1.2" fill="rgba(255,255,255,0.75)"/>
              <circle cx="4.4" cy="29" r="1.2" fill="rgba(255,255,255,0.75)"/>
            </svg>
          </span>
          <span class="brand-text-wrap">
            <span class="brand-name">NEXUS</span>
            <span class="brand-tagline">foro gaming competitivo</span>
          </span>
        </a>

        <nav class="main-nav">
          <a class="is-active" href="index.php">Inicio</a>
          <a href="#foro">Foro</a>
          <a href="#miembros">Miembros</a>
          <a href="#contacto">Contacto</a>
          <a href="login.php">Login</a>
          <a href="register.php">Registro</a>
          <a href="admin.php">Admin</a>
        </nav>

        <div class="auth-slot" data-auth-slot></div>
        <button class="hamburger" aria-label="Abrir menú" data-hamburger>
          <span></span><span></span><span></span>
        </button>

      </div>
    </header>

    <!-- MOBILE NAV -->
    <nav class="mobile-nav" data-mobile-nav>
      <a class="is-active" href="index.php">Inicio</a>
      <a href="#foro">Foro</a>
      <a href="#miembros">Miembros</a>
      <a href="#contacto">Contacto</a>
      <a href="login.php">Login</a>
      <a href="register.php">Registro</a>
      <a href="admin.php">Admin</a>
    </nav>

    <main>

      <!-- ═══════ HERO ═══════ -->
      <section class="hero">
        <!-- Decorative rings — CSS only, no JS -->
        <div class="hero-ring hero-ring-1" aria-hidden="true"></div>
        <div class="hero-ring hero-ring-2" aria-hidden="true"></div>
        <div class="hero-ring hero-ring-3" aria-hidden="true"></div>
        <!-- Floating sparks -->
        <div class="hero-spark hero-spark-1" aria-hidden="true"></div>
        <div class="hero-spark hero-spark-2" aria-hidden="true"></div>
        <div class="hero-spark hero-spark-3" aria-hidden="true"></div>
        <div class="hero-spark hero-spark-4" aria-hidden="true"></div>
        <div class="hero-spark hero-spark-5" aria-hidden="true"></div>

        <div class="container">
          <div class="hero-inner">

            <div class="hero-overline">
              <span class="live-dot"></span>
              Comunidad activa · Debate en tiempo real
            </div>

            <h1 class="hero-title">
              Juega en serio.<br>
              <em>Piensa más rápido.</em>
            </h1>

            <p class="hero-subtitle">
              Estrategia, hardware, meta y comunidad.<br>
              El foro gaming más serio en español — sin ruido, solo nivel.
            </p>

            <div class="hero-cta">
              <a class="btn btn-primary btn-lg btn-shimmer" href="register.php">
                <span class="btn-icon">✦</span> Unirse gratis
              </a>
              <a class="btn btn-outline btn-lg" href="#foro">Explorar foro</a>
            </div>

            <div class="hero-stats">
              <div class="hero-stat">
                <strong data-stat-posts>—</strong>
                <span>Temas publicados</span>
              </div>
              <div class="hero-stat">
                <strong data-stat-users>—</strong>
                <span>Miembros</span>
              </div>
              <div class="hero-stat">
                <strong data-stat-online>—</strong>
                <span>Online ahora</span>
              </div>
            </div>

          </div><!-- /.hero-inner -->
        </div>
      </section>

      <!-- ═══════ CATEGORY FILTER STRIP ═══════ -->
      <div class="categories-strip" id="foro">
        <div class="container cats-inner" data-category-filters>
          <button class="cat-btn is-active" data-category="all">Todos</button>
          <button class="cat-btn" data-category="fps">🎯 FPS</button>
          <button class="cat-btn" data-category="moba">⚔️ MOBA</button>
          <button class="cat-btn" data-category="hardware">🖥️ Hardware</button>
          <button class="cat-btn" data-category="noticias">📰 Noticias</button>
          <button class="cat-btn" data-category="estrategia">🧠 Estrategia</button>
          <div class="cats-right">
            <input type="search" class="input-search" placeholder="Buscar tema…" data-search-input>
            <select class="select-sort" data-sort-select>
              <option value="recent">Recientes</option>
              <option value="popular">Populares</option>
            </select>
          </div>
        </div>
      </div>

      <!-- ═══════ FORUM SECTION ═══════ -->
      <section class="forum-section">
        <div class="container forum-layout">

          <!-- MAIN FEED -->
          <div class="forum-main">

            <div class="forum-head">
              <div class="forum-head-left">
                <span class="eyebrow">Foro principal</span>
                <h2>Temas destacados <span class="post-count-badge" data-post-count-badge></span></h2>
              </div>
              <button class="btn btn-primary btn-sm" data-open-composer>+ Nuevo tema</button>
            </div>

            <!-- POST COMPOSER -->
            <div class="composer-panel hidden" data-composer-panel>
              <div class="composer-head">
                <h3>Crear nuevo tema</h3>
                <button class="btn btn-ghost btn-sm" data-close-composer>Cerrar</button>
              </div>
              <div class="composer-body">
                <form data-post-form>
                  <div class="form-grid">
                    <div class="form-row-2">
                      <div>
                        <label class="field-label">Título</label>
                        <input class="field-input" type="text" name="title" maxlength="80" placeholder="Ej: Mejor configuración para ranked" required>
                      </div>
                      <div>
                        <label class="field-label">Categoría</label>
                        <select class="field-select" name="category" required>
                          <option value="fps">FPS</option>
                          <option value="moba">MOBA</option>
                          <option value="hardware">Hardware</option>
                          <option value="noticias">Noticias</option>
                          <option value="estrategia">Estrategia</option>
                        </select>
                      </div>
                    </div>
                    <div data-char-counter>
                      <label class="field-label">Contenido</label>
                      <textarea class="field-textarea" name="content" rows="4" maxlength="600" placeholder="Comparte tu análisis, pregunta o experiencia…" required></textarea>
                      <span class="char-count-display" data-char-count-display></span>
                    </div>
                    <div class="form-footer">
                      <p class="form-hint">Tu tema aparecerá en el foro en breve. Comparte con respeto y calidad.</p>
                      <button class="btn btn-primary" type="submit">Publicar tema</button>
                    </div>
                    <div class="notice-inline" data-post-notice></div>
                  </div>
                </form>
              </div>
            </div>

            <!-- POST FEED -->
            <div class="feed" data-post-list></div>
            <div class="empty-state hidden" data-empty-state>
              <div class="empty-icon">✦</div>
              <h3>No hay temas con ese filtro</h3>
              <p>Prueba otra categoría o elimina la búsqueda.</p>
            </div>

          </div><!-- /.forum-main -->

          <!-- SIDEBAR -->
          <aside class="sidebar">

            <!-- Activity stats -->
            <div class="side-card side-card-glow">
              <div class="side-head"><h3>Actividad</h3></div>
              <div class="side-body">
                <div class="stat-row">
                  <span class="stat-label">Temas publicados</span>
                  <span class="stat-value" data-approved-count>—</span>
                </div>
                <div class="stat-row">
                  <span class="stat-label">En revisión</span>
                  <span class="stat-value amber" data-pending-count>—</span>
                </div>
                <div class="stat-row">
                  <span class="stat-label">Likes totales</span>
                  <span class="stat-value violet" data-likes-count>—</span>
                </div>
              </div>
            </div>

            <!-- Leaderboard -->
            <div class="side-card side-card-glow">
              <div class="side-head"><h3>Top miembros</h3></div>
              <div class="side-body">
                <div class="leader-list" data-user-list></div>
              </div>
            </div>

            <!-- Session -->
            <div class="side-card side-card-glow">
              <div class="side-head"><h3>Tu sesión</h3></div>
              <div class="session-body" data-session-panel></div>
            </div>

          </aside>

        </div>
      </section>

      <!-- ═══════ MEMBERS SECTION ═══════ -->
      <section class="members-section" id="miembros">
        <div class="container">

          <div class="section-header reveal-on-scroll">
            <span class="eyebrow">Comunidad</span>
            <h2>Miembros de NEXUS</h2>
            <p>Conoce a los jugadores que forman parte de la comunidad. Compiten, debaten y comparten su conocimiento cada día.</p>
          </div>

          <div class="members-grid" data-members-list></div>

          <div class="members-cta reveal-on-scroll">
            <a class="btn btn-primary btn-lg btn-shimmer" href="register.php">
              <span class="btn-icon">✦</span> Únete a la comunidad
            </a>
            <a class="btn btn-outline btn-lg" href="login.php">Iniciar sesión</a>
          </div>

        </div>
      </section>

      <!-- ═══════ CONTACT SECTION ═══════ -->
      <section class="contact-section" id="contacto">
        <div class="container">

          <div class="contact-cap reveal-on-scroll">
            <span class="eyebrow">Contacto</span>
            <h2>¿Tienes algo que decirnos?</h2>
            <p>Sugerencias, reportes o simplemente quieres saludar. Estaremos encantados de responderte.</p>
          </div>

          <div class="contact-grid">

            <!-- FORM CARD -->
            <div class="contact-card reveal-on-scroll">
              <h3>Envíanos un mensaje</h3>
              <form data-contact-form>
                <div>
                  <label class="field-label">Nombre</label>
                  <input class="field-input" type="text" name="name" maxlength="50" placeholder="Tu nombre" required>
                </div>
                <div>
                  <label class="field-label">Email</label>
                  <input class="field-input" type="email" name="email" placeholder="tu@email.com" required>
                </div>
                <div>
                  <label class="field-label">Asunto</label>
                  <input class="field-input" type="text" name="subject" maxlength="80" placeholder="Motivo del mensaje" required>
                </div>
                <div>
                  <label class="field-label">Mensaje</label>
                  <textarea class="field-textarea" name="message" rows="5" maxlength="500" placeholder="Escribe aquí tu mensaje…" required></textarea>
                </div>
                <div class="form-footer" style="margin-top:0;">
                  <p class="form-hint">Nuestro equipo leerá tu mensaje y te responderá lo antes posible.</p>
                  <button class="btn btn-primary btn-shimmer" type="submit">
                    <span class="btn-icon">→</span> Enviar mensaje
                  </button>
                </div>
                <div class="notice-inline" data-contact-notice></div>
              </form>
            </div>

            <!-- INFO CARDS -->
            <div class="contact-info-list reveal-on-scroll">

              <div class="contact-info-item">
                <div class="cii-icon">📧</div>
                <div class="cii-text">
                  <strong>Email</strong>
                  <span>admin@nexusboard.gg</span>
                </div>
              </div>

              <div class="contact-info-item">
                <div class="cii-icon">💬</div>
                <div class="cii-text">
                  <strong>Discord</strong>
                  <span>discord.gg/nexusboard</span>
                </div>
              </div>

              <div class="contact-info-item">
                <div class="cii-icon">🐦</div>
                <div class="cii-text">
                  <strong>Twitter / X</strong>
                  <span>@nexusboard</span>
                </div>
              </div>

              <div class="contact-info-item">
                <div class="cii-icon">🌍</div>
                <div class="cii-text">
                  <strong>Comunidad</strong>
                  <span>Global · online 24/7</span>
                </div>
              </div>

              <div class="contact-info-item">
                <div class="cii-icon">⚡</div>
                <div class="cii-text">
                  <strong>Tiempo de respuesta</strong>
                  <span>Menos de 24 horas</span>
                </div>
              </div>

            </div><!-- /.contact-info-list -->

          </div><!-- /.contact-grid -->
        </div>
      </section>

    </main>

    <!-- ═══════ FOOTER ═══════ -->
    <footer class="site-footer">
      <div class="container">

        <div class="footer-grid">

          <!-- Brand column -->
          <div class="footer-brand">
            <a class="brand" href="index.php">
              <span class="brand-icon">
                <svg class="brand-logo-svg" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M20 2L35.6 11V29L20 38L4.4 29V11Z" stroke="rgba(255,255,255,0.65)" stroke-width="1.3" fill="rgba(255,255,255,0.07)"/>
                  <path d="M11 28V12L29 28V12" stroke="white" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="20" cy="2" r="1.9" fill="white"/>
                  <circle cx="35.6" cy="11" r="1.2" fill="rgba(255,255,255,0.75)"/>
                  <circle cx="35.6" cy="29" r="1.2" fill="rgba(255,255,255,0.75)"/>
                  <circle cx="4.4" cy="11" r="1.2" fill="rgba(255,255,255,0.75)"/>
                  <circle cx="4.4" cy="29" r="1.2" fill="rgba(255,255,255,0.75)"/>
                </svg>
              </span>
              <span class="brand-text-wrap">
                <span class="brand-name">NEXUS</span>
                <span class="brand-tagline">foro gaming competitivo</span>
              </span>
            </a>
            <p>El foro gaming más serio en español. Estrategia, hardware, noticias y comunidad sin drama.</p>
            <div class="footer-social">
              <span class="social-btn" title="Discord">💬</span>
              <span class="social-btn" title="Twitter">🐦</span>
              <span class="social-btn" title="YouTube">▶️</span>
              <span class="social-btn" title="Twitch">🎮</span>
            </div>
          </div>

          <!-- Nav column -->
          <div class="footer-col">
            <h4>Navegación</h4>
            <ul class="footer-links">
              <li><a href="index.php">Inicio</a></li>
              <li><a href="#foro">Foro</a></li>
              <li><a href="#miembros">Miembros</a></li>
              <li><a href="#contacto">Contacto</a></li>
              <li><a href="register.php">Crear cuenta</a></li>
              <li><a href="login.php">Iniciar sesión</a></li>
            </ul>
          </div>

          <!-- Categories column -->
          <div class="footer-col">
            <h4>Categorías</h4>
            <ul class="footer-links">
              <li><a href="#foro">🎯 FPS</a></li>
              <li><a href="#foro">⚔️ MOBA</a></li>
              <li><a href="#foro">🖥️ Hardware</a></li>
              <li><a href="#foro">📰 Noticias</a></li>
              <li><a href="#foro">🧠 Estrategia</a></li>
            </ul>
          </div>

        </div><!-- /.footer-grid -->

        <div class="footer-bottom">
          <span>© 2026 NEXUS. Todos los derechos reservados.</span>
          <div class="footer-status">
            <span class="status-dot"></span>
            Todos los sistemas operativos
          </div>
          <span>Gaming sin límites</span>
        </div>

      </div>
    </footer>

  </div><!-- /.page-wrap -->

  <button class="back-to-top" data-back-to-top aria-label="Volver arriba">↑</button>
  <div class="toast-wrap" data-toast-wrap></div>
  <script>window.NEXUS_CURRENT_USER = <?= $currentUserJson ?: 'null' ?>;</script>
  <script src="script.js?v=9"></script>
</body>
</html>
