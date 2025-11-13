<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PostcodePlaceholdersForSqlTest extends TestCase
{
    public function testEmptyInputReturnsEmptyString(): void
    {
        $this->assertEquals('', postcodePlaceholdersForSql(null));
        $this->assertEquals('', postcodePlaceholdersForSql(''));
    }

    public function testSinglePostcodeReturnsSinglePlaceholder(): void
    {
        $this->assertEquals('?', postcodePlaceholdersForSql('SW1A 1AA'));
    }

    public function testMultiplePostcodesReturnCorrectPlaceholders(): void
    {
        $input = "SW1A 1AA\nM1 1AE\nB33 8TH";
        $this->assertEquals('?,?,?', postcodePlaceholdersForSql($input));
    }

    public function testTenPostcodesReturnTenPlaceholders(): void
    {
        $postcodes = [];
        for ($i = 0; $i < 10; $i++) {
            $postcodes[] = 'SW1A 1AA';
        }
        $input = implode("\n", $postcodes);
        $expected = implode(',', array_fill(0, 10, '?'));
        $this->assertEquals($expected, postcodePlaceholdersForSql($input));
    }

    public function testSkipsEmptyLines(): void
    {
        $input = "SW1A 1AA\n\nM1 1AE\n\nB33 8TH";
        $this->assertEquals('?,?,?', postcodePlaceholdersForSql($input));
    }

    public function testHandlesLargeInput(): void
    {
        $postcodes = array_fill(0, 900, 'SW1A 1AA');
        $input = implode("\n", $postcodes);
        $result = postcodePlaceholdersForSql($input);
        $placeholderCount = substr_count($result, '?');
        $this->assertEquals(900, $placeholderCount);
    }
}
