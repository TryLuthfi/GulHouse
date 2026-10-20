@echo off
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0run-gulhouse-e2e.ps1" %*
exit /b %ERRORLEVEL%
