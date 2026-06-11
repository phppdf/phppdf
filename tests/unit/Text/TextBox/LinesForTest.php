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
#[CoversMethod(TextBox::class, 'linesFor')]
final class LinesForTest extends TestCase
{
    private FontMetrics $metrics;

    #[Test]
    public function linesForReturnsAllLinesWhenHeightIsSufficient(): void
    {
        // Arrange — 3 lines at lineHeight 12 → need 36 pt
        $box = TextBox::create("a\nb\nc", $this->metrics, fontSize: 10, maxWidth: 200, lineHeight: 12);

        // Act
        $lines = $box->linesFor(100);

        // Assert
        self::assertSame(['a', 'b', 'c'], $lines);
    }

    #[Test]
    public function linesForClampsToFittingLines(): void
    {
        // Arrange — 3 lines, lineHeight=12, maxHeight=20 → floor(20/12)=1 line
        $box = TextBox::create("a\nb\nc", $this->metrics, fontSize: 10, maxWidth: 200, lineHeight: 12);

        // Act
        $lines = $box->linesFor(20);

        // Assert
        self::assertSame(['a'], $lines);
    }

    #[Test]
    public function linesForReturnsAtLeastOneLineForTinyHeight(): void
    {
        // Arrange — maxHeight=0 → max(1, floor(0/12))=1
        $box = TextBox::create("a\nb", $this->metrics, fontSize: 10, maxWidth: 200, lineHeight: 12);

        // Act
        $lines = $box->linesFor(0);

        // Assert — at least 1 line
        self::assertCount(1, $lines);
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
