@echo off
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0loadtest.ps1" %*
exit /b %ERRORLEVEL%
