<?php
/**
 * NEXUS — API REST
 * Gestiona todas las peticiones AJAX/fetch del frontend.
 * Todas las respuestas son JSON. Toda modificación requiere POST.
 * Las acciones solo-admin validan $_SESSION['role'] === 'admin'.
 */

ini_set('display_errors', 0); // Oculta errores en pantalla (la salida debe ser JSON limpio)
error_reporting(0);            // Desactiva el reporte de errores al cliente
ob_start();                    // Inicia un buffer de salida (captura salida accidental)
session_start();               // Reanuda/crea la sesión del usuario
require_once __DIR__ . '/db.php'; // Carga la conexión $conn a la base de datos
require_once __DIR__ . '/zip_helper.php'; // Generador/lector de ZIP (funciona sin la extensión zip)
ob_clean();                    // Limpia el buffer: descarta avisos que ensuciarían el JSON

/* ── Cabeceras de seguridad ────────────────────────────────────────── */
header('Content-Type: application/json; charset=utf-8'); // La respuesta es JSON en UTF-8
header('X-Content-Type-Options: nosniff');               // Evita que el navegador adivine el tipo MIME
header('X-Frame-Options: DENY');                         // Prohíbe cargar esta respuesta en un iframe
header('Cache-Control: no-store, no-cache, must-revalidate'); // Nunca cachear respuestas de la API

/* ── Funciones de ayuda ────────────────────────────────────────────── */

function isAdmin(): bool { // ¿El usuario actual es administrador?
    return ($_SESSION['role'] ?? '') === 'admin'; // True solo si el rol de sesión es 'admin'
}

function isLoggedIn(): bool { // ¿Hay una sesión iniciada?
    return !empty($_SESSION['user_id']); // True si existe un ID de usuario en la sesión
}

function currentUserId(): int { // ID del usuario actual (0 si es anónimo)
    return (int) ($_SESSION['user_id'] ?? 0); // Devuelve el ID de sesión como entero
}

function jsonOut(mixed $data, int $status = 200): never { // Envía una respuesta JSON y termina
    http_response_code($status); // Fija el código HTTP de la respuesta
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); // Serializa a JSON legible
    exit; // Termina la ejecución
}

function jsonError(string $msg, int $status = 400): never { // Atajo para responder un error en JSON
    jsonOut(['error' => $msg, 'success' => false], $status); // Devuelve {error, success:false}
}

function requireAdmin(): void { // Corta la ejecución si el usuario no es admin
    if (!isAdmin()) jsonError('No autorizado — se requiere rol admin', 403); // 403 = prohibido
}

function requireLogin(): void { // Corta la ejecución si no hay sesión
    if (!isLoggedIn()) jsonError('Debes iniciar sesión para realizar esta acción', 401); // 401 = no autenticado
}

function postBody(): array { // Lee el cuerpo JSON de una petición POST
    $raw = file_get_contents('php://input'); // Lee el cuerpo crudo de la petición
    return json_decode($raw, true) ?? []; // Lo convierte a array asociativo (vacío si falla)
}

function sanitizeText(string $text, int $maxLen = 0): string { // Limpia y recorta texto de entrada
    $text = strip_tags(trim($text)); // Quita etiquetas HTML y espacios sobrantes
    if ($maxLen > 0 && mb_strlen($text) > $maxLen) { // Si hay límite y se supera...
        $text = mb_substr($text, 0, $maxLen); // ...recorta el texto
    }
    return $text; // Devuelve el texto saneado
}

// paginate — ejecuta una consulta paginada y devuelve datos + metadatos de paginación
function paginate(mysqli $conn, string $sql, string $types, array $params, int $page, int $perPage): array { // Ejecuta una consulta paginada

    $offset = ($page - 1) * $perPage; // Calcula desde qué fila empezar
    $countSql = preg_replace('/SELECT .+ FROM/i', 'SELECT COUNT(*) as total FROM', $sql, 1); // Versión COUNT de la consulta
    $countSql = preg_replace('/ORDER BY.*/i', '', $countSql); // Elimina el ORDER BY (innecesario al contar)

    if ($params) { // Si la consulta lleva parámetros...
        $countStmt = $conn->prepare($countSql); // Prepara el COUNT
        $countStmt->bind_param($types, ...$params); // Enlaza los mismos parámetros
        $countStmt->execute(); // Ejecuta el COUNT
        $total = (int) $countStmt->get_result()->fetch_assoc()['total']; // Total de filas
        $countStmt->close(); // Libera la sentencia
    } else { // Si no hay parámetros...
        $total = (int) $conn->query($countSql)->fetch_assoc()['total']; // Cuenta directamente
    }

    $sql .= " LIMIT ? OFFSET ?"; // Añade la paginación a la consulta
    $types .= 'ii'; // Dos enteros más: LIMIT y OFFSET
    $params[] = $perPage; // Parámetro LIMIT (filas por página)
    $params[] = $offset;  // Parámetro OFFSET (desde dónde empezar)

    $stmt = $conn->prepare($sql); // Prepara la consulta paginada
    $stmt->bind_param($types, ...$params); // Enlaza todos los parámetros
    $stmt->execute(); // Ejecuta la consulta
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // Obtiene las filas de esta página
    $stmt->close(); // Libera la sentencia

    return [ // Devuelve los datos junto a los metadatos de paginación
        'data'        => $rows,                          // Filas de la página actual
        'total'       => $total,                         // Total de filas sin paginar
        'page'        => $page,                          // Página actual
        'per_page'    => $perPage,                       // Filas por página
        'total_pages' => (int) ceil($total / $perPage),  // Número total de páginas
    ];
}

/* ── Despachador de rutas ──────────────────────────────────────────── */

$action = $_GET['action'] ?? '';        // Acción solicitada (?action=...)
$method = $_SERVER['REQUEST_METHOD'];    // Método HTTP (GET/POST)

