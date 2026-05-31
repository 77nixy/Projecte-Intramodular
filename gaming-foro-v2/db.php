<?php
/**
 * NEXUS//BOARD — Database bootstrap
 * Creates all tables if missing, seeds the admin account,
 * seeds demo member accounts, and seeds demo posts & contacts.
 */

$host      = 'localhost';
$usuario   = 'root';
$contrasena = '';
$basedatos  = 'usuarios_db';

/* Intentar conectar, crear la BD si no existe aún */
$connCheck = @new mysqli($host, $usuario, $contrasena);
if ($connCheck->connect_error) {
    $isApi = (str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'api.php'));
    if ($isApi) {
        header('Content-Type: application/json');
        http_response_code(503);
        die(json_encode(['error' => 'MySQL no disponible. Ejecuta ARRANCAR.bat primero.']));
    }
    die('<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
<title>NEXUS//BOARD — Error de base de datos</title>
<style>body{background:#020208;color:#e8eef8;font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
.box{background:rgba(255,255,255,.04);border:1px solid rgba(200,216,240,.15);border-radius:20px;padding:48px 40px;max-width:480px;text-align:center}
h1{color:#ef4444;font-size:1.4rem;margin-bottom:16px}p{color:rgba(200,216,240,.7);line-height:1.7}
code{background:rgba(255,255,255,.07);padding:2px 8px;border-radius:6px;color:#c8d8f0}
</style></head><body><div class="box">
<h1>MySQL no está corriendo</h1>
<p>El servidor de base de datos no responde.<br>
Abre la carpeta del proyecto y ejecuta <code>ARRANCAR.bat</code><br>
o inicia MySQL desde el panel de XAMPP.</p>
</div></body></html>');
}

$connCheck->query("CREATE DATABASE IF NOT EXISTS `{$basedatos}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$connCheck->close();

$conn = new mysqli($host, $usuario, $contrasena, $basedatos);
if ($conn->connect_error) {
    die(json_encode(['error' => 'Error de conexión: ' . $conn->connect_error]));
}
$conn->set_charset('utf8mb4');

/* ═══════════════════════════════════════════════════════════════
   TABLE: usuarios
   ═══════════════════════════════════════════════════════════════ */
$conn->query("CREATE TABLE IF NOT EXISTS usuarios (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100)  NOT NULL,
    username        VARCHAR(50)   NOT NULL UNIQUE,
    email           VARCHAR(150)  NOT NULL UNIQUE,
    password        VARCHAR(255)  NOT NULL,
    favorite_game   VARCHAR(100)  DEFAULT 'Otro',
    bio             TEXT          DEFAULT NULL,
    role            ENUM('admin','member') NOT NULL DEFAULT 'member',
    fecha_registro  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_fecha (fecha_registro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

/* ═══════════════════════════════════════════════════════════════
   TABLE: posts
   ═══════════════════════════════════════════════════════════════ */
$conn->query("CREATE TABLE IF NOT EXISTS posts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(100)  NOT NULL,
    content     TEXT          NOT NULL,
    category    VARCHAR(50)   NOT NULL DEFAULT 'general',
    author_id   INT           NOT NULL,
    likes       INT           DEFAULT 0,
    approved    TINYINT(1)    DEFAULT 0,
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_category   (category),
    INDEX idx_approved   (approved),
    INDEX idx_created_at (created_at),
    INDEX idx_likes      (likes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

/* ═══════════════════════════════════════════════════════════════
   TABLE: contacts
   ═══════════════════════════════════════════════════════════════ */
$conn->query("CREATE TABLE IF NOT EXISTS contacts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    email       VARCHAR(150)  NOT NULL,
    subject     VARCHAR(200)  NOT NULL,
    message     TEXT          NOT NULL,
    `read`      TINYINT(1)    DEFAULT 0,
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_read       (`read`),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

/* ═══════════════════════════════════════════════════════════════
   SEED: Admin account
   ═══════════════════════════════════════════════════════════════ */
$adminEmail = 'admin@nexusboard.gg';
$checkAdmin = $conn->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
$checkAdmin->bind_param('s', $adminEmail);
$checkAdmin->execute();
$adminExists = $checkAdmin->get_result()->num_rows > 0;
$checkAdmin->close();

if (!$adminExists) {
    $adminNombre   = 'Admin Nexus';
    $adminUsername = 'nexusadmin';
    $adminPassword = password_hash('Admin123!', PASSWORD_BCRYPT, ['cost' => 12]);
    $adminGame     = 'Counter-Strike';
    $adminBio      = 'Administrador principal de NEXUS//BOARD. Aquí para mantener el orden en la galaxia.';
    $adminRole     = 'admin';

    $ins = $conn->prepare(
        'INSERT INTO usuarios (nombre, username, email, password, favorite_game, bio, role)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $ins->bind_param('sssssss', $adminNombre, $adminUsername, $adminEmail, $adminPassword, $adminGame, $adminBio, $adminRole);
    $ins->execute();
    $ins->close();
}

/* ═══════════════════════════════════════════════════════════════
   SEED: Demo member accounts (only when users table is nearly empty)
   ═══════════════════════════════════════════════════════════════ */
$userCount = (int) $conn->query("SELECT COUNT(*) as c FROM usuarios")->fetch_assoc()['c'];

if ($userCount <= 1) {
    $demoMembers = [
        ['ProSniper88',   'prosniper88',   'pro@nexus.gg',      'Valorant',          'Main Jett Diamond II. Entreno aim 30 min diarios.'],
        ['LunarGG',       'lunargg',       'lunar@nexus.gg',    'Apex Legends',      'Jugador de ranked desde temporada 1. Lifeline main.'],
        ['GalaxyBuilder', 'galaxybuilder', 'galaxy@nexus.gg',   'Minecraft',         'Constructor de megaproyectos y redstone engineer.'],
        ['MidLaneKing',   'midlaneking',   'mid@nexus.gg',      'League of Legends', 'Plat 1 midlaner. Especialista en Zed y Akali.'],
        ['RifleQueen',    'riflequeen',    'rifle@nexus.gg',    'Counter-Strike',    'FaceIt Nivel 8. Prefiero duels de pistola.'],
        ['FortBuilder',   'fortbuilder',   'fort@nexus.gg',     'Fortnite',          'Box fighter con 15k partidas. Practica builds diario.'],
        ['TacticalAna',   'tacticalana',   'ana@nexus.gg',      'Overwatch 2',       'Support main GM. Especialista en Ana y Kiriko.'],
        ['HardwareGuru',  'hardwareguru',  'hardware@nexus.gg', 'Counter-Strike',    'Enthusiast de periféricos y monitores gaming.'],
    ];

    $insUser = $conn->prepare(
        'INSERT INTO usuarios (nombre, username, email, password, favorite_game, bio, role)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $demoPass = password_hash('Demo1234!', PASSWORD_BCRYPT, ['cost' => 10]);
    $role     = 'member';

    foreach ($demoMembers as $m) {
        $insUser->bind_param('sssssss', $m[0], $m[1], $m[2], $demoPass, $m[3], $m[4], $role);
        $insUser->execute();
    }
    $insUser->close();
}

/* ═══════════════════════════════════════════════════════════════
   SEED: Posts
   ═══════════════════════════════════════════════════════════════ */
$postCount = (int) $conn->query("SELECT COUNT(*) as c FROM posts")->fetch_assoc()['c'];
$adminId   = (int) $conn->query("SELECT id FROM usuarios WHERE role='admin' LIMIT 1")->fetch_assoc()['id'];

if ($postCount === 0 && $adminId > 0) {
    $seedPosts = [
        ['Cómo entrenar aim sin quemarte en ranked',
         'Comparto una rutina simple de 30 minutos con bloques de flick, tracking y revisión rápida para mantener constancia sin saturarse. Practicad en Aim Lab o Kovaak 15 minutos cada mañana antes de jugar ranked. La clave está en la constancia: es mejor 20 minutos diarios que 2 horas los fines de semana.',
         'fps', $adminId, 24, 1],

        ['Guía seria para elegir monitor 240Hz sin gastar de más',
         'Resumen claro de paneles, tiempo de respuesta real y qué mirar si juegas shooters. La idea es no pagar marketing vacío. Buscad panel IPS con 1ms GTG y FreeSync/G-Sync compatible. Marcas recomendadas: ASUS ROG Swift, LG UltraGear, Samsung Odyssey. Evitad paneles TN salvo que prioricéis velocidad sobre color.',
         'hardware', $adminId, 17, 1],

        ['Errores de macro más comunes en solo queue',
         'Si tu equipo pierde ventaja aunque gane líneas, normalmente falla la gestión de tempo, visión y resets. Os dejo un esquema útil: 1) Nunca pelees en el río sin visión. 2) Rotad tras cada kill, no os quedéis en línea. 3) Los barones no se pelean antes del minuto 25 sin ventaja. Priorizad objetivos sobre kills.',
         'estrategia', $adminId, 11, 1],

        ['Cambios del último parche que sí afectan al competitivo',
         'No todo parche cambia el meta. Aquí separo humo de cambios realmente relevantes para equipos y jugadores de ladder. Los nerfs a los ADCs van a cambiar la fase de líneas significativamente. Caitlyn pierde el 8% de su radio de ataque básico — esto cambia los matchups de botlane completamente.',
         'noticias', $adminId, 9, 1],

        ['Mejor configuración de sensibilidad para Apex Legends',
         'Después de 500 horas he encontrado la configuración óptima. eDPI entre 800-1200 funciona mejor para la mayoría. Para Lifeline y soportes recomiendo 1000-1200 eDPI. Para Wraith y personajes de movilidad, entre 800-1000. El ADS multiplier debería estar a 0.85x para control de retroceso.',
         'fps', $adminId, 31, 1],

        ['Setup perfecto para jugar Rust sin lag',
         'Optimización completa probada: shadows en low, draw distance al 60%, anti-aliasing desactivado, y el proceso en prioridad alta desde el administrador de tareas. Con una GTX 1660 Super y 16GB RAM podéis correr a 100+ FPS en servidores de 200 jugadores. También ayuda mucho tener el juego en SSD.',
         'hardware', $adminId, 15, 1],

        ['Estrategias ganadoras en Civilization VI para nuevos jugadores',
         'Empezad siempre con un líder que tenga bonificaciones de ciencia: Corea de Seondeok o China con Qin Shi Huang. Priorizad el Campus científico en los primeros 30 turnos. No os disperséis en conquistas antes del turno 100. La carrera espacial se gana con 3-4 ciudades bien desarrolladas, no con 10 mediocres.',
         'estrategia', $adminId, 19, 1],

        ['Overwatch 2: Composición de equipo ideal en temporada actual',
         'La meta actual favorece tanques de poke con apoyo de curación continua. Reinhardt + Lucio sigue siendo fiable en oro-platino. En diamante+ ved Zarya + Ana para teamfights. Como DPS, Ashe y Sojourn son picks sólidos con buena curva de aprendizaje. Evitad flankers en composiciones de poke.',
         'moba', $adminId, 22, 1],

        ['Construcciones imprescindibles en Minecraft para supervivencia',
         'Día 1: refugio elevado con antorcha en cada esquina. Semana 1: granja de trigo 9x9 y horno de fundición. Semana 2: granja de experiencia con zombies o arañas spawner. Con nivel 30 de xp podéis encantar herramientas de diamante. La automatización con redstone viene después — no os apresuréis.',
         'estrategia', $adminId, 28, 1],

        ['Simracing: Configuración de volante para empezar desde cero',
         'Ajustad la fuerza de retroalimentación al 50% al inicio para no fatigar los brazos. Curva de respuesta del acelerador: lineal. Curva del freno: algo logarítmica. Practicad en Monza, Spa y Silverstone antes de competir. iRacing y Assetto Corsa Competizione son los más realistas para mejorar.',
         'hardware', $adminId, 14, 1],

        ['Elden Ring: Build de samurai op para todo el PvE',
         'Uchigatana +25 con affinidad de Sangrado. Stats objetivo nivel 120: VIG 40, END 25, STR 18, DEX 45, ARK 15. Talismanes: Cuchilla de Raptor, Ojo de Halcón, Espadachín de Bronce. Esta build destruye todos los jefes del juego con la mechanic de sangrado. El parry opcional con escudo pequeño.',
         'estrategia', $adminId, 35, 1],

        ['Fortnite: Técnicas de edificación rápida en temporada actual',
         'El meta de construcción cambió con los últimos patches. Los box fights exigen editar a 90+ acciones por minuto. Mapas de práctica recomendados: Raider464 Edit Course (código 0847-4470-9946). Jugad con 240Hz y la sensibilidad de construcción 20% más baja que la de exploración.',
         'fps', $adminId, 18, 1],

        ['Novedades Gaming de 2026 que no te puedes perder',
         'Este año se perfila increíble para los gamers. Se han confirmado: nuevo FPS de Bungie estilo Destiny 3, Valorant Mobile global launch en Q3, League of Legends 2.0 con nuevo cliente y motor gráfico, y Minecraft 2 con biomas procedurales avanzados. Marcad los calendarios.',
         'noticias', $adminId, 27, 1],

        ['Las mejores plataformas para mejorar tu aim en 2026',
         'Aim Lab sigue siendo el estándar gratuito con mejores escenarios de tracking. Kovaaks tiene la comunidad más activa con mapas de alto nivel. Para Valorant específicamente, los escenarios de VCT Practice y Gridshot Ultimate son los más eficientes. Sed constantes: 20 minutos diarios baten a 2 horas esporádicas.',
         'fps', $adminId, 21, 1],

        ['Cómo montar un PC gaming de alto rendimiento por menos de 800€ en 2026',
         'Build recomendada 2026 sub-800€: RTX 4060 (290€) + Ryzen 5 7600 (200€) + 16GB DDR5-5200 (55€) + SSD NVMe 1TB (70€) + B650 MOBO (120€) + 650W 80+ Gold (60€) = 795€. Resultado: 100+ FPS en todos los juegos en 1080p Ultra. Para 1440p reemplazad la GPU por RTX 4070 con un presupuesto de 1000€.',
         'hardware', $adminId, 33, 1],
    ];

    $insertPost = $conn->prepare(
        'INSERT INTO posts (title, content, category, author_id, likes, approved) VALUES (?, ?, ?, ?, ?, ?)'
    );
    foreach ($seedPosts as $post) {
        $insertPost->bind_param('sssiis', $post[0], $post[1], $post[2], $post[3], $post[4], $post[5]);
        $insertPost->execute();
    }
    $insertPost->close();
}

/* ═══════════════════════════════════════════════════════════════
   SEED: Demo contact messages (admin visibility test)
   ═══════════════════════════════════════════════════════════════ */
$contactCount = (int) $conn->query("SELECT COUNT(*) as c FROM contacts")->fetch_assoc()['c'];

if ($contactCount === 0) {
    $demoContacts = [
        ['Carlos García',   'carlos@ejemplo.com',  'Error al registrarme',          'Intenté crear una cuenta con mi correo pero me dice que ya existe aunque nunca me registré.', 0],
        ['María López',     'maria@ejemplo.com',   'Sugerencia de categoría',       'Estaría genial añadir una categoría para juegos de rol y RPG. ¡Gran foro!', 0],
        ['Alejandro Torres','alex@ejemplo.com',    'Problema con el formulario',    'El formulario de publicación no me deja escribir más de 300 caracteres aunque dice que el máximo es 5000.', 0],
        ['Laura Martínez',  'laura@ejemplo.com',   'Colaboración de streaming',     'Soy streamer con 8k seguidores en Twitch y me gustaría colaborar con el foro para promover contenido.', 0],
        ['Diego Fernández', 'diego@ejemplo.com',   'Post eliminado sin razón',      'Mi post sobre builds de Elden Ring fue eliminado sin explicación. ¿Podéis revisar?', 0],
    ];

    $insCon = $conn->prepare(
        "INSERT INTO contacts (name, email, subject, message, `read`) VALUES (?, ?, ?, ?, ?)"
    );
    foreach ($demoContacts as $c) {
        $insCon->bind_param('ssssi', $c[0], $c[1], $c[2], $c[3], $c[4]);
        $insCon->execute();
    }
    $insCon->close();
}
