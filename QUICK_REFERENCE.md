# IMD Postcode Checker - Test Suite Quick Reference

## ✅ Test Status: ALL PASSING

- **87 tests** | **242 assertions** | **1 intentional warning**

## Test Suites

| Suite       | Tests | Coverage               |
| ----------- | ----- | ---------------------- |
| Unit        | 45    | Input/output functions |
| Integration | 18    | Workflows & database   |
| Security    | 24    | SQL injection & XSS    |

## Run Commands

```bash
# All tests
vendor/bin/phpunit

# By suite
vendor/bin/phpunit --testsuite Unit
vendor/bin/phpunit --testsuite Integration
vendor/bin/phpunit --testsuite Security

# With documentation
vendor/bin/phpunit --testdox

# Single file
vendor/bin/phpunit tests/Unit/NormalisePostcodeTest.php

# Coverage report
composer test-coverage
```

## What's Tested

### ✅ Functions

- `normalisePostcode()` - 10 tests
- `getPostcodesArray()` - 12 tests
- `postcodePlaceholdersForSql()` - 6 tests
- `postcodesForTextarea()` - 6 tests
- `outputTableRow()` - 11 tests

### ✅ Security

- SQL injection prevention
- XSS attack prevention
- Input validation & sanitization

### ✅ Workflows

- Single & multiple postcodes
- Maximum limit (900)
- Empty & malformed input
- Database queries (mocked)

## Key Files

- `src/functions.php` - Core functions
- `tests/` - All test files
- `phpunit.xml` - Test configuration
- `TESTING.md` - Full documentation
- `TEST_SUMMARY.md` - Implementation details

## Installation

```bash
composer install
```

That's it! Tests are ready to run.
