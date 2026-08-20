@echo off
setlocal
cd /d "%~dp0"

set PHP_EXE=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe

REM Kill old processes on ports
powershell -NoProfile -Command "Get-NetTCPConnection -State Listen -LocalPort 8080,5173,5174 -ErrorAction SilentlyContinue | ForEach-Object { Stop-Process -Id $_.OwningProcess -Force -ErrorAction SilentlyContinue }"
timeout /t 2 /nobreak >nul

REM Start PHP backend
start "PHP Backend" cmd /k "cd /d %~dp0backend && %PHP_EXE% -S localhost:8080 index.php"

REM Start frontend
start "Frontend" cmd /k "cd /d %~dp0frontend && npm run dev -- --port 5173 --strictPort"

REM Start admin
start "Admin" cmd /k "cd /d %~dp0frontend-admin && npm run dev -- --port 5174 --strictPort"

echo Dev environment starting...
echo - PHP backend: http://localhost:8080
echo - Frontend:    http://localhost:5173/
echo - Admin:       http://localhost:5174/admin/
echo -
echo Three windows opened. Do not close them.
pause