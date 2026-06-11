<?php

declare(strict_types=1);

namespace PhpPdf\Text\RichTextBox;

use PhpPdf\Font\FontMetrics;
use PhpPdf\Text\RichTextBox;
use PhpPdf\Text\TextAlign;
use PhpPdf\Text\TextSpan;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RichTextBox::class)]
#[CoversMethod(RichTextBox::class, 'skip')]
#[UsesClass(TextSpan::class)]
final class SkipTest extends TestCase
{
    #[Test]
    public function skipReturnsBoxWithRemainingLines(): void
    {
        // Arrange — 3 words wrap to 3 lines
        $spans = [TextSpan::create('aa bb cc', 'F1', 10.0, $this->makeMetrics())];
        $box = RichTextBox::create($spans, maxWidth: 20, lineHeight: 12.0);

        // Act — skip the first line
        $skipped = $box->skip(1);

        // Assert
        self::assertCount(2, $skipped->getLines());
    }

    #[Test]
    public function skipPreservesLayoutParameters(): void
    {
        // Arrange
        $spans = [TextSpan::create('aa bb cc', 'F1', 10.0, $this->makeMetrics())];
        $box = RichTextBox::create($spans, maxWidth: 150, lineHeight: 14.0, align: TextAlign::Right);

        // Act
        $skipped = $box->skip(1);

        // Assert
        self::assertEqualsWithDelta(150.0, $skipped->getMaxWidth(), 0.001);
        self::assertEqualsWithDelta(14.0, $skipped->getLineHeight(), 0.001);
        self::assertSame(TextAlign::Right, $skipped->getAlign());
    }

    #[Test]
    public function skipAllLinesReturnsEmptyBox(): void
    {
        // Arrange — 1 line
        $spans = [TextSpan::create('hello', 'F1', 10.0, $this->makeMetrics())];
        $box = RichTextBox::create($spans, maxWidth: 200, lineHeight: 12.0);

        // Act
        $skipped = $box->skip(1);

        // Assert
        self::assertSame([], $skipped->getLines());
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
