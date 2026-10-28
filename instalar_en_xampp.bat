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

title Papa Conecta - Instalar en XAMPP
set "DESTINO=%XAMPP%\htdocs\papa_conecta"

if /i not "%AQUI%"=="%DESTINO%" (
  echo Copiando el proyecto a %DESTINO% ...
  robocopy "%AQUI%" "%DESTINO%" /E /NFL /NDL /NJH /NJS /NP /XD .git capturas >nul
  if errorlevel 8 (
    echo No se pudo copiar el proyecto.
    pause
    exit /b 1
  )
)

echo Creando la base de datos papa_conecta con los datos de prueba ...
"%MYSQL%" -u root %PCLAVE% --default-character-set=utf8mb4 < "%DESTINO%\database\papa_conecta.sql"
if errorlevel 1 (
  echo No se pudo crear la base de datos. Si MySQL tiene contrasena, escribela en este archivo
  echo ^(linea set "CLAVE="^) y tambien en config\config.php.
  pause
  exit /b 1
)

echo.
echo Listo. Usuarios de prueba:
echo   Administrador  admin@papaconecta.pe      Admin#2026
echo   Agricultora    juana.quispe@correo.pe    Demo#2026
echo.
echo Abriendo http://localhost/papa_conecta/ ...
start "" "http://localhost/papa_conecta/"
pause
