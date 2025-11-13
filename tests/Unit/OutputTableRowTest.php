<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class OutputTableRowTest extends TestCase
{
    public function testOutputsValidRow(): void
    {
        $row = [
            'postcode' => 'SW1A 1AA',
            'lsoa_name_2021' => 'Westminster 001A',
            'imd_rank' => '15234',
            'imd_decile' => '5'
        ];
        $fields = ['postcode', 'lsoa_name_2021', 'imd_rank', 'imd_decile'];

        $result = outputTableRow($row, $fields);

        $this->assertStringContainsString('<tr>', $result);
        $this->assertStringContainsString('</tr>', $result);
        $this->assertStringContainsString('<td>SW1A 1AA</td>', $result);
        $this->assertStringContainsString('<td>Westminster 001A</td>', $result);
        $this->assertStringContainsString('<td>15234</td>', $result);
        $this->assertStringContainsString('<td>5</td>', $result);
    }

    public function testHandlesMissingFields(): void
    {
        $row = ['postcode' => 'SW1A 1AA'];
        $fields = ['postcode', 'lsoa_name_2021', 'imd_rank'];

        $result = outputTableRow($row, $fields);

        $this->assertStringContainsString('<td>SW1A 1AA</td>', $result);
        $this->assertStringContainsString('<td></td>', $result);
    }

    public function testEscapesHtmlEntities(): void
    {
        $row = ['postcode' => 'SW1A<script>alert("XSS")</script> 1AA'];
        $fields = ['postcode'];

        $result = outputTableRow($row, $fields);

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    public function testEscapesQuotes(): void
    {
        $row = ['postcode' => 'Test"Quote\'Here'];
        $fields = ['postcode'];

        $result = outputTableRow($row, $fields);

        $this->assertStringContainsString('&quot;', $result);
        $this->assertStringContainsString('&#039;', $result);
    }

    public function testEscapesAmpersands(): void
    {
        $row = ['lsoa_name_2021' => 'Area & Location'];
        $fields = ['lsoa_name_2021'];

        $result = outputTableRow($row, $fields);

        $this->assertStringContainsString('&amp;', $result);
    }

    public function testHandlesEmptyValues(): void
    {
        $row = [
            'postcode' => '',
            'lsoa_name_2021' => '',
        ];
        $fields = ['postcode', 'lsoa_name_2021'];

        $result = outputTableRow($row, $fields);

        $this->assertEquals('<tr><td></td><td></td></tr>', $result);
    }

    public function testHandlesNumericValues(): void
    {
        $row = [
            'imd_rank' => 12345,
            'imd_decile' => 7
        ];
        $fields = ['imd_rank', 'imd_decile'];

        $result = outputTableRow($row, $fields);

        $this->assertStringContainsString('<td>12345</td>', $result);
        $this->assertStringContainsString('<td>7</td>', $result);
    }

    public function testPreventsXssWithVariousAttacks(): void
    {
        $xssAttempts = [
            '<img src=x onerror=alert(1)>',
            '<svg onload=alert(1)>',
            '"><script>alert(1)</script>',
        ];

        foreach ($xssAttempts as $attempt) {
            $row = ['field' => $attempt];
            $result = outputTableRow($row, ['field']);

            // Tags should be escaped
            $this->assertStringNotContainsString('<script>', $result);
            $this->assertStringNotContainsString('<img', $result);
            $this->assertStringNotContainsString('<svg', $result);
            $this->assertStringContainsString('&lt;', $result);

            // Event handlers in attributes are escaped but the string "onerror=" remains
            // This is safe because the < and > are escaped, so it won't execute
        }

        // Test javascript: protocol separately (no < or > to escape)
        $row = ['field' => 'javascript:alert(1)'];
        $result = outputTableRow($row, ['field']);
        $this->assertStringContainsString('javascript:alert(1)', $result);
        // This is safe in table cells since it's not in an href attribute
    }

    public function testHandlesSpecialCharacters(): void
    {
        $row = ['field' => '£100 & €50 < $75 > ¥25'];
        $result = outputTableRow($row, ['field']);

        $this->assertStringContainsString('&lt;', $result);
        $this->assertStringContainsString('&gt;', $result);
        $this->assertStringContainsString('&amp;', $result);
    }

    public function testCorrectFieldOrder(): void
    {
        $row = [
            'a' => 'First',
            'b' => 'Second',
            'c' => 'Third'
        ];
        $fields = ['c', 'a', 'b'];

        $result = outputTableRow($row, $fields);

        // Check that fields appear in the order specified
        $posC = strpos($result, 'Third');
        $posA = strpos($result, 'First');
        $posB = strpos($result, 'Second');

        $this->assertLessThan($posA, $posC);
        $this->assertLessThan($posB, $posA);
    }
}
