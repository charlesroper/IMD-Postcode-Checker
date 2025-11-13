<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class GetPostcodesArrayTest extends TestCase
{
    public function testReturnsEmptyArrayForNull(): void
    {
        $this->assertEquals([], getPostcodesArray(null));
    }

    public function testReturnsEmptyArrayForEmptyString(): void
    {
        $this->assertEquals([], getPostcodesArray(''));
    }

    public function testSinglePostcode(): void
    {
        $result = getPostcodesArray('SW1A 1AA');
        $this->assertEquals(['SW1A 1AA'], $result);
    }

    public function testMultiplePostcodesWithLF(): void
    {
        $input = "SW1A 1AA\nM1 1AE\nB33 8TH";
        $expected = ['SW1A 1AA', 'M1 1AE', 'B33 8TH'];
        $this->assertEquals($expected, getPostcodesArray($input));
    }

    public function testMultiplePostcodesWithCRLF(): void
    {
        $input = "SW1A 1AA\r\nM1 1AE\r\nB33 8TH";
        $expected = ['SW1A 1AA', 'M1 1AE', 'B33 8TH'];
        $this->assertEquals($expected, getPostcodesArray($input));
    }

    public function testMultiplePostcodesWithCR(): void
    {
        $input = "SW1A 1AA\rM1 1AE\rB33 8TH";
        $expected = ['SW1A 1AA', 'M1 1AE', 'B33 8TH'];
        $this->assertEquals($expected, getPostcodesArray($input));
    }

    public function testNormalisesPostcodesInArray(): void
    {
        $input = "sw1a1aa\nm11ae\nB338TH";
        $expected = ['SW1A 1AA', 'M1 1AE', 'B33 8TH'];
        $this->assertEquals($expected, getPostcodesArray($input));
    }

    public function testSkipsEmptyLines(): void
    {
        $input = "SW1A 1AA\n\nM1 1AE\n\n\nB33 8TH";
        $expected = ['SW1A 1AA', 'M1 1AE', 'B33 8TH'];
        $this->assertEquals($expected, getPostcodesArray($input));
    }

    public function testSkipsLinesWithOnlyWhitespace(): void
    {
        $input = "SW1A 1AA\n   \nM1 1AE\n\t\nB33 8TH";
        $expected = ['SW1A 1AA', 'M1 1AE', 'B33 8TH'];
        $this->assertEquals($expected, getPostcodesArray($input));
    }

    public function testHandlesMixedValidAndInvalidInput(): void
    {
        $input = "SW1A 1AA\nINVALID!@#$%\nM1 1AE";
        // INVALID!@#$% becomes "INVA LID" because special chars are removed,
        // leaving "INVALID" which gets a space inserted at -3 position
        $expected = ['SW1A 1AA', 'INVA LID', 'M1 1AE'];
        $this->assertEquals($expected, getPostcodesArray($input));
    }

    public function testPreservesDuplicates(): void
    {
        $input = "SW1A 1AA\nSW1A 1AA\nM1 1AE";
        $expected = ['SW1A 1AA', 'SW1A 1AA', 'M1 1AE'];
        $this->assertEquals($expected, getPostcodesArray($input));
    }

    public function testHandlesLargeNumberOfPostcodes(): void
    {
        $postcodes = [];
        for ($i = 0; $i < 1000; $i++) {
            $postcodes[] = 'SW1A 1AA';
        }
        $input = implode("\n", $postcodes);
        $result = getPostcodesArray($input);
        $this->assertCount(1000, $result);
        $this->assertEquals('SW1A 1AA', $result[0]);
        $this->assertEquals('SW1A 1AA', $result[999]);
    }

    public function testTrimsWhitespaceFromEachPostcode(): void
    {
        $input = "  SW1A 1AA  \n  M1 1AE  \n  B33 8TH  ";
        $expected = ['SW1A 1AA', 'M1 1AE', 'B33 8TH'];
        $this->assertEquals($expected, getPostcodesArray($input));
    }
}
