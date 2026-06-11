<?php

declare(strict_types=1);

namespace PhpPdf\Text\TextBox;

use PhpPdf\Font\FontMetrics;
use PhpPdf\Text\TextAlign;
use PhpPdf\Text\TextBox;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TextBox::class)]
#[CoversMethod(TextBox::class, 'skip')]
final class SkipTest extends TestCase
{
    private FontMetrics $metrics;

    #[Test]
    public function skipReturnsNewBoxStartingAtGivenLine(): void
    {
        // Arrange — 3 lines
        $box = TextBox::create("a\nb\nc", $this->metrics, fontSize: 10, maxWidth: 200, lineHeight: 12);

        // Act — skip first 1 line
        $remaining = $box->skip(1);

        // Assert
        self::assertSame(['b', 'c'], $remaining->getLines());
    }

    #[Test]
    public function skipPreservesMetricsAndSettings(): void
    {
        // Arrange
        $box = TextBox::create(
            "a\nb",
            $this->metrics,
            fontSize: 12,
            maxWidth: 300,
            lineHeight: 16,
            align: TextAlign::Right,
        );

        // Act
        $remaining = $box->skip(1);

        // Assert — same settings preserved
        self::assertEqualsWithDelta(12.0, $remaining->getFontSize(), 0.001);
        self::assertEqualsWithDelta(300.0, $remaining->getMaxWidth(), 0.001);
        self::assertEqualsWithDelta(16.0, $remaining->getLineHeight(), 0.001);
        self::assertSame(TextAlign::Right, $remaining->getAlign());
        self::assertSame($this->metrics, $remaining->getMetrics());
    }

    #[Test]
    public function skipAllLinesReturnsEmptyBox(): void
    {
        $box = TextBox::create("a\nb", $this->metrics, fontSize: 10, maxWidth: 200, lineHeight: 12);

        $remaining = $box->skip(2);

        self::assertSame([], $remaining->getLines());
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
