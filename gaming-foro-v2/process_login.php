<?php
/**
 * NEXUS — Procesador de inicio de sesión
 * Valida las credenciales y establece la sesión PHP.
 * Implementa una protección básica anti-fuerza-bruta con contadores de sesión.
 */

session_start(); // Reanuda/crea la sesión para leer contadores y guardar datos
require_once __DIR__ . '/db.php'; // Carga la conexión $conn a la base de datos

/* ── Solo se aceptan peticiones POST ───────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { // Si alguien entra por URL directa (GET)...
    header('Location: login.php'); // ...lo manda al formulario de login
    exit; // Detiene la ejecución
}

/* ── CSRF: comprobación básica de origen / referer ───────────── */
$host    = $_SERVER['HTTP_HOST'] ?? '';    // Dominio actual (ej: localhost)
$referer = $_SERVER['HTTP_REFERER'] ?? ''; // Página desde la que se envió el formulario
if ($referer && parse_url($referer, PHP_URL_HOST) !== $host) { // Si el referer es de otro dominio...
    $_SESSION['flash_error'] = 'Solicitud no válida.'; // ...marca la petición como sospechosa
    header('Location: login.php'); // Vuelve al login
    exit; // Detiene la ejecución
}

/* ── Limitador anti-fuerza-bruta ──────────────────────────────── */
$maxAttempts  = 10;                  // Máximo de intentos fallidos antes de bloquear
$lockSeconds  = 600;                 // Duración del bloqueo en segundos (10 minutos)
$attemptKey   = 'login_attempts';     // Clave de sesión: contador de intentos
$lockKey      = 'login_locked_until'; // Clave de sesión: momento en que termina el bloqueo

if (isset($_SESSION[$lockKey]) && time() < $_SESSION[$lockKey]) { // Si sigue bloqueado...
    $remaining = $_SESSION[$lockKey] - time(); // Segundos que faltan para desbloquear
    $mins      = ceil($remaining / 60);        // Los convierte a minutos (hacia arriba)
    $_SESSION['flash_error'] = "Demasiados intentos fallidos. Espera {$mins} minuto(s)."; // Avisa al usuario
    header('Location: login.php'); // Vuelve al login
    exit; // Detiene la ejecución
}

/* ── Lee y limpia los datos del formulario ───────────────────── */
$email    = strtolower(trim($_POST['email']    ?? '')); // Email en minúsculas y sin espacios
$password = $_POST['password'] ?? '';                   // Contraseña tal cual (no se recorta)

/* ── Comprueba que ambos campos estén rellenos ───────────────── */
if ($email === '' || $password === '') { // Si falta alguno...
    $_SESSION['flash_error'] = 'Debes introducir tu correo y contraseña.'; // ...avisa
    header('Location: login.php'); // Vuelve al login
    exit; // Detiene la ejecución
}

/* ── Valida el formato del email ─────────────────────────────── */
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { // Si no parece un email válido...
    $_SESSION['flash_error'] = 'El formato del correo electrónico no es válido.'; // ...avisa
    header('Location: login.php'); // Vuelve al login
    exit; // Detiene la ejecución
}

/* ── Límites de longitud (protección contra cadenas enormes) ──── */
if (strlen($email) > 150 || strlen($password) > 256) { // Si superan el máximo razonable...
    $_SESSION['flash_error'] = 'Los datos introducidos superan la longitud máxima permitida.'; // ...avisa
    header('Location: login.php'); // Vuelve al login
    exit; // Detiene la ejecución
}

/* ── Busca el usuario en la base de datos ────────────────────── */
$stmt = $conn->prepare( // Consulta preparada (evita inyección SQL)
    'SELECT id, nombre, username, password, role FROM usuarios WHERE email = ? LIMIT 1' // Busca al usuario por su email
);
$stmt->bind_param('s', $email); // Enlaza el email como cadena ('s')
$stmt->execute(); // Ejecuta la consulta
$user = $stmt->get_result()->fetch_assoc(); // Fila del usuario (o null si no existe)
$stmt->close(); // Libera la sentencia

/* ── Verifica la contraseña con bcrypt ───────────────────────── */
$valid = $user && password_verify($password, $user['password']); // True solo si existe y la contraseña coincide

if (!$valid) { // Si las credenciales son incorrectas...
    /* Incrementa el contador de intentos fallidos */
    $_SESSION[$attemptKey] = ($_SESSION[$attemptKey] ?? 0) + 1; // Suma 1 al contador

    if ($_SESSION[$attemptKey] >= $maxAttempts) { // Si llegó al máximo de intentos...
        $_SESSION[$lockKey]      = time() + $lockSeconds; // ...activa el bloqueo temporal
        $_SESSION[$attemptKey]   = 0;                     // Reinicia el contador
        $_SESSION['flash_error'] = 'Has excedido el número de intentos. Cuenta bloqueada por 10 minutos.'; // Avisa del bloqueo
    } else { // Si aún quedan intentos...
        $left = $maxAttempts - $_SESSION[$attemptKey]; // Calcula los intentos restantes
        $_SESSION['flash_error'] = "Correo o contraseña incorrectos. Te quedan {$left} intento(s)."; // Avisa cuántos quedan
    }

    header('Location: login.php'); // Vuelve al login
    exit; // Detiene la ejecución
}

/* ── Login correcto: reinicia el limitador y regenera la sesión ─ */
$_SESSION[$attemptKey] = 0;          // Pone el contador de intentos a cero
unset($_SESSION[$lockKey]);          // Elimina cualquier bloqueo previo
session_regenerate_id(true);         // Nuevo ID de sesión (previene fijación de sesión)

/* ── Rellena la sesión con los datos del usuario ─────────────── */
$_SESSION['user_id']  = (int) $user['id']; // ID del usuario (entero)
$_SESSION['role']     = $user['role'];     // Rol: 'admin' o 'member'
$_SESSION['username'] = $user['username']; // Nick público
$_SESSION['nombre']   = $user['nombre'];   // Nombre real

$_SESSION['flash_success'] = '¡Bienvenido de nuevo, ' . htmlspecialchars($user['nombre'], ENT_QUOTES) . '!'; // Saludo de bienvenida (escapado)

/* ── Redirige según el rol ───────────────────────────────────── */
$redirect = $user['role'] === 'admin' ? 'admin.php' : 'index.php'; // Admin → panel; resto → portada
header('Location: ' . $redirect); // Ejecuta la redirección
exit; // Detiene la ejecución
