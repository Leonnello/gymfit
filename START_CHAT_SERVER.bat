@echo off
REM Quick start script for GymFit Chat Server on Windows

echo ======================================
echo GymFit Live Chat Server Starter
echo ======================================
echo.

REM Check if Node.js is installed
node --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Node.js is not installed!
    echo Download from: https://nodejs.org/
    pause
    exit /b 1
)

echo ✓ Node.js found
echo.

REM Navigate to chat-server directory
cd /d "%~dp0chat-server"

echo Checking dependencies...
if not exist "node_modules" (
    echo Installing npm packages...
    call npm install
    if errorlevel 1 (
        echo ERROR: Failed to install packages
        pause
        exit /b 1
    )
)

echo.
echo ✓ Dependencies ready
echo.
echo Starting chat server...
echo Server will run on: http://localhost:3000
echo Press Ctrl+C to stop
echo.

call npm start
pause
