<?php
/**
 * NEXUS//BOARD — Logout handler
 * Destroys the session cleanly and redirects to login.
 */

session_start();

/* Destroy all session data */
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}
session_destroy();

/* Start fresh to deliver farewell flash message */
session_start();
$_SESSION['flash_success'] = 'Sesión cerrada correctamente. ¡Hasta pronto!';

header('Location: login.php');
exit;
