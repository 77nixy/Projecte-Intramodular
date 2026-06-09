<?php
/**
 * NEXUS — Plantilla de configuración (SIN credenciales reales)
 * ------------------------------------------------------------
 * Copia este archivo como `config.php` y rellena tus propios valores.
 * `config.php` está IGNORADO por git, así que tus credenciales nunca
 * se suben al repositorio. Esta plantilla sí se versiona como referencia.
 *
 *   Windows (PowerShell):  Copy-Item config.example.php config.php
 *   Linux / macOS:         cp config.example.php config.php
 */

/* ─── Conexión a la base de datos (valores por defecto de XAMPP) ─── */
define('DB_HOST', 'localhost');   // Servidor de MySQL
define('DB_USER', 'root');        // Usuario de MySQL
define('DB_PASS', '');            // Contraseña de MySQL (vacía en XAMPP por defecto)
define('DB_NAME', 'usuarios_db'); // Nombre de la base de datos

/* ─── Cuenta de administrador que se siembra en la primera visita ─── */
define('ADMIN_EMAIL',    'admin@example.com'); // Email del administrador
define('ADMIN_PASSWORD', 'CAMBIA_ESTA_CLAVE'); // Contraseña del admin (cámbiala)

/* ─── Contraseña de las cuentas demo sembradas ─── */
define('DEMO_PASSWORD', 'CAMBIA_ESTA_DEMO'); // Contraseña común de los usuarios de ejemplo

/* ─── PIN de la zona peligrosa y de restaurar backups ─── */
define('ADMIN_PIN', '000000'); // PIN de 6 dígitos (cámbialo por uno propio)
