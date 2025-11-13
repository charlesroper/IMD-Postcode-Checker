# Quick Reference

**Status:** All 87 tests passing | 242 assertions

## Test Suites

| Suite       | Count | Focus              |
| ----------- | ----- | ------------------ |
| Unit        | 45    | Individual functions |
| Integration | 18    | Workflows & database |
| Security    | 24    | SQL injection & XSS |

## Run Tests

```bash
vendor/bin/phpunit                              # All tests
vendor/bin/phpunit --testsuite Unit             # Unit only
vendor/bin/phpunit --testsuite Integration      # Integration only
vendor/bin/phpunit --testsuite Security         # Security only
vendor/bin/phpunit --testdox                    # With documentation
vendor/bin/phpunit tests/Unit/NormalisePostcodeTest.php  # Single file
```

## Coverage

```bash
composer test-coverage              # HTML report
php scripts/clover-to-json.php coverage/clover.xml > coverage.json  # JSON for LLMs
```

## Tested

**Functions:**
- `normalisePostcode()` — 10 tests
- `getPostcodesArray()` — 12 tests
- `postcodePlaceholdersForSql()` — 6 tests
- `postcodesForTextarea()` — 6 tests
- `outputTableRow()` — 11 tests

**Security:** SQL injection, XSS, input validation

**Workflows:** Single/multiple postcodes, limits, edge cases, database queries (mocked)

## Key Files

- `src/functions.php` — Core functions
- `tests/` — All test files
- `phpunit.xml` — Configuration
- `TESTING.md` — Full documentation
- `TEST_SUMMARY.md` — Implementation details

## Install

```bash
composer install
```
