<?php
/**
 * NEXUS — Procesador del formulario de contacto
 * Valida los campos enviados e inserta el mensaje en la tabla contacts.
 * Responde con JSON para el envío por fetch desde el frontend.
 */

ini_set('display_errors', 0); // Oculta errores en pantalla (la respuesta debe ser JSON limpio)
error_reporting(0);            // Desactiva el reporte de errores al cliente
ob_start();                    // Inicia un buffer de salida (captura cualquier salida accidental)
session_start();               // Reanuda/crea la sesión (para el rate limit)
require_once __DIR__ . '/db.php'; // Carga la conexión $conn a la BD
ob_clean();                    // Limpia el buffer: descarta avisos que ensuciarían el JSON

/* ── Funciones de respuesta ───────────────────────────────────── */
header('Content-Type: application/json; charset=utf-8'); // La respuesta es JSON en UTF-8
header('X-Content-Type-Options: nosniff');               // Evita que el navegador adivine el tipo MIME

function respond(bool $success, string $messageOrError, int $status = 200): never { // Envía la respuesta JSON y termina
    http_response_code($status); // Fija el código HTTP (200, 422, 500...)
    $key = $success ? 'message' : 'error'; // Clave 'message' si fue bien, 'error' si no
    echo json_encode(['success' => $success, $key => $messageOrError], JSON_UNESCAPED_UNICODE); // Devuelve el JSON
    exit; // Termina la ejecución (tipo de retorno 'never')
}

/* ── Solo se permite POST ─────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { // Si no es POST...
    respond(false, 'Método no permitido.', 405); // ...responde 405 y termina
}

/* ── Límite de frecuencia mediante la sesión ─────────────────── */
$limitKey      = 'contact_last_sent'; // Clave de sesión: momento del último envío
$cooldownSecs  = 60;                  // Tiempo mínimo entre envíos (60 segundos)

if (isset($_SESSION[$limitKey])) { // Si ya envió antes...
    $elapsed = time() - (int) $_SESSION[$limitKey]; // Segundos transcurridos desde entonces
    if ($elapsed < $cooldownSecs) { // Si aún no pasó el enfriamiento...
        $wait = $cooldownSecs - $elapsed; // Segundos que debe esperar
        respond(false, "Por favor espera {$wait} segundos antes de enviar otro mensaje.", 429); // 429 = demasiadas peticiones
    }
}

/* ── Lee y limpia los datos del formulario ───────────────────── */
$name    = strip_tags(trim($_POST['name']    ?? ''));               // Nombre sin etiquetas HTML ni espacios
$email   = strtolower(strip_tags(trim($_POST['email']   ?? ''))); // Email en minúsculas y limpio
$subject = strip_tags(trim($_POST['subject'] ?? ''));              // Asunto limpio
$message = strip_tags(trim($_POST['message'] ?? ''));              // Mensaje limpio

/* ── Comprueba que no falte ningún campo ─────────────────────── */
if ($name === '' || $email === '' || $subject === '' || $message === '') { // Si alguno está vacío...
    respond(false, 'Por favor, completa todos los campos del formulario.', 422); // ...error 422
}

/* ── Validaciones de longitud ─────────────────────────────────── */
if (mb_strlen($name) < 2 || mb_strlen($name) > 80) { // El nombre entre 2 y 80 caracteres
    respond(false, 'El nombre debe tener entre 2 y 80 caracteres.', 422); // ...error 422 y termina
}
if (mb_strlen($subject) < 4 || mb_strlen($subject) > 180) { // El asunto entre 4 y 180
    respond(false, 'El asunto debe tener entre 4 y 180 caracteres.', 422); // ...error 422 y termina
}
if (mb_strlen($message) < 10 || mb_strlen($message) > 2000) { // El mensaje entre 10 y 2000
    respond(false, 'El mensaje debe tener entre 10 y 2000 caracteres.', 422); // ...error 422 y termina
}

/* ── Formato del email ────────────────────────────────────────── */
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) { // Email inválido o muy largo
    respond(false, 'El formato del correo electrónico no es válido.', 422); // ...error 422 y termina
}

/* ── Honeypot anti-spam (el campo oculto "website" debe ir vacío) */
$honeypot = trim($_POST['website'] ?? ''); // Campo trampa invisible para humanos
if ($honeypot !== '') { // Si tiene contenido, lo rellenó un bot...
    respond(false, 'Solicitud rechazada.', 400); // ...se rechaza
}

/* ── Inserta el mensaje en la base de datos ──────────────────── */
$stmt = $conn->prepare( // Consulta preparada (evita inyección SQL)
    "INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)" // Inserta el mensaje de contacto
);
if (!$stmt) { // Si la preparación falló...
    respond(false, 'Error interno del servidor. Inténtalo de nuevo más tarde.', 500); // ...error 500
}

$stmt->bind_param('ssss', $name, $email, $subject, $message); // Enlaza los 4 campos como cadenas
$ok = $stmt->execute(); // Ejecuta la inserción

if (!$ok) { // Si la inserción falló...
    $errMsg = $stmt->error; // Captura el mensaje de error de MySQL
    $stmt->close();         // Libera la sentencia
    respond(false, 'No se pudo guardar el mensaje. ' . $errMsg, 500); // Error 500 con detalle
}

$stmt->close(); // Libera la sentencia tras el éxito

/* ── Marca la marca de tiempo del rate-limit ─────────────────── */
$_SESSION[$limitKey] = time(); // Guarda el momento de este envío

/* ── Éxito ───────────────────────────────────────────────────── */
respond(true, '¡Mensaje enviado! Nos pondremos en contacto contigo pronto.'); // Respuesta de éxito (200)
