<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests for stricter postcode validation covering special UK postcode formats.
 *
 * UK postcode formats:
 * - Standard formats: A9 9AA, A9A 9AA, A99 9AA, AA9 9AA, AA9A 9AA, AA99 9AA
 * - Special cases: GIR 0AA (Girobank), BFPO postcodes (British Forces)
 * - Overseas territories: ASCN 1ZZ, BBND 1ZZ, BIQQ 1ZZ, FIQQ 1ZZ, etc.
 * - Santa's postcode: SAN TA1 (not real but culturally significant)
 * - AI1 1AA format (Anguilla - British Overseas Territory)
 *
 * Invalid patterns:
 * - Wrong inward code format (last 3 chars must be 9AA format)
 * - Invalid characters (Q, V, X in first position; I, J, Z in second position)
 * - Too short or too long
 * - Wrong structure
 */
class PostcodeValidationTest extends TestCase
{
    /**
     * GIR 0AA is the only valid postcode with a 0 in the outward code.
     * It was originally assigned to Girobank (now defunct but still used for validation).
     */
    public function testNormaliseSpecialGirobankPostcode(): void
    {
        $this->assertEquals('GIR 0AA', normalisePostcode('GIR 0AA'));
        $this->assertEquals('GIR 0AA', normalisePostcode('gir0aa'));
        $this->assertEquals('GIR 0AA', normalisePostcode('GIR0AA'));
    }

    /**
     * BFPO (British Forces Post Office) postcodes for military addresses.
     * Format: BFPO followed by 1-4 digits, no inward code.
     */
    public function testNormaliseBfpoPostcodes(): void
    {
        // BFPO postcodes are special - they don't follow standard format
        $this->assertEquals('BFPO 1', normalisePostcode('BFPO 1'));
        $this->assertEquals('BFPO 57', normalisePostcode('BFPO57'));
        $this->assertEquals('BFPO 801', normalisePostcode('bfpo 801'));
        $this->assertEquals('BFPO 1234', normalisePostcode('BFPO1234'));
    }

    /**
     * British Overseas Territories use specific postcode formats.
     * These are real, valid postcodes in use.
     */
    public function testNormaliseOverseasTerritoryPostcodes(): void
    {
        // Ascension Island
        $this->assertEquals('ASCN 1ZZ', normalisePostcode('ASCN 1ZZ'));

        // British Indian Ocean Territory
        $this->assertEquals('BIQQ 1ZZ', normalisePostcode('BIQQ1ZZ'));

        // Falkland Islands
        $this->assertEquals('FIQQ 1ZZ', normalisePostcode('fiqq 1zz'));

        // Gibraltar
        $this->assertEquals('GX11 1AA', normalisePostcode('GX111AA'));

        // Pitcairn Islands
        $this->assertEquals('PCRN 1ZZ', normalisePostcode('PCRN1ZZ'));

        // South Georgia and South Sandwich Islands
        $this->assertEquals('SIQQ 1ZZ', normalisePostcode('siqq1zz'));

        // St Helena
        $this->assertEquals('STHL 1ZZ', normalisePostcode('STHL 1ZZ'));

        // Tristan da Cunha
        $this->assertEquals('TDCU 1ZZ', normalisePostcode('tdcu1zz'));

        // Turks and Caicos
        $this->assertEquals('TKCA 1ZZ', normalisePostcode('TKCA1ZZ'));
    }

    /**
     * Crown Dependencies (not UK but use UK-style postcodes).
     */
    public function testNormaliseCrownDependencyPostcodes(): void
    {
        // Guernsey (GY prefix)
        $this->assertEquals('GY1 1AA', normalisePostcode('GY11AA'));
        $this->assertEquals('GY9 3XY', normalisePostcode('gy9 3xy'));

        // Jersey (JE prefix)
        $this->assertEquals('JE1 1AA', normalisePostcode('JE11AA'));
        $this->assertEquals('JE2 3AB', normalisePostcode('je2 3ab'));

        // Isle of Man (IM prefix)
        $this->assertEquals('IM1 1AA', normalisePostcode('IM11AA'));
        $this->assertEquals('IM99 1PS', normalisePostcode('im991ps'));
    }

    /**
     * Single-letter outcodes (rare but valid).
     * Examples: E1, N1, W1, etc. (London districts)
     */
    public function testNormaliseSingleLetterOutcodes(): void
    {
        $this->assertEquals('E1 6AN', normalisePostcode('e16an'));
        $this->assertEquals('N1 9GU', normalisePostcode('N19GU'));
        $this->assertEquals('W1 1AA', normalisePostcode('w1 1aa'));
        $this->assertEquals('S1 2HE', normalisePostcode('S12HE'));
    }

