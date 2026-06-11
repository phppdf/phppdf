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
#[CoversMethod(TextBox::class, 'lineWidthPt')]
final class LineWidthPtTest extends TestCase
{
    #[Test]
    public function lineWidthPtConvertsGlyphUnitsToPoints(): void
    {
        // Arrange — each char = 500 glyph units; fontSize = 10
        // "Hi" = 2 chars → 1000 glyph units × 10/1000 = 10 pt
        $metrics = new class implements FontMetrics {
            public function charWidth(int $codePoint): float
            {
                return 500.0;
            }

            public function stringWidth(string $text): float
            {
                return mb_strlen($text, 'UTF-8') * 500.0;
            }
        };

        $box = TextBox::create('Hi', $metrics, fontSize: 10, maxWidth: 200);

        // Act
        $pt = $box->lineWidthPt('Hi');

        // Assert
        self::assertEqualsWithDelta(10.0, $pt, 0.001);
    }
}
