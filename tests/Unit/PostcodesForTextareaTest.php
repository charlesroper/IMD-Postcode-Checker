<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PostcodesForTextareaTest extends TestCase
{
    public function testEmptyInputReturnsEmptyString(): void
    {
        $this->assertEquals('', postcodesForTextarea(null));
        $this->assertEquals('', postcodesForTextarea(''));
    }

    public function testSinglePostcode(): void
    {
        $this->assertEquals('SW1A 1AA', postcodesForTextarea('SW1A 1AA'));
    }

    public function testMultiplePostcodesJoinedWithPhpEol(): void
    {
        $input = "SW1A 1AA\nM1 1AE\nB33 8TH";
        $expected = 'SW1A 1AA' . PHP_EOL . 'M1 1AE' . PHP_EOL . 'B33 8TH';
        $this->assertEquals($expected, postcodesForTextarea($input));
    }

    public function testNormalisesPostcodes(): void
    {
        $input = "sw1a1aa\nm11ae";
        $expected = 'SW1A 1AA' . PHP_EOL . 'M1 1AE';
        $this->assertEquals($expected, postcodesForTextarea($input));
    }

    public function testSkipsEmptyLines(): void
    {
        $input = "SW1A 1AA\n\nM1 1AE";
        $expected = 'SW1A 1AA' . PHP_EOL . 'M1 1AE';
        $this->assertEquals($expected, postcodesForTextarea($input));
    }

    public function testHandlesMixedLineEndings(): void
    {
        $input = "SW1A 1AA\r\nM1 1AE\rB33 8TH";
        $expected = 'SW1A 1AA' . PHP_EOL . 'M1 1AE' . PHP_EOL . 'B33 8TH';
        $this->assertEquals($expected, postcodesForTextarea($input));
    }
}
