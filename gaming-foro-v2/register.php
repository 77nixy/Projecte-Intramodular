<?php require_once __DIR__ . "/page_top.php"; // Arranca la app y prepara $flashError/$flashSuccess/$currentUserJson ?>
<!DOCTYPE html>
<html lang="es"> <!-- Documento en español -->
<head>
  <meta charset="UTF-8" /> <!-- Codificación UTF-8 -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" /> <!-- Responsive en móvil -->
  <title>Crear cuenta · NEXUS</title> <!-- Título de la pestaña -->
  <link rel="preconnect" href="https://fonts.googleapis.com"> <!-- Pre-conexión a Google Fonts -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin> <!-- Pre-conexión al CDN de fuentes -->
  <!-- Carga la tipografía Inter (sans-serif profesional y muy legible) -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css?v=12" /> <!-- Hoja de estilos del proyecto -->
</head>
<body data-page="register"> <!-- data-page indica a script.js que es la página de registro -->

  <canvas id="starfield"></canvas> <!-- Lienzo del campo de estrellas animado -->
  <div class="nebula-overlay"></div> <!-- Capa de nebulosa decorativa -->

  <div class="page-wrap"> <!-- Envoltorio del contenido -->

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
          <a href="index.php">Inicio</a> <!-- Portada -->
          <a href="index.php#foro">Foro</a> <!-- Ancla del foro -->
          <a href="login.php">Login</a> <!-- Acceso -->
          <a class="is-active" href="register.php">Registro</a> <!-- Página actual (resaltada) -->
        </nav>
        <div class="auth-slot" data-auth-slot></div> <!-- Hueco de sesión (lo rellena script.js) -->
        <button class="hamburger" data-hamburger><span></span><span></span><span></span></button> <!-- Botón menú móvil -->
      </div>
    </header>

    <nav class="mobile-nav" data-mobile-nav> <!-- Menú desplegable móvil -->
      <a href="index.php">Inicio</a> <!-- Portada -->
      <a href="index.php#foro">Foro</a> <!-- Foro -->
      <a href="login.php">Login</a> <!-- Acceso -->
      <a class="is-active" href="register.php">Registro</a> <!-- Página actual -->
    </nav>

    <main class="auth-page"> <!-- Contenido principal de registro -->
      <div class="container auth-layout"> <!-- Layout 2 columnas: branding | formulario -->

        <!-- IZQUIERDA: marca + ventajas -->
        <div class="auth-left">
          <div class="auth-eyebrow">Únete gratis</div> <!-- Rótulo superior -->
          <h1>Empieza a<br><em>jugar en serio</em></h1> <!-- Titular -->
          <p>Crea tu cuenta en segundos y accede a debates de estrategia, guías de hardware y las últimas noticias del gaming competitivo.</p> <!-- Descripción -->

          <div class="auth-features"> <!-- Rejilla de ventajas -->
            <div class="auth-feature"> <!-- Ventaja 1 -->
              <div class="af-text">
                <strong>Publica temas</strong> <!-- Título -->
                <span>Comparte guías, builds y análisis de meta</span> <!-- Detalle -->
              </div>
            </div>
            <div class="auth-feature"> <!-- Ventaja 2 -->
              <div class="af-text">
                <strong>Da likes</strong> <!-- Título -->
                <span>Vota los mejores contenidos de la comunidad</span> <!-- Detalle -->
              </div>
            </div>
            <div class="auth-feature"> <!-- Ventaja 3 -->
              <div class="af-text">
                <strong>Leaderboard</strong> <!-- Título -->
                <span>Sube posiciones con tus publicaciones</span> <!-- Detalle -->
              </div>
            </div>
            <div class="auth-feature"> <!-- Ventaja 4 -->
              <div class="af-text">
                <strong>Perfil gamer</strong> <!-- Título -->
                <span>Muestra tu juego favorito y tu presentación</span> <!-- Detalle -->
              </div>
            </div>
          </div>
        </div>

        <!-- DERECHA: tarjeta del formulario -->
        <div>
          <div class="auth-card"> <!-- Tarjeta del formulario de registro -->
            <span class="auth-card-overline">Registro</span> <!-- Rótulo superior -->
            <h2>Crear cuenta</h2> <!-- Título -->

            <?php if ($flashError): ?> <!-- Si hay error de un intento previo... -->
              <div class="auth-flash auth-flash-error"><?= htmlspecialchars($flashError) ?></div> <!-- ...lo muestra escapado -->
            <?php elseif ($flashSuccess): ?> <!-- Si hay mensaje de éxito... -->
              <div class="auth-flash auth-flash-success"><?= htmlspecialchars($flashSuccess) ?></div> <!-- ...lo muestra escapado -->
            <?php endif; ?> <!-- Fin de los mensajes flash -->

            <form class="auth-form" action="process_register.php" method="POST"> <!-- Envía a process_register.php por POST -->

              <div class="input-row-2"> <!-- Fila de 2 campos -->
                <div class="input-group"> <!-- Campo: nombre visible -->
                  <label class="field-label" for="reg-name">Nombre visible</label> <!-- Etiqueta -->
                  <input class="auth-input" id="reg-name" type="text" name="name"
                         minlength="2" maxlength="24" placeholder="Ej: Hugo" required> <!-- 2-24 caracteres, obligatorio -->
                </div>
                <div class="input-group"> <!-- Campo: nick -->
                  <label class="field-label" for="reg-username">Nick de usuario</label> <!-- Etiqueta -->
                  <input class="auth-input" id="reg-username" type="text" name="username"
                         minlength="3" maxlength="18" placeholder="Ej: ProGamer99" required> <!-- 3-18 caracteres, obligatorio -->
                </div>
              </div>

              <div class="input-row-2"> <!-- Fila de 2 campos -->
                <div class="input-group"> <!-- Campo: email -->
                  <label class="field-label" for="reg-email">Correo electrónico</label> <!-- Etiqueta -->
                  <input class="auth-input" id="reg-email" type="email" name="email"
                         placeholder="correo@ejemplo.com" required> <!-- Email, obligatorio -->
                </div>
                <div class="input-group"> <!-- Campo: juego favorito -->
                  <label class="field-label" for="reg-game">Juego favorito</label> <!-- Etiqueta -->
                  <select class="auth-select" id="reg-game" name="favoriteGame"> <!-- Desplegable de juegos -->
                    <option value="Valorant">Valorant</option> <!-- Opción -->
                    <option value="Counter-Strike">Counter-Strike</option> <!-- Opción -->
                    <option value="League of Legends">League of Legends</option> <!-- Opción -->
                    <option value="Fortnite">Fortnite</option> <!-- Opción -->
                    <option value="Apex Legends">Apex Legends</option> <!-- Opción -->
                    <option value="Overwatch 2">Overwatch 2</option> <!-- Opción -->
                    <option value="Minecraft">Minecraft</option> <!-- Opción -->
                    <option value="Otro">Otro</option> <!-- Opción por defecto -->
                  </select>
                </div>
              </div>

              <div class="input-row-2"> <!-- Fila de 2 campos -->
                <div class="input-group"> <!-- Campo: contraseña -->
                  <label class="field-label" for="reg-password">Contraseña</label> <!-- Etiqueta -->
                  <input class="auth-input" id="reg-password" type="password" name="password"
                         minlength="6" placeholder="Mínimo 6 caracteres" required> <!-- Mín. 6, obligatorio -->
                </div>
                <div class="input-group"> <!-- Campo: confirmar contraseña -->
                  <label class="field-label" for="reg-confirm">Confirmar contraseña</label> <!-- Etiqueta -->
                  <input class="auth-input" id="reg-confirm" type="password" name="confirmPassword"
                         minlength="6" placeholder="Repite la contraseña" required> <!-- Debe coincidir, obligatorio -->
                </div>
              </div>

              <div class="input-group"> <!-- Campo: presentación (biografía) -->
                <label class="field-label" for="reg-bio"> <!-- Etiqueta -->
                  Presentación <span style="color:var(--white-ghost);font-weight:300;text-transform:none;letter-spacing:0;">(opcional)</span> <!-- Marca "opcional" -->
                </label>
                <textarea class="auth-textarea" id="reg-bio" name="bio" rows="3"
                          maxlength="180" placeholder="Cuéntale a la comunidad qué juegas y qué te interesa…"></textarea> <!-- Máx. 180 caracteres -->
              </div>

              <div class="auth-actions"> <!-- Casilla de normas y botones -->
                <label class="check-row"> <!-- Casilla de aceptación de normas -->
                  <input type="checkbox" name="terms" required> <!-- Obligatorio aceptar -->
                  <span>Acepto las normas de la comunidad y me comprometo a participar con respeto.</span> <!-- Texto de las normas -->
                </label>
                <button class="btn btn-primary btn-shimmer" type="submit" style="width:100%;justify-content:center;"> <!-- Botón de envío -->
                  Crear mi cuenta <!-- Texto del botón -->
                </button>
                <div class="auth-sep"><span>¿Ya tienes cuenta?</span></div> <!-- Separador -->
                <a class="btn btn-outline" href="login.php" style="width:100%;justify-content:center;"> <!-- Enlace al login -->
                  Iniciar sesión
                </a>
              </div>

            </form> <!-- Fin del formulario -->
          </div>
        </div>

      </div>
    </main>

  </div><!-- /.page-wrap --> <!-- Fin del envoltorio -->

  <div class="toast-wrap" data-toast-wrap></div> <!-- Contenedor de notificaciones (toasts) -->
  <script>window.NEXUS_CURRENT_USER = <?= $currentUserJson ?: 'null' ?>;</script> <!-- Inyecta el usuario actual como JSON -->
  <script src="script.js?v=12"></script> <!-- Carga la lógica de la página -->
</body>
</html>
