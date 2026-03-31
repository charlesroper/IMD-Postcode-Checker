<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DecileForInputTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        unset($_GET['d']);
    }

    protected function tearDown(): void
    {
        unset($_GET['d']);
        parent::tearDown();
    }

    public function testReturnsEmptyStringWhenParamAbsent(): void
    {
        $this->assertEquals('', decileForInput());
    }

    public function testLogicFlowWhenParamIsPresentWithInvalidValue(): void
    {
        $_GET['d'] = 'invalid';
        $decile = getDecileInt();
        $result = (string) $decile;
        $this->assertEquals('10', $result);
    }

    public function testTypeCastingOfIntegerToString(): void
    {
        $value = 5;
        $this->assertEquals('5', (string) $value);

        $value = 10;
        $this->assertEquals('10', (string) $value);
    }
}
