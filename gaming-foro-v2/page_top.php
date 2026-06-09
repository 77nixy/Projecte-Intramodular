<?php
/**
 * NEXUS — Include de cabecera de página
 * Arranca la aplicación y prepara el JSON del usuario actual
 * que usa script.js para pintar la interfaz de sesión en el cliente.
 */
require_once __DIR__ . '/bootstrap.php'; // Carga la conexión, la sesión y $currentUser

/* Expone los datos del usuario a JavaScript — nunca se incluye el hash de la contraseña */
$userForJs = null; // Por defecto: no hay usuario (visitante anónimo)
if ($currentUser) { // Solo si hay una sesión iniciada
    $userForJs = [ // Construye un array seguro con los campos públicos
        'id'            => (int)    $currentUser['id'],            // ID numérico del usuario
        'nombre'        => (string) $currentUser['nombre'],        // Nombre real para el saludo
        'username'      => (string) $currentUser['username'],      // Nick público
        'email'         => (string) $currentUser['email'],         // Correo electrónico
        'favorite_game' => (string) $currentUser['favorite_game'], // Juego favorito declarado
        'bio'           => (string) ($currentUser['bio'] ?? ''),   // Biografía (vacía si no existe)
        'role'          => (string) $currentUser['role'],          // Rol: 'admin' o 'member'
    ];
}
$currentUserJson = $userForJs // Si hay datos de usuario...
    ? json_encode($userForJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) // ...los serializa a JSON con protección anti-XSS
    : 'null'; // Si no hay usuario, el literal JSON 'null'
