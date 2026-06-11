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
#[CoversMethod(RichTextBox::class, 'lineWidthPt')]
#[UsesClass(TextSpan::class)]
final class LineWidthPtTest extends TestCase
{
    #[Test]
    public function lineWidthPtReturnsSumOfSpanWidths(): void
    {
        // Arrange — 2-char span + 3-char span, each char = 500 units, fontSize=10
        // span1: 2 * 500 * 10 / 1000 = 10pt; span2: 3 * 500 * 10 / 1000 = 15pt
        $metrics = $this->makeMetrics();
        $line = [
            TextSpan::create('ab', 'F1', 10.0, $metrics),
            TextSpan::create('cde', 'F1', 10.0, $metrics),
        ];
        $box = RichTextBox::create([], maxWidth: 200);

        // Act
        $result = $box->lineWidthPt($line);

        // Assert
        self::assertEqualsWithDelta(25.0, $result, 0.001);
    }

    #[Test]
    public function lineWidthPtForEmptyLineIsZero(): void
    {
        // Arrange
        $box = RichTextBox::create([], maxWidth: 200);

        // Act / Assert
        self::assertEqualsWithDelta(0.0, $box->lineWidthPt([]), 0.001);
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
