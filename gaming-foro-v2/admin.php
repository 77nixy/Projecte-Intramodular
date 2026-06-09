<?php require_once __DIR__ . "/page_top.php"; // Arranca la app y prepara $currentUserJson ?>
<!DOCTYPE html>
<html lang="es"> <!-- Documento en español -->
<head>
  <meta charset="UTF-8" /> <!-- Codificación UTF-8 -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" /> <!-- Responsive en móvil -->
  <title>Panel de administración · NEXUS</title> <!-- Título de la pestaña -->
  <link rel="preconnect" href="https://fonts.googleapis.com"> <!-- Pre-conexión a Google Fonts -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin> <!-- Pre-conexión al CDN -->
  <!-- Tipografía Inter (sans-serif profesional y muy legible) -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css?v=12" /> <!-- Hoja de estilos -->
</head>
<body data-page="admin"> <!-- data-page="admin" → script.js inicializa el panel de administración -->

  <canvas id="starfield"></canvas> <!-- Campo de estrellas animado -->
  <div class="nebula-overlay"></div> <!-- Capa de nebulosa -->

  <div class="page-wrap"> <!-- Envoltorio del contenido -->

    <header class="site-header"> <!-- Cabecera fija -->
      <div class="container topbar"> <!-- Barra: logo + navegación + sesión -->
        <a class="brand" href="index.php"> <!-- Logo enlazado a la portada -->
          <span class="brand-icon"> <!-- Icono SVG hexagonal con la "N" -->
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
          <a href="index.php">Inicio</a> <!-- Portada -->
          <a href="index.php#foro">Foro</a> <!-- Foro -->
          <a href="login.php">Login</a> <!-- Acceso -->
          <a class="is-active" href="admin.php">Admin</a> <!-- Página actual -->
        </nav>
        <div class="auth-slot" data-auth-slot></div> <!-- Hueco de sesión (script.js) -->
        <button class="hamburger" data-hamburger><span></span><span></span><span></span></button> <!-- Botón menú móvil -->
      </div>
    </header>

    <nav class="mobile-nav" data-mobile-nav> <!-- Menú desplegable móvil -->
      <a href="index.php">Inicio</a> <!-- Portada -->
      <a href="index.php#foro">Foro</a> <!-- Foro -->
      <a href="login.php">Login</a> <!-- Acceso -->
      <a class="is-active" href="admin.php">Admin</a> <!-- Página actual -->
    </nav>

    <main class="admin-page"> <!-- Contenido principal del panel -->
      <div class="container" data-admin-guard> <!-- "Guardia": script.js decide qué mostrar según el rol -->

        <!-- ACCESO DENEGADO (visible si no eres admin) -->
        <div class="access-denied hidden" data-admin-blocked> <!-- Oculto por defecto; se muestra a no-admins -->
          <h1>Acceso restringido</h1> <!-- Título -->
          <p>Este panel solo está disponible para administradores. Inicia sesión con la cuenta admin para continuar.</p> <!-- Mensaje -->
          <div class="cta-row"> <!-- Botones de acción -->
            <a class="btn btn-primary" href="login.php">Ir a login</a> <!-- Ir al acceso -->
            <a class="btn btn-ghost" href="index.php">Volver al foro</a> <!-- Volver a la portada -->
          </div>
        </div>

        <!-- CONTENIDO DEL ADMIN (visible solo para administradores) -->
        <div data-admin-content class="hidden"> <!-- Oculto hasta que script.js confirme rol admin -->

          <div class="admin-head"> <!-- Cabecera del panel -->
            <div>
              <span class="eyebrow">Panel de control</span> <!-- Rótulo -->
              <h1>Administración</h1> <!-- Título -->
            </div>
            <a class="btn btn-outline" href="index.php">Ver foro</a> <!-- Enlace para volver al foro -->
          </div>

          <!-- NAVEGACIÓN POR PESTAÑAS DEL ADMIN -->
          <div class="admin-tab-nav" data-admin-tab-nav> <!-- Botonera de pestañas (script.js alterna paneles) -->
            <button class="admin-tab-btn is-active" data-admin-tab="overview">Resumen</button> <!-- Pestaña Resumen (activa) -->
            <button class="admin-tab-btn" data-admin-tab="moderation">Moderación</button> <!-- Pestaña Moderación -->
            <button class="admin-tab-btn" data-admin-tab="users">Usuarios</button> <!-- Pestaña Usuarios -->
            <button class="admin-tab-btn" data-admin-tab="backups">Backups</button> <!-- Pestaña Backups -->
            <button class="admin-tab-btn admin-tab-danger" data-admin-tab="danger">Zona Peligrosa</button> <!-- Pestaña Zona Peligrosa -->
          </div>

          <!-- ══ PESTAÑA: RESUMEN ══════════════════════════════════════ -->
          <div class="admin-tab-pane" data-admin-pane="overview"> <!-- Panel de la pestaña Resumen -->
            <div class="metrics-grid"> <!-- Rejilla de tarjetas de métricas -->
              <div class="metric-card m-users"> <!-- Métrica: usuarios -->
                <strong data-admin-users>—</strong> <!-- Valor (lo rellena script.js) -->
                <span>Usuarios</span> <!-- Etiqueta -->
              </div>
              <div class="metric-card m-approved"> <!-- Métrica: posts aprobados -->
                <strong data-admin-approved>—</strong> <!-- Valor -->
                <span>Aprobados</span> <!-- Etiqueta -->
              </div>
              <div class="metric-card m-pending"> <!-- Métrica: posts pendientes -->
                <strong data-admin-pending>—</strong> <!-- Valor -->
                <span>Pendientes</span> <!-- Etiqueta -->
              </div>
              <div class="metric-card m-contacts"> <!-- Métrica: mensajes sin leer -->
                <strong data-admin-contacts>—</strong> <!-- Valor -->
                <span>Sin leer</span> <!-- Etiqueta -->
              </div>
              <div class="metric-card m-likes"> <!-- Métrica: likes totales -->
                <strong data-admin-likes>—</strong> <!-- Valor -->
                <span>Likes totales</span> <!-- Etiqueta -->
              </div>
            </div>
          </div>

          <!-- ══ PESTAÑA: MODERACIÓN ═══════════════════════════════════ -->
          <div class="admin-tab-pane hidden" data-admin-pane="moderation"> <!-- Panel Moderación (oculto al inicio) -->
            <div class="admin-layout"> <!-- Layout de columnas -->
              <div>
                <div class="admin-card"> <!-- Tarjeta: moderación de temas -->
                  <div class="admin-card-head">
                    <h3>Moderación de temas</h3> <!-- Título -->
                    <span class="admin-count-badge" data-posts-count>0 temas</span> <!-- Contador (script.js) -->
                  </div>
                  <div data-admin-posts></div> <!-- Lista de posts a moderar (script.js) -->
                </div>
                <div class="admin-card"> <!-- Tarjeta: mensajes de contacto -->
                  <div class="admin-card-head">
                    <h3>Mensajes de contacto</h3> <!-- Título -->
                    <span class="admin-count-badge" data-contacts-count>0 mensajes</span> <!-- Contador -->
                  </div>
                  <div data-admin-contacts-list></div> <!-- Lista de mensajes (script.js) -->
                </div>
              </div>
              <div>
                <div class="admin-card"> <!-- Tarjeta: miembros registrados -->
                  <div class="admin-card-head">
                    <h3>Miembros registrados</h3> <!-- Título -->
                    <span class="admin-count-badge" data-users-count>0 usuarios</span> <!-- Contador -->
                  </div>
                  <div data-admin-users-list></div> <!-- Lista de miembros (script.js) -->
                </div>
              </div>
            </div>
          </div>

          <!-- ══ PESTAÑA: USUARIOS ═════════════════════════════════════ -->
          <div class="admin-tab-pane hidden" data-admin-pane="users"> <!-- Panel Usuarios (oculto al inicio) -->
            <div class="admin-card"> <!-- Tarjeta de gestión de usuarios -->
              <div class="admin-card-head admin-card-head--filters"> <!-- Cabecera con filtros -->
                <h3>Gestión de usuarios</h3> <!-- Título -->
                <div class="admin-user-filters"> <!-- Filtros de búsqueda -->
                  <input type="text" class="admin-filter-input" data-user-search
                         placeholder="Buscar por username, nombre o email…"> <!-- Búsqueda de usuarios -->
                  <select class="admin-filter-select" data-user-role-filter> <!-- Filtro por rol -->
                    <option value="">Todos los roles</option> <!-- Sin filtro -->
                    <option value="admin">Admin</option> <!-- Solo admins -->
                    <option value="member">Miembro</option> <!-- Solo miembros -->
                  </select>
                </div>
              </div>
              <div data-admin-users-table></div> <!-- Tabla de usuarios (script.js) -->
            </div>
          </div>

          <!-- ══ PESTAÑA: BACKUPS ══════════════════════════════════════ -->
          <div class="admin-tab-pane hidden" data-admin-pane="backups"> <!-- Panel Backups (oculto al inicio) -->
            <div class="backup-intro"> <!-- Introducción -->
              <h2>Copias de seguridad</h2> <!-- Título -->
              <p>Los archivos se descargan directamente a tu carpeta de <strong>Descargas</strong>. Haz un backup antes de cualquier acción destructiva.</p> <!-- Aviso -->
            </div>
            <div class="backup-grid"> <!-- Rejilla de tarjetas de backup -->
              <div class="backup-card"> <!-- Tarjeta: backup de base de datos -->
                <div class="backup-card-body">
                  <h4>Base de datos</h4> <!-- Título -->
                  <p>Exporta usuarios, posts, likes y mensajes en formato SQL. Importable desde phpMyAdmin.</p> <!-- Descripción -->
                  <div class="backup-meta"> <!-- Etiquetas informativas -->
                    <span class="backup-badge">SQL</span> <!-- Formato -->
                    <span class="backup-badge">usuarios_db</span> <!-- BD -->
                  </div>
                </div>
                <button class="btn btn-primary" data-backup-db>Descargar SQL</button> <!-- Descarga el SQL (script.js → api backup_database) -->
              </div>
              <div class="backup-card"> <!-- Tarjeta: backup de archivos web -->
                <div class="backup-card-body">
                  <h4>Web completa (ZIP)</h4> <!-- Título -->
                  <p>ZIP con <strong>todos</strong> los archivos: PHP, CSS, JS, .htaccess, .bat y ip.php. Estructura lista para desplegar. No incluye la base de datos.</p> <!-- Descripción -->
                  <div class="backup-meta">
                    <span class="backup-badge">ZIP</span> <!-- Formato -->
                    <span class="backup-badge">gaming-foro-v2/ + ip.php</span> <!-- Contenido -->
                  </div>
                </div>
                <button class="btn btn-primary" data-backup-files>Descargar ZIP</button> <!-- Descarga el ZIP de archivos (api backup_files) -->
              </div>
            </div>
            <p class="backup-note">Los backups se guardan en la carpeta <strong>Descargas</strong> de tu navegador. Nómbralos con fecha para mantener historial.</p> <!-- Nota -->

            <!-- ── RESTAURAR ── -->
            <div class="restore-section"> <!-- Sección para restaurar desde un ZIP -->
              <div class="restore-section-head">
                <div>
                  <h3>Restaurar web desde backup</h3> <!-- Título -->
                  <p>Sube el ZIP descargado previamente para restaurar todos los archivos de la web. Los archivos actuales serán sobreescritos.</p> <!-- Aviso -->
                </div>
              </div>

              <div class="restore-drop-area" data-restore-drop> <!-- Zona de arrastrar y soltar -->
                <input type="file" id="restore-zip" accept=".zip" data-restore-zip-input> <!-- Selector de archivo ZIP -->
                <label for="restore-zip" class="restore-drop-label"> <!-- Etiqueta clicable del selector -->
                  <strong>Selecciona el ZIP de backup</strong> <!-- Texto principal -->
                  <span class="restore-drop-sub">o arrastra y suelta aquí · Solo archivos .zip</span> <!-- Texto secundario -->
                </label>
              </div>
              <div class="restore-file-info hidden" data-restore-file-info></div> <!-- Info del archivo elegido (script.js) -->

              <div class="restore-actions"> <!-- PIN + botón de restaurar -->
                <div class="restore-pin-row">
                  <label class="restore-pin-label">PIN de administrador</label> <!-- Etiqueta del PIN -->
                  <input type="password" class="danger-pin-input" data-restore-pin
                         maxlength="6" placeholder="PIN de 6 dígitos" autocomplete="off"> <!-- Campo del PIN (6 dígitos) -->
                </div>
                <button class="btn btn-danger restore-btn" data-restore-btn disabled> <!-- Botón restaurar (desactivado hasta validar) -->
                  Restaurar web
                </button>
              </div>

              <div class="restore-status hidden" data-restore-status></div> <!-- Estado de la restauración (script.js) -->
            </div>
          </div>

          <!-- ══ PESTAÑA: ZONA PELIGROSA ═══════════════════════════════ -->
          <div class="admin-tab-pane hidden" data-admin-pane="danger"> <!-- Panel Zona Peligrosa (oculto al inicio) -->

            <div class="danger-header"> <!-- Cabecera de advertencia -->
              <div>
                <h2>Zona peligrosa</h2> <!-- Título -->
                <p>Las acciones de esta sección son <strong>permanentes e irreversibles</strong>. Haz un backup antes de continuar.</p> <!-- Aviso -->
              </div>
            </div>

            <div class="danger-pin-section"> <!-- Sección de desbloqueo por PIN -->
              <label class="danger-pin-label">PIN de administrador para desbloquear acciones</label> <!-- Etiqueta -->
              <div class="danger-pin-row">
                <input type="password" class="danger-pin-input" data-danger-pin
                       maxlength="6" placeholder="PIN de 6 dígitos" autocomplete="off"> <!-- Campo del PIN -->
                <button class="btn btn-outline danger-verify-btn" data-danger-verify>Verificar PIN</button> <!-- Verifica el PIN (script.js) -->
              </div>
              <div class="pin-status" data-pin-status></div> <!-- Estado de la verificación -->
            </div>

            <div class="danger-actions hidden" data-danger-actions> <!-- Acciones destructivas (ocultas hasta validar PIN) -->
              <div class="danger-action-list"> <!-- Lista de acciones -->

                <div class="danger-action-card"> <!-- Acción: eliminar todos los posts -->
                  <div class="danger-action-info">
                    <h4>Eliminar todos los posts</h4> <!-- Título -->
                    <p>Borra permanentemente todos los temas del foro. Los usuarios siguen activos.</p> <!-- Descripción -->
                  </div>
                  <button class="btn btn-danger"
                    data-reset-target="posts" data-reset-label="eliminar todos los posts del foro"> <!-- target=posts → api reset_site -->
                    Eliminar posts
                  </button>
                </div>

                <div class="danger-action-card"> <!-- Acción: eliminar usuarios no-admin -->
                  <div class="danger-action-info">
                    <h4>Eliminar usuarios (no admin)</h4> <!-- Título -->
                    <p>Borra todos los miembros y sus posts (CASCADE). La cuenta admin permanece intacta.</p> <!-- Descripción -->
                  </div>
                  <button class="btn btn-danger"
                    data-reset-target="users" data-reset-label="eliminar todos los usuarios no-admin y sus posts"> <!-- target=users -->
                    Eliminar usuarios
                  </button>
                </div>

                <div class="danger-action-card"> <!-- Acción: vaciar mensajes de contacto -->
                  <div class="danger-action-info">
                    <h4>Vaciar mensajes de contacto</h4> <!-- Título -->
                    <p>Elimina todos los mensajes recibidos a través del formulario de contacto.</p> <!-- Descripción -->
                  </div>
                  <button class="btn btn-danger"
                    data-reset-target="contacts" data-reset-label="vaciar todos los mensajes de contacto"> <!-- target=contacts -->
                    Vaciar mensajes
                  </button>
                </div>

                <div class="danger-action-card danger-action-card--critical"> <!-- Acción crítica: reset total -->
                  <div class="danger-action-info">
                    <h4>Reset total del foro</h4> <!-- Título -->
                    <p>Elimina <strong>todos los posts, usuarios no-admin y mensajes</strong>. El foro queda vacío con solo la cuenta admin.</p> <!-- Descripción -->
                  </div>
                  <button class="btn btn-danger"
                    data-reset-target="all" data-reset-label="hacer un RESET TOTAL del foro (posts + usuarios + mensajes)"> <!-- target=all -->
                    Reset total
                  </button>
                </div>

                <div class="danger-action-card danger-action-card--nuclear"> <!-- Acción nuclear: borrar la BD entera -->
                  <div class="danger-action-info">
                    <h4>Eliminar base de datos completa</h4> <!-- Título -->
                    <p>Elimina la base de datos <code>usuarios_db</code> al completo. Se recreará vacía en la siguiente visita al foro.</p> <!-- Descripción -->
                  </div>
                  <button class="btn btn-nuclear"
                    data-reset-target="database" data-reset-label="ELIMINAR COMPLETAMENTE LA BASE DE DATOS"> <!-- target=database -->
                    ELIMINAR BD
                  </button>
                </div>

              </div>
            </div>

          </div><!-- /danger pane -->

        </div><!-- /data-admin-content -->
      </div>
    </main>

  </div><!-- /.page-wrap -->

  <div class="toast-wrap" data-toast-wrap></div> <!-- Contenedor de notificaciones (toasts) -->
  <script>window.NEXUS_CURRENT_USER = <?= $currentUserJson ?: 'null' ?>;</script> <!-- Inyecta el usuario actual como JSON -->
  <script src="script.js?v=12"></script> <!-- Carga la lógica del panel -->
</body>
</html>
