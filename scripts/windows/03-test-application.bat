@echo off
setlocal EnableExtensions
cd /d "%~dp0\..\.."
set "PHP_BIN="
for /f "delims=" %%D in ('dir /b /ad /o-n "C:\wamp64\bin\php\php*" 2^>nul') do if not defined PHP_BIN set "PHP_BIN=C:\wamp64\bin\php\%%D\php.exe"
if not defined PHP_BIN for %%P in (php.exe) do set "PHP_BIN=%%~$PATH:P"
if not defined PHP_BIN (
  echo FAIL PHP executable not found
  echo   Likely fix: install/start WAMP64.
  exit /b 1
)
"%PHP_BIN%" scripts\php\check_requirements.php
if errorlevel 1 exit /b 1
"%PHP_BIN%" scripts\php\test_application.php
exit /b %ERRORLEVEL%

