<?php

declare(strict_types=1);

namespace Tests\Security;

use PHPUnit\Framework\TestCase;

class InputValidationTest extends TestCase
{
    public function testNormalisePostcodeHandlesExtremelyLongInput(): void
    {
        $longInput = str_repeat('A', 10000);
        $result = normalisePostcode($longInput);

        // Should still process without error
        $this->assertIsString($result);
        // Should have space inserted at -3 position
        $this->assertEquals(10001, strlen($result)); // 10000 + 1 space
    }

    public function testGetPostcodesArrayHandlesExtremelyLongInput(): void
    {
        $longPostcode = str_repeat('A', 1000);
        $result = getPostcodesArray($longPostcode);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    public function testGetPostcodesArrayHandlesManyLines(): void
    {
        $postcodes = array_fill(0, 10000, 'SW1A 1AA');
        $input = implode("\n", $postcodes);
        $result = getPostcodesArray($input);

        $this->assertCount(10000, $result);
    }

    public function testNormalisePostcodeHandlesNullBytes(): void
    {
        $input = "SW1A\x001AA";
        $result = normalisePostcode($input);

        // Null bytes should be stripped
        $this->assertStringNotContainsString("\x00", $result);
        $this->assertEquals('SW1A 1AA', $result);
    }

    public function testGetPostcodesArrayHandlesMalformedInput(): void
    {
        $malformedInputs = [
            "\x00\x00\x00",
            "���",
            "\r\r\r\n\n\n",
        ];

        foreach ($malformedInputs as $input) {
            $result = getPostcodesArray($input);
            $this->assertIsArray($result);
        }
    }

    public function testNormalisePostcodeHandlesUnicodeEdgeCases(): void
    {
        $unicodeInputs = [
            "SW1A\u{200B}1AA",  // Zero-width space
            "SW1A\u{FEFF}1AA",  // Zero-width no-break space
            "SW1A\u{202E}1AA",  // Right-to-left override
        ];

        foreach ($unicodeInputs as $input) {
            $result = normalisePostcode($input);
            // Should strip all non-alphanumeric Unicode
            $this->assertEquals('SW1A 1AA', $result);
        }
    }

    public function testPostcodePlaceholdersHandlesZeroPostcodes(): void
    {
        $result = postcodePlaceholdersForSql('');
        $this->assertEquals('', $result);
    }

    public function testPostcodePlaceholdersHandlesMaximumPostcodes(): void
    {
        $postcodes = array_fill(0, 900, 'SW1A 1AA');
        $input = implode("\n", $postcodes);
        $result = postcodePlaceholdersForSql($input);

        $placeholderCount = substr_count($result, '?');
        $this->assertEquals(900, $placeholderCount);
    }

    public function testOutputTableRowHandlesNullValues(): void
    {
        $row = ['field' => null];
        $result = outputTableRow($row, ['field']);

        $this->assertStringContainsString('<td></td>', $result);
    }

    public function testOutputTableRowHandlesBooleanValues(): void
    {
        $row = ['field' => true];
        $result = outputTableRow($row, ['field']);

        $this->assertStringContainsString('<td>1</td>', $result);

        $row = ['field' => false];
        $result = outputTableRow($row, ['field']);

        $this->assertStringContainsString('<td></td>', $result);
    }

    public function testOutputTableRowHandlesFloatValues(): void
    {
        $row = ['field' => 123.456];
        $result = outputTableRow($row, ['field']);

        $this->assertStringContainsString('<td>123.456</td>', $result);
    }
}
