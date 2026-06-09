<?php
/**
 * NEXUS — Arranque de la aplicación (bootstrap)
 * Inicia la sesión, carga la conexión a la BD y define las funciones
 * de ayuda compartidas que usan todas las páginas y la API.
 */

session_start(); // Reanuda o crea la sesión del usuario
require_once __DIR__ . '/db.php'; // Carga la conexión $conn a la base de datos

/* ═══════════════════════════════════════════════════════════════
   current_user_data — busca en la BD al usuario que tiene sesión
   Devuelve un array asociativo, o null si no hay sesión válida
   ═══════════════════════════════════════════════════════════════ */
function current_user_data(mysqli $conn): ?array { // Devuelve los datos del usuario en sesión (o null)
    if (!isset($_SESSION['user_id'])) { // Si no hay ID de usuario en la sesión...
        return null; // ...no hay nadie autenticado
    }

    $id   = (int) $_SESSION['user_id']; // ID del usuario de la sesión (forzado a entero)
    $stmt = $conn->prepare( // Consulta preparada (evita inyección SQL)
        'SELECT id, nombre, username, email, favorite_game, bio, role, fecha_registro
         FROM usuarios WHERE id = ? LIMIT 1' // Trae todos los datos del usuario por su ID
    );
    $stmt->bind_param('i', $id); // Enlaza el ID como entero ('i')
    $stmt->execute(); // Ejecuta la consulta
    $user = $stmt->get_result()->fetch_assoc() ?: null; // Obtiene la fila como array, o null
    $stmt->close(); // Libera la sentencia

    if (!$user) { // Si la sesión apunta a un usuario que ya no existe...
        /* La sesión referencia a un usuario borrado — se limpia */
        unset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['username'], $_SESSION['nombre']); // Borra los datos de sesión
        return null; // Trata al visitante como anónimo
    }

    /* Mantiene el rol de la sesión sincronizado con la BD (por si cambió a mitad de sesión) */
    $_SESSION['role']     = $user['role'];     // Actualiza el rol en la sesión
    $_SESSION['username'] = $user['username']; // Actualiza el nick en la sesión

    return $user; // Devuelve los datos completos del usuario
}

/* ═══════════════════════════════════════════════════════════════
   require_login — redirige al login si no hay sesión iniciada
   ═══════════════════════════════════════════════════════════════ */
function require_login(string $redirectTo = 'login.php'): void { // Obliga a tener sesión iniciada
    if (!isset($_SESSION['user_id'])) { // Si no hay usuario autenticado...
        header('Location: ' . $redirectTo); // ...redirige al login
        exit; // Detiene la ejecución
    }
}

/* ═══════════════════════════════════════════════════════════════
   require_admin — redirige si el usuario no es administrador
   ═══════════════════════════════════════════════════════════════ */
function require_admin(string $redirectTo = 'index.php'): void { // Obliga a ser administrador
    if (($_SESSION['role'] ?? '') !== 'admin') { // Si el rol no es exactamente 'admin'...
        header('Location: ' . $redirectTo); // ...lo echa a la página indicada
        exit; // Detiene la ejecución
    }
}

/* ═══════════════════════════════════════════════════════════════
   safe_str — escapa y recorta una cadena para mostrarla sin riesgo
   ═══════════════════════════════════════════════════════════════ */
function safe_str(mixed $val, int $maxLen = 0): string { // Escapa y recorta texto para mostrarlo sin riesgo
    $s = htmlspecialchars((string) ($val ?? ''), ENT_QUOTES, 'UTF-8'); // Escapa HTML para prevenir XSS
    if ($maxLen > 0 && mb_strlen($s) > $maxLen) { // Si se pidió límite y se supera...
        $s = mb_substr($s, 0, $maxLen) . '…'; // ...recorta y añade puntos suspensivos
    }
    return $s; // Devuelve la cadena segura
}

/* ═══════════════════════════════════════════════════════════════
   relative_time — fecha legible tipo "hace X tiempo"
   ═══════════════════════════════════════════════════════════════ */
function relative_time(string $dateStr): string { // Convierte una fecha en "hace X tiempo"
    $diff = time() - strtotime($dateStr); // Segundos transcurridos desde la fecha dada
    if ($diff < 60)           return 'hace unos segundos';                          // Menos de 1 minuto
    if ($diff < 3600)         return 'hace ' . floor($diff / 60) . ' min';          // Menos de 1 hora → minutos
    if ($diff < 86400)        return 'hace ' . floor($diff / 3600) . ' h';          // Menos de 1 día → horas
    if ($diff < 86400 * 7)    return 'hace ' . floor($diff / 86400) . ' d';         // Menos de 1 semana → días
    if ($diff < 86400 * 30)   return 'hace ' . floor($diff / (86400 * 7)) . ' sem'; // Menos de 1 mes → semanas
    if ($diff < 86400 * 365)  return 'hace ' . floor($diff / (86400 * 30)) . ' mes';// Menos de 1 año → meses
    return 'hace ' . floor($diff / (86400 * 365)) . ' año(s)';                       // Más de 1 año → años
}

/* ═══════════════════════════════════════════════════════════════
   fmt_number — abrevia números grandes: 1234 → "1.2k"
   ═══════════════════════════════════════════════════════════════ */
function fmt_number(int|float $n): string { // Abrevia números grandes (1234 → "1.2k")
    if ($n >= 1_000_000) return round($n / 1_000_000, 1) . 'M'; // Millones → sufijo M
    if ($n >= 1_000)     return round($n / 1_000, 1)     . 'k'; // Miles → sufijo k
    return (string) $n; // Números pequeños se devuelven tal cual
}

/* ═══════════════════════════════════════════════════════════════
   Helpers CSRF — patrón simple de doble envío (double-submit)
   ═══════════════════════════════════════════════════════════════ */
function csrf_token(): string { // Genera/devuelve el token CSRF de la sesión
    if (empty($_SESSION['csrf_token'])) { // Si aún no hay token en la sesión...
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24)); // ...genera 24 bytes aleatorios en hex
    }
    return $_SESSION['csrf_token']; // Devuelve el token (nuevo o existente)
}

function csrf_verify(string $token): bool { // Comprueba que el token CSRF recibido es válido
    return isset($_SESSION['csrf_token']) &&               // Debe existir un token en la sesión...
           hash_equals($_SESSION['csrf_token'], $token);   // ...y coincidir (comparación segura anti-timing)
}

/* ═══════════════════════════════════════════════════════════════
   Arranque: carga el usuario actual y los mensajes flash
   ═══════════════════════════════════════════════════════════════ */
$currentUser  = current_user_data($conn);        // Datos del usuario logueado (o null)
$flashError   = $_SESSION['flash_error']   ?? ''; // Mensaje de error de una acción previa
$flashSuccess = $_SESSION['flash_success'] ?? ''; // Mensaje de éxito de una acción previa
unset($_SESSION['flash_error'], $_SESSION['flash_success']); // Los borra para que solo se muestren una vez
