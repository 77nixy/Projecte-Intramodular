<?php
/**
 * NEXUS//BOARD — REST API
 * Handles all AJAX/fetch requests from the frontend.
 * All responses are JSON. All mutations require POST.
 * Admin-only actions validate $_SESSION['role'] === 'admin'.
 */

ini_set('display_errors', 0);
error_reporting(0);
ob_start();
session_start();
require_once __DIR__ . '/db.php';
ob_clean();

/* ── Security headers ──────────────────────────────────────────────── */
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store, no-cache, must-revalidate');

/* ── Helpers ───────────────────────────────────────────────────────── */

function isAdmin(): bool {
    return ($_SESSION['role'] ?? '') === 'admin';
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function currentUserId(): int {
    return (int) ($_SESSION['user_id'] ?? 0);
}

function jsonOut(mixed $data, int $status = 200): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function jsonError(string $msg, int $status = 400): never {
    jsonOut(['error' => $msg, 'success' => false], $status);
}

function requireAdmin(): void {
    if (!isAdmin()) jsonError('No autorizado — se requiere rol admin', 403);
}

function requireLogin(): void {
    if (!isLoggedIn()) jsonError('Debes iniciar sesión para realizar esta acción', 401);
}

function postBody(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

function sanitizeText(string $text, int $maxLen = 0): string {
    $text = strip_tags(trim($text));
    if ($maxLen > 0 && mb_strlen($text) > $maxLen) {
        $text = mb_substr($text, 0, $maxLen);
    }
    return $text;
}

function paginate(mysqli $conn, string $sql, string $types, array $params, int $page, int $perPage): array {
    $offset = ($page - 1) * $perPage;
    $countSql = preg_replace('/SELECT .+ FROM/i', 'SELECT COUNT(*) as total FROM', $sql, 1);
    $countSql = preg_replace('/ORDER BY.*/i', '', $countSql);

    if ($params) {
        $countStmt = $conn->prepare($countSql);
        $countStmt->bind_param($types, ...$params);
        $countStmt->execute();
        $total = (int) $countStmt->get_result()->fetch_assoc()['total'];
        $countStmt->close();
    } else {
        $total = (int) $conn->query($countSql)->fetch_assoc()['total'];
    }

    $sql .= " LIMIT ? OFFSET ?";
    $types .= 'ii';
    $params[] = $perPage;
    $params[] = $offset;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return [
        'data'        => $rows,
        'total'       => $total,
        'page'        => $page,
        'per_page'    => $perPage,
        'total_pages' => (int) ceil($total / $perPage),
    ];
}

/* ── Route dispatcher ──────────────────────────────────────────────── */

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

/* ═══════════════════════════════════════════════════════════════════
   GET: get_posts — public forum feed with optional filters
   Query params: category, search, sort, page, per_page
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'get_posts' && $method === 'GET') {
    $category = sanitizeText($_GET['category'] ?? 'all');
    $search   = sanitizeText($_GET['search']   ?? '', 120);
    $sort     = in_array($_GET['sort'] ?? '', ['recent', 'popular']) ? $_GET['sort'] : 'recent';
    $page     = max(1, (int) ($_GET['page'] ?? 1));
    $perPage  = min(50, max(5, (int) ($_GET['per_page'] ?? 20)));

    $sql    = "SELECT p.id, p.title, p.content, p.category, p.likes, p.approved,
                      p.created_at, u.username, u.nombre, u.favorite_game
               FROM posts p
               JOIN usuarios u ON p.author_id = u.id
               WHERE p.approved = 1";
    $params = [];
    $types  = '';

    if ($category !== 'all') {
        $sql    .= " AND p.category = ?";
        $params[] = $category;
        $types  .= 's';
    }
    if ($search !== '') {
        $sql    .= " AND (p.title LIKE ? OR p.content LIKE ?)";
        $like     = "%{$search}%";
        $params[] = $like;
        $params[] = $like;
        $types  .= 'ss';
    }

    $sql .= $sort === 'popular'
        ? " ORDER BY p.likes DESC, p.created_at DESC"
        : " ORDER BY p.created_at DESC";

    $result = paginate($conn, $sql, $types, $params, $page, $perPage);
    jsonOut($result);
}

/* ═══════════════════════════════════════════════════════════════════
   GET: get_post — single post by ID (public)
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'get_post' && $method === 'GET') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id < 1) jsonError('ID de post no válido');

    $stmt = $conn->prepare(
        "SELECT p.*, u.username, u.nombre, u.favorite_game, u.bio as author_bio
         FROM posts p JOIN usuarios u ON p.author_id = u.id
         WHERE p.id = ? AND p.approved = 1 LIMIT 1"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$post) jsonError('Post no encontrado', 404);
    jsonOut($post);
}

/* ═══════════════════════════════════════════════════════════════════
   GET: get_users — all registered members (public)
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'get_users' && $method === 'GET') {
    $result = $conn->query(
        "SELECT id, nombre, username, favorite_game, bio, role, fecha_registro,
                (SELECT COUNT(*) FROM posts WHERE author_id = u.id AND approved = 1) as post_count,
                (SELECT COALESCE(SUM(likes),0) FROM posts WHERE author_id = u.id AND approved = 1) as total_likes
         FROM usuarios u ORDER BY total_likes DESC, fecha_registro DESC"
    );
    $users = $result->fetch_all(MYSQLI_ASSOC);
    jsonOut($users);
}

/* ═══════════════════════════════════════════════════════════════════
   GET: get_user — single user profile (public)
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'get_user' && $method === 'GET') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id < 1) jsonError('ID de usuario no válido');

    $stmt = $conn->prepare(
        "SELECT id, nombre, username, favorite_game, bio, role, fecha_registro,
                (SELECT COUNT(*) FROM posts WHERE author_id = u.id AND approved = 1) as post_count,
                (SELECT COALESCE(SUM(likes),0) FROM posts WHERE author_id = u.id AND approved = 1) as total_likes
         FROM usuarios u WHERE u.id = ? LIMIT 1"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) jsonError('Usuario no encontrado', 404);
    jsonOut($user);
}

/* ═══════════════════════════════════════════════════════════════════
   GET: get_user_posts — posts by a specific user (public)
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'get_user_posts' && $method === 'GET') {
    $userId  = (int) ($_GET['user_id'] ?? 0);
    $page    = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = min(20, max(5, (int) ($_GET['per_page'] ?? 10)));

    if ($userId < 1) jsonError('ID de usuario no válido');

    $sql    = "SELECT p.id, p.title, p.content, p.category, p.likes, p.created_at,
                      u.username, u.nombre
               FROM posts p JOIN usuarios u ON p.author_id = u.id
               WHERE p.author_id = ? AND p.approved = 1
               ORDER BY p.created_at DESC";
    $result = paginate($conn, $sql, 'i', [$userId], $page, $perPage);
    jsonOut($result);
}

/* ═══════════════════════════════════════════════════════════════════
   GET: get_leaderboard — top users ranked by total likes
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'get_leaderboard' && $method === 'GET') {
    $limit = min(20, max(3, (int) ($_GET['limit'] ?? 10)));
    $stmt  = $conn->prepare(
        "SELECT u.id, u.nombre, u.username, u.favorite_game, u.bio, u.role,
                COUNT(p.id) as post_count,
                COALESCE(SUM(p.likes), 0) as total_likes
         FROM usuarios u
         LEFT JOIN posts p ON p.author_id = u.id AND p.approved = 1
         GROUP BY u.id
         ORDER BY total_likes DESC, post_count DESC
         LIMIT ?"
    );
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    jsonOut($rows);
}

/* ═══════════════════════════════════════════════════════════════════
   GET: search — full-text search across posts (public)
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'search' && $method === 'GET') {
    $q    = sanitizeText($_GET['q'] ?? '', 100);
    $page = max(1, (int) ($_GET['page'] ?? 1));

    if (mb_strlen($q) < 2) jsonError('La búsqueda debe tener al menos 2 caracteres');

    $sql    = "SELECT p.id, p.title, p.content, p.category, p.likes, p.created_at,
                      u.username, u.nombre
               FROM posts p JOIN usuarios u ON p.author_id = u.id
               WHERE p.approved = 1 AND (p.title LIKE ? OR p.content LIKE ?)
               ORDER BY p.likes DESC, p.created_at DESC";
    $like   = "%{$q}%";
    $result = paginate($conn, $sql, 'ss', [$like, $like], $page, 15);
    jsonOut($result);
}

/* ═══════════════════════════════════════════════════════════════════
   GET: get_stats — public forum stats for sidebar
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'get_stats' && $method === 'GET') {
    $stats = [
        'posts'    => (int) $conn->query("SELECT COUNT(*) as c FROM posts WHERE approved = 1")->fetch_assoc()['c'],
        'users'    => (int) $conn->query("SELECT COUNT(*) as c FROM usuarios")->fetch_assoc()['c'],
        'likes'    => (int) ($conn->query("SELECT COALESCE(SUM(likes),0) as s FROM posts WHERE approved=1")->fetch_assoc()['s'] ?? 0),
        'pending'  => (int) $conn->query("SELECT COUNT(*) as c FROM posts WHERE approved = 0")->fetch_assoc()['c'],
        'contacts' => (int) $conn->query("SELECT COUNT(*) as c FROM contacts WHERE `read` = 0")->fetch_assoc()['c'],
        'categories' => [],
    ];

    $catResult = $conn->query(
        "SELECT category, COUNT(*) as cnt FROM posts WHERE approved=1 GROUP BY category ORDER BY cnt DESC"
    );
    while ($row = $catResult->fetch_assoc()) {
        $stats['categories'][$row['category']] = (int) $row['cnt'];
    }
    jsonOut($stats);
}

/* ═══════════════════════════════════════════════════════════════════
   POST: add_post — create a new post (requires login)
   Body: { title, content, category }
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'add_post' && $method === 'POST') {
    requireLogin();

    $data     = postBody();
    $title    = sanitizeText($data['title']    ?? '', 100);
    $content  = sanitizeText($data['content']  ?? '', 5000);
    $category = sanitizeText($data['category'] ?? 'fps', 50);
    $userId   = currentUserId();

    $allowedCategories = ['fps', 'hardware', 'estrategia', 'moba', 'noticias', 'general'];
    if (!in_array($category, $allowedCategories, true)) {
        $category = 'general';
    }

    if (mb_strlen($title) < 5)   jsonError('El título debe tener al menos 5 caracteres');
    if (mb_strlen($content) < 10) jsonError('El contenido debe tener al menos 10 caracteres');

    $approved = isAdmin() ? 1 : 0;

    $stmt = $conn->prepare(
        "INSERT INTO posts (title, content, category, author_id, approved) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('sssii', $title, $content, $category, $userId, $approved);
    $ok = $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();

    if (!$ok) jsonError('No se pudo crear el post', 500);

    jsonOut([
        'success'  => true,
        'id'       => $id,
        'approved' => (bool) $approved,
        'message'  => $approved ? 'Post publicado correctamente' : 'Post enviado — pendiente de moderación',
    ]);
}

/* ═══════════════════════════════════════════════════════════════════
   POST: like_post — increment likes on a post
   Body: { post_id }
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'like_post' && $method === 'POST') {
    $data   = postBody();
    $postId = (int) ($data['post_id'] ?? 0);
    if ($postId < 1) jsonError('ID de post no válido');

    $stmt = $conn->prepare("UPDATE posts SET likes = likes + 1 WHERE id = ? AND approved = 1");
    $stmt->bind_param('i', $postId);
    $ok = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected < 1) jsonError('Post no encontrado o no aprobado', 404);

    $newLikes = (int) $conn->query("SELECT likes FROM posts WHERE id = {$postId}")->fetch_assoc()['likes'];
    jsonOut(['success' => true, 'likes' => $newLikes]);
}

/* ═══════════════════════════════════════════════════════════════════
   GET: get_all_posts — all posts including pending (admin only)
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'get_all_posts' && $method === 'GET') {
    requireAdmin();

    $page    = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = min(50, max(10, (int) ($_GET['per_page'] ?? 25)));

    $sql    = "SELECT p.*, u.username, u.nombre, u.email
               FROM posts p JOIN usuarios u ON p.author_id = u.id
               ORDER BY p.approved ASC, p.created_at DESC";
    $result = paginate($conn, $sql, '', [], $page, $perPage);
    jsonOut($result);
}

/* ═══════════════════════════════════════════════════════════════════
   POST: approve_post — toggle approved status (admin only)
   Body: { post_id, approved }
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'approve_post' && $method === 'POST') {
    requireAdmin();

    $data     = postBody();
    $postId   = (int) ($data['post_id'] ?? 0);
    $approved = (int) ($data['approved'] ?? 1);

    if ($postId < 1) jsonError('ID de post no válido');
    if (!in_array($approved, [0, 1], true)) jsonError('Estado no válido');

    $stmt = $conn->prepare("UPDATE posts SET approved = ? WHERE id = ?");
    $stmt->bind_param('ii', $approved, $postId);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) jsonError('Error al actualizar el post', 500);
    jsonOut(['success' => true, 'approved' => (bool) $approved]);
}

/* ═══════════════════════════════════════════════════════════════════
   POST: delete_post — permanently delete a post (admin only)
   Body: { post_id }
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'delete_post' && $method === 'POST') {
    requireAdmin();

    $data   = postBody();
    $postId = (int) ($data['post_id'] ?? 0);
    if ($postId < 1) jsonError('ID de post no válido');

    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->bind_param('i', $postId);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) jsonError('Error al eliminar el post', 500);
    jsonOut(['success' => true]);
}

/* ═══════════════════════════════════════════════════════════════════
   GET: get_contacts — all contact messages (admin only)
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'get_contacts' && $method === 'GET') {
    requireAdmin();

    $page     = max(1, (int) ($_GET['page'] ?? 1));
    $perPage  = min(50, max(5, (int) ($_GET['per_page'] ?? 20)));
    $unread   = isset($_GET['unread']) ? (bool) $_GET['unread'] : false;

    $sql   = "SELECT * FROM contacts";
    if ($unread) $sql .= " WHERE `read` = 0";
    $sql  .= " ORDER BY `read` ASC, created_at DESC";

    $result = paginate($conn, $sql, '', [], $page, $perPage);
    jsonOut($result);
}

/* ═══════════════════════════════════════════════════════════════════
   POST: mark_contact_read — mark a message as read (admin only)
   Body: { contact_id }
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'mark_contact_read' && $method === 'POST') {
    requireAdmin();

    $data      = postBody();
    $contactId = (int) ($data['contact_id'] ?? 0);
    if ($contactId < 1) jsonError('ID de contacto no válido');

    $stmt = $conn->prepare("UPDATE contacts SET `read` = 1 WHERE id = ?");
    $stmt->bind_param('i', $contactId);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) jsonError('Error al actualizar el contacto', 500);
    jsonOut(['success' => true]);
}

/* ═══════════════════════════════════════════════════════════════════
   POST: delete_contact — permanently delete a contact message (admin)
   Body: { contact_id }
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'delete_contact' && $method === 'POST') {
    requireAdmin();

    $data      = postBody();
    $contactId = (int) ($data['contact_id'] ?? 0);
    if ($contactId < 1) jsonError('ID de contacto no válido');

    $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
    $stmt->bind_param('i', $contactId);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) jsonError('Error al eliminar el contacto', 500);
    jsonOut(['success' => true]);
}

/* ═══════════════════════════════════════════════════════════════════
   GET: get_admin_stats — detailed stats for admin dashboard
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'get_admin_stats' && $method === 'GET') {
    requireAdmin();

    $stats = [
        'users_total'    => (int) $conn->query("SELECT COUNT(*) as c FROM usuarios")->fetch_assoc()['c'],
        'posts_approved' => (int) $conn->query("SELECT COUNT(*) as c FROM posts WHERE approved=1")->fetch_assoc()['c'],
        'posts_pending'  => (int) $conn->query("SELECT COUNT(*) as c FROM posts WHERE approved=0")->fetch_assoc()['c'],
        'contacts_unread'=> (int) $conn->query("SELECT COUNT(*) as c FROM contacts WHERE `read`=0")->fetch_assoc()['c'],
        'total_likes'    => (int) ($conn->query("SELECT COALESCE(SUM(likes),0) as s FROM posts")->fetch_assoc()['s'] ?? 0),
        'admins'         => (int) $conn->query("SELECT COUNT(*) as c FROM usuarios WHERE role='admin'")->fetch_assoc()['c'],
        'members'        => (int) $conn->query("SELECT COUNT(*) as c FROM usuarios WHERE role='member'")->fetch_assoc()['c'],
    ];

    $catStats = $conn->query(
        "SELECT category, COUNT(*) as cnt, COALESCE(SUM(likes),0) as likes
         FROM posts WHERE approved=1 GROUP BY category ORDER BY cnt DESC"
    );
    $stats['categories'] = $catStats->fetch_all(MYSQLI_ASSOC);

    $topPosts = $conn->query(
        "SELECT p.id, p.title, p.likes, p.category, u.username
         FROM posts p JOIN usuarios u ON p.author_id=u.id
         WHERE p.approved=1 ORDER BY p.likes DESC LIMIT 5"
    );
    $stats['top_posts'] = $topPosts->fetch_all(MYSQLI_ASSOC);

    jsonOut($stats);
}

/* ═══════════════════════════════════════════════════════════════════
   POST: update_user_role — change a user's role (admin only)
   Body: { user_id, role }
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'update_user_role' && $method === 'POST') {
    requireAdmin();

    $data   = postBody();
    $userId = (int) ($data['user_id'] ?? 0);
    $role   = sanitizeText($data['role'] ?? '', 20);

    if ($userId < 1)           jsonError('ID de usuario no válido');
    if ($userId === currentUserId()) jsonError('No puedes cambiar tu propio rol');
    if (!in_array($role, ['admin', 'member'], true)) jsonError('Rol no válido');

    $stmt = $conn->prepare("UPDATE usuarios SET role = ? WHERE id = ?");
    $stmt->bind_param('si', $role, $userId);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) jsonError('Error al actualizar el rol', 500);
    jsonOut(['success' => true, 'role' => $role]);
}

/* ═══════════════════════════════════════════════════════════════════
   POST: delete_user — permanently delete a user account (admin only)
   Body: { user_id }
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'delete_user' && $method === 'POST') {
    requireAdmin();

    $data   = postBody();
    $userId = (int) ($data['user_id'] ?? 0);

    if ($userId < 1)           jsonError('ID de usuario no válido');
    if ($userId === currentUserId()) jsonError('No puedes eliminar tu propia cuenta');

    $targetRole = $conn->query("SELECT role FROM usuarios WHERE id={$userId}")->fetch_assoc()['role'] ?? '';
    if ($targetRole === 'admin') jsonError('No puedes eliminar a otro administrador');

    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) jsonError('Error al eliminar el usuario', 500);
    jsonOut(['success' => true]);
}

/* ═══════════════════════════════════════════════════════════════════
   GET: backup_database — download full SQL dump (admin only)
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'backup_database' && $method === 'GET') {
    requireAdmin();

    @ini_set('zlib.output_compression', 'Off');
    if (function_exists('apache_setenv')) @apache_setenv('no-gzip', '1');
    while (ob_get_level()) ob_end_clean();

    $date     = date('Y-m-d_H-i-s');
    $filename = "nexus_backup_{$date}.sql";

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header_remove('Content-Encoding');
    header('Cache-Control: no-store, no-cache');
    header('Pragma: no-cache');

    $out  = "-- ================================================\n";
    $out .= "-- NEXUS Database Backup\n";
    $out .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $out .= "-- Database: usuarios_db\n";
    $out .= "-- ================================================\n\n";
    $out .= "SET NAMES utf8mb4;\n";
    $out .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

    $tables = $conn->query("SHOW TABLES")->fetch_all(MYSQLI_NUM);
    foreach ($tables as [$table]) {
        $createRow = $conn->query("SHOW CREATE TABLE `{$table}`")->fetch_assoc();
        $createSql = $createRow['Create Table'] ?? '';

        $out .= "-- ----------------------------\n";
        $out .= "-- Table: `{$table}`\n";
        $out .= "-- ----------------------------\n";
        $out .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $out .= $createSql . ";\n\n";

        $rows = $conn->query("SELECT * FROM `{$table}`");
        if ($rows && $rows->num_rows > 0) {
            $fields   = array_column($rows->fetch_fields(), 'name');
            $colList  = '`' . implode('`, `', $fields) . '`';
            $rows     = $conn->query("SELECT * FROM `{$table}`");
            $inserts  = [];
            while ($row = $rows->fetch_row()) {
                $vals = array_map(
                    fn($v) => $v === null ? 'NULL' : "'" . $conn->real_escape_string((string)$v) . "'",
                    $row
                );
                $inserts[] = '(' . implode(', ', $vals) . ')';
            }
            $out .= "INSERT INTO `{$table}` ({$colList}) VALUES\n" . implode(",\n", $inserts) . ";\n\n";
        }
    }

    $out .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    echo $out;
    exit;
}

/* ═══════════════════════════════════════════════════════════════════
   GET: backup_files — download complete ZIP of all web files (admin only)
   Includes: gaming-foro-v2/ (all PHP/CSS/JS/bat) + ip.php from htdocs root
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'backup_files' && $method === 'GET') {
    requireAdmin();

    @ini_set('zlib.output_compression', 'Off');
    if (function_exists('apache_setenv')) @apache_setenv('no-gzip', '1');

    if (!class_exists('ZipArchive')) {
        jsonError('ZipArchive no disponible en este servidor', 500);
    }

    $date    = date('Y-m-d_H-i-s');
    $zipName = "nexus_web_completa_{$date}.zip";
    $tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipName;

    $zip = new ZipArchive();
    if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        jsonError('No se pudo crear el archivo ZIP', 500);
    }

    $projectDir = realpath(__DIR__);
    $htdocsDir  = realpath(dirname(__DIR__));

    /* ── All files inside gaming-foro-v2/ ── */
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($projectDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($iter as $file) {
        if (!$file->isFile()) continue;
        $realPath = $file->getRealPath();

        /* Skip any previously generated backup ZIPs/SQLs sitting in the folder */
        $basename = basename($realPath);
        if (preg_match('/^nexus_(web_completa|files|backup)_/', $basename)) continue;

        /* Store as  gaming-foro-v2/<relative-path>  so the ZIP has a clean root folder */
        $rel = ltrim(str_replace('\\', '/', substr($realPath, strlen($projectDir))), '/');
        $zip->addFile($realPath, 'gaming-foro-v2/' . $rel);
    }

    /* ── ip.php at htdocs root (the IP / QR display page used in class) ── */
    $ipPhp = $htdocsDir . DIRECTORY_SEPARATOR . 'ip.php';
    if (file_exists($ipPhp)) {
        $zip->addFile($ipPhp, 'ip.php');
    }

    $zip->close();

    while (ob_get_level()) ob_end_clean();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipName . '"');
    header('Content-Length: ' . filesize($tmpFile));
    header('Cache-Control: no-store');
    header_remove('Content-Encoding');

    readfile($tmpFile);
    unlink($tmpFile);
    exit;
}

/* ═══════════════════════════════════════════════════════════════════
   GET: backup_full — single ZIP containing ALL web files + SQL dump
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'backup_full' && $method === 'GET') {
    requireAdmin();

    @ini_set('zlib.output_compression', 'Off');
    if (function_exists('apache_setenv')) @apache_setenv('no-gzip', '1');

    if (!class_exists('ZipArchive')) {
        jsonError('ZipArchive no disponible en este servidor', 500);
    }

    $date    = date('Y-m-d_H-i-s');
    $zipName = "nexus_backup_completo_{$date}.zip";
    $tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipName;

    $zip = new ZipArchive();
    if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        jsonError('No se pudo crear el archivo ZIP', 500);
    }

    $projectDir = realpath(__DIR__);
    $htdocsDir  = realpath(dirname(__DIR__));

    /* ── Web files ── */
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($projectDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($iter as $file) {
        if (!$file->isFile()) continue;
        $realPath = $file->getRealPath();
        $basename = basename($realPath);
        if (preg_match('/^nexus_(web_completa|files|backup)_/', $basename)) continue;
        $rel = ltrim(str_replace('\\', '/', substr($realPath, strlen($projectDir))), '/');
        $zip->addFile($realPath, 'gaming-foro-v2/' . $rel);
    }

    $ipPhp = $htdocsDir . DIRECTORY_SEPARATOR . 'ip.php';
    if (file_exists($ipPhp)) {
        $zip->addFile($ipPhp, 'ip.php');
    }

    /* ── SQL dump (embedded inside the ZIP) ── */
    $sqlOut  = "-- ================================================\n";
    $sqlOut .= "-- NEXUS Database Backup\n";
    $sqlOut .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sqlOut .= "-- Database: usuarios_db\n";
    $sqlOut .= "-- ================================================\n\n";
    $sqlOut .= "SET NAMES utf8mb4;\n";
    $sqlOut .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

    $tables = $conn->query("SHOW TABLES")->fetch_all(MYSQLI_NUM);
    foreach ($tables as [$table]) {
        $createRow = $conn->query("SHOW CREATE TABLE `{$table}`")->fetch_assoc();
        $createSql = $createRow['Create Table'] ?? '';
        $sqlOut .= "-- ----------------------------\n";
        $sqlOut .= "-- Table: `{$table}`\n";
        $sqlOut .= "-- ----------------------------\n";
        $sqlOut .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sqlOut .= $createSql . ";\n\n";
        $rows = $conn->query("SELECT * FROM `{$table}`");
        if ($rows && $rows->num_rows > 0) {
            $fields  = array_column($rows->fetch_fields(), 'name');
            $colList = '`' . implode('`, `', $fields) . '`';
            $rows    = $conn->query("SELECT * FROM `{$table}`");
            $inserts = [];
            while ($row = $rows->fetch_row()) {
                $vals = array_map(
                    fn($v) => $v === null ? 'NULL' : "'" . $conn->real_escape_string((string)$v) . "'",
                    $row
                );
                $inserts[] = '(' . implode(', ', $vals) . ')';
            }
            $sqlOut .= "INSERT INTO `{$table}` ({$colList}) VALUES\n" . implode(",\n", $inserts) . ";\n\n";
        }
    }
    $sqlOut .= "SET FOREIGN_KEY_CHECKS = 1;\n";

    $zip->addFromString("nexus_database_{$date}.sql", $sqlOut);
    $zip->close();

    while (ob_get_level()) ob_end_clean();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipName . '"');
    header('Content-Length: ' . filesize($tmpFile));
    header('Cache-Control: no-store');
    header_remove('Content-Encoding');

    readfile($tmpFile);
    unlink($tmpFile);
    exit;
}

