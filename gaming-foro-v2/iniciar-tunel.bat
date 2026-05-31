@echo off
title NEXUS//BOARD — Tunel publico
color 0A
cls
echo.
echo  ================================================
echo   NEXUS//BOARD — Compartir en red del instituto
echo  ================================================
echo.

REM Comprobar si XAMPP Apache está corriendo
netstat -an | find ":80 " | find "LISTENING" >nul 2>&1
if errorlevel 1 (
    echo  [!] Apache NO está corriendo. Inicia XAMPP primero.
    echo.
    pause
    exit /b 1
)
echo  [OK] Apache corriendo en puerto 80
echo.

REM Arrancar el tunel ngrok apuntando al puerto 80
echo  [..] Iniciando tunel... espera unos segundos
echo.
ngrok http 80 --log=stdout 2>&1
