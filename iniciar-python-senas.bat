@echo off
cd /d "%~dp0"
echo Iniciando motor Python de reconocimiento de senas...
echo.
python python-senas\server.py
pause
