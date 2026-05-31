NEXUS — Foro Gaming Competitivo
================================

REQUISITOS
----------
- XAMPP para Windows (Apache + MySQL)
- PHP 8.0 o superior (incluido en XAMPP)
- ZipArchive habilitado en PHP (viene activo por defecto en XAMPP)

INSTALACIÓN RÁPIDA
------------------
1. Copia la carpeta gaming-foro-v2/ dentro de:
   C:\xampp\htdocs\

2. Ejecuta ARRANCAR.bat como ADMINISTRADOR
   (hace clic derecho → "Ejecutar como administrador")
   Esto inicia Apache + MySQL y abre el cortafuegos.

3. Abre en el navegador:
   http://localhost/gaming-foro-v2/

   La base de datos y las tablas se crean AUTOMÁTICAMENTE
   en la primera visita. No hace falta tocar phpMyAdmin.

CREDENCIALES
------------
   Admin:
     Email:    admin@nexusboard.gg
     Password: Admin123!
     Username: nexusadmin
     PIN:      565656  (para zona peligrosa y restaurar)

   Usuarios demo (password: Demo1234!):
     ProSniper88, LunarGG, GalaxyBuilder, MidLaneKing,
     RifleQueen, FortBuilder, TacticalAna, HardwareGuru

ACCESO EN RED (clase / LAN)
----------------------------
   Ejecuta ARRANCAR.bat — muestra la IP del PC en pantalla.
   Comparte: http://<IP>/gaming-foro-v2/  con los demás.
   Todos deben estar en el mismo WiFi (sin aislamiento AP).

   Para acceso por internet (ngrok):
   Ejecuta iniciar-tunel.bat y comparte la URL que genera.

PANEL DE ADMINISTRACIÓN
------------------------
   http://localhost/gaming-foro-v2/admin.php

   Pestañas:
   - Resumen:      métricas generales del foro
   - Moderación:   aprobar/rechazar posts y gestionar mensajes
   - Usuarios:     buscar, filtrar, cambiar rol o eliminar usuarios
   - Backups:      descargar SQL, ZIP de la web, o backup completo;
                   también restaurar la web desde un ZIP previo
   - Zona peligrosa: borrar posts/usuarios/mensajes (requiere PIN)

ESTRUCTURA DE ARCHIVOS
-----------------------
   admin.php           Panel de administración
   api.php             API REST (todas las llamadas AJAX)
   bootstrap.php       Funciones compartidas (sesión, usuario, CSRF)
   db.php              Conexión MySQL + creación automática de tablas + seeds
   index.php           Página principal del foro
   login.php           Pantalla de inicio de sesión
   logout.php          Cierra la sesión y redirige
   register.php        Formulario de registro
   page_top.php        Cabecera PHP compartida (incluye bootstrap)
   process_login.php   Procesador del formulario de login
   process_register.php Procesador del formulario de registro
   process_contact.php Procesador del formulario de contacto
   styles.css          Todos los estilos (v=7)
   script.js           Todo el JavaScript del frontend (v=7)
   .htaccess           Compresión gzip, caché del navegador, seguridad
   ARRANCAR.bat        Inicia XAMPP, configura red y firewall
   iniciar-tunel.bat   Abre un túnel ngrok para acceso externo

   (ip.php se encuentra en C:\xampp\htdocs\ — muestra la IP con QR)

BASE DE DATOS: usuarios_db
---------------------------
   Tablas: usuarios, posts, contacts
   Se crean automáticamente al abrir el foro por primera vez.
   Para exportar: Admin → Backups → Descargar SQL
