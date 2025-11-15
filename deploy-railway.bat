@echo off
REM SmartStay - Railway Deployment Helper Script (Windows)
REM This script helps you deploy your Laravel app to Railway

echo ========================================
echo 🚀 SmartStay Railway Deployment Helper
echo ========================================
echo.

REM Check if Railway CLI is installed
where railway >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ❌ Railway CLI is not installed.
    echo.
    echo Please install it first:
    echo   npm install -g @railway/cli
    echo.
    echo Or download from: https://railway.app/cli
    pause
    exit /b 1
)

echo ✅ Railway CLI is installed
echo.

REM Check if logged in
echo 🔐 Checking Railway authentication...
railway whoami >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ❌ Not logged in to Railway.
    echo.
    echo Logging you in...
    railway login
) else (
    echo ✅ Already logged in to Railway
)

echo.
echo 📦 Deployment Options:
echo.
echo 1. Initialize new Railway project
echo 2. Link to existing Railway project
echo 3. Deploy to Railway
echo 4. View logs
echo 5. Open Railway dashboard
echo.
set /p option="Choose an option (1-5): "

if "%option%"=="1" (
    echo.
    echo 🆕 Initializing new Railway project...
    railway init
    echo.
    echo ✅ Project initialized!
    echo.
    echo Next steps:
    echo 1. Add PostgreSQL database in Railway dashboard
    echo 2. Configure environment variables
    echo 3. Run: deploy-railway.bat and choose option 3
) else if "%option%"=="2" (
    echo.
    echo 🔗 Linking to existing Railway project...
    railway link
    echo.
    echo ✅ Project linked!
) else if "%option%"=="3" (
    echo.
    echo 🚀 Deploying to Railway...
    railway up
    echo.
    echo ✅ Deployment complete!
    echo.
    echo Run: railway open
    echo to view your app in the browser
) else if "%option%"=="4" (
    echo.
    echo 📋 Viewing logs...
    railway logs
) else if "%option%"=="5" (
    echo.
    echo 🌐 Opening Railway dashboard...
    railway open
) else (
    echo ❌ Invalid option
    pause
    exit /b 1
)

echo.
echo ✨ Done!
pause

