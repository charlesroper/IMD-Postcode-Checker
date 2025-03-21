<?php

$current_year = (new DateTime('now', new DateTimeZone('Europe/London')))->format('Y');


// Get postcodes from the query string, sanitize the input and convert to an
// array
function getPostcodesArray()
{
    $safe_postcodes = filter_input(INPUT_GET, 'p', FILTER_SANITIZE_SPECIAL_CHARS);

    if (empty($safe_postcodes)) {
        return array();
    }

    $safe_postcodes = explode("\n", $safe_postcodes);

    return array_map('strtoupper', $safe_postcodes);
}

// Get the decile value from the query string and validate it, or return a
// default value if there is no user input
function getDecileInt()
{
    $options = [
        'options' => [
            'default'   => 10,
            'min_range' => 1,
            'max_range' => 10,
        ],
    ];

    return filter_input(INPUT_GET, 'd', FILTER_VALIDATE_INT, $options);
}

function addQuotesAndCommas($str)
{
    if (!empty($str)) {
        $str = trim($str);
        return "'$str',";
    }
}

// Convert the postcodes to comma-delimited quoted list; e.g., 'TN33 0PF','BN4 1UH'
function postcodesForSql()
{
    $postcodes = getPostcodesArray();
    $out       = '';

    foreach ($postcodes as $postcode) {
        $out .= addQuotesAndCommas($postcode);
    }

    return rtrim($out, ',');
}

// Convert postcodes from array to string with newline between each postcode so
// they can be stuffed back into the textarea
function postcodesForTextarea()
{
    $postcodes = getPostcodesArray();

    return implode("\n", $postcodes);
}

// Get either the current decile value from the query string, or an empty
// string. This is used to populate the decile input field.
function decileForInput()
{
    $decile = getDecileInt();
    if (!empty($_GET['d'])) {
        return $decile;
    } else {
        return '';
    }
}

// Function to render the table rows populated with database data.
function outputTableRow($row, $fields)
{
    $out = '<tr>';
    foreach ($fields as $field) {
        $out .= '<td>' . $row[$field] . '</td>';
    }
    $out .= '</tr>';
    return $out;
}

$postcodes_for_sql = postcodesForSql();
$decile_for_sql    = getDecileInt();

// Initialise the SQLite database
$db = new PDO('sqlite:./db/imd.sqlite3');

// Get a count of postcodes matching those entered into the textarea. This is
// used prevent empty results from rendering.
$imd_data_count = $db->query(
    "SELECT COUNT() FROM onspd_aug19 WHERE onspd_aug19.pcds IN ( $postcodes_for_sql )"
);

