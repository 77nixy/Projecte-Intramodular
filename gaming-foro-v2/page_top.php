<?php
/**
 * NEXUS//BOARD — Page top include
 * Bootstraps the application and prepares the current-user JSON
 * payload used by script.js to render the auth UI client-side.
 */
require_once __DIR__ . '/bootstrap.php';

/* Expose user data to JavaScript — never include the password hash */
$userForJs = null;
if ($currentUser) {
    $userForJs = [
        'id'            => (int)    $currentUser['id'],
        'nombre'        => (string) $currentUser['nombre'],
        'username'      => (string) $currentUser['username'],
        'email'         => (string) $currentUser['email'],
        'favorite_game' => (string) $currentUser['favorite_game'],
        'bio'           => (string) ($currentUser['bio'] ?? ''),
        'role'          => (string) $currentUser['role'],
    ];
}
$currentUserJson = $userForJs
    ? json_encode($userForJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP)
    : 'null';
