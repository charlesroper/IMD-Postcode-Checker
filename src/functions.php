<?php

declare(strict_types=1);

/**
 * Normalise a UK postcode by uppercasing and adding the standard space.
 *
 * @param string $postcode The postcode to normalise
 * @return string The normalised postcode
 */
function normalisePostcode(string $postcode): string
{
    // Uppercase and strip all non-alphanumeric characters before reinserting final space.
    $clean = preg_replace('/[^A-Z0-9]/', '', strtoupper($postcode));
    if (strlen($clean) <= 3) {
        return $clean;
    }

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
    if ($postcodes_querystring === null || $postcodes_querystring === '') {
        return array();
    }

    // Use a regex to split on the CR+LF or just CR or just LF.
    $rows = preg_split('/\r\n|\r|\n/', $postcodes_querystring);
    if ($rows === false) {
        return array();
    }
    $postcodes = array();

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
 * For example, if there are 3 postcodes, this returns "?, ?, ?"
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
 *
 * @return string The decile as a string, or empty string
 */
function decileForInput(): string
{
    $rawDecile = filter_input(INPUT_GET, 'd', FILTER_DEFAULT);
    if ($rawDecile === null || $rawDecile === false || $rawDecile === '') {
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