    /**
     * Two-letter, one-digit outcodes.
     */
    public function testNormaliseTwoLetterOneDigitOutcodes(): void
    {
        $this->assertEquals('EC1A 1BB', normalisePostcode('ec1a1bb'));
        $this->assertEquals('SW1A 1AA', normalisePostcode('SW1A1AA'));
        $this->assertEquals('W1A 0AX', normalisePostcode('w1a0ax'));
    }

    /**
     * Test rejection of invalid postcode patterns.
     * With stricter validation, these should fall back to simple formatting
     * since they don't match valid UK postcode rules.
     */
    public function testInvalidPostcodePatternsCurrentlyPassThrough(): void
    {
        // Invalid: outward code can't have Q, V, X in first position
        // Falls back to simple split since it doesn't match strict pattern
        $result = normalisePostcode('QA1 1AA');
        $this->assertEquals('QA1 1AA', $result); // Fallback formatting

        // Invalid: second position can't have I, J, Z
        // Falls back to simple split since it doesn't match strict pattern
        $result = normalisePostcode('AI1 1AA');
        $this->assertEquals('AI1 1AA', $result); // Fallback formatting

        // Invalid: inward code can't start with C, I, K, M, O, V
        // Falls back to simple split since it doesn't match strict pattern
        $result = normalisePostcode('SW1A 1CA');
        $this->assertEquals('SW1A 1CA', $result); // Fallback formatting

        // Invalid: too long - should still format
        $result = normalisePostcode('TOOLONG99 9ZZ');
        $this->assertEquals('TOOLONG99 9ZZ', $result); // Fallback formatting

        // Invalid: numbers in wrong places - should still format
        $result = normalisePostcode('S11 111');
        $this->assertEquals('S11 111', $result); // Fallback formatting
    }

    /**
     * Test postcodes that should fail validation but need special handling.
     */
    public function testEdgeCasesForFutureValidation(): void
    {
        // Empty outward code
        $result = normalisePostcode('1AA');
        $this->assertEquals('1AA', $result); // Too short for space insertion

        // No inward code
        $result = normalisePostcode('SW1A');
        $this->assertEquals('SW1A', $result); // Only 4 chars, no space inserted

        // Numeric only
        $result = normalisePostcode('12345');
        $this->assertEquals('12 345', $result); // Splits at -3

        // Alpha only (no numbers)
        $result = normalisePostcode('ABCDEF');
        $this->assertEquals('ABC DEF', $result); // Splits at -3
    }

    /**
     * Test that standard valid formats still work correctly.
     */
    public function testStandardValidFormatsStillWork(): void
    {
        // A9 9AA
        $this->assertEquals('M1 1AE', normalisePostcode('M11AE'));

        // A9A 9AA
        $this->assertEquals('M1A 1AA', normalisePostcode('M1A1AA'));

        // A99 9AA
        $this->assertEquals('M60 1NW', normalisePostcode('M601NW'));

        // AA9 9AA
        $this->assertEquals('CR2 6XH', normalisePostcode('CR26XH'));

        // AA9A 9AA
        $this->assertEquals('EC1A 1BB', normalisePostcode('EC1A1BB'));

        // AA99 9AA
        $this->assertEquals('DN55 1PT', normalisePostcode('DN551PT'));
    }

    /**
     * Test maximum valid postcode lengths.
     * Longest valid format is AA99 9AA (7 chars without space, 8 with).
     */
    public function testMaximumValidLength(): void
    {
        $this->assertEquals('AA99 9AA', normalisePostcode('AA999AA'));
        $this->assertEquals('SW1W 0NY', normalisePostcode('SW1W0NY'));
    }

    /**
     * Test minimum valid postcode lengths.
     * Shortest mainland UK postcode is A9 9AA (5 chars without space, 6 with).
     */
    public function testMinimumValidLength(): void
    {
        $this->assertEquals('M1 1AE', normalisePostcode('M11AE'));
        $this->assertEquals('E1 6AN', normalisePostcode('E16AN'));
    }

    /**
     * Special handling for Santa postcodes (cultural/testing use).
     * Not technically valid but widely used for testing.
     */
    public function testSantaPostcode(): void
    {
        // Various Santa postcode variants used for testing
        $this->assertEquals('XM4 5HQ', normalisePostcode('XM45HQ')); // "Xmas HQ"
        $this->assertEquals('SAN TA1', normalisePostcode('SANTA1')); // Common test postcode
    }
}
