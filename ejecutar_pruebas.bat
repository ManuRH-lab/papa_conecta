@echo off
setlocal
chcp 65001 >nul
title Papa Conecta - Pruebas automaticas
echo Las pruebas vuelven a cargar los datos de prueba: se borra lo que hayas creado en la web.
echo Requisitos: XAMPP con Apache y MySQL iniciados, y Python 3.10 o superior.
echo.
rem Sirve con el instalador clasico (python) y con el nuevo Python Install Manager (py)
set "PY="
python -c "import sys" >nul 2>&1
if not errorlevel 1 set "PY=python"
if not defined PY (
  py -c "import sys" >nul 2>&1
  if not errorlevel 1 set "PY=py"
)
if not defined PY (
  echo No se encontro Python. Instalalo desde https://www.python.org/downloads/
  echo Despues cierra esta ventana, abre una nueva y vuelve a intentarlo.
  pause
  exit /b 1
)
%PY% -c "import playwright" >nul 2>&1
if errorlevel 1 (
  echo Instalando Playwright ^(solo la primera vez^) ...
  %PY% -m pip install playwright
  if errorlevel 1 (
    echo.
    echo No se pudo instalar Playwright. Copia el mensaje de error de arriba para revisarlo.
    echo Las pruebas son opcionales: la web funciona igual sin ellas.
    pause
    exit /b 1
  )
  %PY% -m playwright install chromium
)
%PY% "%~dp0pruebas\pruebas_e2e.py"
echo.
echo Resultados en pruebas\resultados_pruebas.json y capturas en pruebas\capturas\
pause
