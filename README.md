# IMD Postcode Checker 2025

The IMD Postcode Checker 2025 is a tiny thing made with lean but boring code,
open data and plenty of ❤️.

This simple tools enables look up of the Index of Multiple Deprivation rank for UK postcodes. The lower the rank, the more deprived the area. You can filter results by decile (deprivation categories 1–10).

## What is the IMD?

The Index of Multiple Deprivation (IMD) is the official measure of relative deprivation in England. It ranks every small area (lower-layer super output areas or LSOA) from 1 (most deprived) to 32,844 (least deprived).

The IMD combines seven domains using these weights:

- Income Deprivation (22.5%)
- Employment Deprivation (22.5%)
- Education, Skills and Training Deprivation (13.5%)
- Health Deprivation and Disability (13.5%)
- Crime (9.3%)
- Barriers to Housing and Services (9.3%)
- Living Environment Deprivation (9.3%)

For more details, see the [English indices of deprivation 2025 FAQ](https://www.gov.uk/government/statistics/english-indices-of-deprivation-2025/english-indices-of-deprivation-2025-frequently-asked-questions).

## Postcode Support

The tool handles all UK postcode formats:

**Standard formats:** A9 9AA, A9A 9AA, A99 9AA, AA9 9AA, AA9A 9AA, AA99 9AA

**Special cases:**
- BFPO postcodes (British Forces Post Office)
- GIR 0AA (historic Girobank code)
- Overseas territories (Ascension Island, Falklands, Gibraltar, etc.)
- Crown dependencies (Guernsey, Jersey, Isle of Man)

All postcodes are validated and normalized to proper UK format.

## Data used in this tool

- [English Index of Multiple Deprivation 2025](https://deprivation.communities.gov.uk/download-all)

## Local Development

The project is simple PHP and a SQLite database (distributed as a 7zip archive).

1. Clone the repository
2. Extract `db/imd25.sqlite3` from `db/imd25.sqlite3.7z`
3. Run a local PHP server: `php -S localhost:8000`
4. Visit `http://localhost:8000`

**Note:** You'll need PHP with `pdo_sqlite` enabled. See the [PHP installation guide](https://www.php.net/manual/en/install.php) if needed.

## Testing

The project includes 98 tests covering core functions, security, and special UK postcode formats.

```bash
composer install
vendor/bin/phpunit
```

For coverage reports:

```bash
composer test-coverage  # HTML report at coverage/index.html
php scripts/clover-to-json.php coverage/clover.xml > coverage/coverage-summary.json  # JSON for LLMs
```

Documentation:
- [TESTING.md](TESTING.md) — Full testing guide
- [TEST_SUMMARY.md](TEST_SUMMARY.md) — Test details
- [scripts/README.md](scripts/README.md) — Coverage converter

## Technical Stack

No runtime dependencies—just PHP and SQLite:

- Two PHP files: `index.php` and `src/functions.php`
- One stylesheet: `style.css`
- One SQLite database: `db/imd25.sqlite3`

**Requirements:** PHP 8.0+ with `pdo_sqlite` extension. Composer is only needed locally for testing.
