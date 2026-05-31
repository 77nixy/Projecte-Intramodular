<?php
/**
 * NEXUS//BOARD — Application bootstrap
 * Starts the session, loads the DB connection, and provides shared
 * helper functions used by all page files and the API.
 */

session_start();
require_once __DIR__ . '/db.php';

/* ═══════════════════════════════════════════════════════════════
   current_user_data — look up the logged-in user from DB
   Returns assoc array or null if not logged in / invalid session
   ═══════════════════════════════════════════════════════════════ */
function current_user_data(mysqli $conn): ?array {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    $id   = (int) $_SESSION['user_id'];
    $stmt = $conn->prepare(
        'SELECT id, nombre, username, email, favorite_game, bio, role, fecha_registro
         FROM usuarios WHERE id = ? LIMIT 1'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    if (!$user) {
        /* Session references a deleted user — clear it */
        unset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['username'], $_SESSION['nombre']);
        return null;
    }

    /* Keep session role in sync with DB (in case of role change mid-session) */
    $_SESSION['role']     = $user['role'];
    $_SESSION['username'] = $user['username'];

    return $user;
}

/* ═══════════════════════════════════════════════════════════════
   require_login — redirect to login if not authenticated
   ═══════════════════════════════════════════════════════════════ */
function require_login(string $redirectTo = 'login.php'): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . $redirectTo);
        exit;
    }
}

/* ═══════════════════════════════════════════════════════════════
   require_admin — redirect if not an admin
   ═══════════════════════════════════════════════════════════════ */
function require_admin(string $redirectTo = 'index.php'): void {
    if (($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: ' . $redirectTo);
        exit;
    }
}

/* ═══════════════════════════════════════════════════════════════
   safe_str — output-safe string truncation
   ═══════════════════════════════════════════════════════════════ */
function safe_str(mixed $val, int $maxLen = 0): string {
    $s = htmlspecialchars((string) ($val ?? ''), ENT_QUOTES, 'UTF-8');
    if ($maxLen > 0 && mb_strlen($s) > $maxLen) {
        $s = mb_substr($s, 0, $maxLen) . '…';
    }
    return $s;
}

/* ═══════════════════════════════════════════════════════════════
   relative_time — "hace X tiempo" human-readable date
   ═══════════════════════════════════════════════════════════════ */
function relative_time(string $dateStr): string {
    $diff = time() - strtotime($dateStr);
    if ($diff < 60)           return 'hace unos segundos';
    if ($diff < 3600)         return 'hace ' . floor($diff / 60) . ' min';
    if ($diff < 86400)        return 'hace ' . floor($diff / 3600) . ' h';
    if ($diff < 86400 * 7)    return 'hace ' . floor($diff / 86400) . ' d';
    if ($diff < 86400 * 30)   return 'hace ' . floor($diff / (86400 * 7)) . ' sem';
    if ($diff < 86400 * 365)  return 'hace ' . floor($diff / (86400 * 30)) . ' mes';
    return 'hace ' . floor($diff / (86400 * 365)) . ' año(s)';
}

/* ═══════════════════════════════════════════════════════════════
   fmt_number — 1234 → "1.2k"
   ═══════════════════════════════════════════════════════════════ */
function fmt_number(int|float $n): string {
    if ($n >= 1_000_000) return round($n / 1_000_000, 1) . 'M';
    if ($n >= 1_000)     return round($n / 1_000, 1)     . 'k';
    return (string) $n;
}

/* ═══════════════════════════════════════════════════════════════
   CSRF token helpers — simple double-submit cookie pattern
   ═══════════════════════════════════════════════════════════════ */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(string $token): bool {
    return isset($_SESSION['csrf_token']) &&
           hash_equals($_SESSION['csrf_token'], $token);
}

/* ═══════════════════════════════════════════════════════════════
   Boot: load current user and flash messages
   ═══════════════════════════════════════════════════════════════ */
$currentUser  = current_user_data($conn);
$flashError   = $_SESSION['flash_error']   ?? '';
$flashSuccess = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);
