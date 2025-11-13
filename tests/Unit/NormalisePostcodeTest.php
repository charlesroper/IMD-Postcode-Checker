<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class NormalisePostcodeTest extends TestCase
{
    public function testNormaliseValidFullPostcode(): void
    {
        $this->assertEquals('SW1A 1AA', normalisePostcode('SW1A 1AA'));
        $this->assertEquals('M1 1AE', normalisePostcode('M1 1AE'));
        $this->assertEquals('B33 8TH', normalisePostcode('B33 8TH'));
    }

    public function testNormalisePostcodeWithoutSpace(): void
    {
        $this->assertEquals('SW1A 1AA', normalisePostcode('SW1A1AA'));
        $this->assertEquals('M1 1AE', normalisePostcode('M11AE'));
        $this->assertEquals('B33 8TH', normalisePostcode('B338TH'));
    }

    public function testNormalisePostcodeWithMixedCase(): void
    {
        $this->assertEquals('SW1A 1AA', normalisePostcode('sw1a 1aa'));
        $this->assertEquals('M1 1AE', normalisePostcode('m1 1ae'));
        $this->assertEquals('B33 8TH', normalisePostcode('b33 8th'));
    }

    public function testNormalisePostcodeWithExtraSpaces(): void
    {
        $this->assertEquals('SW1A 1AA', normalisePostcode('SW1A  1AA'));
        $this->assertEquals('M1 1AE', normalisePostcode('M 1 1 A E'));
        $this->assertEquals('B33 8TH', normalisePostcode(' B33 8TH '));
    }

    public function testNormalisePostcodeWithSpecialCharacters(): void
    {
        $this->assertEquals('SW1A 1AA', normalisePostcode('SW1A-1AA'));
        $this->assertEquals('M1 1AE', normalisePostcode('M1_1AE'));
        $this->assertEquals('B33 8TH', normalisePostcode('B33.8TH'));
    }

    public function testNormaliseShortPostcode(): void
    {
        // Postcodes 3 characters or less don't get a space
        $this->assertEquals('M1', normalisePostcode('M1'));
        $this->assertEquals('SW1', normalisePostcode('SW1'));
        $this->assertEquals('A', normalisePostcode('A'));
    }

    public function testNormaliseEmptyString(): void
    {
        $this->assertEquals('', normalisePostcode(''));
    }

    public function testNormalisePostcodeWithUnicodeCharacters(): void
    {
        // Unicode characters should be stripped
        $this->assertEquals('SW1A 1AA', normalisePostcode('SW1A☺1AA'));
        $this->assertEquals('M1 1AE', normalisePostcode('M1£1AE'));
    }

    public function testNormalisePostcodePreservesAlphanumeric(): void
    {
        // Only alphanumeric should be preserved
        $this->assertEquals('AB12 3CD', normalisePostcode('AB!@12#$3CD'));
    }

    public function testNormaliseVariousValidFormats(): void
    {
        // Test various UK postcode formats
        $this->assertEquals('EC1A 1BB', normalisePostcode('EC1A1BB'));
        $this->assertEquals('W1A 0AX', normalisePostcode('W1A0AX'));
        $this->assertEquals('CR2 6XH', normalisePostcode('CR26XH'));
        $this->assertEquals('DN55 1PT', normalisePostcode('DN551PT'));
    }
}