// The main database query
$imd_data = $db->query(
    "SELECT
    onspd.pcds,
    imd.lsoa_name_11,
    imd.imd_rank,
    imd.imd_decile
  FROM
    imd19 AS imd
  INNER JOIN
    onspd_aug19 AS onspd ON imd.lsoa_code_11 = onspd.lsoa11
  WHERE
    onspd.pcds IN (	$postcodes_for_sql )
  AND
    imd.imd_decile <= $decile_for_sql"
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="This simple tool enables you to look up the Index of Multiple Deprivation (IMD) rank for a list of postcodes.">
    <title>IMD Postcode Checker</title>
    <link rel="stylesheet" href="./style.css?v=5">
</head>

<body>

    <header>
        <h1>English IMD Postcode Checker</h1>
    </header>

    <main>

        <details>
            <summary>
                <h2>What is this?</h2>
            </summary>

            <p>This tool enables you to look up the Index of Multiple Deprivation rank for a list of postcodes. The lower the rank, the more deprived the area.</p>

            <p>The results can be limited to a maximum decile value. A <i>decile</i> is a range divided into 10 chunks similar to the way a percentage is a range divided into 100 chunks. A decile of 1 means the postcode is in the bottom 10% of the deprivation index, a decile of 2 means the postcode is in the bottom 20%, and so on.</p>

            <h3>What is the IMD?</h3>

            <p>The Index of Multiple Deprivation, commonly known as the IMD, is the official measure of relative deprivation for small areas in England.</p>

            <p>The Index of Multiple Deprivation ranks every small area, called lower-layer super output areas (LSOA), in
                England from 1 (most deprived area) to 32,844 (least deprived area).</p>

            <p>The IMD combines information from the seven domains to produce an overall relative measure of deprivation. The domains are combined using the following weights:</p>

            <ul>
                <li>Income Deprivation (22.5%)</li>
                <li>Employment Deprivation (22.5%)</li>
                <li>Education, Skills and Training Deprivation (13.5%)</li>
                <li>Health Deprivation and Disability (13.5%)</li>
                <li>Crime (9.3%)</li>
                <li>Barriers to Housing and Services (9.3%)</li>
                <li>Living Environment Deprivation (9.3%)</li>
            </ul>

            <h3>Data used in this tool</h3>

            <ul>
                <li><a href="https://www.ons.gov.uk/methodology/geography/geographicalproducts/postcodeproducts">ONS Postcode Directory (ONSPD)</a></li>
                <li><a href="https://www.gov.uk/government/statistics/english-indices-of-deprivation-2019">English Index of Multiple Deprivation 2019 (IMD)</a></li>
            </ul>
        </details>

        <form action="./index.php#data" method="get" class="flow">
            <label for="postcodes">
                Enter Postcodes<br>
                <span class="more-detail">Enter one postcode per line. Press the <i>Search IMD</i> button when ready to check them against the IMD.</span><br>
                <textarea id="postcodes" name="p" rows="6"><?php echo postcodesForTextarea(); ?></textarea><br>
            </label>
            <label for="decile">
                Max Decile
                <span class="more-detail">Enter a number between 1 and 10, with 1 being the bottom 10%, 2 the bottom 20% and so on. <strong>Leave blank to include all deciles.</strong></span>
                <input type="number" min="1" max="10" name="d" id="decile" value="<?php echo decileForInput(); ?>">
            </label>
            <button type="submit">Search IMD</button>
        </form><br>

        <?php if (!empty($_GET['p'])) : ?>

            <table id="data">
                <tr>
                    <th>Postcode</th>
                    <th>LSOA Name</th>
                    <th>IMD Rank</th>
                    <th>IMD Decile</th>
                </tr>

            <?php endif; ?>

            <?php

            if (!empty($_GET['p'])) {

                $fields_to_output = array('pcds', 'lsoa_name_11', 'imd_rank', 'imd_decile');

                $row_count = (int) $imd_data_count->fetchColumn();

                if ($row_count > 0) {
                    foreach ($imd_data as $row) {
                        echo outputTableRow($row, $fields_to_output);
                    }
                    echo '</table>';
                } else {
                    echo '<tr><td colspan="' . count($fields_to_output) . '">No results found.</td></tr></table>';
                }
            }
            ?>
    </main>

    <footer>
        <div class="footer-content">
            <p>The IMD Checker is a tiny thing made with <a href="https://pagespeed.web.dev/analysis/https-charlesroper-com-tools-imd/a7pvmpj83q">lean</a> but boring code, some open data, and plenty of ❤️.</p>
            <p>Originally made for the <a href="https://www.field-studies-council.org/">Field Studies Council</a>. Copyright &copy; <?php echo (new DateTime('now', new DateTimeZone('Europe/London')))->format('Y'); ?> Charles Roper. <a href="https://charlesroper.com/">Get in touch</a>.</p>
            <p>
                <svg viewBox="0 0 1065 214" xmlns="http://www.w3.org/2000/svg" style="width:200px; margin: 1.5em 0 0 -.7em;">

                    <path d="M226 88.1c-2.37.43-5.05.9-7 1.15-2.75.35-5.68.77-8.74 1.26-19.2 2.73-53.4 10.2-66.4 29.6a46.8 46.8 0 0 0-5.19 10 .2.2 0 0 0-.09.09s-.4 1.11-.9 3c-.27 1-.5 2-.73 3.15 0 .24-.09.49-.14.74v.16a3.38 3.38 0 0 1-.09.54c-.06.33-.12.67-.16 1a87.8 87.8 0 0 0-1 16.8c.08 2.94.27 5.38.41 7.35.51 6.79.67 9.08-4 13.2a5.91 5.91 0 0 0-.92.95l-9.76 8.76a.21.21 0 0 1-.29 0 .24.24 0 0 1-.05-.14v-49.2c.1-3.11.85-8.78 4.71-11.9 13.4-10.7 22.4-27.8 22.5-28a.17.17 0 0 0 0-.1 54.8 54.8 0 0 0 4.52-10.3c8.23-25.4-20.9-63.4-30-74.4l-1.31-1.65c-2.93-3.73-2.27-6.36-1.79-8.25a4.74 4.74 0 0 0 .26-1.71.18.18 0 0 0-.15-.18c-.29-.06-.81.63-3.78 4.81-1.37 2-2.92 4.19-4.15 5.78-1.7 2.19-3.43 4.49-5.3 7-11.7 15.5-30.5 45-26 67.9a47 47 0 0 0 3.66 11.3.17.17 0 0 0 0 .14 68.5 68.5 0 0 0 22.1 26.4c3.37 2.34 6.49 6.1 6.55 17.6v44.6a.31.31 0 0 1-.51.23l-6.45-5.67-3.38-3c-2.74-2.52-4.17-8.42-4.53-18.6a130 130 0 0 0-2.62-23.5 44.4 44.4 0 0 0-6.74-15.3c-13.1-19.4-47.3-26.9-66.4-29.6-3.07-.47-6-.89-8.73-1.26-2-.25-4.66-.73-7-1.15-5.08-.89-5.92-.99-6.14-.7a.24.24 0 0 0 0 .23 4.82 4.82 0 0 0 1.45 1c1.71 1 4 2.38 4.54 7.11l.24 2v.06c1.34 14.2 7.56 61.6 31.4 73.8a55.9 55.9 0 0 0 9.26 3.81c7.71 3.05 29.3 5.41 38.5 6.41 1.82.2 3.27.36 4 .46 3.83.5 6 1.93 11.9 5.76l2 1.26 5.83 5.1c.82.76 3.46 3.64 3.46 9.29v10.2a.17.17 0 0 0 .17.16h.11a18.4 18.4 0 0 1 3.78-.46 19.9 19.9 0 0 1 3.91.46.17.17 0 0 0 .14 0 .14.14 0 0 0 .06-.12v-9.94c.06-6.27 3.32-9.49 3.45-9.61l13-11.6c6-5.54 10.3-4.56 15.8-3.32l.63.13c16.3 3.67 31.8-2.73 39.6-6.86a5.45 5.45 0 0 1 .55-.25l.7-.32c23.8-12.2 30-59.6 31.4-73.8v-.07l.24-2c.55-4.73 2.86-6.1 4.55-7.1a4.73 4.73 0 0 0 1.39-1 .3.3 0 0 0 0-.23c-.17-.39-1.02-.29-6.07.6zM62 109.4a56.2 56.2 0 0 0-1.68 12.1l-19.4-19.4a120 120 0 0 1 21 7.24zM14.1 97.3a210 210 0 0 1 12.3 1.76l35 35a58.9 58.9 0 0 0 6.54 17.4 59.2 59.2 0 0 0-17-6.34l-35.2-35.1a197 197 0 0 1-1.7-12.8zm12 48.5a116 116 0 0 1-7.38-21.3l19.6 19.6a55.9 55.9 0 0 0-12.3 1.75zm59.1 27.6c-3.89 0-24-.38-40.8-8-1.26-.54-2.36-1-3.39-1.57-3.92-2-7.57-5.61-10.9-10.7a47.8 47.8 0 0 1 28.6 2.79c.73.25 17.8 6.2 26.8 17.5zM73.6 145a.12.12 0 0 0 0-.14 47.8 47.8 0 0 1-4.24-31.6 38.9 38.9 0 0 1 11.9 10.8 40.5 40.5 0 0 1 2.6 4.46v.05l.1.05c1.41 2.32 5.26 13.5 5.84 20.8s1.36 16.7 1.53 18.7a81.1 81.1 0 0 1-17.8-23.2zm58.7-105a116 116 0 0 1 9.9 20.2 55.3 55.3 0 0 0-9.9 7.44zm-31.5-.11v27.4a56.8 56.8 0 0 0-9.77-7.4 120 120 0 0 1 9.75-20zm11.8 77.9a57.7 57.7 0 0 1-21.7-24.3l-.39-.85-.11-.24-.09-.18a.11.11 0 0 0 0-.08 39.9 39.9 0 0 1-2.48-8.16c-.9-4.57-.63-10 .79-16a47.2 47.2 0 0 1 17.5 20.6.15.15 0 0 0 .13.1c.09.23.33.78.63 1.59l.26.67v.08c1.86 4.66 6.02 16.6 5.46 26.8zm-3.7-41v-49.4c3.24-4.59 6-8.15 7.46-9.94 2.52 3.08 5.25 6.62 7.84 10.2v49.6a59 59 0 0 0-7.56 16.5 58.7 58.7 0 0 0-7.74-17zm11.1 41c-.79-12.8 7-29.1 7.71-30.6a.17.17 0 0 0 .14-.09 47.1 47.1 0 0 1 16.7-18.9c1.26 6 1.23 11.1-.13 15.3a48.6 48.6 0 0 1-2.73 6.78.14.14 0 0 0 0 .09c-8.42 18.5-20 26.3-21.7 27.4zm70.4-15.2-19.4 19.4a56.9 56.9 0 0 0-1.67-12.1 121 121 0 0 1 21-7.23zm-47.2 63.3c-4.05-24 3.53-35.6 3.87-36.1a40.2 40.2 0 0 1 3-5.21c2.6-3.87 6.6-7.5 11.9-10.8a47.1 47.1 0 0 1-1.29 24.4.11.11 0 0 0 0 .08c-2.62 10.6-15.6 25.4-17.5 27.6zm47.1-1.52a49.5 49.5 0 0 1-6.37 2.71s-.06 0-.07.05c-14.3 5.82-32.8 4-35.6 3.74 7.08-6.46 17.7-11.6 23.4-14.1.58-.27 1.21-.54 1.89-.8l1.78-.71a.14.14 0 0 0 .16 0 47.2 47.2 0 0 1 25.7-1.7c-3.35 5.11-7 8.74-10.9 10.7zm15-18.1a56.4 56.4 0 0 0-12.3-1.74l19.5-19.6a115 115 0 0 1-7.32 21.3zm10.3-35.8-35.1 35.1a59.7 59.7 0 0 0-17 6.34 58.9 58.9 0 0 0 6.53-17.4l35-35c5.53-1 10-1.5 12.3-1.76-.38 4-1 8.37-1.69 12.8zm742-39.9 20.5-2.77q9.75-1.41 14.6-5.67t4.81-11.9a18.5 18.5 0 0 0-3.15-10.5 21.5 21.5 0 0 0-9.34-7.46 36.3 36.3 0 0 0-14.9-2.79 35 35 0 0 0-17.7 4.32 29.6 29.6 0 0 0-11.7 12.5 42 42 0 0 0-4.18 19.3 40.4 40.4 0 0 0 4.44 19.4 30.8 30.8 0 0 0 12.1 12.6 34.8 34.8 0 0 0 17.5 4.4 35.1 35.1 0 0 0 14.9-2.9 27.1 27.1 0 0 0 9.9-7.46 26.8 26.8 0 0 0 5-9.69 1.84 1.84 0 0 0 0-1.68 1.39 1.39 0 0 0-1.26-.69 1.72 1.72 0 0 0-1.54 1.12 25 25 0 0 1-7.39 8.64 18.7 18.7 0 0 1-11.2 3.21 18.4 18.4 0 0 1-10.7-3.28 22.3 22.3 0 0 1-6.09-6.45s-1.73-2.82-3.91-6.71c-1.37-2.4-3.38-4.1-6.75-3.71l-.12-.9zm0-25.2a18.2 18.2 0 0 1 4.32-8.36 9.4 9.4 0 0 1 7-3 8.92 8.92 0 0 1 8 4.3q2.79 4.34 2.79 11.4 0 6.3-2.92 10t-9.07 4.53l-11 1.52v-.22a68.3 68.3 0 0 1-.38-7.43 51.2 51.2 0 0 1 1.28-12.8zm-673 55.2h7.67a2.23 2.23 0 0 0 1.53-.48 1.69 1.69 0 0 0 .56-1.33 2.05 2.05 0 0 0-.49-1.4 5.87 5.87 0 0 0-1.46-1.11 14.6 14.6 0 0 1-4.89-5.16q-1.94-3.35-1.94-11.8v-24h12.7a26 26 0 0 1 6.7.7 10.1 10.1 0 0 1 4.32 2.44 16.6 16.6 0 0 1 3.35 5.23 8.28 8.28 0 0 0 1.6 1.89 2.44 2.44 0 0 0 1.61.63 1.88 1.88 0 0 0 1.6-.63 2 2 0 0 0 .34-1.6l-3.07-23.7c-.19-1.77-1-2.65-2.38-2.65a2 2 0 0 0-1.66.69 10.1 10.1 0 0 0-1.27 2.52 1.32 1.32 0 0 1-.13.56 1.2 1.2 0 0 0-.15.56 11.1 11.1 0 0 1-1.74 4.53 7 7 0 0 1-3.21 2.37 17 17 0 0 1-5.76.75h-12.8v-25.6a53.8 53.8 0 0 0-.56-9.14 5.64 5.64 0 0 0-2.23-3.9 10.1 10.1 0 0 0-5.16-1v-1.2h31.5a15.6 15.6 0 0 1 7.88 1.74 14 14 0 0 1 5 5.23 107 107 0 0 1 5.23 11l.29.7a10.6 10.6 0 0 0 1.39 1.95 2 2 0 0 0 1.53.55 1.94 1.94 0 0 0 1.68-.76 2.77 2.77 0 0 0 .41-2l-2.78-20.8a3.59 3.59 0 0 0-3.87-3.37h-64.7a2.34 2.34 0 0 0-1.46.49 1.61 1.61 0 0 0-.63 1.33 2 2 0 0 0 .49 1.39 5.06 5.06 0 0 0 1.6 1.11 13.9 13.9 0 0 1 4.81 5.17q1.89 3.35 1.89 11.8v55q0 8.51-1.89 11.8a13.9 13.9 0 0 1-4.81 5.16 5.25 5.25 0 0 0-1.6 1.12 2.06 2.06 0 0 0-.49 1.39 1.61 1.61 0 0 0 .63 1.33 2.27 2.27 0 0 0 1.46.48h23.4zm58-79.2a14.6 14.6 0 0 0 8.58 2.37 14.9 14.9 0 0 0 9-2.58 8 8 0 0 0 3.51-6.72 7.43 7.43 0 0 0-3.31-6.5 14.5 14.5 0 0 0-8.51-2.34 15.1 15.1 0 0 0-9.13 2.6 8 8 0 0 0-3.55 6.74 7.41 7.41 0 0 0 3.41 6.43zm25.7 79.2a2 2 0 0 0 1.33-.41 1.4 1.4 0 0 0 .48-1.12c0-.74-.63-1.4-1.81-2a11.2 11.2 0 0 1-4-4.41c-1-1.91-1.54-5.18-1.54-9.83V32.5a3.38 3.38 0 0 0-.69-2.3 2.34 2.34 0 0 0-1.82-.77 7.56 7.56 0 0 0-2.52.56q-6.69 2.79-18.3 3.78a2.59 2.59 0 0 0-1.33.56 1.6 1.6 0 0 0-.48 1.26 2.19 2.19 0 0 0 .56 1 5 5 0 0 0 1.26.83 10 10 0 0 1 4 4q1.61 2.76 1.6 10.2v31.3q0 7-1.53 9.83a11.4 11.4 0 0 1-4.05 4.41c-1.21.55-1.81 1.21-1.81 2a1.38 1.38 0 0 0 .49 1.12 2 2 0 0 0 1.32.41h28.9zm81.6-7.87a11.2 11.2 0 0 1-4 4.41c-1.2.55-1.81 1.21-1.81 2a1.41 1.41 0 0 0 .49 1.12 2 2 0 0 0 1.32.41h28.8a2 2 0 0 0 1.33-.41 1.4 1.4 0 0 0 .48-1.12c0-.74-.63-1.4-1.81-2a11.4 11.4 0 0 1-4.05-4.41c-1-1.91-1.53-5.18-1.53-9.83V3.07a3.31 3.31 0 0 0-.68-2.31A2.35 2.35 0 0 0 466.6 0a7.86 7.86 0 0 0-2.52.55q-6.69 2.79-18.3 3.78a2.68 2.68 0 0 0-1.31.55 1.54 1.54 0 0 0-.49 1.26 2 2 0 0 0 .56 1 4.48 4.48 0 0 0 1.26.84 10.1 10.1 0 0 1 4 4.05q1.64 2.78 1.63 10.2v60.7q-.03 6.91-1.58 9.78zm60.7 9.22q9.76 0 14.9-5.67a21.4 21.4 0 0 0 4.72-8.7v.69q.71 6.14 5 9.2t11.3 3.07h6.55a2 2 0 0 0 1.32-.42 1.4 1.4 0 0 0 .49-1.11c0-.75-.63-1.4-1.81-2a13.6 13.6 0 0 1-4-4.46c-1.08-1.86-1.61-5.11-1.61-9.76V3.16a3.63 3.63 0 0 0-.63-2.31 2.14 2.14 0 0 0-1.7-.76 6.79 6.79 0 0 0-2.59.55 35.6 35.6 0 0 1-7.15 2.33 90.7 90.7 0 0 1-10.5 1.3c-1.2.18-1.8.74-1.8 1.67a1.38 1.38 0 0 0 .55 1c.37.32.79.67 1.26 1a12 12 0 0 1 3.49 3.63q1.39 2.24 1.39 7.39v8.65a13.7 13.7 0 0 0 .91 5.58 13.6 13.6 0 0 0 2.88 3.96c.09 0 .14 0 .14.07s0 .07.14.07v1.19a25.7 25.7 0 0 0-20.5-9 30.2 30.2 0 0 0-16.1 4.33 29.2 29.2 0 0 0-11 12.5q-4 8.14-4 19.4a48 48 0 0 0 3.49 19 29 29 0 0 0 9.9 12.7 25.1 25.1 0 0 0 14.9 4.54zm-5.59-61.4q3.35-6 9.62-6 4.88 0 8.37 5a36.5 36.5 0 0 1 5.23 13.1 83.5 83.5 0 0 1 1.74 17.6 48.9 48.9 0 0 1-1.54 13.3 18 18 0 0 1-4.32 8.09 9.45 9.45 0 0 1-6.82 2.7q-4.87 0-8.43-4.88a34.8 34.8 0 0 1-5.37-13 81.1 81.1 0 0 1-1.84-17.6q0-12.3 3.34-18.3zm170 49.1a22.8 22.8 0 0 0 4.46-14 22.9 22.9 0 0 0-3.49-12.7 36.8 36.8 0 0 0-9.69-9.9 178 178 0 0 0-17.4-10.6 142 142 0 0 1-12.4-7.73 24.9 24.9 0 0 1-6.55-6.7 15 15 0 0 1-2.27-8.16 12.7 12.7 0 0 1 3.41-9q3.42-3.63 9.69-3.63a16.6 16.6 0 0 1 9.07 2.3 15.3 15.3 0 0 1 5.44 5.72 25 25 0 0 1 2.52 7.46 13.7 13.7 0 0 0 3 7.18 8.22 8.22 0 0 0 6.48 2.58 8.06 8.06 0 0 0 6.07-2.23q2.17-2.22 2.17-6.66a18.1 18.1 0 0 0-3.52-10.3q-3.46-5.09-10.7-8.43t-18-3.34q-10.2 0-17.3 3.41a25.5 25.5 0 0 0-10.7 8.93 21.9 21.9 0 0 0-3.56 12.1 22 22 0 0 0 3 11.3 34.5 34.5 0 0 0 9.45 9.91 121 121 0 0 0 17.5 10.4q11 5.73 16 11.6t5.05 12.4a14.1 14.1 0 0 1-4.19 10.6q-4.18 4-11.4 4a18.2 18.2 0 0 1-9.62-2.37 16.7 16.7 0 0 1-6.06-6.3 23.1 23.1 0 0 1-2.69-8.61 17.8 17.8 0 0 0-3.79-8.93 9.31 9.31 0 0 0-7.39-3.2 8.5 8.5 0 0 0-6.69 2.64 10 10 0 0 0-2.37 7 20.8 20.8 0 0 0 4.1 11.9q4.11 5.93 12.3 9.76t19.6 3.81a48.3 48.3 0 0 0 18.1-3.15 28.3 28.3 0 0 0 12.4-9.07zm55.9-11.7a1.3 1.3 0 0 0-1 .49 2.61 2.61 0 0 0-.56 1q-.27 6.14-2.23 9.34a6.57 6.57 0 0 1-6 3.21q-4.74 0-7-6t-2.31-17.1v-21.5c0-4.28.79-7.31 2.37-9.07s4.18-2.65 7.8-2.65h4.75a2.28 2.28 0 0 0 1.89-.77 2.67 2.67 0 0 0 .63-1.73 2.25 2.25 0 0 0-.7-1.75 2.61 2.61 0 0 0-1.81-.63h-14.9V8.86a2.69 2.69 0 0 0-.7-1.89 2.16 2.16 0 0 0-1.67-.76 2.46 2.46 0 0 0-2.4 1.98 45.1 45.1 0 0 1-4.95 13.1 23.3 23.3 0 0 1-6.4 7.19 12.2 12.2 0 0 1-6.93 2.3h-3.21a2.61 2.61 0 0 0-1.81.63 2.22 2.22 0 0 0-.7 1.75 2.49 2.49 0 0 0 .7 1.73 2.28 2.28 0 0 0 1.81.77h15.9v1q-3.9 0-5.67 2.37c-1.17 1.58-1.75 4.32-1.72 8.23v24.2q0 14.8 5.23 22.6t15.8 7.76q8.64 0 13.1-5.85t4.28-16.2a2.26 2.26 0 0 0-.41-1.4 1.35 1.35 0 0 0-1.12-.54zm80.8 19.1a13.8 13.8 0 0 1-4-4.46c-1.04-1.86-1.62-5.12-1.62-9.71v-50.4a3.54 3.54 0 0 0-.63-2.3 2.14 2.14 0 0 0-1.75-.77 6.69 6.69 0 0 0-2.52.56q-6.69 2.79-18.4 3.78a2.59 2.59 0 0 0-1.33.56 1.26 1.26 0 0 0-.35 1.26 2.19 2.19 0 0 0 .56 1 5.7 5.7 0 0 0 1.12.83 9.49 9.49 0 0 1 4 4c1 1.86 1.53 5.25 1.54 10.2v20.6q0 10.6-2.66 16t-9.58 5.34a10.3 10.3 0 0 1-7.67-3.56q-3.33-3.57-5.19-10.2a60.1 60.1 0 0 1-1.89-16.2v-31.1a3.29 3.29 0 0 0-.7-2.3 2.31 2.31 0 0 0-1.81-.77 7.5 7.5 0 0 0-2.52.56q-6.69 2.79-18.3 3.78a2.6 2.6 0 0 0-1.32.56 1.54 1.54 0 0 0-.49 1.26 2.1 2.1 0 0 0 .56 1 4.83 4.83 0 0 0 1.26.83 10.2 10.2 0 0 1 4 4c1.06 1.86 1.6 5.25 1.6 10.2v17.9q0 10.3 3.2 17.6a25.3 25.3 0 0 0 8.93 11.1 23 23 0 0 0 13.1 3.83q10.2 0 15.1-5.67a23.3 23.3 0 0 0 4.84-8.67c.71 4.33 2.34 7.56 4.92 9.65s6.5 3.28 11.4 3.27h6.6a2 2 0 0 0 1.33-.41 1.4 1.4 0 0 0 .48-1.12c-.03-.74-.64-1.4-1.83-1.95zm78.4 0a13.8 13.8 0 0 1-4-4.46c-1.07-1.86-1.61-5.12-1.61-9.76V3a3.57 3.57 0 0 0-.59-2.31 2.14 2.14 0 0 0-1.74-.76 6.83 6.83 0 0 0-2.52.55 35.6 35.6 0 0 1-7.19 2.33 90.6 90.6 0 0 1-10.5 1.3c-1.21.18-1.83.7-1.83 1.7a1.41 1.41 0 0 0 .55 1c.37.32.79.67 1.26 1a12 12 0 0 1 3.49 3.63c.92 1.49 1.39 4 1.39 7.39v8.65a13.5 13.5 0 0 0 .91 5.58 13.7 13.7 0 0 0 2.85 3.9c.09 0 .14 0 .14.07s0 .07.14.07v1.19a25.6 25.6 0 0 0-20.5-9 30.2 30.2 0 0 0-16.1 4.33 29.2 29.2 0 0 0-11 12.5q-4 8.18-4 19.5a48.2 48.2 0 0 0 3.49 19 29 29 0 0 0 9.9 12.7 25.2 25.2 0 0 0 15 4.56q9.76 0 14.9-5.67a21.5 21.5 0 0 0 4.73-8.7v.69q.69 6.14 5 9.2c2.83 2 6.62 3.06 11.3 3.07h6.52a2 2 0 0 0 1.33-.42 1.39 1.39 0 0 0 .48-1.11c.01-.68-.59-1.34-1.81-1.89zm-24.7-13.5a18.1 18.1 0 0 1-4.33 8.09 9.43 9.43 0 0 1-6.84 2.71q-4.87 0-8.43-4.87a34.5 34.5 0 0 1-5.37-13 81.1 81.1 0 0 1-1.8-17.6q0-12.3 3.35-18.3t9.62-6q4.88 0 8.37 5a36.5 36.5 0 0 1 5.22 13.1 83.6 83.6 0 0 1 1.75 17.6 48.8 48.8 0 0 1-1.54 13.3zm64.5 13.5a11.3 11.3 0 0 1-4.05-4.41c-1-1.91-1.53-5.18-1.53-9.83v-50.3a3.24 3.24 0 0 0-.7-2.3 2.31 2.31 0 0 0-1.81-.77 7.5 7.5 0 0 0-2.52.56q-6.69 2.79-18.3 3.78a2.6 2.6 0 0 0-1.32.56 1.54 1.54 0 0 0-.49 1.26 2.19 2.19 0 0 0 .56 1 4.83 4.83 0 0 0 1.26.83 10.1 10.1 0 0 1 4 4c1.06 1.86 1.6 5.25 1.6 10.2v31.3c0 4.65-.51 7.92-1.53 9.83a11.3 11.3 0 0 1-4 4.41c-1.21.56-1.81 1.21-1.81 2a1.37 1.37 0 0 0 .49 1.11 2 2 0 0 0 1.32.42h28.9a2 2 0 0 0 1.32-.42 1.44 1.44 0 0 0 .49-1.11c-.06-.86-.71-1.52-1.87-2.07zm-25.7-75.7a14.6 14.6 0 0 0 8.58 2.37 14.9 14.9 0 0 0 9-2.58 8 8 0 0 0 3.55-6.72 7.41 7.41 0 0 0-3.35-6.5 14.6 14.6 0 0 0-8.5-2.32 15.2 15.2 0 0 0-9.14 2.58 8 8 0 0 0-3.55 6.74 7.4 7.4 0 0 0 3.41 6.43zm159 50.7a22.2 22.2 0 0 0-5.93-6.21 79.8 79.8 0 0 0-9.55-5.57q-2-.84-4.74-2.38a81.6 81.6 0 0 1-13-7.95q-4.14-3.35-4.18-7.81a7.53 7.53 0 0 1 2.44-5.93 9.51 9.51 0 0 1 6.48-2.16 10 10 0 0 1 7.19 2.58 11.3 11.3 0 0 1 3.27 7 11.6 11.6 0 0 0 3.18 6.29 8.62 8.62 0 0 0 6.35 2.44 6.82 6.82 0 0 0 5.3-1.95 7.17 7.17 0 0 0 1.81-5 12.4 12.4 0 0 0-2.86-7.81 19.9 19.9 0 0 0-8.5-5.86 36.7 36.7 0 0 0-13.5-2.23 37.5 37.5 0 0 0-14 2.44 21.1 21.1 0 0 0-9.41 6.84 16.3 16.3 0 0 0-3.28 10 15.6 15.6 0 0 0 4.68 11.5q4.66 4.66 15 10.2l.55.27a96.5 96.5 0 0 1 9.48 5.8 24.8 24.8 0 0 1 5.73 5.43 10.5 10.5 0 0 1 2.08 6.35 8.22 8.22 0 0 1-2.85 6.69 11.3 11.3 0 0 1-7.46 2.37 10.8 10.8 0 0 1-7.89-3.07q-3.15-3.06-3.55-8.78a11.1 11.1 0 0 0-3.07-6.56 8.5 8.5 0 0 0-6.3-2.52 8 8 0 0 0-5.78 2.1 7.39 7.39 0 0 0-2.17 5.57 13.6 13.6 0 0 0 3.56 9.2 23.6 23.6 0 0 0 9.83 6.35 41 41 0 0 0 14.2 2.3 40.1 40.1 0 0 0 14.5-2.58 25.3 25.3 0 0 0 10.7-7.54 16.7 16.7 0 0 0 4-11 16.3 16.3 0 0 0-2.36-8.86zm-728 112a1.9 1.9 0 0 0-1.39.84 36.1 36.1 0 0 1-5.23 8.29 30.5 30.5 0 0 1-9.55 7.6 29 29 0 0 1-14 3.21 29.5 29.5 0 0 1-17.7-5.67 37.7 37.7 0 0 1-12.3-16 58.8 58.8 0 0 1-4.46-23.6 65.9 65.9 0 0 1 3.42-21.9q3.42-9.76 9.83-15.3a22.1 22.1 0 0 1 14.9-5.58q10.6 0 15.1 6.35a24.8 24.8 0 0 1 4.51 14.9 10 10 0 0 0 1.81 6.35 6.19 6.19 0 0 0 5.16 2.3 6.05 6.05 0 0 0 5-2.16 8.13 8.13 0 0 0 1.74-5.23 25.7 25.7 0 0 0-3.15-12 26.4 26.4 0 0 0-10.1-10.2q-6.93-4.19-18-4.19a40.2 40.2 0 0 0-22.2 6.35 42.2 42.2 0 0 0-15.3 18.1q-5.5 11.8-5.51 27.5 0 15.4 5.67 26.3a40.7 40.7 0 0 0 15 16.6 38.3 38.3 0 0 0 20.2 5.67q11.4 0 19.4-4.41a37 37 0 0 0 12.3-10.4 36.2 36.2 0 0 0 5.83-11.8 1.62 1.62 0 0 0 0-1.33 1 1 0 0 0-1-.63zm55.8-40.2a32.1 32.1 0 0 0-15.8-3.91 29.3 29.3 0 0 0-16.1 4.6 31.4 31.4 0 0 0-11.3 13.1 45.3 45.3 0 0 0-4.1 19.8q0 11.3 4.05 19.2a27.7 27.7 0 0 0 11.1 11.8 31.7 31.7 0 0 0 15.8 4 29.3 29.3 0 0 0 16.2-4.67 31.5 31.5 0 0 0 11.3-13.2 45.5 45.5 0 0 0 4.11-19.9q0-11.3-4.11-19.1a27.7 27.7 0 0 0-11.1-11.7zm-2.44 57.8q-4.89 7.47-13.1 7.46-5.72 0-10-4.88t-6.62-13.2a71 71 0 0 1-2.3-18.9q0-14.2 4.87-21.6t13.1-7.32q5.57 0 9.9 4.81t6.69 13.2a67.7 67.7 0 0 1 2.41 18.6q0 14.4-4.9 21.8zm92 7.03a9.47 9.47 0 0 1-3.84-3.84q-1.47-2.58-1.46-9.69v-53.1a2.11 2.11 0 0 0-.56-1.53 1.74 1.74 0 0 0-1.26-.56 4.62 4.62 0 0 0-2 .56 39.6 39.6 0 0 1-13.1 2.92 1.42 1.42 0 0 0-.91.29 1 1 0 0 0-.35.83 1.45 1.45 0 0 0 .49.7 4.62 4.62 0 0 0 .9.55 8.59 8.59 0 0 1 3.84 3.7q1.61 2.73 1.6 10.5v21.3q0 11.7-3.27 18.3t-12.8 6.63a13.9 13.9 0 0 1-9.41-3.63q-4.12-3.63-6.49-10.5a51.6 51.6 0 0 1-2.37-16.5v-33.6a2.23 2.23 0 0 0-.49-1.53 1.66 1.66 0 0 0-1.32-.56 4.48 4.48 0 0 0-1.95.56 39.7 39.7 0 0 1-13.1 2.97 1.42 1.42 0 0 0-.91.29.72.72 0 0 0-.2.83.84.84 0 0 0 .34.7 4.69 4.69 0 0 0 .91.55 8.1 8.1 0 0 1 3.9 3.7q1.54 2.73 1.54 10.5v18.1q0 11.3 3.34 19t9.07 11.5a23 23 0 0 0 13 3.78q10.9 0 15.5-6.3a27.3 27.3 0 0 0 4.67-9.75v2.22q.56 6.71 3.91 9.55t9.34 2.86h3.62a1.16 1.16 0 0 0 .84-.28 1 1 0 0 0 .28-.7c-.02-.55-.46-1.01-1.41-1.4zm78.7 0a8.56 8.56 0 0 1-3.84-3.78c-1-1.76-1.46-5-1.46-9.76v-27.4q0-14.1-5.37-20.9t-16.4-6.83a21.8 21.8 0 0 0-13.3 4 26.1 26.1 0 0 0-8 9.27 30.8 30.8 0 0 0-2.52 6.61v-17.8a2.23 2.23 0 0 0-.49-1.53 1.66 1.66 0 0 0-1.32-.56 4.48 4.48 0 0 0-1.95.56 39.7 39.7 0 0 1-13.1 2.97 1.42 1.42 0 0 0-.91.29.72.72 0 0 0-.2.83.84.84 0 0 0 .34.7 4.69 4.69 0 0 0 .91.55 8.1 8.1 0 0 1 3.92 3.63q1.54 2.73 1.54 10.5v35.1q0 7.11-1.54 9.76a9 9 0 0 1-3.9 3.78c-.84.37-1.26.84-1.26 1.39a.93.93 0 0 0 .28.7 1.15 1.15 0 0 0 .83.28h22.4a1.16 1.16 0 0 0 .84-.28.92.92 0 0 0 .28-.7c0-.55-.42-1-1.26-1.39a9 9 0 0 1-3.9-3.78q-1.55-2.65-1.53-9.76V172a32.9 32.9 0 0 1 2.52-12.8 24.5 24.5 0 0 1 7.12-9.81 16.1 16.1 0 0 1 10.5-3.78q14.2 0 14.2 19.1v30.4q0 7.13-1.53 9.76a8.85 8.85 0 0 1-3.9 3.78c-.94.38-1.4.84-1.4 1.4a.89.89 0 0 0 .35.7 1.4 1.4 0 0 0 .91.27h22.5a1.12 1.12 0 0 0 .84-.27 1 1 0 0 0 .27-.7c-.03-.58-.49-1.04-1.4-1.43zm65.8-18.8a1.27 1.27 0 0 0-1.11.83 24.5 24.5 0 0 1-7.67 11 19.6 19.6 0 0 1-12.7 4.11 21.2 21.2 0 0 1-12.8-4 25.8 25.8 0 0 1-8.65-11.8 49.6 49.6 0 0 1-3.07-18.3 45.8 45.8 0 0 1 2.3-15.6 21 21 0 0 1 6.35-9.49 14.8 14.8 0 0 1 9.48-3.2 11.9 11.9 0 0 1 9.76 4.46q3.63 4.45 3.49 12.7a7.56 7.56 0 0 0 1.6 5.09 5.47 5.47 0 0 0 4.41 1.89 5.93 5.93 0 0 0 4.41-1.61 6.5 6.5 0 0 0 1.6-4.8 19.7 19.7 0 0 0-11.6-18.3 31.2 31.2 0 0 0-40.5 14.3 42.3 42.3 0 0 0-4 19q0 11.6 4.18 19.7a29.6 29.6 0 0 0 11.4 12.4 33.9 33.9 0 0 0 30.2 1.36 24.2 24.2 0 0 0 9.2-7.68 30.5 30.5 0 0 0 4.74-10.3 1.19 1.19 0 0 0-.07-1.18 1 1 0 0 0-.94-.46zm29.6 18.8a8.8 8.8 0 0 1-3.9-3.78c-1-1.76-1.55-5-1.54-9.76v-53.1a2.23 2.23 0 0 0-.49-1.53 1.66 1.66 0 0 0-1.32-.56 4.44 4.44 0 0 0-2 .56 39.7 39.7 0 0 1-13.1 2.97 1.42 1.42 0 0 0-.91.29.74.74 0 0 0-.21.83.88.88 0 0 0 .35.7 4.69 4.69 0 0 0 .91.55 8.1 8.1 0 0 1 3.9 3.7q1.54 2.73 1.54 10.5v35.1c0 4.74-.52 8-1.54 9.76a9 9 0 0 1-3.9 3.78c-.84.37-1.26.84-1.26 1.39a.93.93 0 0 0 .28.7 1.14 1.14 0 0 0 .83.28h22.4a1.18 1.18 0 0 0 .84-.28 1 1 0 0 0 .28-.7c.01-.62-.37-1.08-1.25-1.47zm-13.4-75.4a10 10 0 0 0 6.62-2.16 6.75 6.75 0 0 0 2.58-5.37 6.33 6.33 0 0 0-2.52-5.3 10 10 0 0 0-6.3-2 10.2 10.2 0 0 0-6.62 2.1 6.66 6.66 0 0 0-2.58 5.43 6.4 6.4 0 0 0 2.52 5.23 9.7 9.7 0 0 0 6.3 2.07zm46.4 75.4a8.82 8.82 0 0 1-3.91-3.78c-1-1.76-1.53-5-1.53-9.76v-82.5a2.29 2.29 0 0 0-.49-1.54 1.69 1.69 0 0 0-1.33-.56 4.62 4.62 0 0 0-1.95.56 39.9 39.9 0 0 1-13.1 2.93 1.34 1.34 0 0 0-.9.28.73.73 0 0 0-.21.83.85.85 0 0 0 .34.7 5.71 5.71 0 0 0 .91.56 8 8 0 0 1 3.91 3.7q1.53 2.72 1.53 10.5v64.6q0 7.11-1.53 9.76a9 9 0 0 1-3.91 3.78c-.83.37-1.26.84-1.26 1.39a1 1 0 0 0 .28.7 1.16 1.16 0 0 0 .84.28h22.4a1.18 1.18 0 0 0 .84-.28 1 1 0 0 0 .27-.7c.01-.57-.41-1.03-1.24-1.42zm-290-139 20.5-2.77q9.76-1.41 14.6-5.67t4.82-11.9a18.5 18.5 0 0 0-3.15-10.5 21.5 21.5 0 0 0-9.35-7.46 36.3 36.3 0 0 0-14.8-2.79 35 35 0 0 0-17.7 4.32 29.6 29.6 0 0 0-11.7 12.5q-4.17 8.19-4.17 19.3a40.4 40.4 0 0 0 4.41 19.3 30.8 30.8 0 0 0 12.1 12.6 34.7 34.7 0 0 0 17.5 4.41 35.1 35.1 0 0 0 14.9-2.92 27.1 27.1 0 0 0 9.9-7.46 26.7 26.7 0 0 0 5-9.69 1.73 1.73 0 0 0 0-1.68 1.35 1.35 0 0 0-1.26-.69 1.71 1.71 0 0 0-1.53 1.12 25.2 25.2 0 0 1-7.39 8.64 18.8 18.8 0 0 1-11.2 3.21 18.4 18.4 0 0 1-10.7-3.29 22 22 0 0 1-6.03-6.41s-1.72-2.81-3.93-6.71c-1.37-2.4-3.38-4.1-6.75-3.71l-.12-.9zm0-25.2a18.4 18.4 0 0 1 4.32-8.36 9.4 9.4 0 0 1 7-3 8.9 8.9 0 0 1 8 4.3q2.79 4.34 2.79 11.4 0 6.3-2.93 10t-9.07 4.53l-11 1.52v-.22a65.9 65.9 0 0 1-.39-7.43 50.7 50.7 0 0 1 1.35-12.8z" fill="#fff" />
                </svg>
            </p>

            <p class="footer-separate github">
                <a href="https://github.com/charlesroper/IMD-Postcode-Checker">
                    <svg xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="2" clip-rule="evenodd" viewBox="0 0 136 133">
                        <path fill="#fff" d="M68 0a68 68 0 00-22 132c4 1 5-1 5-3v-12c-19 5-23-9-23-9-3-7-7-10-7-10-7-4 0-4 0-4 7 1 10 7 10 7 6 11 16 8 20 6l4-9c-15-2-30-8-30-34 0-7 2-13 7-18-1-2-3-9 0-18 0 0 6-2 19 7a65 65 0 0134 0c13-9 19-7 19-7 3 9 1 16 0 18 5 5 7 11 7 18 0 26-16 32-31 34 3 2 5 6 5 12v19c0 2 1 4 4 3A68 68 0 0068 0" />
                    </svg>Source available on GitHub.
                </a>
            </p>

            <p class="footer-separate">Contains OS data &copy; Crown copyright and database rights <?php echo $current_year; ?></p>
            <p>Contains Royal Mail data © Royal Mail copyright and database rights <?php echo $current_year; ?></p>
            <p>Contains National Statistics data © Crown copyright and database rights <?php echo $current_year; ?></p>

            <p class="footer-separate">Made in The United Kingdom.</p>
            <div class="flags">
                <svg xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="2" clip-rule="evenodd" viewBox="0 0 512 342">
                    <path fill="#f0f0f0" d="M0 0h512v341H0z" />
                    <path fill="#d80027" fill-rule="nonzero" d="M288 0h-64v139H0v64h224v138h64V203h224v-64H288V0z" />
                    <path fill="#039" fill-rule="nonzero" d="M394 230l118 66v-66H394z" />
                    <path fill="#0052b4" fill-rule="nonzero" d="M312 230l200 111v-31l-144-80h-56z" />
                    <path fill="#039" fill-rule="nonzero" d="M459 341l-147-81v81h147z" />
                    <path fill="#f0f0f0" fill-rule="nonzero" d="M312 230l200 111v-31l-144-80h-56z" />
                    <path fill="#d80027" fill-rule="nonzero" d="M312 230l200 111v-31l-144-80h-56z" />
                    <path fill="#039" fill-rule="nonzero" d="M90 230L0 280v-50h90zM200 244v97H25l175-97z" />
                    <path fill="#d80027" fill-rule="nonzero" d="M144 230L0 310v31l200-111h-56z" />
                    <path fill="#039" fill-rule="nonzero" d="M118 111L0 46v65h118z" />
                    <path fill="#0052b4" fill-rule="nonzero" d="M200 111L0 0v31l144 80h56z" />
                    <path fill="#039" fill-rule="nonzero" d="M53 0l147 82V0H53z" />
                    <path fill="#f0f0f0" fill-rule="nonzero" d="M200 111L0 0v31l144 80h56z" />
                    <path fill="#d80027" fill-rule="nonzero" d="M200 111L0 0v31l144 80h56z" />
                    <path fill="#039" fill-rule="nonzero" d="M422 111l90-50v50h-90zM312 97V0h175L312 97z" />
                    <path fill="#d80027" fill-rule="nonzero" d="M368 111l144-80V0L312 111h56z" />
                </svg>
            </div>
        </div>
    </footer>

</body>

</html>
