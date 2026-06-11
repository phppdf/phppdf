<?php

declare(strict_types=1);

namespace PhpPdf\Text\RichTextBox;

use PhpPdf\Font\FontMetrics;
use PhpPdf\Text\RichTextBox;
use PhpPdf\Text\TextSpan;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RichTextBox::class)]
#[CoversMethod(RichTextBox::class, 'linesFor')]
#[UsesClass(TextSpan::class)]
final class LinesForTest extends TestCase
{
    #[Test]
    public function linesForReturnsAllLinesWhenMaxHeightIsLarge(): void
    {
        // Arrange — 3 lines at lineHeight=12 fit within 100 pt
        $spans = [TextSpan::create('aa bb cc', 'F1', 10.0, $this->makeMetrics())];
        $box = RichTextBox::create($spans, maxWidth: 20, lineHeight: 12.0);

        // Act
        $result = $box->linesFor(100.0);

        // Assert
        self::assertCount(3, $result);
    }

    #[Test]
    public function linesForTruncatesToFittingLines(): void
    {
        // Arrange — 3 lines at lineHeight=12; maxHeight=24 fits 2 lines (floor(24/12)=2)
        $spans = [TextSpan::create('aa bb cc', 'F1', 10.0, $this->makeMetrics())];
        $box = RichTextBox::create($spans, maxWidth: 20, lineHeight: 12.0);

        // Act
        $result = $box->linesFor(24.0);

        // Assert
        self::assertCount(2, $result);
    }

    #[Test]
    public function linesForReturnsAtLeastOneLineForTinyMaxHeight(): void
    {
        // Arrange
        $spans = [TextSpan::create('hello world', 'F1', 10.0, $this->makeMetrics())];
        $box = RichTextBox::create($spans, maxWidth: 20, lineHeight: 12.0);

        // Act — maxHeight smaller than one line still returns at least 1
        $result = $box->linesFor(1.0);

        // Assert
        self::assertCount(1, $result);
    }

    private function makeMetrics(): FontMetrics
    {
        return new class implements FontMetrics {
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
