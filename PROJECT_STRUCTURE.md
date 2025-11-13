# Project Structure

```text
imd/
├── src/
│   └── functions.php              # Core application functions (extracted from index.php)
├── tests/
│   ├── Unit/                      # Unit tests for individual functions
│   │   ├── NormalisePostcodeTest.php
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

### Files to Modify

- **Add new functions**: `src/functions.php`
- **Add new tests**: `tests/{Unit|Integration|Security}/`
- **Configure tests**: `phpunit.xml`
