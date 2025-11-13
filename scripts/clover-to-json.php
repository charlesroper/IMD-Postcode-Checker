<?php
declare(strict_types=1);

// Usage:
// php scripts/clover-to-json.php coverage/clover.xml [--top N] [--min-percent P]
// Outputs compact JSON to stdout. Defaults: top=0 (all files), min-percent=0.

$argv0 = $argv[0] ?? 'clover-to-json.php';
$path = $argv[1] ?? 'coverage/clover.xml';

$top = 0;
$minPercent = 0.0;
for ($i = 2; $i < count($argv); $i++) {
    if ($argv[$i] === '--top' && isset($argv[$i+1])) { $top = (int)$argv[++$i]; continue; }
    if ($argv[$i] === '--min-percent' && isset($argv[$i+1])) { $minPercent = (float)$argv[++$i]; continue; }
}

if (!is_readable($path)) {
    fwrite(STDERR, "File not readable: $path\n");
    exit(2);
}

$xml = @simplexml_load_file($path);
if ($xml === false) {
    fwrite(STDERR, "Failed to parse XML: $path\n");
    exit(3);
}

$summary = [
    'generated' => isset($xml['generated']) ? (string)$xml['generated'] : null,
    'totals' => null,
    'files' => [],
];

$projMetrics = $xml->project->metrics ?? null;
if ($projMetrics) {
    $a = $projMetrics->attributes();
    $statements = intval($a['statements'] ?? 0);
    $covered = intval($a['coveredstatements'] ?? ($a['coveredelements'] ?? 0));
    $summary['totals'] = [
        'statements' => $statements,
        'covered_statements' => $covered,
        'coverage_percent' => $statements > 0 ? round(($covered / $statements) * 100, 2) : null,
    ];
}

// Collect file metrics and uncovered lines
$files = $xml->xpath('//file');
foreach ($files as $file) {
    $name = (string)$file['name'];
    $m = $file->metrics ?? null;
    $statements = 0;
    $covered = 0;
    if ($m) {
        $a = $m->attributes();
        $statements = intval($a['statements'] ?? 0);
        $covered = intval($a['coveredstatements'] ?? ($a['coveredelements'] ?? 0));
    }
    $percent = $statements > 0 ? round(($covered / $statements) * 100, 2) : null;
    
    // Collect uncovered lines
    $uncoveredLines = [];
    $lines = $file->xpath('.//line[@type="stmt" and @count="0"]');
    foreach ($lines as $line) {
        $lineNum = (int)$line['num'];
        if ($lineNum > 0) {
            $uncoveredLines[] = $lineNum;
        }
    }
    sort($uncoveredLines);
    
    $summary['files'][] = [
        'file' => $name,
        'statements' => $statements,
        'covered_statements' => $covered,
        'coverage_percent' => $percent,
        'uncovered_lines' => $uncoveredLines,
    ];
}

// Optionally filter and sort
usort($summary['files'], static function ($a, $b) {
    $pa = $a['coverage_percent'] ?? -1;
    $pb = $b['coverage_percent'] ?? -1;
    if ($pa === $pb) return 0;
    if ($pa === null) return 1;
    if ($pb === null) return -1;
    return $pa < $pb ? -1 : 1;
});

if ($minPercent > 0) {
    $summary['files'] = array_values(array_filter($summary['files'], function ($f) use ($minPercent) {
        return ($f['coverage_percent'] ?? 0) < $minPercent;
    }));
}

if ($top > 0 && count($summary['files']) > $top) {
    $summary['files'] = array_slice($summary['files'], 0, $top);
}

echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
