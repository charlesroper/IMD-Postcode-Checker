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

### Start Chrome

```bash
# Start Chrome with remote debugging enabled
Start-Process -FilePath "C:\Program Files\Google\Chrome\Application\chrome.exe" -ArgumentList "--remote-debugging-port=9222"

# Connect Rodney to the browser
rodney connect localhost:9222
```

### Common Rodney Commands

```bash
rodney start              # Start Chrome (headless by default)
rodney open <url>        # Navigate to a URL
rodney reload            # Reload current page
rodney html              # Get page HTML
rodney click <selector>  # Click an element
rodney input <selector> <text>  # Type into an input
rodney screenshot [file] # Take a screenshot
rodney status            # Show browser status
rodney stop              # Stop Chrome
```

### Test with Rodney

1. Start the PHP development server:
   ```bash
   cd tools/imd
   php -S 127.0.0.1:8080
   ```

2. Start Chrome with debugging:
   ```bash
   Start-Process -FilePath "C:\Program Files\Google\Chrome\Application\chrome.exe" -ArgumentList "--remote-debugging-port=9222"
   ```

3. Connect Rodney:
   ```bash
   rodney connect localhost:9222
   ```

4. Navigate to the app:
   ```bash
   rodney open "http://127.0.0.1:8080/index.php"
   ```

## Notes

- The main project is in `tools/imd/` - this is the IMD Postcode Checker
- The parent directory contains the main website (principles.md, index.html, etc.)
- PHP minimum version: 8.5
- PHPUnit version: ^10.0
- Formatting: dprint with Mago plugin for PHP
