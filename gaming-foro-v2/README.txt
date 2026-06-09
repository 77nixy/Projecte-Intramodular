NEXUS — Foro Gaming Competitivo
================================

REQUISITOS
----------
- XAMPP para Windows (Apache + MySQL)
- PHP 8.0 o superior (incluido en XAMPP)
- Extensión ZipArchive de PHP (opcional): si está desactivada, los backups
  ZIP siguen funcionando mediante un generador en PHP puro (zip_helper.php)

INSTALACIÓN RÁPIDA
------------------
1. Copia la carpeta gaming-foro-v2/ dentro de:
   C:\xampp\htdocs\

2. Crea tu configuración local (credenciales):
   Copy-Item config.example.php config.php
   y edita config.php con tus valores.

3. Ejecuta ARRANCAR.bat como ADMINISTRADOR
   (hace clic derecho → "Ejecutar como administrador")
   Esto inicia Apache + MySQL y abre el cortafuegos.

4. Abre en el navegador:
   http://localhost/gaming-foro-v2/

   La base de datos y las tablas se crean AUTOMÁTICAMENTE
   en la primera visita. No hace falta tocar phpMyAdmin.

CONFIGURACIÓN (CREDENCIALES)
----------------------------
   Las credenciales NO están en el repositorio. Antes de arrancar:

     1. Copia la plantilla:
          Windows:  Copy-Item config.example.php config.php
          Linux:    cp config.example.php config.php

     2. Edita config.php y define tus valores:
          DB_HOST / DB_USER / DB_PASS / DB_NAME  → conexión a MySQL
          ADMIN_EMAIL / ADMIN_PASSWORD           → cuenta de administrador
          DEMO_PASSWORD                          → contraseña de las demos
          ADMIN_PIN                              → PIN de la zona peligrosa

   config.php está en .gitignore: tus credenciales nunca se suben.
   El admin y los usuarios demo se siembran en la primera visita usando
   esos valores. Usuarios demo sembrados:
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
   - Backups:      descargar la base de datos (SQL) o la web completa (ZIP);
                   también restaurar la web desde un ZIP previo
   - Zona peligrosa: borrar posts/usuarios/mensajes (requiere PIN)

ESTRUCTURA DE ARCHIVOS
-----------------------
   admin.php           Panel de administración
   api.php             API REST (todas las llamadas AJAX)
   zip_helper.php      Generador/lector de ZIP (funciona sin la extensión zip)
   bootstrap.php       Funciones compartidas (sesión, usuario, CSRF)
   config.example.php  Plantilla de configuración (cópiala a config.php)
   db.php              Conexión MySQL + creación automática de tablas + seeds
   index.php           Página principal del foro
   login.php           Pantalla de inicio de sesión
   logout.php          Cierra la sesión y redirige
   register.php        Formulario de registro
   page_top.php        Cabecera PHP compartida (incluye bootstrap)
   process_login.php   Procesador del formulario de login
   process_register.php Procesador del formulario de registro
   process_contact.php Procesador del formulario de contacto
   styles.css          Todos los estilos (tema oscuro, tipografía Inter)
   script.js           Todo el JavaScript del frontend
   .htaccess           Compresión gzip, caché del navegador, seguridad
   ARRANCAR.bat        Inicia XAMPP, configura red y firewall
   iniciar-tunel.bat   Abre un túnel ngrok para acceso externo

   (ip.php se encuentra en C:\xampp\htdocs\ — muestra la IP con QR)

BASE DE DATOS: usuarios_db
---------------------------
   Tablas: usuarios, posts, contacts
   Se crean automáticamente al abrir el foro por primera vez.
   Para exportar: Admin → Backups → Descargar SQL
