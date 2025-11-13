# Project Structure

```text
imd/
├── src/
│   └── functions.php              # Core application functions (extracted from index.php)
├── scripts/
│   ├── clover-to-json.php         # Converts coverage XML to JSON for LLM analysis
│   └── README.md                  # Documentation for scripts
├── tests/
│   ├── Unit/                      # Unit tests for individual functions
│   │   ├── NormalisePostcodeTest.php
│   │   ├── PostcodeValidationTest.php
│   │   ├── GetPostcodesArrayTest.php
│   │   ├── PostcodePlaceholdersForSqlTest.php
│   │   ├── PostcodesForTextareaTest.php
│   │   └── OutputTableRowTest.php
│   ├── Integration/               # Integration and workflow tests
│   │   ├── DatabaseOperationsTest.php
│   │   └── WorkflowTest.php
│   └── Security/                  # Security-focused tests
│       ├── SqlInjectionTest.php
│       ├── XssPreventionTest.php
│       └── InputValidationTest.php
├── coverage/                      # Coverage reports (gitignored)
│   ├── index.html                 # HTML coverage report
│   ├── clover.xml                 # Clover XML format
│   └── coverage-summary.json      # JSON summary with uncovered lines
├── db/
│   └── imd25.sqlite3              # Database (extract from .7z)
├── vendor/                        # Composer dependencies (gitignored)
├── .phpunit.cache/                # PHPUnit cache (gitignored)
├── composer.json                  # PHP dependencies configuration
├── composer.lock                  # Locked dependency versions (gitignored)
├── phpunit.xml                    # PHPUnit configuration
├── index.php                      # Main application file
├── style.css                      # Styles
├── README.md                      # Project documentation
├── TESTING.md                     # Testing guide
├── TEST_SUMMARY.md                # Test implementation summary
└── .gitignore                     # Git ignore rules
```

## Quick Reference

### Running Tests

```bash
composer test                           # All tests
vendor/bin/phpunit --testsuite Unit     # Unit tests only
vendor/bin/phpunit --testsuite Security # Security tests only
```

### Coverage Reports

```bash
composer test-coverage                  # Generate HTML coverage report
php scripts/clover-to-json.php coverage/clover.xml  # Convert to JSON
```

### Files to Modify

- **Add new functions**: `src/functions.php`
- **Add new tests**: `tests/{Unit|Integration|Security}/`
- **Add new scripts**: `scripts/`
- **Configure tests**: `phpunit.xml`
