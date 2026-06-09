<?php
/**
 * NEXUS — Procesador de registro
 * Valida todos los campos, comprueba duplicados, cifra la contraseña
 * e inserta el nuevo usuario en la base de datos.
 */

session_start(); // Reanuda/crea la sesión
require_once __DIR__ . '/db.php'; // Carga la conexión $conn a la BD

/* ── Solo se aceptan peticiones POST ───────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { // Si no es POST (acceso directo)...
    header('Location: register.php'); // ...vuelve al formulario de registro
    exit; // Detiene la ejecución
}

/* ── CSRF: comprobación básica de origen ─────────────────────── */
$host    = $_SERVER['HTTP_HOST'] ?? '';    // Dominio actual
$referer = $_SERVER['HTTP_REFERER'] ?? ''; // Página de origen del formulario
if ($referer && parse_url($referer, PHP_URL_HOST) !== $host) { // Si viene de otro dominio...
    $_SESSION['flash_error'] = 'Solicitud no válida.'; // ...se marca como sospechosa
    header('Location: register.php'); // Vuelve al registro
    exit; // Detiene la ejecución
}

/* ── Lee los datos crudos del formulario ─────────────────────── */
$nombre          = trim($_POST['name']            ?? '');          // Nombre visible
$username        = trim($_POST['username']         ?? '');          // Nick público
$email           = strtolower(trim($_POST['email'] ?? ''));         // Email en minúsculas
$favoriteGame    = trim($_POST['favoriteGame']     ?? 'Otro');      // Juego favorito (por defecto 'Otro')
$password        = $_POST['password']              ?? '';           // Contraseña
$confirmPassword = $_POST['confirmPassword']       ?? '';           // Confirmación de la contraseña
$bio             = trim($_POST['bio']              ?? '');          // Biografía opcional
$termsAccepted   = isset($_POST['terms']);                          // ¿Marcó la casilla de normas?

/* ── Aceptación de las normas ─────────────────────────────────── */
if (!$termsAccepted) { // Si no aceptó las normas...
    $_SESSION['flash_error'] = 'Debes aceptar las normas del foro para continuar.'; // ...avisa
    header('Location: register.php'); // Vuelve al registro
    exit; // Detiene la ejecución
}

/* ── Comprueba que los campos obligatorios estén rellenos ────── */
if ($nombre === '' || $username === '' || $email === '' || $password === '') { // Si falta alguno obligatorio...
    $_SESSION['flash_error'] = 'Por favor, completa todos los campos obligatorios.'; // ...avisa
    header('Location: register.php'); // Vuelve al registro
    exit; // Detiene la ejecución
}

/* ── Límites de longitud ──────────────────────────────────────── */
if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 24) { // Nombre entre 2 y 24 caracteres
    $_SESSION['flash_error'] = 'El nombre visible debe tener entre 2 y 24 caracteres.'; // Mensaje de error para el usuario
    header('Location: register.php'); // Vuelve al formulario de registro
    exit; // Detiene la ejecución
}
if (mb_strlen($username) < 3 || mb_strlen($username) > 18) { // Nick entre 3 y 18 caracteres
    $_SESSION['flash_error'] = 'El nick debe tener entre 3 y 18 caracteres.'; // Mensaje de error para el usuario
    header('Location: register.php'); // Vuelve al formulario de registro
    exit; // Detiene la ejecución
}
if (mb_strlen($bio) > 180) { // Si la biografía supera 180 caracteres...
    $bio = mb_substr($bio, 0, 180); // ...la recorta en vez de rechazarla
}

/* ── Formato del nick: alfanumérico + guion bajo + guion ─────── */
if (!preg_match('/^[a-zA-Z0-9_\-]{3,18}$/', $username)) { // Si contiene caracteres no permitidos...
    $_SESSION['flash_error'] = 'El nick solo puede contener letras, números, guiones y guiones bajos.'; // Mensaje de error para el usuario
    header('Location: register.php'); // Vuelve al formulario de registro
    exit; // Detiene la ejecución
}

/* ── Formato del email ────────────────────────────────────────── */
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) { // Email inválido o muy largo
    $_SESSION['flash_error'] = 'El formato del correo electrónico no es válido.'; // Mensaje de error para el usuario
    header('Location: register.php'); // Vuelve al formulario de registro
    exit; // Detiene la ejecución
}