/* ═══════════════════════════════════════════════════════════════════
   GET: get_posts — feed público del foro con filtros opcionales
   Parámetros: category, search, sort, page, per_page
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'get_posts' && $method === 'GET') { // Ruta: feed público de posts
    $category = sanitizeText($_GET['category'] ?? 'all'); // Categoría a filtrar ('all' = todas)
    $search   = sanitizeText($_GET['search']   ?? '', 120); // Texto de búsqueda (máx 120)
    $sort     = in_array($_GET['sort'] ?? '', ['recent', 'popular']) ? $_GET['sort'] : 'recent'; // Orden válido o 'recent'
    $page     = max(1, (int) ($_GET['page'] ?? 1)); // Página (mínimo 1)
    $perPage  = min(50, max(5, (int) ($_GET['per_page'] ?? 20))); // Entre 5 y 50 por página

    $sql    = "SELECT p.id, p.title, p.content, p.category, p.likes, p.approved,
                      p.created_at, u.username, u.nombre, u.favorite_game
               FROM posts p
               JOIN usuarios u ON p.author_id = u.id
               WHERE p.approved = 1"; // Solo posts aprobados
    $params = []; // Parámetros para la consulta preparada
    $types  = ''; // Tipos de los parámetros

    if ($category !== 'all') { // Si se filtra por categoría...
        $sql    .= " AND p.category = ?"; // ...añade la condición
        $params[] = $category; // Parámetro de categoría
        $types  .= 's'; // Es una cadena
    }
    if ($search !== '') { // Si hay texto de búsqueda...
        $sql    .= " AND (p.title LIKE ? OR p.content LIKE ?)"; // ...busca en título y contenido
        $like     = "%{$search}%"; // Patrón LIKE con comodines
        $params[] = $like; // Para el título
        $params[] = $like; // Para el contenido
        $types  .= 'ss'; // Dos cadenas
    }

    $sql .= $sort === 'popular' // Según el orden elegido...
        ? " ORDER BY p.likes DESC, p.created_at DESC" // 'popular': por likes
        : " ORDER BY p.created_at DESC"; // 'recent': por fecha

    $result = paginate($conn, $sql, $types, $params, $page, $perPage); // Ejecuta paginado
    jsonOut($result); // Devuelve el resultado en JSON
}

/* ═══════════════════════════════════════════════════════════════════
   GET: get_post — un único post por ID (público)
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'get_post' && $method === 'GET') { // Ruta: un post por su ID
    $id = (int) ($_GET['id'] ?? 0); // ID del post solicitado
    if ($id < 1) jsonError('ID de post no válido'); // Valida el ID

    $stmt = $conn->prepare( // Consulta preparada con datos del autor
        "SELECT p.*, u.username, u.nombre, u.favorite_game, u.bio as author_bio
         FROM posts p JOIN usuarios u ON p.author_id = u.id
         WHERE p.id = ? AND p.approved = 1 LIMIT 1"
    );
    $stmt->bind_param('i', $id); // Enlaza el ID
    $stmt->execute(); // Ejecuta
    $post = $stmt->get_result()->fetch_assoc(); // Obtiene el post (o null)
    $stmt->close(); // Libera la sentencia

    if (!$post) jsonError('Post no encontrado', 404); // 404 si no existe
    jsonOut($post); // Devuelve el post
}

/* ═══════════════════════════════════════════════════════════════════
   GET: get_users — todos los miembros registrados (público)
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'get_users' && $method === 'GET') { // Ruta: lista pública de miembros
    $result = $conn->query( // Consulta con conteo de posts y likes por usuario
        "SELECT id, nombre, username, favorite_game, bio, role, fecha_registro,
                (SELECT COUNT(*) FROM posts WHERE author_id = u.id AND approved = 1) as post_count,
                (SELECT COALESCE(SUM(likes),0) FROM posts WHERE author_id = u.id AND approved = 1) as total_likes
         FROM usuarios u ORDER BY total_likes DESC, fecha_registro DESC"
    );
    $users = $result->fetch_all(MYSQLI_ASSOC); // Todas las filas como array
    jsonOut($users); // Devuelve la lista de usuarios
}

/* ═══════════════════════════════════════════════════════════════════
   GET: get_user — perfil de un único usuario (público)
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'get_user' && $method === 'GET') { // Ruta: perfil de un usuario
    $id = (int) ($_GET['id'] ?? 0); // ID del usuario solicitado
    if ($id < 1) jsonError('ID de usuario no válido'); // Valida el ID

    $stmt = $conn->prepare( // Consulta con estadísticas del usuario
        "SELECT id, nombre, username, favorite_game, bio, role, fecha_registro,
                (SELECT COUNT(*) FROM posts WHERE author_id = u.id AND approved = 1) as post_count,
                (SELECT COALESCE(SUM(likes),0) FROM posts WHERE author_id = u.id AND approved = 1) as total_likes
         FROM usuarios u WHERE u.id = ? LIMIT 1"
    );
    $stmt->bind_param('i', $id); // Enlaza el ID
    $stmt->execute(); // Ejecuta
    $user = $stmt->get_result()->fetch_assoc(); // Obtiene el usuario (o null)
    $stmt->close(); // Libera la sentencia

    if (!$user) jsonError('Usuario no encontrado', 404); // 404 si no existe
    jsonOut($user); // Devuelve el perfil
}

/* ═══════════════════════════════════════════════════════════════════
   GET: get_user_posts — posts de un usuario concreto (público)
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'get_user_posts' && $method === 'GET') { // Ruta: posts de un usuario
    $userId  = (int) ($_GET['user_id'] ?? 0); // ID del autor
    $page    = max(1, (int) ($_GET['page'] ?? 1)); // Página (mínimo 1)
    $perPage = min(20, max(5, (int) ($_GET['per_page'] ?? 10))); // Entre 5 y 20 por página

    if ($userId < 1) jsonError('ID de usuario no válido'); // Valida el ID

    $sql    = "SELECT p.id, p.title, p.content, p.category, p.likes, p.created_at,
                      u.username, u.nombre
               FROM posts p JOIN usuarios u ON p.author_id = u.id
               WHERE p.author_id = ? AND p.approved = 1
               ORDER BY p.created_at DESC"; // Posts aprobados del autor, más recientes primero
    $result = paginate($conn, $sql, 'i', [$userId], $page, $perPage); // Ejecuta paginado
    jsonOut($result); // Devuelve el resultado
}

/* ═══════════════════════════════════════════════════════════════════
   GET: get_leaderboard — ranking de usuarios por likes totales
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'get_leaderboard' && $method === 'GET') { // Ruta: ranking por likes
    $limit = min(20, max(3, (int) ($_GET['limit'] ?? 10))); // Cuántos mostrar (entre 3 y 20)
    $stmt  = $conn->prepare( // Consulta del ranking
        "SELECT u.id, u.nombre, u.username, u.favorite_game, u.bio, u.role,
                COUNT(p.id) as post_count,
                COALESCE(SUM(p.likes), 0) as total_likes
         FROM usuarios u
         LEFT JOIN posts p ON p.author_id = u.id AND p.approved = 1
         GROUP BY u.id
         ORDER BY total_likes DESC, post_count DESC
         LIMIT ?"
    );
    $stmt->bind_param('i', $limit); // Enlaza el límite
    $stmt->execute(); // Ejecuta
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // Filas del ranking
    $stmt->close(); // Libera la sentencia
    jsonOut($rows); // Devuelve el ranking
}

/* ═══════════════════════════════════════════════════════════════════
   GET: search — búsqueda de texto en los posts (público)
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'search' && $method === 'GET') { // Ruta: búsqueda de posts
    $q    = sanitizeText($_GET['q'] ?? '', 100); // Términos de búsqueda (máx 100)
    $page = max(1, (int) ($_GET['page'] ?? 1)); // Página (mínimo 1)

    if (mb_strlen($q) < 2) jsonError('La búsqueda debe tener al menos 2 caracteres'); // Mínimo 2 caracteres

    $sql    = "SELECT p.id, p.title, p.content, p.category, p.likes, p.created_at,
                      u.username, u.nombre
               FROM posts p JOIN usuarios u ON p.author_id = u.id
               WHERE p.approved = 1 AND (p.title LIKE ? OR p.content LIKE ?)
               ORDER BY p.likes DESC, p.created_at DESC"; // Busca en título y contenido
    $like   = "%{$q}%"; // Patrón LIKE con comodines
    $result = paginate($conn, $sql, 'ss', [$like, $like], $page, 15); // 15 resultados por página
    jsonOut($result); // Devuelve los resultados
}

/* ═══════════════════════════════════════════════════════════════════
   GET: get_stats — estadísticas públicas para la barra lateral
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'get_stats' && $method === 'GET') { // Ruta: estadísticas públicas
    $stats = [ // Conjunto de cifras públicas del foro
        'posts'    => (int) $conn->query("SELECT COUNT(*) as c FROM posts WHERE approved = 1")->fetch_assoc()['c'], // Posts aprobados
        'users'    => (int) $conn->query("SELECT COUNT(*) as c FROM usuarios")->fetch_assoc()['c'], // Total de usuarios
        'likes'    => (int) ($conn->query("SELECT COALESCE(SUM(likes),0) as s FROM posts WHERE approved=1")->fetch_assoc()['s'] ?? 0), // Suma de likes
        'pending'  => (int) $conn->query("SELECT COUNT(*) as c FROM posts WHERE approved = 0")->fetch_assoc()['c'], // Posts pendientes
        'contacts' => (int) $conn->query("SELECT COUNT(*) as c FROM contacts WHERE `read` = 0")->fetch_assoc()['c'], // Mensajes sin leer
        'categories' => [], // Se rellena debajo con el conteo por categoría
    ];

    $catResult = $conn->query( // Cuenta posts por categoría
        "SELECT category, COUNT(*) as cnt FROM posts WHERE approved=1 GROUP BY category ORDER BY cnt DESC"
    );
    while ($row = $catResult->fetch_assoc()) { // Recorre cada categoría
        $stats['categories'][$row['category']] = (int) $row['cnt']; // Guarda categoría => cantidad
    }
    jsonOut($stats); // Devuelve las estadísticas
}

/* ═══════════════════════════════════════════════════════════════════
   POST: add_post — crea un nuevo post (requiere login)
   Cuerpo: { title, content, category }
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'add_post' && $method === 'POST') { // Ruta: crear un post (requiere login)
    requireLogin(); // Solo usuarios autenticados

    $data     = postBody(); // Lee el cuerpo JSON
    $title    = sanitizeText($data['title']    ?? '', 100); // Título (máx 100)
    $content  = sanitizeText($data['content']  ?? '', 5000); // Contenido (máx 5000)
    $category = sanitizeText($data['category'] ?? 'fps', 50); // Categoría (máx 50)
    $userId   = currentUserId(); // Autor = usuario actual

    $allowedCategories = ['fps', 'hardware', 'estrategia', 'moba', 'noticias', 'general']; // Categorías válidas
    if (!in_array($category, $allowedCategories, true)) { // Si la categoría no es válida...
        $category = 'general'; // ...usa 'general' por defecto
    }

    if (mb_strlen($title) < 5)   jsonError('El título debe tener al menos 5 caracteres'); // Valida título
    if (mb_strlen($content) < 10) jsonError('El contenido debe tener al menos 10 caracteres'); // Valida contenido

    $approved = isAdmin() ? 1 : 0; // Los posts de admin se aprueban automáticamente

    $stmt = $conn->prepare( // Inserción preparada
        "INSERT INTO posts (title, content, category, author_id, approved) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('sssii', $title, $content, $category, $userId, $approved); // 3 cadenas, 2 enteros
    $ok = $stmt->execute(); // Ejecuta la inserción
    $id = $stmt->insert_id; // ID del nuevo post
    $stmt->close(); // Libera la sentencia

    if (!$ok) jsonError('No se pudo crear el post', 500); // Error si falla

    jsonOut([ // Respuesta de éxito
        'success'  => true,
        'id'       => $id, // ID del post creado
        'approved' => (bool) $approved, // ¿Quedó aprobado?
        'message'  => $approved ? 'Post publicado correctamente' : 'Post enviado — pendiente de moderación', // Mensaje según estado
    ]);
}

/* ═══════════════════════════════════════════════════════════════════
   POST: like_post — suma un "me gusta" a un post
   Cuerpo: { post_id }
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'like_post' && $method === 'POST') { // Ruta: dar "me gusta" a un post
    $data   = postBody(); // Lee el cuerpo JSON
    $postId = (int) ($data['post_id'] ?? 0); // ID del post
    if ($postId < 1) jsonError('ID de post no válido'); // Valida el ID

    $stmt = $conn->prepare("UPDATE posts SET likes = likes + 1 WHERE id = ? AND approved = 1"); // Incrementa likes
    $stmt->bind_param('i', $postId); // Enlaza el ID
    $ok = $stmt->execute(); // Ejecuta
    $affected = $stmt->affected_rows; // Filas afectadas (0 si no existe/aprobado)
    $stmt->close(); // Libera la sentencia

    if ($affected < 1) jsonError('Post no encontrado o no aprobado', 404); // 404 si no se actualizó nada

    $newLikes = (int) $conn->query("SELECT likes FROM posts WHERE id = {$postId}")->fetch_assoc()['likes']; // Nuevo total de likes
    jsonOut(['success' => true, 'likes' => $newLikes]); // Devuelve el nuevo total
}

/* ═══════════════════════════════════════════════════════════════════
   GET: get_all_posts — todos los posts incluyendo pendientes (solo admin)
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'get_all_posts' && $method === 'GET') { // Ruta: todos los posts incl. pendientes (admin)
    requireAdmin(); // Solo administradores

    $page    = max(1, (int) ($_GET['page'] ?? 1)); // Página (mínimo 1)
    $perPage = min(50, max(10, (int) ($_GET['per_page'] ?? 25))); // Entre 10 y 50 por página

    $sql    = "SELECT p.*, u.username, u.nombre, u.email
               FROM posts p JOIN usuarios u ON p.author_id = u.id
               ORDER BY p.approved ASC, p.created_at DESC"; // Pendientes primero, luego por fecha
    $result = paginate($conn, $sql, '', [], $page, $perPage); // Ejecuta paginado
    jsonOut($result); // Devuelve el resultado
}

/* ═══════════════════════════════════════════════════════════════════
   POST: approve_post — alterna el estado de aprobación (solo admin)
   Cuerpo: { post_id, approved }
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'approve_post' && $method === 'POST') { // Ruta: aprobar/rechazar un post (admin)
    requireAdmin(); // Solo administradores

    $data     = postBody(); // Lee el cuerpo JSON
    $postId   = (int) ($data['post_id'] ?? 0); // ID del post
    $approved = (int) ($data['approved'] ?? 1); // Nuevo estado (0 o 1)

    if ($postId < 1) jsonError('ID de post no válido'); // Valida el ID
    if (!in_array($approved, [0, 1], true)) jsonError('Estado no válido'); // Solo se admite 0 o 1

    $stmt = $conn->prepare("UPDATE posts SET approved = ? WHERE id = ?"); // Actualiza el estado
    $stmt->bind_param('ii', $approved, $postId); // Enlaza estado e ID
    $ok = $stmt->execute(); // Ejecuta
    $stmt->close(); // Libera la sentencia

    if (!$ok) jsonError('Error al actualizar el post', 500); // Error si falla
    jsonOut(['success' => true, 'approved' => (bool) $approved]); // Devuelve el nuevo estado
}

/* ═══════════════════════════════════════════════════════════════════
   POST: delete_post — elimina un post permanentemente (solo admin)
   Cuerpo: { post_id }
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'delete_post' && $method === 'POST') { // Ruta: borrar un post (admin)
    requireAdmin(); // Solo administradores

    $data   = postBody(); // Lee el cuerpo JSON
    $postId = (int) ($data['post_id'] ?? 0); // ID del post
    if ($postId < 1) jsonError('ID de post no válido'); // Valida el ID

    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?"); // Borra el post
    $stmt->bind_param('i', $postId); // Enlaza el ID
    $ok = $stmt->execute(); // Ejecuta
    $stmt->close(); // Libera la sentencia

    if (!$ok) jsonError('Error al eliminar el post', 500); // Error si falla
    jsonOut(['success' => true]); // Confirma el borrado
}

/* ═══════════════════════════════════════════════════════════════════
   GET: get_contacts — todos los mensajes de contacto (solo admin)
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'get_contacts' && $method === 'GET') { // Ruta: mensajes de contacto (admin)
    requireAdmin(); // Solo administradores

    $page     = max(1, (int) ($_GET['page'] ?? 1)); // Página (mínimo 1)
    $perPage  = min(50, max(5, (int) ($_GET['per_page'] ?? 20))); // Entre 5 y 50 por página
    $unread   = isset($_GET['unread']) ? (bool) $_GET['unread'] : false; // ¿Solo no leídos?

    $sql   = "SELECT * FROM contacts"; // Consulta base
    if ($unread) $sql .= " WHERE `read` = 0"; // Filtra no leídos si se pidió
    $sql  .= " ORDER BY `read` ASC, created_at DESC"; // No leídos primero, más recientes arriba

    $result = paginate($conn, $sql, '', [], $page, $perPage); // Ejecuta paginado
    jsonOut($result); // Devuelve los mensajes
}

/* ═══════════════════════════════════════════════════════════════════
   POST: mark_contact_read — marca un mensaje como leído (solo admin)
   Cuerpo: { contact_id }
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'mark_contact_read' && $method === 'POST') { // Ruta: marcar mensaje como leído (admin)
    requireAdmin(); // Solo administradores

    $data      = postBody(); // Lee el cuerpo JSON
    $contactId = (int) ($data['contact_id'] ?? 0); // ID del contacto
    if ($contactId < 1) jsonError('ID de contacto no válido'); // Valida el ID

    $stmt = $conn->prepare("UPDATE contacts SET `read` = 1 WHERE id = ?"); // Marca como leído
    $stmt->bind_param('i', $contactId); // Enlaza el ID
    $ok = $stmt->execute(); // Ejecuta
    $stmt->close(); // Libera la sentencia

    if (!$ok) jsonError('Error al actualizar el contacto', 500); // Error si falla
    jsonOut(['success' => true]); // Confirma la operación
}

/* ═══════════════════════════════════════════════════════════════════
   POST: delete_contact — elimina un mensaje de contacto (solo admin)
   Cuerpo: { contact_id }
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'delete_contact' && $method === 'POST') { // Ruta: borrar mensaje de contacto (admin)
    requireAdmin(); // Solo administradores

    $data      = postBody(); // Lee el cuerpo JSON
    $contactId = (int) ($data['contact_id'] ?? 0); // ID del contacto
    if ($contactId < 1) jsonError('ID de contacto no válido'); // Valida el ID

    $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?"); // Borra el mensaje
    $stmt->bind_param('i', $contactId); // Enlaza el ID
    $ok = $stmt->execute(); // Ejecuta
    $stmt->close(); // Libera la sentencia

    if (!$ok) jsonError('Error al eliminar el contacto', 500); // Error si falla
    jsonOut(['success' => true]); // Confirma el borrado
}

/* ═══════════════════════════════════════════════════════════════════
   GET: get_admin_stats — estadísticas detalladas para el panel admin
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'get_admin_stats' && $method === 'GET') { // Ruta: métricas del panel (admin)
    requireAdmin(); // Solo administradores

    $stats = [ // Métricas del panel de administración
        'users_total'    => (int) $conn->query("SELECT COUNT(*) as c FROM usuarios")->fetch_assoc()['c'], // Total de usuarios
        'posts_approved' => (int) $conn->query("SELECT COUNT(*) as c FROM posts WHERE approved=1")->fetch_assoc()['c'], // Posts aprobados
        'posts_pending'  => (int) $conn->query("SELECT COUNT(*) as c FROM posts WHERE approved=0")->fetch_assoc()['c'], // Posts pendientes
        'contacts_unread'=> (int) $conn->query("SELECT COUNT(*) as c FROM contacts WHERE `read`=0")->fetch_assoc()['c'], // Mensajes sin leer
        'total_likes'    => (int) ($conn->query("SELECT COALESCE(SUM(likes),0) as s FROM posts")->fetch_assoc()['s'] ?? 0), // Suma de todos los likes
        'admins'         => (int) $conn->query("SELECT COUNT(*) as c FROM usuarios WHERE role='admin'")->fetch_assoc()['c'], // Nº de administradores
        'members'        => (int) $conn->query("SELECT COUNT(*) as c FROM usuarios WHERE role='member'")->fetch_assoc()['c'], // Nº de miembros
    ];

    $catStats = $conn->query( // Estadísticas por categoría
        "SELECT category, COUNT(*) as cnt, COALESCE(SUM(likes),0) as likes
         FROM posts WHERE approved=1 GROUP BY category ORDER BY cnt DESC"
    );
    $stats['categories'] = $catStats->fetch_all(MYSQLI_ASSOC); // Guarda el desglose por categoría

    $topPosts = $conn->query( // Los 5 posts con más likes
        "SELECT p.id, p.title, p.likes, p.category, u.username
         FROM posts p JOIN usuarios u ON p.author_id=u.id
         WHERE p.approved=1 ORDER BY p.likes DESC LIMIT 5"
    );
    $stats['top_posts'] = $topPosts->fetch_all(MYSQLI_ASSOC); // Guarda el top 5

    jsonOut($stats); // Devuelve todas las métricas
}

/* ═══════════════════════════════════════════════════════════════════
   POST: update_user_role — cambia el rol de un usuario (solo admin)
   Cuerpo: { user_id, role }
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'update_user_role' && $method === 'POST') { // Ruta: cambiar rol de usuario (admin)
    requireAdmin(); // Solo administradores

    $data   = postBody(); // Lee el cuerpo JSON
    $userId = (int) ($data['user_id'] ?? 0); // ID del usuario objetivo
    $role   = sanitizeText($data['role'] ?? '', 20); // Nuevo rol

    if ($userId < 1)           jsonError('ID de usuario no válido'); // Valida el ID
    if ($userId === currentUserId()) jsonError('No puedes cambiar tu propio rol'); // No puede auto-modificarse
    if (!in_array($role, ['admin', 'member'], true)) jsonError('Rol no válido'); // Solo admin/member

    $stmt = $conn->prepare("UPDATE usuarios SET role = ? WHERE id = ?"); // Actualiza el rol
    $stmt->bind_param('si', $role, $userId); // Enlaza rol e ID
    $ok = $stmt->execute(); // Ejecuta
    $stmt->close(); // Libera la sentencia

    if (!$ok) jsonError('Error al actualizar el rol', 500); // Error si falla
    jsonOut(['success' => true, 'role' => $role]); // Devuelve el nuevo rol
}

/* ═══════════════════════════════════════════════════════════════════
   POST: delete_user — elimina una cuenta de usuario (solo admin)
   Cuerpo: { user_id }
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'delete_user' && $method === 'POST') { // Ruta: borrar una cuenta de usuario (admin)
    requireAdmin(); // Solo administradores

    $data   = postBody(); // Lee el cuerpo JSON
    $userId = (int) ($data['user_id'] ?? 0); // ID del usuario objetivo

    if ($userId < 1)           jsonError('ID de usuario no válido'); // Valida el ID
    if ($userId === currentUserId()) jsonError('No puedes eliminar tu propia cuenta'); // No puede auto-eliminarse

    $targetRole = $conn->query("SELECT role FROM usuarios WHERE id={$userId}")->fetch_assoc()['role'] ?? ''; // Rol del objetivo
    if ($targetRole === 'admin') jsonError('No puedes eliminar a otro administrador'); // No se borra a otro admin

    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?"); // Borra el usuario
    $stmt->bind_param('i', $userId); // Enlaza el ID
    $ok = $stmt->execute(); // Ejecuta
    $stmt->close(); // Libera la sentencia

    if (!$ok) jsonError('Error al eliminar el usuario', 500); // Error si falla
    jsonOut(['success' => true]); // Confirma el borrado
}

/* ═══════════════════════════════════════════════════════════════════
   GET: backup_database — descarga el volcado SQL completo (solo admin)
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'backup_database' && $method === 'GET') { // Ruta: descargar dump SQL (admin)
    requireAdmin(); // Solo administradores

    @ini_set('zlib.output_compression', 'Off'); // Desactiva la compresión (rompe descargas binarias)
    if (function_exists('apache_setenv')) @apache_setenv('no-gzip', '1'); // Refuerza: sin gzip en Apache
    while (ob_get_level()) ob_end_clean(); // Vacía cualquier buffer de salida pendiente

    $date     = date('Y-m-d_H-i-s'); // Marca de tiempo para el nombre del archivo
    $filename = "nexus_backup_{$date}.sql"; // Nombre del archivo SQL

    header('Content-Type: application/octet-stream'); // Tipo binario (forzar descarga)
    header('Content-Disposition: attachment; filename="' . $filename . '"'); // Nombre de descarga
    header_remove('Content-Encoding'); // Asegura que no se anuncie compresión
    header('Cache-Control: no-store, no-cache'); // Sin caché
    header('Pragma: no-cache'); // Compatibilidad con navegadores antiguos

    $out  = "-- ================================================\n"; // Cabecera del dump
    $out .= "-- NEXUS Database Backup\n"; // Título
    $out .= "-- Generated: " . date('Y-m-d H:i:s') . "\n"; // Fecha de generación
    $out .= "-- Database: usuarios_db\n"; // Nombre de la BD
    $out .= "-- ================================================\n\n"; // Fin de cabecera
    $out .= "SET NAMES utf8mb4;\n"; // Fija la codificación
    $out .= "SET FOREIGN_KEY_CHECKS = 0;\n\n"; // Desactiva claves foráneas durante la importación

    $tables = $conn->query("SHOW TABLES")->fetch_all(MYSQLI_NUM); // Lista de tablas de la BD
    foreach ($tables as [$table]) { // Recorre cada tabla
        $createRow = $conn->query("SHOW CREATE TABLE `{$table}`")->fetch_assoc(); // Obtiene su CREATE TABLE
        $createSql = $createRow['Create Table'] ?? ''; // SQL de creación

        $out .= "-- ----------------------------\n"; // Separador
        $out .= "-- Table: `{$table}`\n"; // Nombre de la tabla
        $out .= "-- ----------------------------\n"; // Separador
        $out .= "DROP TABLE IF EXISTS `{$table}`;\n"; // Borra la tabla si existe
        $out .= $createSql . ";\n\n"; // Añade su estructura

        $rows = $conn->query("SELECT * FROM `{$table}`"); // Lee todas las filas
        if ($rows && $rows->num_rows > 0) { // Si la tabla tiene datos...
            $fields   = array_column($rows->fetch_fields(), 'name'); // Nombres de las columnas
            $colList  = '`' . implode('`, `', $fields) . '`'; // Lista de columnas para el INSERT
            $rows     = $conn->query("SELECT * FROM `{$table}`"); // Relee las filas (el cursor se consumió)
            $inserts  = []; // Acumulador de filas en formato SQL
            while ($row = $rows->fetch_row()) { // Recorre cada fila
                $vals = array_map( // Convierte cada valor a SQL seguro
                    fn($v) => $v === null ? 'NULL' : "'" . $conn->real_escape_string((string)$v) . "'", // NULL o cadena escapada
                    $row
                );
                $inserts[] = '(' . implode(', ', $vals) . ')'; // Añade la fila como (v1, v2, ...)
            }
            $out .= "INSERT INTO `{$table}` ({$colList}) VALUES\n" . implode(",\n", $inserts) . ";\n\n"; // INSERT con todas las filas
        }
    }

    $out .= "SET FOREIGN_KEY_CHECKS = 1;\n"; // Reactiva las claves foráneas
    echo $out; // Envía el dump al navegador
    exit; // Termina la ejecución
}

/* ═══════════════════════════════════════════════════════════════════
   GET: backup_files — descarga un ZIP con todos los archivos web (admin)
   Incluye: gaming-foro-v2/ (PHP/CSS/JS/bat) + ip.php de la raíz de htdocs
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'backup_files' && $method === 'GET') { // Ruta: descargar ZIP de la web (admin)
    requireAdmin(); // Solo administradores

    @ini_set('zlib.output_compression', 'Off'); // Desactiva la compresión (rompe descargas binarias)
    if (function_exists('apache_setenv')) @apache_setenv('no-gzip', '1'); // Refuerza: sin gzip en Apache

    $date    = date('Y-m-d_H-i-s'); // Marca de tiempo
    $zipName = "nexus_web_completa_{$date}.zip"; // Nombre del ZIP
    $tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipName; // Ruta temporal del ZIP

    $projectDir = realpath(__DIR__); // Carpeta del proyecto
    $htdocsDir  = realpath(dirname(__DIR__)); // Carpeta padre (htdocs)

    /* ── Recoge TODOS los archivos de la carpeta del proyecto ── */
    $files = []; // Mapa 'ruta/en/zip' => 'ruta/real'
    $iter = new RecursiveIteratorIterator( // Recorre recursivamente la carpeta del proyecto
        new RecursiveDirectoryIterator($projectDir, RecursiveDirectoryIterator::SKIP_DOTS), // Ignora . y ..
        RecursiveIteratorIterator::LEAVES_ONLY // Solo archivos (no carpetas)
    );
    foreach ($iter as $file) { // Recorre cada archivo
        if (!$file->isFile()) continue; // Salta si no es un archivo
        $realPath = $file->getRealPath(); // Ruta real del archivo

        /* Salta cualquier ZIP de backup generado previamente en la carpeta */
        $basename = basename($realPath); // Nombre del archivo
        if (preg_match('/^nexus_(web_completa|files|backup)_/', $basename)) continue; // Ignora backups previos

        /* Lo guarda como gaming-foro-v2/<ruta-relativa> para que el ZIP tenga una raíz limpia */
        $rel = ltrim(str_replace('\\', '/', substr($realPath, strlen($projectDir))), '/'); // Ruta relativa
        $files['gaming-foro-v2/' . $rel] = $realPath; // Lo añade al mapa
    }

    /* ── ip.php en la raíz de htdocs (la página de IP/QR usada en clase) ── */
    $ipPhp = $htdocsDir . DIRECTORY_SEPARATOR . 'ip.php'; // Ruta a ip.php
    if (file_exists($ipPhp)) $files['ip.php'] = $ipPhp; // Si existe, lo añade

    /* ── Genera el ZIP (ZipArchive si está; si no, PHP puro) ── */
    if (!nexus_make_zip($files, [], $tmpFile)) { // Si no se pudo crear...
        jsonError('No se pudo crear el archivo ZIP', 500); // ...error
    }

    while (ob_get_level()) ob_end_clean(); // Vacía buffers antes de enviar el binario

    header('Content-Type: application/zip'); // Tipo ZIP
    header('Content-Disposition: attachment; filename="' . $zipName . '"'); // Nombre de descarga
    header('Content-Length: ' . filesize($tmpFile)); // Tamaño exacto (clave para que el navegador no aborte)
    header('Cache-Control: no-store'); // Sin caché
    header_remove('Content-Encoding'); // Sin compresión anunciada

    readfile($tmpFile); // Envía el ZIP al navegador
    unlink($tmpFile); // Borra el archivo temporal
    exit; // Termina la ejecución
}

