# Agent Instructions

This document provides development commands for working with this project.

## PHP (tools/imd)

**Important**: Run format and lint checks on every change before committing.

### Install Dependencies

```bash
cd tools/imd
composer install
npm install
```

### Format Code

```bash
cd tools/imd
npm run format
```

### Check Formatting

```bash
cd tools/imd
npm run format:check
```

### Run Tests

```bash
cd tools/imd
composer test
```

### Run Tests with Coverage

```bash
cd tools/imd
composer run test-coverage
```

## Rodney (Browser Automation)

Rodney is a Chrome automation tool used for browser testing.

> **Important:** These instructions are for PowerShell on Windows.

### Start Chrome (PowerShell)

The `rodney start` command launches Chrome but the connection can drop immediately. The reliable approach is to start Chrome manually with remote debugging:

```powershell
# Start Chrome with remote debugging and a dedicated profile
Start-Process -FilePath "C:\Program Files\Google\Chrome\Application\chrome.exe" -ArgumentList "--remote-debugging-port=9222","--user-data-dir=C:\temp\chrome-dev"

# Wait for Chrome to fully start
Start-Sleep -Seconds 3

# Connect Rodney to the browser
rodney connect localhost:9222
```

> **Note:** Using `--user-data-dir` creates an isolated profile, preventing conflicts with existing Chrome instances.

### Common Rodney Commands

```powershell
rodney start              # Start Chrome (headless by default)
rodney open <url>         # Navigate to a URL
rodney reload            # Reload current page
rodney html              # Get page HTML
rodney click <selector>  # Click an element
rodney input <selector> <text>  # Type into an input
rodney screenshot [file] # Take a screenshot
rodney status            # Show browser status
rodney stop              # Stop Chrome
```

### Test with Rodney (PowerShell)

1. Start the PHP development server:
   ```powershell
   cd tools/imd
   php -S 127.0.0.1:8080
   ```

2. Start Chrome with debugging:
   ```powershell
   Start-Process -FilePath "C:\Program Files\Google\Chrome\Application\chrome.exe" -ArgumentList "--remote-debugging-port=9222","--user-data-dir=C:\temp\chrome-dev"
   
   # Wait for Chrome to start
   Start-Sleep -Seconds 3
   ```

3. Connect Rodney:
   ```powershell
   rodney connect localhost:9222
   ```

4. Navigate to the app:
   ```powershell
   rodney open "http://127.0.0.1:8080/index.php"
   ```

### Troubleshooting (PowerShell)

**Chrome not responding / connection refused:**
- Chrome needs time to start - use `Start-Sleep -Seconds 3` after launching
- Ensure the port (default 9222) is not already in use

**"Browser not responding" after `rodney start`:**
- Use the manual Chrome start method instead (see above)

**Check if Chrome is already running:**
```powershell
Get-Process chrome -ErrorAction SilentlyContinue | Select-Object Id,MainWindowTitle
```

**Kill all Chrome processes if needed:**
```powershell
Get-Process chrome | Stop-Process -Force
```

**Use a different port if 9222 is in use:**
```powershell
Start-Process -FilePath "C:\Program Files\Google\Chrome\Application\chrome.exe" -ArgumentList "--remote-debugging-port=9230"
rodney connect localhost:9230
```

### Cleanup (PowerShell)

```powershell
# Stop Chrome via Rodney
rodney stop

# Clean up temp Chrome profile
Remove-Item -Path "C:\temp\chrome-dev" -Recurse -Force -ErrorAction SilentlyContinue
```

## Notes

- The main project is in `tools/imd/` - this is the IMD Postcode Checker
- The parent directory contains the main website (principles.md, index.html, etc.)
- PHP minimum version: 8.5
- PHPUnit version: ^10.0
- Formatting: dprint with Mago plugin for PHP
