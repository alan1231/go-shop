@echo off
setlocal
cd /d "%~dp0"

set PHP_EXE=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe
set MYSQLD_EXE=C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe
set MYSQLD_CNF=C:\laragon\bin\mysql\mysql-8.4.3-winx64\my.ini

REM Kill old dev processes on ports (note: MySQL on 3306 is left running on purpose)
powershell -NoProfile -Command "Get-NetTCPConnection -State Listen -LocalPort 8080,5173,5174 -ErrorAction SilentlyContinue | ForEach-Object { Stop-Process -Id $_.OwningProcess -Force -ErrorAction SilentlyContinue }"
timeout /t 2 /nobreak >nul

REM Start MySQL only if not already listening on 3306
netstat -ano | findstr /R ":3306[ ]" >nul
if errorlevel 1 (
    start "MySQL" cmd /k "%MYSQLD_EXE% --defaults-file=%MYSQLD_CNF% --console"
    echo Starting MySQL...
    timeout /t 3 /nobreak >nul
) else (
    echo MySQL already running, skip.
)

REM Start PHP backend
start "PHP Backend" cmd /k "cd /d %~dp0backend && %PHP_EXE% -S localhost:8080 index.php"

REM Start frontend
start "Frontend" cmd /k "cd /d %~dp0frontend && npm run dev -- --port 5173 --strictPort"

REM Start admin
start "Admin" cmd /k "cd /d %~dp0frontend-admin && npm run dev -- --port 5174 --strictPort"

echo Dev environment starting...
echo - MySQL:       http://localhost:3306 (data at C:\laragon\data\mysql-8.4)
echo - PHP backend: http://localhost:8080
echo - Frontend:    http://localhost:5173/
echo - Admin:       http://localhost:5174/admin/
echo -
echo Four windows opened. Do not close them.
pause
