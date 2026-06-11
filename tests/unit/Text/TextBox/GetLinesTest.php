<?php

declare(strict_types=1);

namespace PhpPdf\Text\TextBox;

use PhpPdf\Font\FontMetrics;
use PhpPdf\Text\TextBox;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TextBox::class)]
#[CoversMethod(TextBox::class, 'getLines')]
final class GetLinesTest extends TestCase
{
    private FontMetrics $metrics;

    #[Test]
    public function getLinesReturnsWrappedLines(): void
    {
        $box = TextBox::create('hello world', $this->metrics, fontSize: 10, maxWidth: 200);

        self::assertNotEmpty($box->getLines());
    }

    #[Test]
    public function getLinesForEmptyTextReturnsOneEmptyLine(): void
    {
        // An empty string passes through explode("\n", "") → [""], which the
        // wrap engine treats as a blank line, preserving paragraph spacing.
        $box = TextBox::create('', $this->metrics, fontSize: 10, maxWidth: 200);

        self::assertSame([''], $box->getLines());
    }

    protected function setUp(): void
    {
        $this->metrics = new class implements FontMetrics {
            public function charWidth(int $codePoint): float
            {
                return 500.0;
            }

            public function stringWidth(string $text): float
            {
                return mb_strlen($text, 'UTF-8') * 500.0;
            }
        };
    }
}
