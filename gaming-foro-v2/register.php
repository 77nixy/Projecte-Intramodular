<?php require_once __DIR__ . "/page_top.php"; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <title>Crear cuenta · NEXUS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css?v=9" />
</head>
<body data-page="register">

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
          <a class="is-active" href="register.php">Registro</a>
        </nav>
        <div class="auth-slot" data-auth-slot></div>
        <button class="hamburger" data-hamburger><span></span><span></span><span></span></button>
      </div>
    </header>

    <nav class="mobile-nav" data-mobile-nav>
      <a href="index.php">Inicio</a>
      <a href="index.php#foro">Foro</a>
      <a href="login.php">Login</a>
      <a class="is-active" href="register.php">Registro</a>
    </nav>

    <main class="auth-page">
      <div class="container auth-layout">

        <!-- LEFT: branding + features -->
        <div class="auth-left">
          <div class="auth-eyebrow">🚀 Únete gratis</div>
          <h1>Empieza a<br><em>jugar en serio</em></h1>
          <p>Crea tu cuenta en segundos y accede a debates de estrategia, guías de hardware y las últimas noticias del gaming competitivo.</p>

          <div class="auth-features">
            <div class="auth-feature">
              <div class="af-icon">📝</div>
              <div class="af-text">
                <strong>Publica temas</strong>
                <span>Comparte guías, builds y análisis de meta</span>
              </div>
            </div>
            <div class="auth-feature">
              <div class="af-icon">❤️</div>
              <div class="af-text">
                <strong>Da likes</strong>
                <span>Vota los mejores contenidos de la comunidad</span>
              </div>
            </div>
            <div class="auth-feature">
              <div class="af-icon">🏆</div>
              <div class="af-text">
                <strong>Leaderboard</strong>
                <span>Sube posiciones con tus publicaciones</span>
              </div>
            </div>
            <div class="auth-feature">
              <div class="af-icon">🎮</div>
              <div class="af-text">
                <strong>Perfil gamer</strong>
                <span>Muestra tu juego favorito y tu presentación</span>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT: form card -->
        <div>
          <div class="auth-card">
            <span class="auth-card-overline">Registro</span>
            <h2>Crear cuenta</h2>

            <?php if ($flashError): ?>
              <div class="auth-flash auth-flash-error"><?= htmlspecialchars($flashError) ?></div>
            <?php elseif ($flashSuccess): ?>
              <div class="auth-flash auth-flash-success"><?= htmlspecialchars($flashSuccess) ?></div>
            <?php endif; ?>

            <form class="auth-form" action="process_register.php" method="POST">

              <div class="input-row-2">
                <div class="input-group">
                  <label class="field-label" for="reg-name">Nombre visible</label>
                  <input class="auth-input" id="reg-name" type="text" name="name"
                         minlength="2" maxlength="24" placeholder="Ej: Hugo" required>
                </div>
                <div class="input-group">
                  <label class="field-label" for="reg-username">Nick de usuario</label>
                  <input class="auth-input" id="reg-username" type="text" name="username"
                         minlength="3" maxlength="18" placeholder="Ej: ProGamer99" required>
                </div>
              </div>

              <div class="input-row-2">
                <div class="input-group">
                  <label class="field-label" for="reg-email">Correo electrónico</label>
                  <input class="auth-input" id="reg-email" type="email" name="email"
                         placeholder="correo@ejemplo.com" required>
                </div>
                <div class="input-group">
                  <label class="field-label" for="reg-game">Juego favorito</label>
                  <select class="auth-select" id="reg-game" name="favoriteGame">
                    <option value="Valorant">Valorant</option>
                    <option value="Counter-Strike">Counter-Strike</option>
                    <option value="League of Legends">League of Legends</option>
                    <option value="Fortnite">Fortnite</option>
                    <option value="Apex Legends">Apex Legends</option>
                    <option value="Overwatch 2">Overwatch 2</option>
                    <option value="Minecraft">Minecraft</option>
                    <option value="Otro">Otro</option>
                  </select>
                </div>
              </div>

              <div class="input-row-2">
                <div class="input-group">
                  <label class="field-label" for="reg-password">Contraseña</label>
                  <input class="auth-input" id="reg-password" type="password" name="password"
                         minlength="6" placeholder="Mínimo 6 caracteres" required>
                </div>
                <div class="input-group">
                  <label class="field-label" for="reg-confirm">Confirmar contraseña</label>
                  <input class="auth-input" id="reg-confirm" type="password" name="confirmPassword"
                         minlength="6" placeholder="Repite la contraseña" required>
                </div>
              </div>

              <div class="input-group">
                <label class="field-label" for="reg-bio">
                  Presentación <span style="color:var(--white-ghost);font-weight:300;text-transform:none;letter-spacing:0;">(opcional)</span>
                </label>
                <textarea class="auth-textarea" id="reg-bio" name="bio" rows="3"
                          maxlength="180" placeholder="Cuéntale a la comunidad qué juegas y qué te interesa…"></textarea>
              </div>

              <div class="auth-actions">
                <label class="check-row">
                  <input type="checkbox" name="terms" required>
                  <span>Acepto las normas de la comunidad y me comprometo a participar con respeto.</span>
                </label>
                <button class="btn btn-primary btn-shimmer" type="submit" style="width:100%;justify-content:center;">
                  <span class="btn-icon">✦</span> Crear mi cuenta
                </button>
                <div class="auth-sep"><span>¿Ya tienes cuenta?</span></div>
                <a class="btn btn-outline" href="login.php" style="width:100%;justify-content:center;">
                  Iniciar sesión
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
