<?php require_once __DIR__ . "/page_top.php"; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <title>Iniciar sesión · NEXUS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css?v=9" />
</head>
<body data-page="login">

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
          <a class="is-active" href="login.php">Login</a>
          <a href="register.php">Registro</a>
        </nav>
        <div class="auth-slot" data-auth-slot></div>
        <button class="hamburger" data-hamburger><span></span><span></span><span></span></button>
      </div>
    </header>

    <nav class="mobile-nav" data-mobile-nav>
      <a href="index.php">Inicio</a>
      <a href="index.php#foro">Foro</a>
      <a class="is-active" href="login.php">Login</a>
      <a href="register.php">Registro</a>
    </nav>

    <main class="auth-page">
      <div class="container auth-layout">

        <!-- LEFT: branding + features -->
        <div class="auth-left">
          <div class="auth-eyebrow">✦ Bienvenido de vuelta</div>
          <h1>Entra y retoma<br><em>la partida</em></h1>
          <p>Tu sesión se mantiene activa mientras navegas. Participa en debates, da likes y crea nuevos temas desde cualquier dispositivo.</p>

          <div class="auth-features">
            <div class="auth-feature">
              <div class="af-icon">⚡</div>
              <div class="af-text">
                <strong>Acceso instantáneo</strong>
                <span>Sesión persistente en todas las páginas</span>
              </div>
            </div>
            <div class="auth-feature">
              <div class="af-icon">🏆</div>
              <div class="af-text">
                <strong>Leaderboard</strong>
                <span>Escala posiciones con tus publicaciones</span>
              </div>
            </div>
            <div class="auth-feature">
              <div class="af-icon">💬</div>
              <div class="af-text">
                <strong>Publicar y debatir</strong>
                <span>Crea temas y da likes a los mejores posts</span>
              </div>
            </div>
            <div class="auth-feature">
              <div class="af-icon">🌐</div>
              <div class="af-text">
                <strong>Comunidad global</strong>
                <span>Conecta con gamers de toda la comunidad</span>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT: form card -->
        <div>
          <div class="auth-card">
            <span class="auth-card-overline">Identificación</span>
            <h2>Iniciar sesión</h2>

            <?php if ($flashError): ?>
              <div class="auth-flash auth-flash-error"><?= htmlspecialchars($flashError) ?></div>
            <?php elseif ($flashSuccess): ?>
              <div class="auth-flash auth-flash-success"><?= htmlspecialchars($flashSuccess) ?></div>
            <?php endif; ?>

            <form class="auth-form" action="process_login.php" method="POST">

              <div class="input-group">
                <label class="field-label" for="login-email">Correo electrónico</label>
                <input class="auth-input" id="login-email" type="email" name="email"
                       placeholder="correo@ejemplo.com" autocomplete="email" required>
              </div>

              <div class="input-group">
                <label class="field-label" for="login-password">Contraseña</label>
                <input class="auth-input" id="login-password" type="password" name="password"
                       placeholder="••••••••" autocomplete="current-password" required>
              </div>

              <div class="auth-actions">
                <button class="btn btn-primary btn-shimmer" type="submit" style="width:100%;justify-content:center;">
                  <span class="btn-icon">→</span> Entrar al foro
                </button>
                <div class="auth-sep"><span>¿Aún no tienes cuenta?</span></div>
                <a class="btn btn-outline" href="register.php" style="width:100%;justify-content:center;">
                  Crear cuenta gratis
                </a>
              </div>

            </form>
          </div>
        </div>

      </div>
    </main>

  </div><!-- /.page-wrap -->

  <div class="toast-wrap" data-toast-wrap></div>
  <script>window.NEXUS_CURRENT_USER = <?= $currentUserJson ?: 'null' ?>;</script>
  <script src="script.js?v=9"></script>
</body>
</html>
