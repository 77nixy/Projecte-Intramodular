<?php require_once __DIR__ . "/page_top.php"; // Arranca la app y prepara $currentUserJson para el cliente ?>
<!DOCTYPE html>
<html lang="es"> <!-- Documento en español -->
<head>
  <meta charset="UTF-8" /> <!-- Codificación UTF-8 -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" /> <!-- Responsive en móvil -->
  <title>NEXUS · Foro Gaming Competitivo</title> <!-- Título de la pestaña -->
  <meta name="description" content="El foro gaming más serio en español. Debates de estrategia, hardware, FPS, MOBA y mucho más." /> <!-- Descripción SEO -->
  <link rel="preconnect" href="https://fonts.googleapis.com"> <!-- Pre-conexión a Google Fonts -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin> <!-- Pre-conexión al CDN de fuentes -->
  <!-- Tipografía del proyecto: Inter (sans-serif profesional y muy legible) -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css?v=12" /> <!-- Hoja de estilos (?v=12 cache-busting) -->
</head>
<body data-page="home"> <!-- data-page="home" lo lee script.js para inicializar la portada -->

  <canvas id="starfield"></canvas> <!-- Lienzo del campo de estrellas animado -->
  <div class="nebula-overlay"></div> <!-- Capa de nebulosa decorativa -->

  <div class="page-wrap"> <!-- Envoltorio de todo el contenido -->

    <!-- ═══════ CABECERA ═══════ -->
    <header class="site-header"> <!-- Cabecera fija superior -->
      <div class="container topbar"> <!-- Barra: logo + navegación + sesión -->

        <a class="brand" href="index.php"> <!-- Logo enlazado a la portada -->
          <span class="brand-icon"> <!-- Contenedor del icono SVG -->
            <!-- Logo SVG hexagonal con la "N" de NEXUS -->
            <svg class="brand-logo-svg" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M20 2L35.6 11V29L20 38L4.4 29V11Z" stroke="rgba(255,255,255,0.65)" stroke-width="1.3" fill="rgba(255,255,255,0.07)"/> <!-- Hexágono -->
              <path d="M11 28V12L29 28V12" stroke="white" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/> <!-- Letra N -->
              <circle cx="20" cy="2" r="1.9" fill="white"/> <!-- Vértice superior -->
              <circle cx="35.6" cy="11" r="1.2" fill="rgba(255,255,255,0.75)"/> <!-- Vértice sup. derecho -->
              <circle cx="35.6" cy="29" r="1.2" fill="rgba(255,255,255,0.75)"/> <!-- Vértice inf. derecho -->
              <circle cx="4.4" cy="11" r="1.2" fill="rgba(255,255,255,0.75)"/> <!-- Vértice sup. izquierdo -->
              <circle cx="4.4" cy="29" r="1.2" fill="rgba(255,255,255,0.75)"/> <!-- Vértice inf. izquierdo -->
            </svg>
          </span>
          <span class="brand-text-wrap"> <!-- Texto de la marca -->
            <span class="brand-name">NEXUS</span> <!-- Nombre -->
            <span class="brand-tagline">foro gaming competitivo</span> <!-- Lema -->
          </span>
        </a>

        <nav class="main-nav"> <!-- Navegación de escritorio -->
          <a class="is-active" href="index.php">Inicio</a> <!-- Página actual (resaltada) -->
          <a href="#foro">Foro</a> <!-- Ancla a la sección del foro -->
          <a href="#miembros">Miembros</a> <!-- Ancla a la sección de miembros -->
          <a href="#contacto">Contacto</a> <!-- Ancla a la sección de contacto -->
          <a href="login.php">Login</a> <!-- Acceso -->
          <a href="register.php">Registro</a> <!-- Registro -->
          <a href="admin.php">Admin</a> <!-- Panel de administración -->
        </nav>

        <div class="auth-slot" data-auth-slot></div> <!-- Hueco de sesión (lo rellena script.js) -->
        <button class="hamburger" aria-label="Abrir menú" data-hamburger> <!-- Botón menú móvil -->
          <span></span><span></span><span></span> <!-- Las 3 barras del icono -->
        </button>

      </div>
    </header>

    <!-- NAVEGACIÓN MÓVIL -->
    <nav class="mobile-nav" data-mobile-nav> <!-- Menú desplegable para móvil -->
      <a class="is-active" href="index.php">Inicio</a> <!-- Página actual -->
      <a href="#foro">Foro</a> <!-- Foro -->
      <a href="#miembros">Miembros</a> <!-- Miembros -->
      <a href="#contacto">Contacto</a> <!-- Contacto -->
      <a href="login.php">Login</a> <!-- Acceso -->
      <a href="register.php">Registro</a> <!-- Registro -->
      <a href="admin.php">Admin</a> <!-- Admin -->
    </nav>

    <main>

      <!-- ═══════ HERO (portada destacada) ═══════ -->
      <section class="hero">
        <!-- Anillos decorativos — solo CSS, sin JS -->
        <div class="hero-ring hero-ring-1" aria-hidden="true"></div> <!-- Anillo 1 -->
        <div class="hero-ring hero-ring-2" aria-hidden="true"></div> <!-- Anillo 2 -->
        <div class="hero-ring hero-ring-3" aria-hidden="true"></div> <!-- Anillo 3 -->
        <!-- Destellos flotantes -->
        <div class="hero-spark hero-spark-1" aria-hidden="true"></div> <!-- Destello 1 -->
        <div class="hero-spark hero-spark-2" aria-hidden="true"></div> <!-- Destello 2 -->
        <div class="hero-spark hero-spark-3" aria-hidden="true"></div> <!-- Destello 3 -->
        <div class="hero-spark hero-spark-4" aria-hidden="true"></div> <!-- Destello 4 -->
        <div class="hero-spark hero-spark-5" aria-hidden="true"></div> <!-- Destello 5 -->

        <div class="container">
          <div class="hero-inner"> <!-- Contenido centrado del hero -->

            <div class="hero-overline"> <!-- Rótulo superior con punto "en vivo" -->
              <span class="live-dot"></span> <!-- Punto parpadeante -->
              Comunidad activa · Debate en tiempo real <!-- Texto del rótulo -->
            </div>

            <h1 class="hero-title"> <!-- Titular principal -->
              Juega en serio.<br>
              <em>Piensa más rápido.</em> <!-- Palabra acentuada en cursiva -->
            </h1>

            <p class="hero-subtitle"> <!-- Subtítulo descriptivo -->
              Estrategia, hardware, meta y comunidad.<br>
              El foro gaming más serio en español — sin ruido, solo nivel.
            </p>

            <div class="hero-cta"> <!-- Botones de llamada a la acción -->
              <a class="btn btn-primary btn-lg btn-shimmer" href="register.php"> <!-- Botón principal: registro -->
                Unirse gratis
              </a>
              <a class="btn btn-outline btn-lg" href="#foro">Explorar foro</a> <!-- Botón secundario: ir al foro -->
            </div>

            <div class="hero-stats"> <!-- Cifras (las rellena script.js) -->
              <div class="hero-stat"> <!-- Estadística: temas -->
                <strong data-stat-posts>—</strong> <!-- Valor (placeholder "—") -->
                <span>Temas publicados</span> <!-- Etiqueta -->
              </div>
              <div class="hero-stat"> <!-- Estadística: miembros -->
                <strong data-stat-users>—</strong> <!-- Valor -->
                <span>Miembros</span> <!-- Etiqueta -->
              </div>
              <div class="hero-stat"> <!-- Estadística: online -->
                <strong data-stat-online>—</strong> <!-- Valor -->
                <span>Online ahora</span> <!-- Etiqueta -->
              </div>
            </div>

          </div><!-- /.hero-inner -->
        </div>
      </section>

      <!-- ═══════ BARRA DE FILTROS POR CATEGORÍA ═══════ -->
      <div class="categories-strip" id="foro"> <!-- Barra pegajosa; #foro es el ancla -->
        <div class="container cats-inner" data-category-filters> <!-- Contenedor de botones de filtro -->
          <button class="cat-btn is-active" data-category="all">Todos</button> <!-- Filtro: todas las categorías -->
          <button class="cat-btn" data-category="fps">FPS</button> <!-- Filtro: FPS -->
          <button class="cat-btn" data-category="moba">MOBA</button> <!-- Filtro: MOBA -->
          <button class="cat-btn" data-category="hardware">Hardware</button> <!-- Filtro: Hardware -->
          <button class="cat-btn" data-category="noticias">Noticias</button> <!-- Filtro: Noticias -->
          <button class="cat-btn" data-category="estrategia">Estrategia</button> <!-- Filtro: Estrategia -->
          <div class="cats-right"> <!-- Búsqueda + orden a la derecha -->
            <input type="search" class="input-search" placeholder="Buscar tema…" data-search-input> <!-- Caja de búsqueda -->
            <select class="select-sort" data-sort-select> <!-- Selector de orden -->
              <option value="recent">Recientes</option> <!-- Orden por fecha -->
              <option value="popular">Populares</option> <!-- Orden por likes -->
            </select>
          </div>
        </div>
      </div>

      <!-- ═══════ SECCIÓN DEL FORO ═══════ -->
      <section class="forum-section">
        <div class="container forum-layout"> <!-- Layout: feed + barra lateral -->

          <!-- COLUMNA PRINCIPAL (FEED) -->
          <div class="forum-main">

            <div class="forum-head"> <!-- Cabecera del feed -->
              <div class="forum-head-left">
                <span class="eyebrow">Foro principal</span> <!-- Rótulo -->
                <h2>Temas destacados <span class="post-count-badge" data-post-count-badge></span></h2> <!-- Título + contador de posts -->
              </div>
              <button class="btn btn-primary btn-sm" data-open-composer>+ Nuevo tema</button> <!-- Abre el formulario de nuevo tema -->
            </div>

            <!-- COMPOSITOR DE POSTS -->
            <div class="composer-panel hidden" data-composer-panel> <!-- Panel oculto hasta pulsar "Nuevo tema" -->
              <div class="composer-head"> <!-- Cabecera del compositor -->
                <h3>Crear nuevo tema</h3> <!-- Título -->
                <button class="btn btn-ghost btn-sm" data-close-composer>Cerrar</button> <!-- Cierra el compositor -->
              </div>
              <div class="composer-body"> <!-- Cuerpo con el formulario -->
                <form data-post-form> <!-- Formulario de creación de post (lo gestiona script.js) -->
                  <div class="form-grid">
                    <div class="form-row-2"> <!-- Fila de 2 campos -->
                      <div>
                        <label class="field-label">Título</label> <!-- Etiqueta del título -->
                        <input class="field-input" type="text" name="title" maxlength="80" placeholder="Ej: Mejor configuración para ranked" required> <!-- Campo título -->
                      </div>
                      <div>
                        <label class="field-label">Categoría</label> <!-- Etiqueta de categoría -->
                        <select class="field-select" name="category" required> <!-- Selector de categoría -->
                          <option value="fps">FPS</option> <!-- Opción FPS -->
                          <option value="moba">MOBA</option> <!-- Opción MOBA -->
                          <option value="hardware">Hardware</option> <!-- Opción Hardware -->
                          <option value="noticias">Noticias</option> <!-- Opción Noticias -->
                          <option value="estrategia">Estrategia</option> <!-- Opción Estrategia -->
                        </select>
                      </div>
                    </div>
                    <div data-char-counter> <!-- Envoltorio con contador de caracteres -->
                      <label class="field-label">Contenido</label> <!-- Etiqueta del contenido -->
                      <textarea class="field-textarea" name="content" rows="4" maxlength="600" placeholder="Comparte tu análisis, pregunta o experiencia…" required></textarea> <!-- Área de texto -->
                      <span class="char-count-display" data-char-count-display></span> <!-- Muestra los caracteres usados -->
                    </div>
                    <div class="form-footer"> <!-- Pie del formulario -->
                      <p class="form-hint">Tu tema aparecerá en el foro en breve. Comparte con respeto y calidad.</p> <!-- Aviso -->
                      <button class="btn btn-primary" type="submit">Publicar tema</button> <!-- Botón de envío -->
                    </div>
                    <div class="notice-inline" data-post-notice></div> <!-- Aviso de resultado (éxito/error) -->
                  </div>
                </form>
              </div>
            </div>

            <!-- LISTA DE POSTS -->
            <div class="feed" data-post-list></div> <!-- Aquí script.js inyecta las tarjetas de posts -->
            <div class="empty-state hidden" data-empty-state> <!-- Estado vacío (sin resultados) -->
              <h3>No hay temas con ese filtro</h3> <!-- Mensaje -->
              <p>Prueba otra categoría o elimina la búsqueda.</p> <!-- Sugerencia -->
            </div>

          </div><!-- /.forum-main -->

          <!-- BARRA LATERAL -->
          <aside class="sidebar">

            <!-- Estadísticas de actividad -->
            <div class="side-card side-card-glow">
              <div class="side-head"><h3>Actividad</h3></div> <!-- Título de la tarjeta -->
              <div class="side-body">
                <div class="stat-row"> <!-- Fila: temas publicados -->
                  <span class="stat-label">Temas publicados</span> <!-- Etiqueta -->
                  <span class="stat-value" data-approved-count>—</span> <!-- Valor (lo rellena script.js) -->
                </div>
                <div class="stat-row"> <!-- Fila: en revisión -->
                  <span class="stat-label">En revisión</span> <!-- Etiqueta -->
                  <span class="stat-value amber" data-pending-count>—</span> <!-- Valor -->
                </div>
                <div class="stat-row"> <!-- Fila: likes totales -->
                  <span class="stat-label">Likes totales</span> <!-- Etiqueta -->
                  <span class="stat-value violet" data-likes-count>—</span> <!-- Valor -->
                </div>
              </div>
            </div>

            <!-- Ranking (leaderboard) -->
            <div class="side-card side-card-glow">
              <div class="side-head"><h3>Top miembros</h3></div> <!-- Título -->
              <div class="side-body">
                <div class="leader-list" data-user-list></div> <!-- Lista de top miembros (script.js) -->
              </div>
            </div>

            <!-- Sesión del usuario -->
            <div class="side-card side-card-glow">
              <div class="side-head"><h3>Tu sesión</h3></div> <!-- Título -->
              <div class="session-body" data-session-panel></div> <!-- Panel de sesión (script.js) -->
            </div>

          </aside>

        </div>
      </section>

      <!-- ═══════ SECCIÓN DE MIEMBROS ═══════ -->
      <section class="members-section" id="miembros"> <!-- #miembros es el ancla -->
        <div class="container">

          <div class="section-header reveal-on-scroll"> <!-- Cabecera (animada al hacer scroll) -->
            <span class="eyebrow">Comunidad</span> <!-- Rótulo -->
            <h2>Miembros de NEXUS</h2> <!-- Título -->
            <p>Conoce a los jugadores que forman parte de la comunidad. Compiten, debaten y comparten su conocimiento cada día.</p> <!-- Descripción -->
          </div>

          <div class="members-grid" data-members-list></div> <!-- Rejilla de miembros (script.js) -->

          <div class="members-cta reveal-on-scroll"> <!-- Llamada a la acción -->
            <a class="btn btn-primary btn-lg btn-shimmer" href="register.php"> <!-- Botón: unirse -->
              Únete a la comunidad
            </a>
            <a class="btn btn-outline btn-lg" href="login.php">Iniciar sesión</a> <!-- Botón: login -->
          </div>

        </div>
      </section>

      <!-- ═══════ SECCIÓN DE CONTACTO ═══════ -->
      <section class="contact-section" id="contacto"> <!-- #contacto es el ancla -->
        <div class="container">

          <div class="contact-cap reveal-on-scroll"> <!-- Cabecera de contacto -->
            <span class="eyebrow">Contacto</span> <!-- Rótulo -->
            <h2>¿Tienes algo que decirnos?</h2> <!-- Título -->
            <p>Sugerencias, reportes o simplemente quieres saludar. Estaremos encantados de responderte.</p> <!-- Descripción -->
          </div>

          <div class="contact-grid"> <!-- Layout: formulario + info -->

            <!-- TARJETA DEL FORMULARIO -->
            <div class="contact-card reveal-on-scroll">
              <h3>Envíanos un mensaje</h3> <!-- Título -->
              <form data-contact-form> <!-- Formulario de contacto (lo gestiona script.js → process_contact.php) -->
                <div>
                  <label class="field-label">Nombre</label> <!-- Etiqueta nombre -->
                  <input class="field-input" type="text" name="name" maxlength="50" placeholder="Tu nombre" required> <!-- Campo nombre -->
                </div>
                <div>
                  <label class="field-label">Email</label> <!-- Etiqueta email -->
                  <input class="field-input" type="email" name="email" placeholder="tu@email.com" required> <!-- Campo email -->
                </div>
                <div>
                  <label class="field-label">Asunto</label> <!-- Etiqueta asunto -->
                  <input class="field-input" type="text" name="subject" maxlength="80" placeholder="Motivo del mensaje" required> <!-- Campo asunto -->
                </div>
                <div>
                  <label class="field-label">Mensaje</label> <!-- Etiqueta mensaje -->
                  <textarea class="field-textarea" name="message" rows="5" maxlength="500" placeholder="Escribe aquí tu mensaje…" required></textarea> <!-- Área del mensaje -->
                </div>
                <div class="form-footer" style="margin-top:0;"> <!-- Pie del formulario -->
                  <p class="form-hint">Nuestro equipo leerá tu mensaje y te responderá lo antes posible.</p> <!-- Aviso -->
                  <button class="btn btn-primary btn-shimmer" type="submit"> <!-- Botón de envío -->
                    Enviar mensaje
                  </button>
                </div>
                <div class="notice-inline" data-contact-notice></div> <!-- Aviso de resultado -->
              </form>
            </div>

            <!-- TARJETAS DE INFORMACIÓN -->
            <div class="contact-info-list reveal-on-scroll">

              <div class="contact-info-item"> <!-- Info: Email -->
                <div class="cii-text">
                  <strong>Email</strong> <!-- Título -->
                  <span>admin@nexusboard.gg</span> <!-- Dato -->
                </div>
              </div>

              <div class="contact-info-item"> <!-- Info: Discord -->
                <div class="cii-text">
                  <strong>Discord</strong> <!-- Título -->
                  <span>discord.gg/nexusboard</span> <!-- Dato -->
                </div>
              </div>

              <div class="contact-info-item"> <!-- Info: Twitter/X -->
                <div class="cii-text">
                  <strong>Twitter / X</strong> <!-- Título -->
                  <span>@nexusboard</span> <!-- Dato -->
                </div>
              </div>

              <div class="contact-info-item"> <!-- Info: Comunidad -->
                <div class="cii-text">
                  <strong>Comunidad</strong> <!-- Título -->
                  <span>Global · online 24/7</span> <!-- Dato -->
                </div>
              </div>

              <div class="contact-info-item"> <!-- Info: Tiempo de respuesta -->
                <div class="cii-text">
                  <strong>Tiempo de respuesta</strong> <!-- Título -->
                  <span>Menos de 24 horas</span> <!-- Dato -->
                </div>
              </div>

            </div><!-- /.contact-info-list -->

          </div><!-- /.contact-grid -->
        </div>
      </section>

    </main>

    <!-- ═══════ PIE DE PÁGINA ═══════ -->
    <footer class="site-footer">
      <div class="container">

        <div class="footer-grid"> <!-- Rejilla de 3 columnas del footer -->

          <!-- Columna de marca -->
          <div class="footer-brand">
            <a class="brand" href="index.php"> <!-- Logo (mismo SVG que la cabecera) -->
              <span class="brand-icon">
                <svg class="brand-logo-svg" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M20 2L35.6 11V29L20 38L4.4 29V11Z" stroke="rgba(255,255,255,0.65)" stroke-width="1.3" fill="rgba(255,255,255,0.07)"/> <!-- Hexágono -->
                  <path d="M11 28V12L29 28V12" stroke="white" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/> <!-- Letra N -->
                  <circle cx="20" cy="2" r="1.9" fill="white"/> <!-- Vértice superior -->
                  <circle cx="35.6" cy="11" r="1.2" fill="rgba(255,255,255,0.75)"/> <!-- Vértice sup. derecho -->
                  <circle cx="35.6" cy="29" r="1.2" fill="rgba(255,255,255,0.75)"/> <!-- Vértice inf. derecho -->
                  <circle cx="4.4" cy="11" r="1.2" fill="rgba(255,255,255,0.75)"/> <!-- Vértice sup. izquierdo -->
                  <circle cx="4.4" cy="29" r="1.2" fill="rgba(255,255,255,0.75)"/> <!-- Vértice inf. izquierdo -->
                </svg>
              </span>
              <span class="brand-text-wrap">
                <span class="brand-name">NEXUS</span> <!-- Nombre -->
                <span class="brand-tagline">foro gaming competitivo</span> <!-- Lema -->
              </span>
            </a>
            <p>El foro gaming más serio en español. Estrategia, hardware, noticias y comunidad sin drama.</p> <!-- Descripción -->
            <div class="footer-social"> <!-- Enlaces de redes sociales (texto, sin iconos) -->
              <a class="social-link" href="#">Discord</a> <!-- Discord -->
              <a class="social-link" href="#">Twitter</a> <!-- Twitter -->
              <a class="social-link" href="#">YouTube</a> <!-- YouTube -->
              <a class="social-link" href="#">Twitch</a> <!-- Twitch -->
            </div>
          </div>

          <!-- Columna de navegación -->
          <div class="footer-col">
            <h4>Navegación</h4> <!-- Título de columna -->
            <ul class="footer-links"> <!-- Lista de enlaces -->
              <li><a href="index.php">Inicio</a></li> <!-- Portada -->
              <li><a href="#foro">Foro</a></li> <!-- Foro -->
              <li><a href="#miembros">Miembros</a></li> <!-- Miembros -->
              <li><a href="#contacto">Contacto</a></li> <!-- Contacto -->
              <li><a href="register.php">Crear cuenta</a></li> <!-- Registro -->
              <li><a href="login.php">Iniciar sesión</a></li> <!-- Login -->
            </ul>
          </div>

          <!-- Columna de categorías -->
          <div class="footer-col">
            <h4>Categorías</h4> <!-- Título de columna -->
            <ul class="footer-links"> <!-- Lista de categorías -->
              <li><a href="#foro">FPS</a></li> <!-- FPS -->
              <li><a href="#foro">MOBA</a></li> <!-- MOBA -->
              <li><a href="#foro">Hardware</a></li> <!-- Hardware -->
              <li><a href="#foro">Noticias</a></li> <!-- Noticias -->
              <li><a href="#foro">Estrategia</a></li> <!-- Estrategia -->
            </ul>
          </div>

        </div><!-- /.footer-grid -->

        <div class="footer-bottom"> <!-- Barra inferior del footer -->
          <span>© 2026 NEXUS. Todos los derechos reservados.</span> <!-- Copyright -->
          <div class="footer-status"> <!-- Indicador de estado -->
            <span class="status-dot"></span> <!-- Punto verde -->
            Todos los sistemas operativos <!-- Texto de estado -->
          </div>
          <span>Gaming sin límites</span> <!-- Lema final -->
        </div>

      </div>
    </footer>

  </div><!-- /.page-wrap -->

  <button class="back-to-top" data-back-to-top aria-label="Volver arriba" style="font-size:0.6rem;letter-spacing:0.04em;">Subir</button> <!-- Botón flotante "volver arriba" (texto, sin icono) -->
  <div class="toast-wrap" data-toast-wrap></div> <!-- Contenedor de notificaciones (toasts) -->
  <script>window.NEXUS_CURRENT_USER = <?= $currentUserJson ?: 'null' ?>;</script> <!-- Inyecta el usuario actual como JSON -->
  <script src="script.js?v=12"></script> <!-- Carga la lógica de la página -->
</body>
</html>
