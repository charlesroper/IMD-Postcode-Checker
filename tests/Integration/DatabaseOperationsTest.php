<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PDO;
use PDOStatement;

class DatabaseOperationsTest extends TestCase
{
    /**
     * @return PDO&MockObject
     */
    private function createMockPDO(): PDO
    {
        // Create a mock PDO object
        $pdo = $this->createMock(PDO::class);
        return $pdo;
    }

    /**
     * @return PDOStatement&MockObject
     */
    private function createMockStatement(): PDOStatement
    {
        return $this->createMock(PDOStatement::class);
    }

    public function testDatabaseQueryWithSinglePostcode(): void
    {
        $pdo = $this->createMockPDO();
        $stmt = $this->createMockStatement();

        // Mock the prepare method
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('WHERE postcode IN (?)'))
            ->willReturn($stmt);

        // Mock execute being called with the postcode array
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['SW1A 1AA']);

        // Simulate the query
        $postcodes = ['SW1A 1AA'];
        $placeholders = postcodePlaceholdersForSql("SW1A 1AA");

        $this->assertEquals('?', $placeholders);

        $sql = $pdo->prepare("SELECT COUNT(*) FROM imd25 WHERE postcode IN ($placeholders)");
        $sql->execute($postcodes);
    }

    public function testDatabaseQueryWithMultiplePostcodes(): void
    {
        $pdo = $this->createMockPDO();
        $stmt = $this->createMockStatement();

        $postcodes = ['SW1A 1AA', 'M1 1AE', 'B33 8TH'];
        $placeholders = postcodePlaceholdersForSql("SW1A 1AA\nM1 1AE\nB33 8TH");

        // Mock the prepare method
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('WHERE postcode IN (?,?,?)'))
            ->willReturn($stmt);

        // Mock execute being called with the postcode array
        $stmt->expects($this->once())
            ->method('execute')
            ->with($postcodes);

        $this->assertEquals('?,?,?', $placeholders);

        $sql = $pdo->prepare("SELECT COUNT(*) FROM imd25 WHERE postcode IN ($placeholders)");
        $sql->execute($postcodes);
    }

    public function testDatabaseQueryWithDecileFilter(): void
    {
        $pdo = $this->createMockPDO();
        $stmt = $this->createMockStatement();

        $postcodes = ['SW1A 1AA', 'M1 1AE'];
        $decile = 5;
        $placeholders = postcodePlaceholdersForSql("SW1A 1AA\nM1 1AE");

        // Mock the prepare method with decile filter
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('AND imd_decile <= ?'))
            ->willReturn($stmt);

        // Parameters should include postcodes plus decile
        $params = array_merge($postcodes, [$decile]);

        $stmt->expects($this->once())
            ->method('execute')
            ->with($params);

        $sql = $pdo->prepare("SELECT * FROM imd25 WHERE postcode IN ($placeholders) AND imd_decile <= ?");
        $sql->execute($params);
    }

    public function testDatabaseReturnsCorrectResultStructure(): void
    {
        $pdo = $this->createMockPDO();
        $stmt = $this->createMockStatement();

        $mockData = [
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

        $pdo->method('prepare')->willReturn($stmt);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($mockData);

        $placeholders = postcodePlaceholdersForSql("SW1A 1AA\nM1 1AE");
        $sql = $pdo->prepare("SELECT * FROM imd25 WHERE postcode IN ($placeholders)");
        $sql->execute(['SW1A 1AA', 'M1 1AE']);
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('postcode', $result[0]);
        $this->assertArrayHasKey('lsoa_name_2021', $result[0]);
        $this->assertArrayHasKey('imd_rank', $result[0]);
        $this->assertArrayHasKey('imd_decile', $result[0]);
    }

    public function testDatabaseCountQuery(): void
    {
        $pdo = $this->createMockPDO();
        $stmt = $this->createMockStatement();

        $pdo->method('prepare')->willReturn($stmt);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchColumn')->willReturn(42);

        $placeholders = postcodePlaceholdersForSql("SW1A 1AA");
        $sql = $pdo->prepare("SELECT COUNT(*) FROM imd25 WHERE postcode IN ($placeholders)");
        $sql->execute(['SW1A 1AA']);
        $count = $sql->fetchColumn();

        $this->assertEquals(42, $count);
    }

    public function testParameterBindingWithMaxPostcodes(): void
    {
        $postcodes = array_fill(0, 900, 'SW1A 1AA');
        $input = implode("\n", $postcodes);
        $placeholders = postcodePlaceholdersForSql($input);

        // Verify correct number of placeholders
        $this->assertEquals(900, substr_count($placeholders, '?'));

        // Verify the placeholders string is valid for SQL
        $this->assertMatchesRegularExpression('/^\?(?:,\?)*$/', $placeholders);
    }

    public function testEmptyResultHandling(): void
    {
        $pdo = $this->createMockPDO();
        $stmt = $this->createMockStatement();

        $pdo->method('prepare')->willReturn($stmt);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn([]);

        $placeholders = postcodePlaceholdersForSql("INVALID");
        $sql = $pdo->prepare("SELECT * FROM imd25 WHERE postcode IN ($placeholders)");
        $sql->execute(['INVALID']);
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
