<?php
/**
 * NEXUS//BOARD — Registration processor
 * Validates all fields, checks for duplicates, hashes the password,
 * and inserts the new user into the database.
 */

session_start();
require_once __DIR__ . '/db.php';

/* ── Only accept POST ──────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

/* ── CSRF: basic origin check ─────────────────────────────────── */
$host    = $_SERVER['HTTP_HOST'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';
if ($referer && parse_url($referer, PHP_URL_HOST) !== $host) {
    $_SESSION['flash_error'] = 'Solicitud no válida.';
    header('Location: register.php');
    exit;
}

/* ── Read raw inputs ──────────────────────────────────────────── */
$nombre          = trim($_POST['name']            ?? '');
$username        = trim($_POST['username']         ?? '');
$email           = strtolower(trim($_POST['email'] ?? ''));
$favoriteGame    = trim($_POST['favoriteGame']     ?? 'Otro');
$password        = $_POST['password']              ?? '';
$confirmPassword = $_POST['confirmPassword']       ?? '';
$bio             = trim($_POST['bio']              ?? '');
$termsAccepted   = isset($_POST['terms']);

/* ── Terms of service ─────────────────────────────────────────── */
if (!$termsAccepted) {
    $_SESSION['flash_error'] = 'Debes aceptar las normas del foro para continuar.';
    header('Location: register.php');
    exit;
}

/* ── Required field presence ─────────────────────────────────── */
if ($nombre === '' || $username === '' || $email === '' || $password === '') {
    $_SESSION['flash_error'] = 'Por favor, completa todos los campos obligatorios.';
    header('Location: register.php');
    exit;
}

/* ── Length guards ────────────────────────────────────────────── */
if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 24) {
    $_SESSION['flash_error'] = 'El nombre visible debe tener entre 2 y 24 caracteres.';
    header('Location: register.php');
    exit;
}
if (mb_strlen($username) < 3 || mb_strlen($username) > 18) {
    $_SESSION['flash_error'] = 'El nick debe tener entre 3 y 18 caracteres.';
    header('Location: register.php');
    exit;
}
if (mb_strlen($bio) > 180) {
    $bio = mb_substr($bio, 0, 180);
}

/* ── Username format: alphanumeric + underscore + hyphen ─────── */
if (!preg_match('/^[a-zA-Z0-9_\-]{3,18}$/', $username)) {
    $_SESSION['flash_error'] = 'El nick solo puede contener letras, números, guiones y guiones bajos.';
    header('Location: register.php');
    exit;
}

/* ── Email format ─────────────────────────────────────────────── */
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
    $_SESSION['flash_error'] = 'El formato del correo electrónico no es válido.';
    header('Location: register.php');
    exit;
}

/* ── Password strength ────────────────────────────────────────── */
if (strlen($password) < 6) {
    $_SESSION['flash_error'] = 'La contraseña debe tener al menos 6 caracteres.';
    header('Location: register.php');
    exit;
}
if (strlen($password) > 256) {
    $_SESSION['flash_error'] = 'La contraseña es demasiado larga.';
    header('Location: register.php');
    exit;
}

/* ── Password confirmation ────────────────────────────────────── */
if (!hash_equals($password, $confirmPassword)) {
    $_SESSION['flash_error'] = 'Las contraseñas no coinciden. Compruébalas de nuevo.';
    header('Location: register.php');
    exit;
}

/* ── Sanitize nombre and bio ──────────────────────────────────── */
$nombre = strip_tags($nombre);
$bio    = strip_tags($bio);

/* ── Allowed favorite games whitelist ────────────────────────── */
$allowedGames = [
    'Valorant', 'Counter-Strike', 'League of Legends',
    'Fortnite', 'Apex Legends', 'Overwatch 2', 'Minecraft', 'Otro'
];
if (!in_array($favoriteGame, $allowedGames, true)) {
    $favoriteGame = 'Otro';
}

/* ── Check email / username uniqueness ───────────────────────── */
$check = $conn->prepare(
    'SELECT id, email, username FROM usuarios WHERE email = ? OR username = ? LIMIT 1'
);
$check->bind_param('ss', $email, $username);
$check->execute();
$existing = $check->get_result()->fetch_assoc();
$check->close();

if ($existing) {
    if (strtolower($existing['email']) === $email) {
        $_SESSION['flash_error'] = 'Ese correo electrónico ya está registrado. ¿Olvidaste tu contraseña?';
    } else {
        $_SESSION['flash_error'] = 'Ese nick de usuario ya está en uso. Elige otro.';
    }
    header('Location: register.php');
    exit;
}

/* ── Hash password and insert ─────────────────────────────────── */
$passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
$role         = 'member';

$insert = $conn->prepare(
    'INSERT INTO usuarios (nombre, username, email, password, favorite_game, bio, role)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
);
$insert->bind_param('sssssss', $nombre, $username, $email, $passwordHash, $favoriteGame, $bio, $role);

if (!$insert->execute()) {
    $_SESSION['flash_error'] = 'Ocurrió un error al crear la cuenta. Inténtalo de nuevo.';
    header('Location: register.php');
    exit;
}

$newUserId = (int) $insert->insert_id;
$insert->close();

/* ── Auto-login after registration ───────────────────────────── */
session_regenerate_id(true);
$_SESSION['user_id']  = $newUserId;
$_SESSION['role']     = $role;
$_SESSION['username'] = $username;
$_SESSION['nombre']   = $nombre;

$_SESSION['flash_success'] = "¡Bienvenido a NEXUS//BOARD, {$nombre}! Tu cuenta ha sido creada correctamente.";
header('Location: index.php');
exit;
