<?php

declare(strict_types=1);

namespace Tests\Security;

use PHPUnit\Framework\TestCase;

class XssPreventionTest extends TestCase
{
    public function testOutputTableRowEscapesScriptTags(): void
    {
        $xssAttempts = [
            '<script>alert("XSS")</script>',
            '<script src="malicious.js"></script>',
            '<SCRIPT>alert(1)</SCRIPT>',
        ];

        foreach ($xssAttempts as $attempt) {
            $row = ['field' => $attempt];
            $result = outputTableRow($row, ['field']);

            $this->assertStringNotContainsString('<script>', strtolower($result));
            $this->assertStringContainsString('&lt;', $result);
            $this->assertStringContainsString('&gt;', $result);
        }
    }

    public function testOutputTableRowEscapesEventHandlers(): void
    {
        $xssAttempts = [
            '<img src=x onerror=alert(1)>',
            '<body onload=alert(1)>',
            '<div onclick="alert(1)">',
            '<svg onload=alert(1)>',
        ];

        foreach ($xssAttempts as $attempt) {
            $row = ['field' => $attempt];
            $result = outputTableRow($row, ['field']);

            // The critical part is that < and > are escaped, preventing tag execution
            $this->assertStringNotContainsString('<img', $result);
            $this->assertStringNotContainsString('<body', $result);
            $this->assertStringNotContainsString('<div', $result);
            $this->assertStringNotContainsString('<svg', $result);
            $this->assertStringContainsString('&lt;', $result);
            $this->assertStringContainsString('&gt;', $result);
        }
    }

    public function testOutputTableRowEscapesJavascriptProtocol(): void
    {
        $xssAttempts = [
            'javascript:alert(1)',
            'JAVASCRIPT:alert(1)',
            'data:text/html,<script>alert(1)</script>',
        ];

        foreach ($xssAttempts as $attempt) {
            $row = ['field' => $attempt];
            $result = outputTableRow($row, ['field']);

            // The colon should be preserved but the content should be safe
            $this->assertStringNotContainsString('<script>', $result);
        }
    }

    public function testOutputTableRowEscapesQuotes(): void
    {
        $row = ['field' => '"><script>alert(1)</script>'];
        $result = outputTableRow($row, ['field']);

        $this->assertStringContainsString('&quot;', $result);
        $this->assertStringNotContainsString('"><script>', $result);
    }

    public function testOutputTableRowEscapesSingleQuotes(): void
    {
        $row = ['field' => "'; alert(1); '"];
        $result = outputTableRow($row, ['field']);

        $this->assertStringContainsString('&#039;', $result);
    }

    public function testOutputTableRowHandlesNestedTags(): void
    {
        $row = ['field' => '<div><script>alert(1)</script></div>'];
        $result = outputTableRow($row, ['field']);

        $this->assertStringNotContainsString('<div>', $result);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;div&gt;&lt;script&gt;', $result);
    }

    public function testOutputTableRowEscapesAmpersands(): void
    {
        $row = ['field' => 'Test & More & Stuff'];
        $result = outputTableRow($row, ['field']);

        $this->assertStringContainsString('&amp;', $result);
        // Make sure it doesn't double-encode
        $this->assertStringNotContainsString('&amp;amp;', $result);
    }

    public function testOutputTableRowHandlesComplexXSSPayload(): void
    {
        $complexPayload = '"><img src=x onerror=alert(document.cookie)>';
        $row = ['field' => $complexPayload];
        $result = outputTableRow($row, ['field']);

        $this->assertStringContainsString('&quot;', $result);
        $this->assertStringContainsString('&lt;', $result);
        $this->assertStringContainsString('&gt;', $result);
        // The critical security check: tags are escaped
        $this->assertStringNotContainsString('<img', $result);
        // The string "onerror=" may remain but it's harmless since < > are escaped
    }

    public function testNormalisePostcodeDoesNotIntroduceXSS(): void
    {
        $xssAttempts = [
            '<script>alert(1)</script>',
            '<img src=x onerror=alert(1)>',
        ];

        foreach ($xssAttempts as $attempt) {
            $result = normalisePostcode($attempt);
            // normalisePostcode strips all HTML, leaving only alphanumeric
            $this->assertStringNotContainsString('<', $result);
            $this->assertStringNotContainsString('>', $result);
            $this->assertStringNotContainsString('=', $result);
        }
    }
}
