# Testing Guide for IMD Postcode Checker

This project includes a comprehensive test suite to ensure code quality, security, and reliability.

## Test Structure

The test suite is organized into three main categories:

### Unit Tests (`tests/Unit/`)

Tests for individual functions in isolation:

- **NormalisePostcodeTest.php** - Postcode normalization and sanitization
- **GetPostcodesArrayTest.php** - Array processing and line splitting
- **PostcodePlaceholdersForSqlTest.php** - SQL placeholder generation
- **PostcodesForTextareaTest.php** - Textarea output formatting
- **OutputTableRowTest.php** - HTML table row generation

### Integration Tests (`tests/Integration/`)

Tests for component interactions and workflows:

- **DatabaseOperationsTest.php** - Database queries with mocked PDO
- **WorkflowTest.php** - End-to-end user workflows

### Security Tests (`tests/Security/`)

Tests for security vulnerabilities:

- **SqlInjectionTest.php** - SQL injection prevention
- **XssPreventionTest.php** - Cross-site scripting (XSS) prevention
- **InputValidationTest.php** - Input validation and edge cases

## Prerequisites

- PHP 8.0 or higher
- Composer
- PCOV or Xdebug (for code coverage)

## Installation

1. Install dependencies:

```bash
composer install
```

This will install PHPUnit and set up the autoloader.

2. Install a coverage driver (optional, needed for `composer test-coverage`):

```bash
# PCOV (recommended - fast, coverage-only)
sudo apt-get install php8.4-pcov

# OR Xdebug (slower, includes debugging features)
sudo apt-get install php8.4-xdebug
```

Without a coverage driver, tests run normally but coverage reports can't be generated.

## Running Tests

### Run All Tests

```bash
composer test
```

Or directly with PHPUnit:

```bash
vendor/bin/phpunit
```

### Run Specific Test Suites

**Unit tests only:**

```bash
vendor/bin/phpunit --testsuite Unit
```

**Integration tests only:**

```bash
vendor/bin/phpunit --testsuite Integration
```

**Security tests only:**

```bash
vendor/bin/phpunit --testsuite Security
```

### Run a Specific Test File

```bash
vendor/bin/phpunit tests/Unit/NormalisePostcodeTest.php
```

### Run a Specific Test Method

```bash
vendor/bin/phpunit --filter testNormaliseValidFullPostcode
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

**HTTP input functions** - `getDecileInt()` and `decileForInput()` use `filter_input(INPUT_GET)`, which requires actual HTTP requests. These can't be unit tested without additional mocking infrastructure. They're covered by integration tests instead.

**Error handling paths** - A few error conditions in `getPostcodesArray()` remain untested (lines 38, 46). These handle edge cases like `preg_split()` failures.

The core business logic—postcode normalization, array processing, SQL placeholder generation, and HTML output—has complete test coverage.

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
+++ Actual
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

✅ **Output Generation**

- Table row HTML generation
- Field ordering
- Missing field handling

✅ **Workflows**

- Single and multiple postcode processing
- Maximum limit handling (900 postcodes)
- Error conditions
- Real-world input scenarios

### What is NOT Tested

❌ **Browser/UI Testing** - These are backend function tests only
❌ **Actual Database Operations** - Database tests use mocks
❌ **GET Parameter Processing** - `filter_input()` requires actual HTTP requests
❌ **Full Integration** - The complete index.php flow with real database

## Adding New Tests

### 1. Create a new test file

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

### 2. Common Assertions

```php
// Equality
$this->assertEquals($expected, $actual);
$this->assertSame($expected, $actual); // Strict comparison

// Types
$this->assertIsString($value);
$this->assertIsArray($value);
$this->assertIsInt($value);

// Arrays
$this->assertCount(3, $array);
$this->assertEmpty($array);
$this->assertArrayHasKey('key', $array);

// Strings
$this->assertStringContainsString('needle', 'haystack');
$this->assertStringStartsWith('prefix', 'prefixAndMore');
$this->assertMatchesRegularExpression('/pattern/', 'string');

// Booleans
$this->assertTrue($condition);
$this->assertFalse($condition);
```

### 3. Run your new tests

```bash
vendor/bin/phpunit tests/Unit/MyNewTest.php
```

## Continuous Integration

These tests can be integrated into CI/CD pipelines:

### GitHub Actions Example

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

### "Class not found" errors

Make sure you've run `composer install` to set up the autoloader.

### "filter_input() returns null" in tests

The `getDecileInt()` and `decileForInput()` functions use `filter_input()` which requires actual HTTP requests. These functions can't be fully unit tested without additional mocking infrastructure.

### Memory limit errors with large tests

Increase PHP memory limit:

```bash
php -d memory_limit=512M vendor/bin/phpunit
```

## Best Practices

1. **Run tests before committing** - Catch issues early
2. **Write tests for bugs** - When you fix a bug, add a test to prevent regression
3. **Keep tests fast** - Unit tests should run in milliseconds
4. **Test edge cases** - Empty inputs, maximum values, special characters
5. **Test security** - Always verify XSS and SQL injection prevention

## Further Reading

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Testing Best Practices](https://phpunit.de/manual/current/en/writing-tests-for-phpunit.html)
- [OWASP Testing Guide](https://owasp.org/www-project-web-security-testing-guide/)
