@echo off
setlocal
cd /d "%~dp0"
set "PHP_BIN=%USERPROFILE%\.config\herd\bin\php84\php.exe"
if not exist "%PHP_BIN%" (
  echo PHP Herd tidak ditemukan: %PHP_BIN%
  pause
  exit /b 1
)
echo CateringKu pelanggan: http://127.0.0.1:8000/pesan
 echo CateringKu admin: http://127.0.0.1:8000/login
echo Biarkan jendela ini terbuka saat memakai website atau Device Toolbar.
echo Ctrl+C untuk menghentikan server. Jangan jalankan dua server pada port yang sama.
"%PHP_BIN%" artisan optimize:clear
"%PHP_BIN%" artisan serve --host=127.0.0.1 --port=8000 --no-reload
if errorlevel 1 (
  echo Server berhenti dengan error. Periksa apakah port 8000 sudah dipakai.
  pause
  exit /b 1
)
