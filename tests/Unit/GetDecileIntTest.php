<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class GetDecileIntTest extends TestCase
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

    public function testReturnsDefaultWhenParamAbsent(): void
    {
        $this->assertEquals(10, getDecileInt());
    }

    public function testReturnsDefaultForNonNumericValue(): void
    {
        $_GET['d'] = 'abc';
        $this->assertEquals(10, getDecileInt());
    }

    public function testReturnsDefaultForEmptyString(): void
    {
        $_GET['d'] = '';
        $this->assertEquals(10, getDecileInt());
    }

    public function testReturnsDefaultForValueBelowRange(): void
    {
        $_GET['d'] = '0';
        $this->assertEquals(10, getDecileInt());

        $_GET['d'] = '-1';
        $this->assertEquals(10, getDecileInt());
    }

    public function testReturnsDefaultForValueAboveRange(): void
    {
        $_GET['d'] = '11';
        $this->assertEquals(10, getDecileInt());

        $_GET['d'] = '100';
        $this->assertEquals(10, getDecileInt());
    }

    public function testFilterValidateIntRejectsOutOfRangeValues(): void
    {
        $result = filter_var('11', FILTER_VALIDATE_INT, [
            'options' => [
                'default' => 10,
                'min_range' => 1,
                'max_range' => 10,
            ],
        ]);
        $this->assertEquals(10, $result);

        $result = filter_var('0', FILTER_VALIDATE_INT, [
            'options' => [
                'default' => 10,
                'min_range' => 1,
                'max_range' => 10,
            ],
        ]);
        $this->assertEquals(10, $result);
    }

    public function testFilterValidateIntAcceptsValidRange(): void
    {
        foreach (range(1, 10) as $value) {
            $result = filter_var((string) $value, FILTER_VALIDATE_INT, [
                'options' => [
                    'default' => 10,
                    'min_range' => 1,
                    'max_range' => 10,
                ],
            ]);
            $this->assertEquals($value, $result, "Expected $value but got $result");
        }
    }

    public function testFilterValidateIntRejectsFloat(): void
    {
        $result = filter_var('5.7', FILTER_VALIDATE_INT, [
            'options' => [
                'default' => 10,
                'min_range' => 1,
                'max_range' => 10,
            ],
        ]);
        $this->assertEquals(10, $result);
    }

    public function testFilterValidateIntRejectsNonNumeric(): void
    {
        $result = filter_var('abc', FILTER_VALIDATE_INT, [
            'options' => [
                'default' => 10,
                'min_range' => 1,
                'max_range' => 10,
            ],
        ]);
        $this->assertEquals(10, $result);
    }
}
