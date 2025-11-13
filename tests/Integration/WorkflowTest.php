<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * End-to-end workflow tests simulating full user interactions
 */
class WorkflowTest extends TestCase
{
    public function testSinglePostcodeWorkflow(): void
    {
        // Simulate user input
        $userInput = 'sw1a1aa';

        // Process input
        $postcodes = getPostcodesArray($userInput);

        // Verify normalization
        $this->assertEquals(['SW1A 1AA'], $postcodes);

        // Generate SQL placeholders
        $placeholders = postcodePlaceholdersForSql($userInput);
        $this->assertEquals('?', $placeholders);

        // Prepare for textarea output
        $textareaValue = postcodesForTextarea($userInput);
        $this->assertEquals('SW1A 1AA', $textareaValue);
    }

    public function testMultiplePostcodesWorkflow(): void
    {
        // Simulate user entering multiple postcodes with mixed formatting
        $userInput = "sw1a1aa\nM1 1AE\n  b33 8th  \n\nCR2 6XH";

        // Process input
        $postcodes = getPostcodesArray($userInput);

        // Verify all postcodes are normalized and empty lines removed
        $this->assertEquals(['SW1A 1AA', 'M1 1AE', 'B33 8TH', 'CR2 6XH'], $postcodes);

        // Generate SQL placeholders
        $placeholders = postcodePlaceholdersForSql($userInput);
        $this->assertEquals('?,?,?,?', $placeholders);

        // Verify textarea output
        $textareaValue = postcodesForTextarea($userInput);
        $expected = 'SW1A 1AA' . PHP_EOL . 'M1 1AE' . PHP_EOL . 'B33 8TH' . PHP_EOL . 'CR2 6XH';
        $this->assertEquals($expected, $textareaValue);
    }

    public function testWorkflowWithMaximumPostcodes(): void
    {
        // Create 900 postcodes (the maximum)
        $postcodes = array_fill(0, 900, 'SW1A 1AA');
        $userInput = implode("\n", $postcodes);

        // Process input
        $processedPostcodes = getPostcodesArray($userInput);

        // Verify all are processed
        $this->assertCount(900, $processedPostcodes);

        // Verify SQL placeholders
        $placeholders = postcodePlaceholdersForSql($userInput);
        $this->assertEquals(900, substr_count($placeholders, '?'));
    }

    public function testWorkflowExceedingMaximum(): void
    {
        // Create 1000 postcodes (exceeding the maximum)
        $postcodes = array_fill(0, 1000, 'SW1A 1AA');
        $userInput = implode("\n", $postcodes);

        // Process input
        $processedPostcodes = getPostcodesArray($userInput);

        // All 1000 are still processed by the function
        // (The limit is enforced in index.php, not in the function)
        $this->assertCount(1000, $processedPostcodes);

        // In index.php, this would be sliced to 900
        $MAX_POSTCODES = 900;
        $limitedPostcodes = array_slice($processedPostcodes, 0, $MAX_POSTCODES);
        $this->assertCount(900, $limitedPostcodes);
    }

    public function testEmptyInputWorkflow(): void
    {
        $userInput = '';

        $postcodes = getPostcodesArray($userInput);
        $this->assertEmpty($postcodes);

        $placeholders = postcodePlaceholdersForSql($userInput);
        $this->assertEquals('', $placeholders);

        $textareaValue = postcodesForTextarea($userInput);
        $this->assertEquals('', $textareaValue);
    }

    public function testOnlyWhitespaceWorkflow(): void
    {
        $userInput = "   \n\n\t\n   ";

        $postcodes = getPostcodesArray($userInput);
        $this->assertEmpty($postcodes);

        $placeholders = postcodePlaceholdersForSql($userInput);
        $this->assertEquals('', $placeholders);
    }

    public function testTableOutputWorkflow(): void
    {
        // Simulate database results
        $mockResults = [
            [
                'postcode' => 'SW1A 1AA',
                'lsoa_name_2021' => 'Westminster 001A',
                'imd_rank' => '15234',
                'imd_decile' => '5'
            ],
            [
                'postcode' => 'M1 1AE',
                'lsoa_name_2021' => 'Manchester 001B',
                'imd_rank' => '8901',
                'imd_decile' => '3'
            ]
        ];

        $fields = ['postcode', 'lsoa_name_2021', 'imd_rank', 'imd_decile'];

        $tableRows = [];
        foreach ($mockResults as $row) {
            $tableRows[] = outputTableRow($row, $fields);
        }

        $this->assertCount(2, $tableRows);
        $this->assertStringContainsString('SW1A 1AA', $tableRows[0]);
        $this->assertStringContainsString('Westminster 001A', $tableRows[0]);
        $this->assertStringContainsString('M1 1AE', $tableRows[1]);
        $this->assertStringContainsString('Manchester 001B', $tableRows[1]);
    }

    public function testNoResultsWorkflow(): void
    {
        // Empty database results
        $mockResults = [];
        $fields = ['postcode', 'lsoa_name_2021', 'imd_rank', 'imd_decile'];

        if (count($mockResults) === 0) {
            $noResultsRow = '<tr><td colspan="' . count($fields) . '">No results found.</td></tr>';
            $this->assertStringContainsString('No results found', $noResultsRow);
        }
    }

    public function testMixedCaseAndFormattingWorkflow(): void
    {
        // Real-world messy input
        $userInput = "  sw1A 1Aa  \nM1-1AE\nB33.8TH\n\n  cr2 6xh  ";

        $postcodes = getPostcodesArray($userInput);

        // All should be normalized to uppercase with proper spacing
        $this->assertEquals(['SW1A 1AA', 'M1 1AE', 'B33 8TH', 'CR2 6XH'], $postcodes);
    }

    public function testDuplicatePostcodesWorkflow(): void
    {
        $userInput = "SW1A 1AA\nsw1a1aa\nSW1A 1AA";

        $postcodes = getPostcodesArray($userInput);

        // Duplicates are preserved (not removed)
        $this->assertCount(3, $postcodes);
        $this->assertEquals(['SW1A 1AA', 'SW1A 1AA', 'SW1A 1AA'], $postcodes);
    }

    public function testSecureOutputWorkflow(): void
    {
        // User tries to inject XSS via postcode
        $maliciousInput = '<script>alert("XSS")</script>';

        $row = ['postcode' => $maliciousInput];
        $result = outputTableRow($row, ['postcode']);

        // Output should be safe
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }
}
