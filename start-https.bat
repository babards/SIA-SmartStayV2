@echo off
echo Starting SmartStay with HTTPS support...
echo.
echo This will start your Laravel application with HTTPS support for PWA testing.
echo The browser's native install button should appear in the address bar.
echo.
echo Press Ctrl+C to stop the server.
echo.

php artisan serve --host=127.0.0.1 --port=8000
