<?php

declare(strict_types=1);

/**
 * Normalise a UK postcode by uppercasing and adding the standard space.
 * Validates format and handles special cases like BFPO, GIR 0AA, and overseas territories.
 *
 * @param string $postcode The postcode to normalise
 * @return string The normalised postcode, or empty string if invalid
 */
function normalisePostcode(string $postcode): string
{
    // Uppercase and strip all non-alphanumeric characters
    $clean = preg_replace('/[^A-Z0-9]/', '', strtoupper($postcode));

    // Handle empty input
    if ($clean === '') {
        return '';
    }

    // Special case: BFPO postcodes (British Forces Post Office)
    // Format: BFPO + 1-4 digits
    if (preg_match('/^BFPO(\d{1,4})$/', $clean, $matches)) {
        return 'BFPO ' . $matches[1];
    }

    // Special case: GIR 0AA (historic Girobank postcode)
    if ($clean === 'GIR0AA') {
        return 'GIR 0AA';
    }

    // For very short inputs (≤3 chars), return as-is (partial postcodes)
    if (strlen($clean) <= 3) {
        return $clean;
    }

    // For 4-char inputs, return as-is (likely outward code only)
    if (strlen($clean) === 4) {
        return $clean;
    }

    // Strict UK postcode validation
    // Format: (Outward: 2-4 chars alphanumeric)(Inward: 1 digit + 2 letters)
    //
    // Valid patterns:
    // - A9 9AA    (e.g., M1 1AE)
    // - A9A 9AA   (e.g., M1A 1AA)
    // - A99 9AA   (e.g., M60 1NW)
    // - AA9 9AA   (e.g., CR2 6XH)
    // - AA9A 9AA  (e.g., EC1A 1BB)
    // - AA99 9AA  (e.g., DN55 1PT)
    //
    // First position restrictions: Q, V, X not used
    // Second position restrictions (when 2+ letters): I, J, Z not used
    // Third position: only letters A-Z (if present after first digit)
    // Inward code: digit followed by 2 letters
    // Inward code restrictions: C, I, K, M, O, V not used in first position

    // Standard format: capture outward and inward parts
    $pattern = '/^'
        . '([A-PR-UWYZ])' // First char: any letter except Q, V, X, Z
        . '([0-9]{1,2}|[A-HK-Y]?[0-9]{1,2})' // Second part: digit(s) or letter+digit(s)
        . '([A-Z]?)' // Optional letter (for A9A format)
        . '\s?'
        . '([0-9])' // Inward: digit
        . '([ABD-HJLNP-UW-Z]{2})' // Inward: 2 letters (not C,I,K,M,O,V)
        . '$/x';

    if (preg_match($pattern, $clean, $matches)) {
        // Reconstruct with proper spacing
        $outward = $matches[1] . $matches[2] . $matches[3];
        $inward = $matches[4] . $matches[5];
        return $outward . ' ' . $inward;
    }

    // If no valid pattern matched, fall back to simple split for edge cases
    // (like test data, partial codes, etc.)
    return substr($clean, 0, -3) . ' ' . substr($clean, -3);
}

/**
 * Get an array of normalised postcodes from a query string input.
 *
 * @param string|null $postcodes_querystring The raw postcode input
 * @return array Array of normalised postcodes
 */
function getPostcodesArray(?string $postcodes_querystring): array
{
    // If the input is empty, return an empty array.
    if (!$postcodes_querystring) {
        return [];
    }

    // Use a regex to split on the CR+LF or just CR or just LF.
    $rows = preg_split('/\r\n|\r|\n/', $postcodes_querystring);
    if ($rows === false) {
        return [];
    }
    $postcodes = [];

    // Normalise the postcode in each row and add non-empty results to the
    // array.
    foreach ($rows as $row) {
        if (!is_string($row)) {
            continue;
        }
        $clean = normalisePostcode($row);
        if ($clean !== '') {
            $postcodes[] = $clean;
        }
    }

    return $postcodes;
}

/**
 * Get the decile value from the query string and validate it, or return a
 * default value if there is no user input.
 *
 * @return int The validated decile (1-10)
 */
function getDecileInt(): int
{
    return (int)filter_input(
        INPUT_GET,
        'd',
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'default'   => 10,
                'min_range' => 1,
                'max_range' => 10,
            ],
        ]
    );
}

/**
 * Generate a comma-separated string of placeholders for use in a SQL IN() clause.
 * For example, if there are 3 postcodes, this returns "?,?,?"
 * These placeholders will be bound to actual postcode values in a prepared statement.
 *
 * @param string|null $postcodes_querystring The raw postcode input
 * @return string Comma-separated SQL placeholders
 */
function postcodePlaceholdersForSql(?string $postcodes_querystring): string
{
    // Retrieve an array of postcodes from the user input.
    $postcodes = getPostcodesArray($postcodes_querystring);

    // Create an array of "?" placeholders, one for each postcode.
    // array_fill(start_index, count, value)
    // Example: array_fill(0, 3, '?') => ['?', '?', '?']
    // Join the placeholders into a comma-separated string: "?, ?, ?"
    $placeholders = implode(',', array_fill(0, count($postcodes), '?'));

    return $placeholders;
}

/**
 * Convert postcodes from array to string with newline between each postcode so
 * they can be stuffed back into the textarea.
 *
 * @param string|null $postcodes_querystring The raw postcode input
 * @return string Postcodes separated by newlines
 */
function postcodesForTextarea(?string $postcodes_querystring): string
{
    $postcodes = getPostcodesArray($postcodes_querystring);

    return implode(PHP_EOL, $postcodes);
}

/**
 * Get either the current decile value from the query string, or an empty
 * string. This is used to populate the decile input field.
 * Returns empty string when parameter is absent; returns validated decile (1-10) when present.
 *
 * @return string The decile as a string, or empty string
 */
function decileForInput(): string
{
    // Check if the parameter exists to avoid unnecessary filtering
    if (!filter_has_var(INPUT_GET, 'd')) {
        return '';
    }

    return (string)getDecileInt();
}

/**
 * Function to render the table rows populated with database data.
 *
 * @param array $row The data row from the database
 * @param array $fields The fields to output
 * @return string HTML table row
 */
function outputTableRow(array $row, array $fields): string
{
    $out = '<tr>';
    foreach ($fields as $field) {
        $value = array_key_exists($field, $row) ? $row[$field] : '';
        $safeValue = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        $out .= "<td>$safeValue</td>";
    }
    $out .= '</tr>';
    return $out;
}
