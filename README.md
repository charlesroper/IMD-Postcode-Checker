# IMD Postcode Checker 2025

The IMD Postcode Checker is a tiny thing made with lean but boring code,
open data and plenty of ❤️.

The tool enables you to look up the Index of Multiple Deprivation rank for a
list of postcodes. The lower the rank, the more deprived the area.

The results can be limited to a maximum decile value. A decile is a range
divided into 10 chunks similar to the way a percentage is a range divided into
100 chunks. A decile of 1 means the postcode is in the bottom 10% of of the
deprivation index, a decile of 2 means the postcode is in the bottom 20%, and so
on.

## What is the IMD?

The Index of Multiple Deprivation, commonly known as the IMD, is the official
measure of relative deprivation for small areas in England.

The Index of Multiple Deprivation ranks every small area, called lower-layer
super output areas (LSOA), in England from 1 (most deprived area) to 32,844
(least deprived area).

The IMD combines information from the seven domains to produce an overall
relative measure of deprivation. The domains are combined using the following
weights:

- Income Deprivation (22.5%)
- Employment Deprivation (22.5%)
- Education, Skills and Training Deprivation (13.5%)
- Health Deprivation and Disability (13.5%)
- Crime (9.3%)
- Barriers to Housing and Services (9.3%)
- Living Environment Deprivation (9.3%)

More details can be found on the government's [English indices of deprivation 2025: frequently asked questions](https://www.gov.uk/government/statistics/english-indices-of-deprivation-2025/english-indices-of-deprivation-2025-frequently-asked-questions) page.

## Data used in this tool

- [English Index of Multiple Deprivation 2025](https://deprivation.communities.gov.uk/download-all)

## Installing for local development

This project is a dead simple single file PHP script plus a SQLite database. The database is rather large, so I distribute it in a compressed 7zip file.

1. Clone or download the code
2. Extract the SQLite database file found in `db\imd25.sqlite3.7z`. The `imd25.sqlite3` file should be placed in the `db` folder.
3. Start a local PHP server in the root directory of the project: `php -S localhost:8000`
4. Visit `http://localhost:8000`

Note: you might need to [install PHP](https://www.php.net/manual/en/install.php) locally first. If you get errors, check your `php.ini` file and make sure `extension=pdo_sqlite` is enabled.

## Testing

This project includes a comprehensive test suite covering all core functionality, security vulnerabilities, and edge cases.

**Quick start:**

```bash
composer install
vendor/bin/phpunit
```

For detailed testing documentation, see:

- [TESTING.md](TESTING.md) - Complete testing guide
- [TEST_SUMMARY.md](TEST_SUMMARY.md) - Test implementation details
- [QUICK_REFERENCE.md](QUICK_REFERENCE.md) - Quick reference card

**Test coverage:**

- ✅ Unit tests (45 tests) - Individual function testing
- ✅ Integration tests (18 tests) - Workflow and database operations
- ✅ Security tests (24 tests) - SQL injection and XSS prevention

## Technical notes

This tool is intentionally minimal and has no runtime dependencies:

- Two PHP files: `index.php` (UI + queries) and `src/functions.php` (shared functions)
- One stylesheet: `style.css`
- One SQLite database: `db/imd25.sqlite3`

Production requirements:

- PHP 8.0+ with `pdo_sqlite` and `sqlite3` extensions enabled
- No Composer/vendor needed in production (Composer is only used locally for tests)
