@echo off
setlocal EnableExtensions
cd /d "%~dp0\..\.."
if exist .env (
  echo PASS .env already exists
  echo   Tested: existing secrets will not be overwritten
) else (
  copy /y .env.example .env >nul
  if errorlevel 1 (
    echo FAIL Could not create .env
    echo   Likely fix: verify write access to the project folder and copy .env.example to .env.
    exit /b 1
  )
  echo PASS .env created from .env.example
)
findstr /c:"DB_DATABASE=stockdata" .env >nul
if errorlevel 1 (
  echo FAIL DB_DATABASE is not stockdata
  echo   Likely fix: edit .env and set DB_DATABASE=stockdata.
  exit /b 1
)
echo PASS Main database is stockdata
findstr /c:"INTERNAL_N8N_API_KEY=replace-with-a-long-random-value" .env >nul
if not errorlevel 1 (
  echo BLOCKED_BY_SETUP Replace INTERNAL_N8N_API_KEY placeholder before starting n8n
  echo   File: .env
)
echo PASS Local environment preparation completed without printing secrets