/* ═══════════════════════════════════════════════════════════════════
   GET: backup_full — un único ZIP con TODOS los archivos web + dump SQL
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'backup_full' && $method === 'GET') { // Ruta: ZIP de la web + dump SQL (admin)
    requireAdmin(); // Solo administradores

    @ini_set('zlib.output_compression', 'Off'); // Desactiva la compresión (rompe descargas binarias)
    if (function_exists('apache_setenv')) @apache_setenv('no-gzip', '1'); // Refuerza: sin gzip en Apache

    $date    = date('Y-m-d_H-i-s'); // Marca de tiempo
    $zipName = "nexus_backup_completo_{$date}.zip"; // Nombre del ZIP
    $tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipName; // Ruta temporal del ZIP

    $projectDir = realpath(__DIR__); // Carpeta del proyecto
    $htdocsDir  = realpath(dirname(__DIR__)); // Carpeta padre (htdocs)

    /* ── Archivos web ── */
    $files = []; // Mapa 'ruta/en/zip' => 'ruta/real'
    $iter = new RecursiveIteratorIterator( // Recorre recursivamente la carpeta del proyecto
        new RecursiveDirectoryIterator($projectDir, RecursiveDirectoryIterator::SKIP_DOTS), // Ignora . y ..
        RecursiveIteratorIterator::LEAVES_ONLY // Solo archivos
    );
    foreach ($iter as $file) { // Recorre cada archivo
        if (!$file->isFile()) continue; // Salta si no es un archivo
        $realPath = $file->getRealPath(); // Ruta real
        $basename = basename($realPath); // Nombre del archivo
        if (preg_match('/^nexus_(web_completa|files|backup)_/', $basename)) continue; // Ignora backups previos
        $rel = ltrim(str_replace('\\', '/', substr($realPath, strlen($projectDir))), '/'); // Ruta relativa
        $files['gaming-foro-v2/' . $rel] = $realPath; // Lo añade al mapa
    }

    $ipPhp = $htdocsDir . DIRECTORY_SEPARATOR . 'ip.php'; // Ruta a ip.php
    if (file_exists($ipPhp)) $files['ip.php'] = $ipPhp; // Si existe, lo añade

    /* ── Dump SQL (incrustado dentro del ZIP) ── */
    $sqlOut  = "-- ================================================\n"; // Cabecera del dump
    $sqlOut .= "-- NEXUS Database Backup\n"; // Título
    $sqlOut .= "-- Generated: " . date('Y-m-d H:i:s') . "\n"; // Fecha de generación
    $sqlOut .= "-- Database: usuarios_db\n"; // Nombre de la BD
    $sqlOut .= "-- ================================================\n\n"; // Fin de cabecera
    $sqlOut .= "SET NAMES utf8mb4;\n"; // Fija la codificación
    $sqlOut .= "SET FOREIGN_KEY_CHECKS = 0;\n\n"; // Desactiva claves foráneas

    $tables = $conn->query("SHOW TABLES")->fetch_all(MYSQLI_NUM); // Lista de tablas
    foreach ($tables as [$table]) { // Recorre cada tabla
        $createRow = $conn->query("SHOW CREATE TABLE `{$table}`")->fetch_assoc(); // Su CREATE TABLE
        $createSql = $createRow['Create Table'] ?? ''; // SQL de creación
        $sqlOut .= "-- ----------------------------\n"; // Separador
        $sqlOut .= "-- Table: `{$table}`\n"; // Nombre de la tabla
        $sqlOut .= "-- ----------------------------\n"; // Separador
        $sqlOut .= "DROP TABLE IF EXISTS `{$table}`;\n"; // Borra si existe
        $sqlOut .= $createSql . ";\n\n"; // Añade la estructura
        $rows = $conn->query("SELECT * FROM `{$table}`"); // Lee las filas
        if ($rows && $rows->num_rows > 0) { // Si hay datos...
            $fields  = array_column($rows->fetch_fields(), 'name'); // Nombres de columnas
            $colList = '`' . implode('`, `', $fields) . '`'; // Lista de columnas
            $rows    = $conn->query("SELECT * FROM `{$table}`"); // Relee las filas
            $inserts = []; // Acumulador de filas
            while ($row = $rows->fetch_row()) { // Recorre cada fila
                $vals = array_map( // Convierte cada valor a SQL seguro
                    fn($v) => $v === null ? 'NULL' : "'" . $conn->real_escape_string((string)$v) . "'", // NULL o cadena escapada
                    $row
                );
                $inserts[] = '(' . implode(', ', $vals) . ')'; // Fila como (v1, v2, ...)
            }
            $sqlOut .= "INSERT INTO `{$table}` ({$colList}) VALUES\n" . implode(",\n", $inserts) . ";\n\n"; // INSERT con todas las filas
        }
    }
    $sqlOut .= "SET FOREIGN_KEY_CHECKS = 1;\n"; // Reactiva las claves foráneas

    /* ── Genera el ZIP con los archivos + el dump SQL incrustado ── */
    $strings = ["nexus_database_{$date}.sql" => $sqlOut]; // El dump SQL como archivo dentro del ZIP
    if (!nexus_make_zip($files, $strings, $tmpFile)) { // ZipArchive si está; si no, PHP puro
        jsonError('No se pudo crear el archivo ZIP', 500); // Error si falla
    }

    while (ob_get_level()) ob_end_clean(); // Vacía buffers antes de enviar el binario

    header('Content-Type: application/zip'); // Tipo ZIP
    header('Content-Disposition: attachment; filename="' . $zipName . '"'); // Nombre de descarga
    header('Content-Length: ' . filesize($tmpFile)); // Tamaño exacto
    header('Cache-Control: no-store'); // Sin caché
    header_remove('Content-Encoding'); // Sin compresión anunciada

    readfile($tmpFile); // Envía el ZIP
    unlink($tmpFile); // Borra el temporal
    exit; // Termina la ejecución
}