/* ── Robustez de la contraseña ────────────────────────────────── */
if (strlen($password) < 6) { // Mínimo 6 caracteres
    $_SESSION['flash_error'] = 'La contraseña debe tener al menos 6 caracteres.'; // Mensaje de error para el usuario
    header('Location: register.php'); // Vuelve al formulario de registro
    exit; // Detiene la ejecución
}
if (strlen($password) > 256) { // Máximo razonable para evitar abusos
    $_SESSION['flash_error'] = 'La contraseña es demasiado larga.'; // Mensaje de error para el usuario
    header('Location: register.php'); // Vuelve al formulario de registro
    exit; // Detiene la ejecución
}

/* ── Confirmación de la contraseña ────────────────────────────── */
if (!hash_equals($password, $confirmPassword)) { // Comparación segura: ambas deben coincidir
    $_SESSION['flash_error'] = 'Las contraseñas no coinciden. Compruébalas de nuevo.'; // Mensaje de error para el usuario
    header('Location: register.php'); // Vuelve al formulario de registro
    exit; // Detiene la ejecución
}

/* ── Sanea el nombre y la biografía ───────────────────────────── */
$nombre = strip_tags($nombre); // Elimina etiquetas HTML del nombre
$bio    = strip_tags($bio);    // Elimina etiquetas HTML de la biografía

/* ── Lista blanca de juegos favoritos permitidos ─────────────── */
$allowedGames = [ // Solo se aceptan estos valores
    'Valorant', 'Counter-Strike', 'League of Legends',
    'Fortnite', 'Apex Legends', 'Overwatch 2', 'Minecraft', 'Otro'
];
if (!in_array($favoriteGame, $allowedGames, true)) { // Si el valor enviado no está en la lista...
    $favoriteGame = 'Otro'; // ...se fuerza a 'Otro'
}

/* ── Comprueba que email y nick sean únicos ──────────────────── */
$check = $conn->prepare( // Consulta preparada
    'SELECT id, email, username FROM usuarios WHERE email = ? OR username = ? LIMIT 1' // Busca un usuario con ese email o nick
);
$check->bind_param('ss', $email, $username); // Enlaza email y username
$check->execute(); // Ejecuta la búsqueda
$existing = $check->get_result()->fetch_assoc(); // Fila existente (o null)
$check->close(); // Libera la sentencia

if ($existing) { // Si ya existe un usuario con ese email o nick...
    if (strtolower($existing['email']) === $email) { // ...si coincide el email...
        $_SESSION['flash_error'] = 'Ese correo electrónico ya está registrado. ¿Olvidaste tu contraseña?'; // avisa del email
    } else { // ...si no, es el nick el que coincide
        $_SESSION['flash_error'] = 'Ese nick de usuario ya está en uso. Elige otro.'; // avisa del nick
    }
    header('Location: register.php'); // Vuelve al registro
    exit; // Detiene la ejecución
}

/* ── Cifra la contraseña e inserta el usuario ────────────────── */
$passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]); // Hash bcrypt con coste 12
$role         = 'member'; // Todo registro nuevo es 'member' por defecto

$insert = $conn->prepare( // Consulta preparada de inserción
    'INSERT INTO usuarios (nombre, username, email, password, favorite_game, bio, role)
     VALUES (?, ?, ?, ?, ?, ?, ?)' // Inserta el nuevo usuario con sus 7 campos
);
$insert->bind_param('sssssss', $nombre, $username, $email, $passwordHash, $favoriteGame, $bio, $role); // 7 cadenas

if (!$insert->execute()) { // Si la inserción falla...
    $_SESSION['flash_error'] = 'Ocurrió un error al crear la cuenta. Inténtalo de nuevo.'; // ...avisa
    header('Location: register.php'); // Vuelve al formulario de registro
    exit; // Detiene la ejecución
}

$newUserId = (int) $insert->insert_id; // ID autogenerado del nuevo usuario
$insert->close(); // Libera la sentencia

/* ── Inicia sesión automáticamente tras el registro ──────────── */
session_regenerate_id(true);          // Nuevo ID de sesión (previene fijación de sesión)
$_SESSION['user_id']  = $newUserId;   // Guarda el ID del nuevo usuario
$_SESSION['role']     = $role;        // Guarda el rol ('member')
$_SESSION['username'] = $username;    // Guarda el nick
$_SESSION['nombre']   = $nombre;      // Guarda el nombre

$_SESSION['flash_success'] = "¡Bienvenido a NEXUS, {$nombre}! Tu cuenta ha sido creada correctamente."; // Mensaje de bienvenida
header('Location: index.php'); // Redirige a la portada
exit; // Detiene la ejecución
