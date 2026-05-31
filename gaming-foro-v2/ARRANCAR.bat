@echo off
setlocal enabledelayedexpansion
title NEXUS — Foro Gaming
color 0A
cls
echo.
echo  ============================================
echo   NEXUS — Foro Gaming Competitivo
echo  ============================================
echo.

REM ── Verificar permisos de administrador ──────────────────────────
net session >nul 2>&1
if errorlevel 1 (
    echo  [!] Se necesitan permisos de administrador.
    echo  [!] Cierra esta ventana, haz clic derecho en ARRANCAR.bat
    echo  [!] y selecciona "Ejecutar como administrador".
    echo.
    pause
    exit /b 1
)

REM ── Comprobar si Apache esta corriendo ───────────────────────────
sc query Apache2.4 2>nul | findstr /C:"RUNNING" >nul 2>&1
if not errorlevel 1 goto CHECK_MYSQL
tasklist /FI "IMAGENAME eq httpd.exe" 2>nul | findstr /I "httpd" >nul 2>&1
if not errorlevel 1 goto CHECK_MYSQL

echo  [..] Iniciando Apache...
net start Apache2.4 >nul 2>&1
if errorlevel 1 start "" /B "C:\xampp\apache\bin\httpd.exe"
timeout /t 3 /nobreak >nul

:CHECK_MYSQL
REM ── Comprobar si MySQL esta corriendo ────────────────────────────
sc query MySQL 2>nul | findstr /C:"RUNNING" >nul 2>&1
if not errorlevel 1 goto GET_IP
tasklist /FI "IMAGENAME eq mysqld.exe" 2>nul | findstr /I "mysqld" >nul 2>&1
if not errorlevel 1 goto GET_IP

echo  [..] Iniciando MySQL...
net start MySQL >nul 2>&1
if errorlevel 1 (
    if exist "C:\xampp\mysql\data\mysql.pid" del /F "C:\xampp\mysql\data\mysql.pid" >nul 2>&1
    start "" /B "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini"
    timeout /t 4 /nobreak >nul
)

:GET_IP
REM ── Poner TODOS los adaptadores activos en red Privada ───────────
powershell -Command "Get-NetAdapter | Where-Object {$_.Status -eq 'Up' -and $_.InterfaceDescription -notlike '*VMware*' -and $_.InterfaceDescription -notlike '*Loopback*'} | ForEach-Object { try { Set-NetConnectionProfile -InterfaceAlias $_.Name -NetworkCategory Private -ErrorAction SilentlyContinue } catch {} }" >nul 2>&1

REM ── Abrir puerto 80 en el Firewall de Windows (todos los perfiles) ──
REM    Se recrea cada vez para garantizar que funciona en cualquier WiFi.
echo  [..] Configurando firewall...
netsh advfirewall firewall delete rule name="NEXUS-HTTP-80"    >nul 2>&1
netsh advfirewall firewall delete rule name="NEXUS-Apache-EXE" >nul 2>&1
netsh advfirewall firewall add rule name="NEXUS-HTTP-80"    dir=in action=allow protocol=TCP localport=80 profile=any enable=yes >nul 2>&1
netsh advfirewall firewall add rule name="NEXUS-Apache-EXE" dir=in action=allow program="C:\xampp\apache\bin\httpd.exe" profile=any enable=yes >nul 2>&1
echo  [OK] Firewall configurado (puerto 80 abierto)

REM ── Detectar IP con prioridad: hotspot(192.168.43.x) > 192.168.x > 10.x ──
set "MYIP=localhost"
set "IP_REDMI="
set "IP_192="
set "IP_10="

for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /i "IPv4"') do (
    set "IPRAW=%%a"
    set "IPCLEAN=!IPRAW: =!"

    REM Saltar loopback, APIPA, VMware VMnet1 y VMnet8
    set "VALID=1"
    if "!IPCLEAN!"=="127.0.0.1"          set "VALID=0"
    if "!IPCLEAN:~0,8!"=="169.254."      set "VALID=0"
    if "!IPCLEAN:~0,11!"=="192.168.32."  set "VALID=0"
    if "!IPCLEAN:~0,11!"=="192.168.76."  set "VALID=0"

    if "!VALID!"=="1" (
        if "!IPCLEAN:~0,11!"=="192.168.43." if "!IP_REDMI!"=="" set "IP_REDMI=!IPCLEAN!"
        if "!IPCLEAN:~0,8!"=="192.168."     if "!IP_192!"==""   set "IP_192=!IPCLEAN!"
        if "!IPCLEAN:~0,3!"=="10."          if "!IP_10!"==""    set "IP_10=!IPCLEAN!"
    )
)

REM Aplicar prioridad (el ultimo que escribe gana, en orden ascendente de prioridad)
if not "!IP_10!"==""    set "MYIP=!IP_10!"
if not "!IP_192!"==""   set "MYIP=!IP_192!"
if not "!IP_REDMI!"=="" set "MYIP=!IP_REDMI!"

:OPEN
echo  [OK] Servidores listos
echo.
echo  ============================================
echo   ACCESO DESDE LA RED:
echo   http://!MYIP!/gaming-foro-v2/
echo.
echo   Desde este PC (localhost):
echo   http://localhost/gaming-foro-v2/
echo  ============================================
echo.
echo   Comparte la primera URL con tu clase.
echo   Todos deben estar en el mismo WiFi.
echo.
start "" "http://!MYIP!/ip.php"
timeout /t 5 /nobreak >nul