/* ═══════════════════════════════════════════════════════════════════
   POST: reset_site — borra datos con protección por PIN (solo admin)
   Cuerpo: { pin, target }
   Objetivos: posts | users | contacts | all | database
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'reset_site' && $method === 'POST') { // Ruta: borrado de datos con PIN (admin)
    requireAdmin(); // Solo administradores

    $data   = postBody(); // Lee el cuerpo JSON
    $pin    = (string) ($data['pin'] ?? ''); // PIN de seguridad introducido
    $target = sanitizeText($data['target'] ?? '', 20); // Qué se quiere borrar

    if ($pin !== ADMIN_PIN) jsonError('PIN incorrecto', 403); // El PIN debe coincidir (desde config.php)

    $allowed = ['posts', 'users', 'contacts', 'all', 'database']; // Objetivos permitidos
    if (!in_array($target, $allowed, true)) jsonError('Objetivo no válido'); // Valida el objetivo

    $selfId = currentUserId(); // ID del admin actual (no usado aquí pero disponible)

    switch ($target) { // Según el objetivo elegido...
        case 'posts': // Borrar todos los posts
            $conn->query("DELETE FROM posts"); // Ejecuta el borrado
            $msg = 'Todos los posts han sido eliminados.'; // Mensaje
            break; // Fin de este caso del switch
        case 'users': // Borrar usuarios no-admin
            $conn->query("DELETE FROM usuarios WHERE role != 'admin'"); // Borra (y sus posts por CASCADE)
            $msg = 'Usuarios no-admin eliminados (y sus posts por CASCADE).'; // Mensaje
            break; // Fin de este caso del switch
        case 'contacts': // Borrar mensajes de contacto
            $conn->query("DELETE FROM contacts"); // Ejecuta el borrado
            $msg = 'Todos los mensajes de contacto eliminados.'; // Mensaje
            break; // Fin de este caso del switch
        case 'all': // Resetear el foro entero (menos el admin)
            $conn->query("DELETE FROM posts"); // Borra posts
            $conn->query("DELETE FROM usuarios WHERE role != 'admin'"); // Borra usuarios no-admin
            $conn->query("DELETE FROM contacts"); // Borra contactos
            $msg = 'Foro reseteado: posts, usuarios y mensajes eliminados. Solo queda la cuenta admin.'; // Mensaje
            break; // Fin de este caso del switch
        case 'database': // Eliminar la base de datos completa
            $conn->query("DROP DATABASE IF EXISTS `usuarios_db`"); // Borra la BD entera
            $msg = 'Base de datos eliminada completamente. El sitio necesitará recarga para recrearla.'; // Mensaje
            break; // Fin de este caso del switch
        default: // Objetivo no reconocido (por seguridad)
            jsonError('Objetivo no reconocido'); // Error
    }

    jsonOut(['success' => true, 'message' => $msg]); // Devuelve el resultado
}

/* ═══════════════════════════════════════════════════════════════════
   POST: restore_files — restaura la web desde un ZIP subido (admin + PIN)
   Cuerpo: multipart/form-data  { zipfile: <file>, pin: "<PIN_DE_CONFIG>" }
   ═══════════════════════════════════════════════════════════════════ */
