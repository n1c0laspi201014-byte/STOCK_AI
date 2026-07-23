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
echo Testing market provider...
"%PHP_BIN%" scripts\php\test_market_api.php AAPL
echo Testing OpenRouter...
"%PHP_BIN%" scripts\php\test_openrouter.php AAPL 7d
echo Testing internal n8n authentication...
"%PHP_BIN%" scripts\php\test_internal_api.php
echo PASS Integration test runner finished
echo   BLOCKED_BY_SETUP results are expected until external keys and services are configured.

