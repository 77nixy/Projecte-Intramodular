<?php
/**
 * NEXUS//BOARD — Login processor
 * Validates credentials and establishes a PHP session.
 * Implements basic brute-force throttling via session counters.
 */

session_start();
require_once __DIR__ . '/db.php';

/* ── Only accept POST ──────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

/* ── CSRF: basic origin / referer check ──────────────────────── */
$host    = $_SERVER['HTTP_HOST'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';
if ($referer && parse_url($referer, PHP_URL_HOST) !== $host) {
    $_SESSION['flash_error'] = 'Solicitud no válida.';
    header('Location: login.php');
    exit;
}

/* ── Brute-force throttle ─────────────────────────────────────── */
$maxAttempts  = 10;
$lockSeconds  = 600;
$attemptKey   = 'login_attempts';
$lockKey      = 'login_locked_until';

if (isset($_SESSION[$lockKey]) && time() < $_SESSION[$lockKey]) {
    $remaining = $_SESSION[$lockKey] - time();
    $mins      = ceil($remaining / 60);
    $_SESSION['flash_error'] = "Demasiados intentos fallidos. Espera {$mins} minuto(s).";
    header('Location: login.php');
    exit;
}

/* ── Read and sanitize inputs ────────────────────────────────── */
$email    = strtolower(trim($_POST['email']    ?? ''));
$password = $_POST['password'] ?? '';

/* ── Field presence check ────────────────────────────────────── */
if ($email === '' || $password === '') {
    $_SESSION['flash_error'] = 'Debes introducir tu correo y contraseña.';
    header('Location: login.php');
    exit;
}

/* ── Email format check ──────────────────────────────────────── */
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['flash_error'] = 'El formato del correo electrónico no es válido.';
    header('Location: login.php');
    exit;
}

/* ── Length guards ────────────────────────────────────────────── */
if (strlen($email) > 150 || strlen($password) > 256) {
    $_SESSION['flash_error'] = 'Los datos introducidos superan la longitud máxima permitida.';
    header('Location: login.php');
    exit;
}

/* ── Look up user in DB ───────────────────────────────────────── */
$stmt = $conn->prepare(
    'SELECT id, nombre, username, password, role FROM usuarios WHERE email = ? LIMIT 1'
);
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* ── Verify password ──────────────────────────────────────────── */
$valid = $user && password_verify($password, $user['password']);

if (!$valid) {
    /* Increment attempt counter */
    $_SESSION[$attemptKey] = ($_SESSION[$attemptKey] ?? 0) + 1;

    if ($_SESSION[$attemptKey] >= $maxAttempts) {
        $_SESSION[$lockKey]      = time() + $lockSeconds;
        $_SESSION[$attemptKey]   = 0;
        $_SESSION['flash_error'] = 'Has excedido el número de intentos. Cuenta bloqueada por 10 minutos.';
    } else {
        $left = $maxAttempts - $_SESSION[$attemptKey];
        $_SESSION['flash_error'] = "Correo o contraseña incorrectos. Te quedan {$left} intento(s).";
    }

    header('Location: login.php');
    exit;
}

/* ── Login successful: reset throttle, regenerate session ───── */
$_SESSION[$attemptKey] = 0;
unset($_SESSION[$lockKey]);
session_regenerate_id(true);

/* ── Populate session with user data ─────────────────────────── */
$_SESSION['user_id']  = (int) $user['id'];
$_SESSION['role']     = $user['role'];
$_SESSION['username'] = $user['username'];
$_SESSION['nombre']   = $user['nombre'];

$_SESSION['flash_success'] = '¡Bienvenido de nuevo, ' . htmlspecialchars($user['nombre'], ENT_QUOTES) . '!';

/* ── Redirect based on role ──────────────────────────────────── */
$redirect = $user['role'] === 'admin' ? 'admin.php' : 'index.php';
header('Location: ' . $redirect);
exit;
