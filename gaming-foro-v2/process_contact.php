<?php
/**
 * NEXUS//BOARD — Contact form processor
 * Validates submitted fields and inserts the message into the contacts table.
 * Responds with JSON for fetch-based submission from the frontend.
 */

ini_set('display_errors', 0);
error_reporting(0);
ob_start();
session_start();
require_once __DIR__ . '/db.php';
ob_clean();

/* ── Response helpers ─────────────────────────────────────────── */
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function respond(bool $success, string $messageOrError, int $status = 200): never {
    http_response_code($status);
    $key = $success ? 'message' : 'error';
    echo json_encode(['success' => $success, $key => $messageOrError], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── Method guard ─────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Método no permitido.', 405);
}

/* ── Rate limiting via session ────────────────────────────────── */
$limitKey      = 'contact_last_sent';
$cooldownSecs  = 60;

if (isset($_SESSION[$limitKey])) {
    $elapsed = time() - (int) $_SESSION[$limitKey];
    if ($elapsed < $cooldownSecs) {
        $wait = $cooldownSecs - $elapsed;
        respond(false, "Por favor espera {$wait} segundos antes de enviar otro mensaje.", 429);
    }
}

/* ── Read and sanitize inputs ─────────────────────────────────── */
$name    = strip_tags(trim($_POST['name']    ?? ''));
$email   = strtolower(strip_tags(trim($_POST['email']   ?? '')));
$subject = strip_tags(trim($_POST['subject'] ?? ''));
$message = strip_tags(trim($_POST['message'] ?? ''));

/* ── Required field check ────────────────────────────────────── */
if ($name === '' || $email === '' || $subject === '' || $message === '') {
    respond(false, 'Por favor, completa todos los campos del formulario.', 422);
}

/* ── Length validations ───────────────────────────────────────── */
if (mb_strlen($name) < 2 || mb_strlen($name) > 80) {
    respond(false, 'El nombre debe tener entre 2 y 80 caracteres.', 422);
}
if (mb_strlen($subject) < 4 || mb_strlen($subject) > 180) {
    respond(false, 'El asunto debe tener entre 4 y 180 caracteres.', 422);
}
if (mb_strlen($message) < 10 || mb_strlen($message) > 2000) {
    respond(false, 'El mensaje debe tener entre 10 y 2000 caracteres.', 422);
}

/* ── Email format ─────────────────────────────────────────────── */
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
    respond(false, 'El formato del correo electrónico no es válido.', 422);
}

/* ── Basic spam honeypot check (hidden field "website" must be blank) */
$honeypot = trim($_POST['website'] ?? '');
if ($honeypot !== '') {
    respond(false, 'Solicitud rechazada.', 400);
}

/* ── Insert into database ─────────────────────────────────────── */
$stmt = $conn->prepare(
    "INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)"
);
if (!$stmt) {
    respond(false, 'Error interno del servidor. Inténtalo de nuevo más tarde.', 500);
}

$stmt->bind_param('ssss', $name, $email, $subject, $message);
$ok = $stmt->execute();

if (!$ok) {
    $errMsg = $stmt->error;
    $stmt->close();
    respond(false, 'No se pudo guardar el mensaje. ' . $errMsg, 500);
}

$stmt->close();

/* ── Mark rate-limit timestamp ────────────────────────────────── */
$_SESSION[$limitKey] = time();

/* ── Success ─────────────────────────────────────────────────── */
respond(true, '¡Mensaje enviado! Nos pondremos en contacto contigo pronto.');
