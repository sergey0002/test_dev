@echo off
powershell -NoProfile -ExecutionPolicy Bypass -Command "Set-Location '%~dp0..\..'; docker compose exec -T app php artisan loadtest:reset"
exit /b %ERRORLEVEL%
