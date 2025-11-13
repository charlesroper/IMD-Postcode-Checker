<?php

declare(strict_types=1);

namespace Tests\Security;

use PHPUnit\Framework\TestCase;

class SqlInjectionTest extends TestCase
{
    public function testPostcodePlaceholdersDoesNotExecuteSQL(): void
    {
        // Attempt SQL injection through postcode input
        $maliciousInputs = [
            "SW1A 1AA'; DROP TABLE imd25; --",
            "SW1A 1AA' OR '1'='1",
            "SW1A 1AA\n1' UNION SELECT * FROM imd25 --",
            "'; DELETE FROM imd25; --",
        ];

        foreach ($maliciousInputs as $input) {
            $placeholders = postcodePlaceholdersForSql($input);
            // Should only contain question marks and commas
            $this->assertMatchesRegularExpression('/^[\?,]*$/', $placeholders);
            $this->assertStringNotContainsString('DROP', $placeholders);
            $this->assertStringNotContainsString('DELETE', $placeholders);
            $this->assertStringNotContainsString('UNION', $placeholders);
        }
    }

    public function testNormalisePostcodeRemovesSQLKeywords(): void
    {
        $maliciousInputs = [
            "SW1A'OR'1'='1",
            "DROP TABLE",
            "'; SELECT * FROM",
        ];

        foreach ($maliciousInputs as $input) {
            $result = normalisePostcode($input);
            // Since normalisePostcode strips non-alphanumeric, SQL keywords become harmless
            $this->assertStringNotContainsString("'", $result);
            $this->assertStringNotContainsString(";", $result);
            $this->assertStringNotContainsString("=", $result);
        }
    }

    public function testGetPostcodesArraySanitizesInput(): void
    {
        $input = "SW1A 1AA\n'; DROP TABLE imd25; --\nM1 1AE";
        $result = getPostcodesArray($input);

        // Each postcode should be sanitized
        foreach ($result as $postcode) {
            $this->assertStringNotContainsString("'", $postcode);
            $this->assertStringNotContainsString(";", $postcode);
            $this->assertStringNotContainsString("-", $postcode);
        }
    }
}
