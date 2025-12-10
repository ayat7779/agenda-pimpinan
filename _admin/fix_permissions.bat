@echo off
echo Fixing permissions for XAMPP...
echo.

:: Berikan permission ke folder
icacls "C:\xampp\htdocs\agenda-pimpinan" /grant "Everyone:(OI)(CI)F" /T

:: Buat folder uploads jika belum ada
if not exist "C:\xampp\htdocs\agenda-pimpinan\uploads" mkdir "C:\xampp\htdocs\agenda-pimpinan\uploads"

:: Buat file index.html di uploads untuk protection
echo <!-- Directory protection --> > "C:\xampp\htdocs\agenda-pimpinan\uploads\index.html"

echo.
echo Permissions fixed!
echo Please restart Apache from XAMPP Control Panel
pause