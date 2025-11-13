# Testing Guide

The test suite has 98 tests covering core functions, workflows, and security.

## Test Organization

**Unit Tests** (`tests/Unit/`)
- `NormalisePostcodeTest.php` – Standard postcode normalization
- `PostcodeValidationTest.php` – Special UK formats (BFPO, overseas territories, Crown dependencies)
- `GetPostcodesArrayTest.php` – Array processing
- `PostcodePlaceholdersForSqlTest.php` – SQL placeholders
- `PostcodesForTextareaTest.php` – Textarea formatting
- `OutputTableRowTest.php` – HTML output

**Integration Tests** (`tests/Integration/`)
- `DatabaseOperationsTest.php` – Database queries (mocked)
- `WorkflowTest.php` – End-to-end workflows

**Security Tests** (`tests/Security/`)
- `SqlInjectionTest.php` – SQL injection prevention
- `XssPreventionTest.php` – XSS prevention
- `InputValidationTest.php` – Input validation

## Prerequisites

- PHP 8.0+
- Composer
- PCOV or Xdebug (optional, for coverage reports)

## Installation

```bash
composer install
```

This installs PHPUnit and the autoloader.

**For coverage reports**, install a driver:

```bash
# PCOV (recommended – fast)
sudo apt-get install php8.4-pcov

# OR Xdebug (slower, includes debugging)
sudo apt-get install php8.4-xdebug
```

Without a driver, tests run but coverage reports won't be generated.

## Running Tests

```bash
composer test                               # All tests
vendor/bin/phpunit                          # All tests (direct)
vendor/bin/phpunit --testsuite Unit         # Unit only
vendor/bin/phpunit --testsuite Integration  # Integration only
vendor/bin/phpunit --testsuite Security     # Security only
vendor/bin/phpunit tests/Unit/NormalisePostcodeTest.php  # Single file
vendor/bin/phpunit --filter testNormaliseValidFullPostcode  # Single test
```

## Code Coverage

### HTML Coverage Report

Generate HTML code coverage report:

```bash
composer test-coverage
```

Coverage report will be generated in the `coverage/` directory. Open `coverage/index.html` in your browser.

### JSON Coverage Report (for LLMs)

Generate a compact JSON summary with uncovered line numbers:

```bash
vendor/bin/phpunit --coverage-clover=coverage/clover.xml --coverage-filter src
php scripts/clover-to-json.php coverage/clover.xml > coverage/coverage-summary.json
```

This produces a lightweight report perfect for LLM analysis. The JSON includes:
- Overall coverage percentages
- Per-file coverage statistics
- Specific line numbers of untested code

Filter to show only files below 80% coverage:

```bash
php scripts/clover-to-json.php coverage/clover.xml --min-percent 80
```

See `scripts/README.md` for full documentation on the converter tool.

### Current Coverage: 60%

The project maintains 60% statement coverage, which is appropriate for this codebase. The untested code falls into two categories:

**HTTP input functions** – `getDecileInt()` and `decileForInput()` use `filter_input(INPUT_GET)`, which requires actual HTTP requests. These can't be unit tested without additional mocking infrastructure. They're covered by integration tests instead.

**Error handling paths** – A few error conditions in `getPostcodesArray()` remain untested (lines 38, 46). These handle edge cases like `preg_split()` failures.

The core business logic – postcode normalization, array processing, SQL placeholder generation, and HTML output – has complete test coverage.

### Improving Coverage

To push coverage higher:

1. Test the `preg_split()` error path (line 38) by passing malformed regex patterns
2. Add integration tests that simulate GET parameters for the HTTP input functions
3. Test non-string elements in the postcode array (line 46)

These additions would increase coverage to near 100%, though the return on effort diminishes quickly beyond testing the core paths.

## Understanding Test Results

### Successful Test Run

```text
PHPUnit 10.x

...                                                                63 / 63 (100%)

Time: 00:00.123, Memory: 10.00 MB

OK (63 tests, 150 assertions)
```

### Failed Test

```text
F

Time: 00:00.050, Memory: 8.00 MB

There was 1 failure:

1) Tests\Unit\NormalisePostcodeTest::testNormaliseValidFullPostcode
Failed asserting that two strings are equal.
--- Expected
+ + Actual
@@ @@
-'SW1A 1AA'
+'SW1A1AA'

FAILURES!
Tests: 63, Assertions: 150, Failures: 1.
```

## Test Coverage Summary

### What is Tested

✅ **Input Sanitization**

- Postcode normalization (uppercase, spacing, special character removal)
- Special UK formats (BFPO, GIR 0AA, overseas territories, Crown dependencies)
- Pattern validation (6 standard formats: A9 9AA through AA99 9AA)
- Line splitting (LF, CRLF, CR handling)
- Empty input handling
- Unicode and special character handling

✅ **SQL Safety**

- Prepared statement placeholder generation
- SQL injection prevention
- Parameter binding validation

✅ **XSS Prevention**

- HTML entity escaping
- Script tag neutralization
- Event handler escaping
- Quote escaping

✅ **Data Processing**

- Array operations
- String manipulation
- Edge case handling (empty, null, extreme values)

✅ **Output Generation** – HTML generation, field ordering, missing fields

✅ **Workflows** – Single/multiple postcodes, limits, error conditions

### Not Tested

❌ **Browser/UI** – Backend function tests only
❌ **Real Database** – Database tests use mocks
❌ **HTTP Input** – `filter_input()` requires actual requests
❌ **Full Flow** – Complete index.php with real database

## Adding Tests

Create a test file:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MyNewTest extends TestCase
{
    public function testSomething(): void
    {
        $result = myFunction('input');
        $this->assertEquals('expected', $result);
    }
}
```

Common assertions:

```php
// Equality
$this->assertEquals($expected, $actual);
$this->assertSame($expected, $actual);  // Strict

// Types
$this->assertIsString($value);
$this->assertIsArray($value);

// Arrays
$this->assertCount(3, $array);
$this->assertEmpty($array);

// Strings
$this->assertStringContainsString('needle', 'haystack');
$this->assertStringStartsWith('prefix', 'prefixAndMore');

// Booleans
$this->assertTrue($condition);
$this->assertFalse($condition);
```

Run tests:

```bash
vendor/bin/phpunit tests/Unit/MyNewTest.php
```

## CI/CD

Example GitHub Actions workflow:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v2
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.0'
    - name: Install dependencies
      run: composer install
    - name: Run tests
      run: composer test
```

## Troubleshooting

**"Class not found" errors** – Run `composer install` to set up the autoloader.

**"filter_input() returns null"** – Functions using `filter_input(INPUT_GET)` require actual HTTP requests. Unit tests can't fully test these without additional mocking infrastructure.

**Memory errors** – Increase the limit:
```bash
php -d memory_limit=512M vendor/bin/phpunit
```

## Best Practices

1. Run tests before committing – catch issues early
2. Add tests when fixing bugs – prevent regression
3. Keep unit tests fast – milliseconds, not seconds
4. Test edge cases – empty inputs, maximums, special characters
5. Test security – verify XSS and SQL injection prevention

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Testing Best Practices](https://phpunit.de/manual/current/en/writing-tests-for-phpunit.html)
- [OWASP Testing Guide](https://owasp.org/www-project-web-security-testing-guide/)