/* ═══════════════════════════════════════════════════════════════════
   POST: reset_site — delete data with PIN protection (admin only)
   Body: { pin, target }
   Targets: posts | users | contacts | all | database
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'reset_site' && $method === 'POST') {
    requireAdmin();

    $data   = postBody();
    $pin    = (string) ($data['pin'] ?? '');
    $target = sanitizeText($data['target'] ?? '', 20);

    if ($pin !== '565656') jsonError('PIN incorrecto', 403);

    $allowed = ['posts', 'users', 'contacts', 'all', 'database'];
    if (!in_array($target, $allowed, true)) jsonError('Objetivo no válido');

    $selfId = currentUserId();

    switch ($target) {
        case 'posts':
            $conn->query("DELETE FROM posts");
            $msg = 'Todos los posts han sido eliminados.';
            break;
        case 'users':
            $conn->query("DELETE FROM usuarios WHERE role != 'admin'");
            $msg = 'Usuarios no-admin eliminados (y sus posts por CASCADE).';
            break;
        case 'contacts':
            $conn->query("DELETE FROM contacts");
            $msg = 'Todos los mensajes de contacto eliminados.';
            break;
        case 'all':
            $conn->query("DELETE FROM posts");
            $conn->query("DELETE FROM usuarios WHERE role != 'admin'");
            $conn->query("DELETE FROM contacts");
            $msg = 'Foro reseteado: posts, usuarios y mensajes eliminados. Solo queda la cuenta admin.';
            break;
        case 'database':
            $conn->query("DROP DATABASE IF EXISTS `usuarios_db`");
            $msg = 'Base de datos eliminada completamente. El sitio necesitará recarga para recrearla.';
            break;
        default:
            jsonError('Objetivo no reconocido');
    }

    jsonOut(['success' => true, 'message' => $msg]);
}

/* ═══════════════════════════════════════════════════════════════════
   POST: restore_files — restore web from uploaded ZIP (admin + PIN)
   Body: multipart/form-data  { zipfile: <file>, pin: "565656" }
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'restore_files' && $method === 'POST') {
    requireAdmin();

    $pin = trim($_POST['pin'] ?? '');
    if ($pin !== '565656') jsonError('PIN incorrecto', 403);

    if (!isset($_FILES['zipfile']) || $_FILES['zipfile']['error'] !== UPLOAD_ERR_OK) {
        $uploadErr = $_FILES['zipfile']['error'] ?? -1;
        $msg = match((int)$uploadErr) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño máximo permitido por PHP.',
            UPLOAD_ERR_NO_FILE  => 'No se recibió ningún archivo.',
            default             => "Error de subida (código {$uploadErr}).",
        };
        jsonError($msg);
    }

    $file = $_FILES['zipfile'];
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'zip') jsonError('El archivo debe tener extensión .zip');

    if (!class_exists('ZipArchive')) jsonError('ZipArchive no disponible en este servidor', 500);

    $zip = new ZipArchive();
    if ($zip->open($file['tmp_name']) !== true) jsonError('No se pudo abrir el ZIP. Comprueba que no está corrupto.');

    $projectDir = realpath(__DIR__);
    $htdocsDir  = realpath(dirname(__DIR__));
    $extracted  = 0;
    $skipped    = 0;

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entry = $zip->getNameIndex($i);

        /* Skip directory entries */
        if (substr($entry, -1) === '/') continue;

        /* Normalize to forward slashes and block path traversal */
        $entry = str_replace('\\', '/', $entry);
        if (str_contains($entry, '..')) { $skipped++; continue; }

        /* Map ZIP path → real filesystem path */
        if (str_starts_with($entry, 'gaming-foro-v2/')) {
            $rel  = substr($entry, strlen('gaming-foro-v2/'));
            if ($rel === '') { $skipped++; continue; }
            $dest = $projectDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        } elseif ($entry === 'ip.php') {
            $dest = $htdocsDir . DIRECTORY_SEPARATOR . 'ip.php';
        } else {
            /* Only restore known paths — ignore anything else */
            $skipped++;
            continue;
        }

        /* Create intermediate directories if needed */
        $destDir = dirname($dest);
        if (!is_dir($destDir)) mkdir($destDir, 0755, true);

        $content = $zip->getFromIndex($i);
        if ($content === false) { $skipped++; continue; }

        if (file_put_contents($dest, $content) !== false) {
            $extracted++;
        } else {
            $skipped++;
        }
    }

    $zip->close();

    $msg = "Restauración completada: {$extracted} archivo" . ($extracted !== 1 ? 's' : '') . " restaurado" . ($extracted !== 1 ? 's' : '') . ".";
    if ($skipped > 0) $msg .= " ({$skipped} omitido" . ($skipped !== 1 ? 's' : '') . ")";

    jsonOut(['success' => true, 'extracted' => $extracted, 'skipped' => $skipped, 'message' => $msg]);
}

/* ═══════════════════════════════════════════════════════════════════
   Fallback — unknown action
   ═══════════════════════════════════════════════════════════════════ */
jsonError("Acción '{$action}' no reconocida. Acciones disponibles: get_posts, get_post, get_users, get_user, get_user_posts, get_leaderboard, search, get_stats, add_post, like_post, get_all_posts, approve_post, delete_post, get_contacts, mark_contact_read, delete_contact, get_admin_stats, update_user_role, delete_user", 404);
