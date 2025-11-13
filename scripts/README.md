# Coverage Converter

The `clover-to-json.php` script converts PHPUnit's Clover XML coverage report into compact JSON.

## Why Use This

PHPUnit generates verbose XML reports. LLMs work better with clean JSON that shows what matters: coverage percentages and untested lines.

## Basic Usage

Generate a Clover report, then convert it:

```bash
vendor/bin/phpunit --coverage-clover=coverage/clover.xml --coverage-filter src
php scripts/clover-to-json.php coverage/clover.xml > coverage/coverage-summary.json
```

## Output Format

The JSON contains three sections:

- `generated` - Unix timestamp from the coverage run
- `totals` - Project-wide coverage statistics
- `files` - Per-file breakdown with uncovered line numbers

Example:

```json
{
    "generated": "1763041588",
    "totals": {
        "statements": 45,
        "covered_statements": 27,
        "coverage_percent": 60
    },
    "files": [
        {
            "file": "/path/to/src/functions.php",
            "statements": 45,
            "covered_statements": 27,
            "coverage_percent": 60,
            "uncovered_lines": [38, 46, 65, 66, 67]
        }
    ]
}
```

## Filtering Results

Show only files below 80% coverage:

```bash
php scripts/clover-to-json.php coverage/clover.xml --min-percent 80
```

Show the five files with lowest coverage:

```bash
php scripts/clover-to-json.php coverage/clover.xml --top 5
```

Combine both filters:

```bash
php scripts/clover-to-json.php coverage/clover.xml --top 10 --min-percent 90
```

## Using With LLMs

Paste the JSON into your LLM conversation to get targeted suggestions:

> "Here's my code coverage report. Which untested lines pose the highest risk?"

Or ask specific questions:

> "Lines 65-76 in functions.php are untested. What test cases should I add?"

The compact format keeps token usage low while giving the LLM everything it needs.

## How It Works

The script:

1. Parses the Clover XML file
2. Extracts project totals from `<project><metrics>`
3. Collects per-file metrics from `<file><metrics>`
4. Finds uncovered lines via XPath query for `<line type="stmt" count="0">`
5. Sorts files by coverage (lowest first)
6. Applies optional filters
7. Outputs JSON to stdout

## Requirements

- PHP 8.0+
- SimpleXML extension (bundled with PHP)
- A Clover XML report from PHPUnit

## Error Handling

The script exits with specific codes:

- `0` - Success
- `2` - File not readable
- `3` - XML parsing failed

Errors go to stderr, so you can safely redirect stdout to a file.
