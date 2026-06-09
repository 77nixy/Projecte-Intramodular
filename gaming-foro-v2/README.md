# NEXUS — Foro Gaming Competitivo

Foro web para una comunidad gaming, con feed de temas, sistema de likes, ranking
de miembros, panel de administración completo y backups descargables. Construido
con **PHP 8 + MySQL** sobre **XAMPP**, sin frameworks ni dependencias externas.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?logo=mysql&logoColor=white)
![Vanilla JS](https://img.shields.io/badge/JavaScript-Vanilla-F7DF1E?logo=javascript&logoColor=black)

---

## Características

- **Foro**: publicar temas, filtrar por categoría, buscar, dar likes.
- **Cuentas**: registro e inicio de sesión con contraseñas cifradas (bcrypt),
  sesiones persistentes y protección anti-fuerza-bruta.
- **Ranking de miembros** por likes totales.
- **Panel de administración** (`admin.php`):
  - **Resumen** — métricas del foro.
  - **Moderación** — aprobar/rechazar temas y gestionar mensajes de contacto.
  - **Usuarios** — buscar, cambiar rol o eliminar.
  - **Backups** — descargar la base de datos (SQL) o la web completa (ZIP),
    y restaurar la web desde un ZIP.
  - **Zona peligrosa** — borrados masivos protegidos por PIN.
- **Backups sin dependencias**: el generador de ZIP funciona aunque la extensión
  `zip` de PHP esté desactivada (ver `zip_helper.php`).
- **Interfaz profesional**: tipografía Inter, tema oscuro, sin librerías de CSS.

---

## Requisitos

- [XAMPP](https://www.apachefriends.org/) para Windows (Apache + MySQL/MariaDB)
- PHP 8.0 o superior (incluido en XAMPP)

---

## Instalación

1. Clona o copia este repositorio dentro de la carpeta de XAMPP:

   ```
   C:\xampp\htdocs\gaming-foro-v2\
   ```

2. Inicia **Apache** y **MySQL** desde el panel de XAMPP
   (o ejecuta `ARRANCAR.bat` como administrador).

3. Abre en el navegador:

   ```
   http://localhost/gaming-foro-v2/
   ```

   La base de datos `usuarios_db` y sus tablas (`usuarios`, `posts`, `contacts`)
   se crean **automáticamente** en la primera visita, junto con datos de ejemplo.

---

## Configuración (credenciales)

Las credenciales **no** están en el repositorio. Antes de arrancar, crea tu
archivo de configuración local a partir de la plantilla:

```
# Windows (PowerShell)
Copy-Item config.example.php config.php

# Linux / macOS
cp config.example.php config.php
```

Luego edita `config.php` y define tus valores:

| Constante        | Para qué sirve                                   |
|------------------|--------------------------------------------------|
| `DB_HOST/USER/PASS/NAME` | Conexión a MySQL                          |
| `ADMIN_EMAIL`    | Email del administrador que se siembra           |
| `ADMIN_PASSWORD` | Contraseña del administrador                      |
| `DEMO_PASSWORD`  | Contraseña común de las cuentas demo             |
| `ADMIN_PIN`      | PIN de la zona peligrosa y de restaurar backups  |

> `config.php` está en `.gitignore`, así que tus credenciales nunca se suben.
> La cuenta de administrador y los datos de ejemplo se siembran en la primera
> visita usando los valores de tu `config.php`.

---

## Estructura del proyecto

| Archivo                | Descripción                                              |
|------------------------|---------------------------------------------------------|
| `index.php`            | Página principal del foro                                |
| `login.php` / `register.php` | Pantallas de acceso y registro                    |
| `admin.php`            | Panel de administración                                  |
| `api.php`              | API REST: todas las llamadas AJAX/fetch (respuestas JSON)|
| `zip_helper.php`       | Generador/lector de ZIP (funciona sin la extensión `zip`)|
| `config.example.php`   | Plantilla de configuración (cópiala a `config.php`)      |
| `db.php`               | Conexión MySQL + creación automática de tablas y seeds   |
| `bootstrap.php`        | Funciones compartidas: sesión, usuario actual, CSRF      |
| `page_top.php`         | Cabecera PHP común (arranca la app)                      |
| `process_login.php`    | Procesa el formulario de login                           |
| `process_register.php` | Procesa el formulario de registro                       |
| `process_contact.php`  | Procesa el formulario de contacto                       |
| `logout.php`           | Cierra la sesión y redirige                              |
| `styles.css`           | Todos los estilos (tema oscuro, Inter)                  |
| `script.js`            | Todo el JavaScript del frontend                         |
| `.htaccess`            | Compresión, caché del navegador y cabeceras de seguridad |
| `ARRANCAR.bat`         | Inicia XAMPP y configura red/firewall                   |
| `iniciar-tunel.bat`    | Abre un túnel ngrok para acceso externo                 |

---

## Base de datos

**`usuarios_db`** — se crea sola al abrir el foro por primera vez.

- `usuarios` — cuentas (nombre, nick, email, hash de contraseña, rol, bio…)
- `posts` — temas del foro (título, contenido, categoría, autor, likes…)
- `contacts` — mensajes del formulario de contacto

Exportable desde **Admin → Backups → Descargar SQL**.

---

## Notas técnicas

- Todo el código está comentado línea por línea en español.
- El cache-busting de CSS/JS se controla con el parámetro `?v=` en los `<link>`/`<script>`.
- Los backups y archivos generados quedan fuera del control de versiones (ver `.gitignore`).
