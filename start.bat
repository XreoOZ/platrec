@echo off
start cmd /k "cd /d D:\XAMPP\htdocs\PlatrecLaravel\PlatRec_Laravel && php artisan serve"
start cmd /k "cd /d D:\XAMPP\htdocs\PlatrecLaravel\PlatRec_Service && python api.py"

REM .\start.bat COPY TO START