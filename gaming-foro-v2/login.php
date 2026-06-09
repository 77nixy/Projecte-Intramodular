<?php require_once __DIR__ . "/page_top.php"; // Arranca la app y prepara $flashError/$flashSuccess/$currentUserJson ?>
<!DOCTYPE html>
<html lang="es"> <!-- Documento en español -->
<head>
  <meta charset="UTF-8" /> <!-- Codificación de caracteres UTF-8 -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" /> <!-- Responsive: ajusta al ancho del dispositivo -->
  <title>Iniciar sesión · NEXUS</title> <!-- Título de la pestaña del navegador -->
  <link rel="preconnect" href="https://fonts.googleapis.com"> <!-- Adelanta la conexión a Google Fonts (rendimiento) -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin> <!-- Adelanta la conexión al CDN de fuentes -->
  <!-- Carga la tipografía Inter (sans-serif profesional y muy legible) -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css?v=12" /> <!-- Hoja de estilos (?v=12 fuerza recarga al cambiar) -->
</head>
<body data-page="login"> <!-- data-page lo lee script.js para saber en qué página está -->

  <canvas id="starfield"></canvas> <!-- Lienzo donde se dibuja el campo de estrellas animado -->
  <div class="nebula-overlay"></div> <!-- Capa de nebulosa con degradados encima del canvas -->

  <div class="page-wrap"> <!-- Envoltorio de todo el contenido (por encima del fondo) -->

    <header class="site-header"> <!-- Cabecera fija superior -->
      <div class="container topbar"> <!-- Barra interna: logo + navegación + sesión -->
        <a class="brand" href="index.php"> <!-- Logo enlazado a la portada -->
          <span class="brand-icon"> <!-- Contenedor del icono hexagonal SVG -->
            <!-- Logo SVG: hexágono con la "N" de NEXUS y vértices iluminados -->
            <svg class="brand-logo-svg" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M20 2L35.6 11V29L20 38L4.4 29V11Z" stroke="rgba(255,255,255,0.65)" stroke-width="1.3" fill="rgba(255,255,255,0.07)"/> <!-- Contorno del hexágono -->
              <path d="M11 28V12L29 28V12" stroke="white" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/> <!-- Trazo de la letra N -->
              <circle cx="20" cy="2" r="1.9" fill="white"/> <!-- Vértice superior (más brillante) -->
              <circle cx="35.6" cy="11" r="1.2" fill="rgba(255,255,255,0.75)"/> <!-- Vértice superior derecho -->
              <circle cx="35.6" cy="29" r="1.2" fill="rgba(255,255,255,0.75)"/> <!-- Vértice inferior derecho -->
              <circle cx="4.4" cy="11" r="1.2" fill="rgba(255,255,255,0.75)"/> <!-- Vértice superior izquierdo -->
              <circle cx="4.4" cy="29" r="1.2" fill="rgba(255,255,255,0.75)"/> <!-- Vértice inferior izquierdo -->
            </svg>
          </span>
          <span class="brand-text-wrap"> <!-- Texto de la marca (nombre + lema) -->
            <span class="brand-name">NEXUS</span> <!-- Nombre de la marca -->
            <span class="brand-tagline">foro gaming competitivo</span> <!-- Lema bajo el nombre -->
          </span>
        </a>
        <nav class="main-nav"> <!-- Navegación principal (escritorio) -->
          <a href="index.php">Inicio</a> <!-- Enlace a la portada -->
          <a href="index.php#foro">Foro</a> <!-- Enlace al ancla del foro -->
          <a class="is-active" href="login.php">Login</a> <!-- Página actual (resaltada) -->
          <a href="register.php">Registro</a> <!-- Enlace al registro -->
        </nav>
        <div class="auth-slot" data-auth-slot></div> <!-- Hueco que script.js rellena con el estado de sesión -->
        <button class="hamburger" data-hamburger><span></span><span></span><span></span></button> <!-- Botón menú móvil (3 barras) -->
      </div>
    </header>

    <nav class="mobile-nav" data-mobile-nav> <!-- Menú desplegable para móvil -->
      <a href="index.php">Inicio</a> <!-- Enlace a la portada -->
      <a href="index.php#foro">Foro</a> <!-- Enlace al foro -->
      <a class="is-active" href="login.php">Login</a> <!-- Página actual -->
      <a href="register.php">Registro</a> <!-- Enlace al registro -->
    </nav>

    <main class="auth-page"> <!-- Contenido principal de la página de acceso -->
      <div class="container auth-layout"> <!-- Layout de 2 columnas: branding | formulario -->

        <!-- IZQUIERDA: marca + ventajas -->
        <div class="auth-left">
          <div class="auth-eyebrow">Bienvenido de vuelta</div> <!-- Pequeño rótulo superior -->
          <h1>Entra y retoma<br><em>la partida</em></h1> <!-- Titular de la sección -->
          <p>Tu sesión se mantiene activa mientras navegas. Participa en debates, da likes y crea nuevos temas desde cualquier dispositivo.</p> <!-- Texto descriptivo -->

          <div class="auth-features"> <!-- Rejilla de ventajas del foro -->
            <div class="auth-feature"> <!-- Ventaja 1 -->
              <div class="af-text"> <!-- Texto de la ventaja -->
                <strong>Acceso instantáneo</strong> <!-- Título -->
                <span>Sesión persistente en todas las páginas</span> <!-- Detalle -->
              </div>
            </div>
            <div class="auth-feature"> <!-- Ventaja 2 -->
              <div class="af-text">
                <strong>Leaderboard</strong> <!-- Título -->
                <span>Escala posiciones con tus publicaciones</span> <!-- Detalle -->
              </div>
            </div>
            <div class="auth-feature"> <!-- Ventaja 3 -->
              <div class="af-text">
                <strong>Publicar y debatir</strong> <!-- Título -->
                <span>Crea temas y da likes a los mejores posts</span> <!-- Detalle -->
              </div>
            </div>
            <div class="auth-feature"> <!-- Ventaja 4 -->
              <div class="af-text">
                <strong>Comunidad global</strong> <!-- Título -->
                <span>Conecta con gamers de toda la comunidad</span> <!-- Detalle -->
              </div>
            </div>
          </div>
        </div>

        <!-- DERECHA: tarjeta del formulario -->
        <div>
          <div class="auth-card"> <!-- Tarjeta que contiene el formulario de login -->
            <span class="auth-card-overline">Identificación</span> <!-- Rótulo superior de la tarjeta -->
            <h2>Iniciar sesión</h2> <!-- Título del formulario -->

            <?php if ($flashError): ?> <!-- Si hay un mensaje de error de un intento previo... -->
              <div class="auth-flash auth-flash-error"><?= htmlspecialchars($flashError) ?></div> <!-- ...lo muestra (escapado) -->
            <?php elseif ($flashSuccess): ?> <!-- Si en cambio hay un mensaje de éxito... -->
              <div class="auth-flash auth-flash-success"><?= htmlspecialchars($flashSuccess) ?></div> <!-- ...lo muestra (escapado) -->
            <?php endif; ?> <!-- Fin del bloque de mensajes flash -->

            <form class="auth-form" action="process_login.php" method="POST"> <!-- Envía los datos a process_login.php por POST -->

              <div class="input-group"> <!-- Grupo del campo email -->
                <label class="field-label" for="login-email">Correo electrónico</label> <!-- Etiqueta del email -->
                <input class="auth-input" id="login-email" type="email" name="email"
                       placeholder="correo@ejemplo.com" autocomplete="email" required> <!-- Campo email (obligatorio) -->
              </div>

              <div class="input-group"> <!-- Grupo del campo contraseña -->
                <label class="field-label" for="login-password">Contraseña</label> <!-- Etiqueta de la contraseña -->
                <input class="auth-input" id="login-password" type="password" name="password"
                       placeholder="Tu contraseña" autocomplete="current-password" required> <!-- Campo contraseña (obligatorio) -->
              </div>

              <div class="auth-actions"> <!-- Botones y enlaces de acción -->
                <button class="btn btn-primary btn-shimmer" type="submit" style="width:100%;justify-content:center;"> <!-- Botón de envío -->
                  Entrar al foro <!-- Texto del botón -->
                </button>
                <div class="auth-sep"><span>¿Aún no tienes cuenta?</span></div> <!-- Separador con texto -->
                <a class="btn btn-outline" href="register.php" style="width:100%;justify-content:center;"> <!-- Enlace al registro -->
                  Crear cuenta gratis
                </a>
              </div>

            </form> <!-- Fin del formulario -->
          </div>
        </div>

      </div>
    </main>

  </div><!-- /.page-wrap --> <!-- Fin del envoltorio de la página -->

  <div class="toast-wrap" data-toast-wrap></div> <!-- Contenedor de notificaciones emergentes (toasts) -->
  <script>window.NEXUS_CURRENT_USER = <?= $currentUserJson ?: 'null' ?>;</script> <!-- Inyecta el usuario actual como JSON para script.js -->
  <script src="script.js?v=12"></script> <!-- Carga la lógica de la página -->
</body>
</html>
