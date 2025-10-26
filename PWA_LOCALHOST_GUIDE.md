# PWA Localhost Setup Guide

## Problem
Chrome's native PWA install button (in the address bar) doesn't appear on localhost because it requires HTTPS.

## Solutions

### Option 1: Use Laravel Valet (Recommended)
```bash
# Install Laravel Valet (if not already installed)
composer global require laravel/valet

# Start Valet
valet start

# Park your project directory
cd /path/to/your/SmartStay
valet park

# Your app will be available at: https://smartstay.test
```

### Option 2: Use Laravel Sail with HTTPS
```bash
# Start with HTTPS
./vendor/bin/sail up -d
# Access at: https://localhost (you may need to accept the self-signed certificate)
```

### Option 3: Use ngrok for HTTPS
```bash
# Install ngrok
# Then run:
ngrok http 8000

# Use the HTTPS URL provided by ngrok
```

### Option 4: Enable Chrome Flags for Localhost (Temporary)
1. Open Chrome
2. Go to `chrome://flags/`
3. Search for "Insecure origins treated as secure"
4. Add `http://127.0.0.1:8000` to the list
5. Restart Chrome

## Why This Happens
- Chrome requires HTTPS for PWA installation
- Localhost HTTP doesn't meet PWA security requirements
- The browser's native install button only appears for secure origins

## Testing
After implementing any of the above solutions:
1. Visit your app with HTTPS
2. Look for the install button in Chrome's address bar
3. The button should appear as a computer screen icon with a download arrow
