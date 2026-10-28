@echo off
setlocal
chcp 65001 >nul
rem ---------------------------------------------------------------------------
rem  Si tu MySQL tiene contrasena, escribela aqui (con XAMPP recien instalado va vacia)
set "CLAVE="
set "XAMPP=C:\xampp"
rem ---------------------------------------------------------------------------
set "MYSQL=%XAMPP%\mysql\bin\mysql.exe"
set "MYSQLADMIN=%XAMPP%\mysql\bin\mysqladmin.exe"
set "PCLAVE="
if not "%CLAVE%"=="" set "PCLAVE=-p%CLAVE%"
set "AQUI=%~dp0"
if "%AQUI:~-1%"=="\" set "AQUI=%AQUI:~0,-1%"

if not exist "%MYSQL%" (
  echo No se encontro XAMPP en %XAMPP%.
  echo Instala XAMPP 8.2 o superior desde https://www.apachefriends.org y vuelve a intentarlo.
  pause
  exit /b 1
)
"%MYSQLADMIN%" -u root %PCLAVE% ping >nul 2>&1
if errorlevel 1 (
  echo MySQL no esta iniciado.
  echo Abre el XAMPP Control Panel, presiona Start en Apache y en MySQL, y vuelve a intentarlo.
  pause
  exit /b 1
)

title Papa Conecta - Reiniciar datos de prueba
echo Esto borra los pedidos, usuarios y fotos que se hayan creado y vuelve a cargar los datos de prueba.
choice /m "Continuar"
if errorlevel 2 exit /b 0

"%MYSQL%" -u root %PCLAVE% --default-character-set=utf8mb4 < "%AQUI%\database\papa_conecta.sql"
if errorlevel 1 (
  echo No se pudo recargar la base de datos.
  pause
  exit /b 1
)
for %%F in ("%AQUI%\public\uploads\productos\*") do (
  if /i not "%%~nxF"==".gitkeep" del /q "%%F"
)
echo Listo: la base de datos quedo como al inicio. Ya puedes hacer la demo.
pause
