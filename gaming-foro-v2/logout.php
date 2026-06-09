<?php
/**
 * NEXUS — Manejador de cierre de sesión (logout)
 * Destruye la sesión de forma limpia y redirige al login.
 */

session_start(); // Reanuda la sesión actual para poder destruirla

/* Vacía todos los datos almacenados en la sesión */
$_SESSION = []; // Sustituye el array de sesión por uno vacío

// Si la sesión usa cookie, hay que borrar también la cookie del navegador
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params(); // Recupera la configuración de la cookie de sesión
    setcookie(
        session_name(),        // Nombre de la cookie de sesión (normalmente PHPSESSID)
        '',                    // Valor vacío: la deja sin contenido
        time() - 42000,        // Fecha de expiración en el pasado: fuerza su borrado
        $params['path'],       // Mismo path con el que se creó
        $params['domain'],     // Mismo dominio con el que se creó
        $params['secure'],     // Mismo flag secure (solo HTTPS) original
        $params['httponly']    // Mismo flag httponly (inaccesible desde JS) original
    );
}
session_destroy(); // Elimina el archivo de sesión del servidor

/* Arranca una sesión nueva solo para entregar el mensaje de despedida */
session_start(); // Nueva sesión limpia
$_SESSION['flash_success'] = 'Sesión cerrada correctamente. ¡Hasta pronto!'; // Mensaje flash que verá el usuario en el login

header('Location: login.php'); // Redirige a la página de inicio de sesión
exit; // Detiene la ejecución para que la redirección surta efecto
