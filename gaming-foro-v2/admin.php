<?php require_once __DIR__ . "/page_top.php"; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <title>Panel de administración · NEXUS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css?v=9" />
</head>
<body data-page="admin">

  <canvas id="starfield"></canvas>
  <div class="nebula-overlay"></div>

  <div class="page-wrap">

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
          <a href="index.php">Inicio</a>
          <a href="index.php#foro">Foro</a>
          <a href="login.php">Login</a>
          <a class="is-active" href="admin.php">Admin</a>
        </nav>
        <div class="auth-slot" data-auth-slot></div>
        <button class="hamburger" data-hamburger><span></span><span></span><span></span></button>
      </div>
    </header>

    <nav class="mobile-nav" data-mobile-nav>
      <a href="index.php">Inicio</a>
      <a href="index.php#foro">Foro</a>
      <a href="login.php">Login</a>
      <a class="is-active" href="admin.php">Admin</a>
    </nav>

    <main class="admin-page">
      <div class="container" data-admin-guard>

        <!-- ACCESS DENIED -->
        <div class="access-denied hidden" data-admin-blocked>
          <div class="access-denied-icon">🔒</div>
          <h1>Acceso restringido</h1>
          <p>Este panel solo está disponible para administradores. Inicia sesión con la cuenta admin para continuar.</p>
          <div class="cta-row">
            <a class="btn btn-primary" href="login.php">Ir a login</a>
            <a class="btn btn-ghost" href="index.php">Volver al foro</a>
          </div>
        </div>

        <!-- ADMIN CONTENT -->
        <div data-admin-content class="hidden">

          <div class="admin-head">
            <div>
              <span class="eyebrow">Panel de control</span>
              <h1>Administración</h1>
            </div>
            <a class="btn btn-outline" href="index.php">← Ver foro</a>
          </div>

          <!-- ADMIN TAB NAV -->
          <div class="admin-tab-nav" data-admin-tab-nav>
            <button class="admin-tab-btn is-active" data-admin-tab="overview">Resumen</button>
            <button class="admin-tab-btn" data-admin-tab="moderation">Moderación</button>
            <button class="admin-tab-btn" data-admin-tab="users">Usuarios</button>
            <button class="admin-tab-btn" data-admin-tab="backups">Backups</button>
            <button class="admin-tab-btn admin-tab-danger" data-admin-tab="danger">Zona Peligrosa</button>
          </div>

          <!-- ══ TAB: RESUMEN ══════════════════════════════════════════ -->
          <div class="admin-tab-pane" data-admin-pane="overview">
            <div class="metrics-grid">
              <div class="metric-card m-users">
                <div class="metric-icon">👥</div>
                <strong data-admin-users>—</strong>
                <span>Usuarios</span>
              </div>
              <div class="metric-card m-approved">
                <div class="metric-icon">✅</div>
                <strong data-admin-approved>—</strong>
                <span>Aprobados</span>
              </div>
              <div class="metric-card m-pending">
                <div class="metric-icon">⏳</div>
                <strong data-admin-pending>—</strong>
                <span>Pendientes</span>
              </div>
              <div class="metric-card m-contacts">
                <div class="metric-icon">📨</div>
                <strong data-admin-contacts>—</strong>
                <span>Sin leer</span>
              </div>
              <div class="metric-card m-likes">
                <div class="metric-icon">❤️</div>
                <strong data-admin-likes>—</strong>
                <span>Likes totales</span>
              </div>
            </div>
          </div>

          <!-- ══ TAB: MODERACIÓN ═══════════════════════════════════════ -->
          <div class="admin-tab-pane hidden" data-admin-pane="moderation">
            <div class="admin-layout">
              <div>
                <div class="admin-card">
                  <div class="admin-card-head">
                    <h3>Moderación de temas</h3>
                    <span class="admin-count-badge" data-posts-count>0 temas</span>
                  </div>
                  <div data-admin-posts></div>
                </div>
                <div class="admin-card">
                  <div class="admin-card-head">
                    <h3>Mensajes de contacto</h3>
                    <span class="admin-count-badge" data-contacts-count>0 mensajes</span>
                  </div>
                  <div data-admin-contacts-list></div>
                </div>
              </div>
              <div>
                <div class="admin-card">
                  <div class="admin-card-head">
                    <h3>Miembros registrados</h3>
                    <span class="admin-count-badge" data-users-count>0 usuarios</span>
                  </div>
                  <div data-admin-users-list></div>
                </div>
              </div>
            </div>
          </div>

          <!-- ══ TAB: USUARIOS ═════════════════════════════════════════ -->
          <div class="admin-tab-pane hidden" data-admin-pane="users">
            <div class="admin-card">
              <div class="admin-card-head admin-card-head--filters">
                <h3>Gestión de usuarios</h3>
                <div class="admin-user-filters">
                  <input type="text" class="admin-filter-input" data-user-search
                         placeholder="Buscar por username, nombre o email…">
                  <select class="admin-filter-select" data-user-role-filter>
                    <option value="">Todos los roles</option>
                    <option value="admin">Admin</option>
                    <option value="member">Miembro</option>
                  </select>
                </div>
              </div>
              <div data-admin-users-table></div>
            </div>
          </div>

          <!-- ══ TAB: BACKUPS ══════════════════════════════════════════ -->
          <div class="admin-tab-pane hidden" data-admin-pane="backups">
            <div class="backup-intro">
              <h2>Copias de seguridad</h2>
              <p>Los archivos se descargan directamente a tu carpeta de <strong>Descargas</strong>. Haz un backup antes de cualquier acción destructiva.</p>
            </div>
            <div class="backup-grid">
              <div class="backup-card">
                <div class="backup-card-icon">🗄️</div>
                <div class="backup-card-body">
                  <h4>Base de datos</h4>
                  <p>Exporta usuarios, posts, likes y mensajes en formato SQL. Importable desde phpMyAdmin.</p>
                  <div class="backup-meta">
                    <span class="backup-badge">SQL</span>
                    <span class="backup-badge">usuarios_db</span>
                  </div>
                </div>
                <button class="btn btn-primary" data-backup-db>Descargar SQL</button>
              </div>
              <div class="backup-card">
                <div class="backup-card-icon">📁</div>
                <div class="backup-card-body">
                  <h4>Web completa (ZIP)</h4>
                  <p>ZIP con <strong>todos</strong> los archivos: PHP, CSS, JS, .htaccess, .bat y ip.php. Estructura lista para desplegar. No incluye la base de datos.</p>
                  <div class="backup-meta">
                    <span class="backup-badge">ZIP</span>
                    <span class="backup-badge">gaming-foro-v2/ + ip.php</span>
                  </div>
                </div>
                <button class="btn btn-primary" data-backup-files>Descargar ZIP</button>
              </div>
              <div class="backup-card backup-card--full">
                <div class="backup-card-icon">💾</div>
                <div class="backup-card-body">
                  <h4>Backup completo</h4>
                  <p>Un solo ZIP con <strong>todo</strong>: PHP, CSS, JS, .htaccess y la base de datos SQL dentro. Todo lo necesario para restaurar NEXUS desde cero.</p>
                  <div class="backup-meta">
                    <span class="backup-badge">ZIP todo-en-uno</span>
                    <span class="backup-badge backup-badge--green">Recomendado</span>
                  </div>
                </div>
                <button class="btn btn-primary" data-backup-full>Backup completo</button>
              </div>
            </div>
            <p class="backup-note">Los backups se guardan en la carpeta <strong>Descargas</strong> de tu navegador. Nómbralos con fecha para mantener historial.</p>

            <!-- ── RESTAURAR ── -->
            <div class="restore-section">
              <div class="restore-section-head">
                <div>
                  <h3>Restaurar web desde backup</h3>
                  <p>Sube el ZIP descargado previamente para restaurar todos los archivos de la web. Los archivos actuales serán sobreescritos.</p>
                </div>
              </div>

              <div class="restore-drop-area" data-restore-drop>
                <input type="file" id="restore-zip" accept=".zip" data-restore-zip-input>
                <label for="restore-zip" class="restore-drop-label">
                  <span class="restore-drop-icon">📦</span>
                  <strong>Selecciona el ZIP de backup</strong>
                  <span class="restore-drop-sub">o arrastra y suelta aquí · Solo archivos .zip</span>
                </label>
              </div>
              <div class="restore-file-info hidden" data-restore-file-info></div>

              <div class="restore-actions">
                <div class="restore-pin-row">
                  <label class="restore-pin-label">PIN de administrador</label>
                  <input type="password" class="danger-pin-input" data-restore-pin
                         maxlength="6" placeholder="● ● ● ● ● ●" autocomplete="off">
                </div>
                <button class="btn btn-danger restore-btn" data-restore-btn disabled>
                  Restaurar web
                </button>
              </div>

              <div class="restore-status hidden" data-restore-status></div>
            </div>
          </div>

          <!-- ══ TAB: ZONA PELIGROSA ═══════════════════════════════════ -->
          <div class="admin-tab-pane hidden" data-admin-pane="danger">

            <div class="danger-header">
              <div class="danger-header-icon">⚠️</div>
              <div>
                <h2>Zona peligrosa</h2>
                <p>Las acciones de esta sección son <strong>permanentes e irreversibles</strong>. Haz un backup antes de continuar.</p>
              </div>
            </div>

            <div class="danger-pin-section">
              <label class="danger-pin-label">PIN de administrador para desbloquear acciones</label>
              <div class="danger-pin-row">
                <input type="password" class="danger-pin-input" data-danger-pin
                       maxlength="6" placeholder="● ● ● ● ● ●" autocomplete="off">
                <button class="btn btn-outline danger-verify-btn" data-danger-verify>Verificar PIN</button>
              </div>
              <div class="pin-status" data-pin-status></div>
            </div>

            <div class="danger-actions hidden" data-danger-actions>
              <div class="danger-action-list">

                <div class="danger-action-card">
                  <div class="danger-action-info">
                    <h4>Eliminar todos los posts</h4>
                    <p>Borra permanentemente todos los temas del foro. Los usuarios siguen activos.</p>
                  </div>
                  <button class="btn btn-danger"
                    data-reset-target="posts" data-reset-label="eliminar todos los posts del foro">
                    Eliminar posts
                  </button>
                </div>

                <div class="danger-action-card">
                  <div class="danger-action-info">
                    <h4>Eliminar usuarios (no admin)</h4>
                    <p>Borra todos los miembros y sus posts (CASCADE). La cuenta admin permanece intacta.</p>
                  </div>
                  <button class="btn btn-danger"
                    data-reset-target="users" data-reset-label="eliminar todos los usuarios no-admin y sus posts">
                    Eliminar usuarios
                  </button>
                </div>

                <div class="danger-action-card">
                  <div class="danger-action-info">
                    <h4>Vaciar mensajes de contacto</h4>
                    <p>Elimina todos los mensajes recibidos a través del formulario de contacto.</p>
                  </div>
                  <button class="btn btn-danger"
                    data-reset-target="contacts" data-reset-label="vaciar todos los mensajes de contacto">
                    Vaciar mensajes
                  </button>
                </div>

                <div class="danger-action-card danger-action-card--critical">
                  <div class="danger-action-info">
                    <h4>Reset total del foro</h4>
                    <p>Elimina <strong>todos los posts, usuarios no-admin y mensajes</strong>. El foro queda vacío con solo la cuenta admin.</p>
                  </div>
                  <button class="btn btn-danger"
                    data-reset-target="all" data-reset-label="hacer un RESET TOTAL del foro (posts + usuarios + mensajes)">
                    Reset total
                  </button>
                </div>

                <div class="danger-action-card danger-action-card--nuclear">
                  <div class="danger-action-info">
                    <h4>☠️ Eliminar base de datos completa</h4>
                    <p>Elimina la base de datos <code>usuarios_db</code> al completo. Se recreará vacía en la siguiente visita al foro.</p>
                  </div>
                  <button class="btn btn-nuclear"
                    data-reset-target="database" data-reset-label="ELIMINAR COMPLETAMENTE LA BASE DE DATOS">
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

  <div class="toast-wrap" data-toast-wrap></div>
  <script>window.NEXUS_CURRENT_USER = <?= $currentUserJson ?: 'null' ?>;</script>
  <script src="script.js?v=9"></script>
</body>
</html>
