<?php

declare(strict_types=1);

namespace PhpPdf\Text\TextSpan;

use PhpPdf\Font\FontMetrics;
use PhpPdf\Text\TextSpan;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TextSpan::class)]
#[CoversMethod(TextSpan::class, 'widthPt')]
final class WidthPtTest extends TestCase
{
    #[Test]
    public function widthPtIsStringWidthTimesScaledByFontSize(): void
    {
        // Arrange — 4-char text, each char = 500 glyph units, fontSize = 10
        // widthPt = (4 * 500) * 10 / 1000 = 20.0 pt
        $span = TextSpan::create('abcd', 'F1', 10.0, $this->makeMetrics());

        // Act
        $result = $span->widthPt();

        // Assert
        self::assertEqualsWithDelta(20.0, $result, 0.001);
    }

    #[Test]
    public function widthPtForEmptyTextIsZero(): void
    {
        // Arrange
        $span = TextSpan::create('', 'F1', 10.0, $this->makeMetrics());

        // Act / Assert
        self::assertEqualsWithDelta(0.0, $span->widthPt(), 0.001);
    }

    #[Test]
    public function widthPtScalesWithFontSize(): void
    {
        // Arrange — same text, doubled font size → doubled width
        $spanA = TextSpan::create('ab', 'F1', 10.0, $this->makeMetrics());
        $spanB = TextSpan::create('ab', 'F1', 20.0, $this->makeMetrics());

        // Act / Assert
        self::assertEqualsWithDelta($spanA->widthPt() * 2, $spanB->widthPt(), 0.001);
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