if ($action === 'restore_files' && $method === 'POST') { // Ruta: restaurar la web desde un ZIP (admin)
    requireAdmin(); // Solo administradores

    $pin = trim($_POST['pin'] ?? ''); // PIN de seguridad
    if ($pin !== ADMIN_PIN) jsonError('PIN incorrecto', 403); // El PIN debe coincidir (desde config.php)

    if (!isset($_FILES['zipfile']) || $_FILES['zipfile']['error'] !== UPLOAD_ERR_OK) { // Si no llegó un archivo válido...
        $uploadErr = $_FILES['zipfile']['error'] ?? -1; // Código de error de subida
        $msg = match((int)$uploadErr) { // Traduce el código a un mensaje
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño máximo permitido por PHP.', // Demasiado grande
            UPLOAD_ERR_NO_FILE  => 'No se recibió ningún archivo.', // No se envió
            default             => "Error de subida (código {$uploadErr}).", // Otro error
        };
        jsonError($msg); // Responde el error
    }

    $file = $_FILES['zipfile']; // Datos del archivo subido
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)); // Extensión del archivo
    if ($ext !== 'zip') jsonError('El archivo debe tener extensión .zip'); // Solo se acepta .zip

    /* Lee el ZIP subido (ZipArchive si está; si no, lector en PHP puro) */
    $entries = nexus_read_zip($file['tmp_name']); // Mapa nombre => contenido
    if ($entries === false) jsonError('No se pudo abrir el ZIP. Comprueba que no está corrupto.'); // Error al leer

    $projectDir = realpath(__DIR__); // Carpeta del proyecto
    $htdocsDir  = realpath(dirname(__DIR__)); // Carpeta padre (htdocs)
    $extracted  = 0; // Contador de archivos restaurados
    $skipped    = 0; // Contador de archivos omitidos

    foreach ($entries as $entry => $content) { // Recorre cada entrada del ZIP
        /* Salta las entradas que son carpetas */
        if (substr($entry, -1) === '/') continue; // Si termina en '/', es un directorio

        /* Normaliza las barras y bloquea el path traversal */
        $entry = str_replace('\\', '/', $entry); // Unifica separadores a '/'
        if (str_contains($entry, '..')) { $skipped++; continue; } // Bloquea rutas con '..' (seguridad)

        /* Mapea la ruta del ZIP → ruta real del sistema de archivos */
        if (str_starts_with($entry, 'gaming-foro-v2/')) { // Archivo del proyecto
            $rel  = substr($entry, strlen('gaming-foro-v2/')); // Ruta relativa dentro del proyecto
            if ($rel === '') { $skipped++; continue; } // Salta si queda vacío
            $dest = $projectDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel); // Ruta destino
        } elseif ($entry === 'ip.php') { // El ip.php de la raíz
            $dest = $htdocsDir . DIRECTORY_SEPARATOR . 'ip.php'; // Ruta destino en htdocs
        } else { // Cualquier otra ruta desconocida
            /* Solo se restauran rutas conocidas — el resto se ignora */
            $skipped++; // Cuenta como omitido
            continue; // Pasa a la siguiente
        }

        if ($content === false || $content === null) { $skipped++; continue; } // Salta si no se pudo leer

        /* Crea las carpetas intermedias si hacen falta */
        $destDir = dirname($dest); // Carpeta destino
        if (!is_dir($destDir)) mkdir($destDir, 0755, true); // La crea recursivamente si no existe

        if (file_put_contents($dest, $content) !== false) { // Escribe el archivo en disco
            $extracted++; // Éxito: suma al contador
        } else { // Si falló la escritura...
            $skipped++; // ...lo cuenta como omitido
        }
    }

    $msg = "Restauración completada: {$extracted} archivo" . ($extracted !== 1 ? 's' : '') . " restaurado" . ($extracted !== 1 ? 's' : '') . "."; // Mensaje con plural correcto
    if ($skipped > 0) $msg .= " ({$skipped} omitido" . ($skipped !== 1 ? 's' : '') . ")"; // Añade los omitidos si hay

    jsonOut(['success' => true, 'extracted' => $extracted, 'skipped' => $skipped, 'message' => $msg]); // Devuelve el resumen
}

/* ═══════════════════════════════════════════════════════════════════
   Fallback — acción desconocida
   ═══════════════════════════════════════════════════════════════════ */
jsonError("Acción '{$action}' no reconocida. Acciones disponibles: get_posts, get_post, get_users, get_user, get_user_posts, get_leaderboard, search, get_stats, add_post, like_post, get_all_posts, approve_post, delete_post, get_contacts, mark_contact_read, delete_contact, get_admin_stats, update_user_role, delete_user", 404); // Si ninguna acción coincidió, error 404
