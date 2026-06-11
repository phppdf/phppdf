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
#[CoversMethod(RichTextBox::class, 'create')]
#[UsesClass(TextSpan::class)]
final class CreateTest extends TestCase
{
    #[Test]
    public function createReturnsRichTextBoxInstance(): void
    {
        // Arrange / Act
        $box = RichTextBox::create([], maxWidth: 200);

        // Assert
        self::assertInstanceOf(RichTextBox::class, $box);
    }

    #[Test]
    public function createDefaultsLineHeightToMaxFontSizeTimesOnePointTwo(): void
    {
        // Arrange — fontSize=10 → lineHeight = 10 * 1.2 = 12
        $spans = [TextSpan::create('hello', 'F1', 10.0, $this->makeMetrics())];

        // Act
        $box = RichTextBox::create($spans, maxWidth: 200);

        // Assert
        self::assertEqualsWithDelta(12.0, $box->getLineHeight(), 0.001);
    }

    #[Test]
    public function createPicksLargestFontSizeForLineHeight(): void
    {
        // Arrange — spans with sizes 8 and 14; max = 14 → lineHeight = 14 * 1.2 = 16.8
        $metrics = $this->makeMetrics();
        $spans = [
            TextSpan::create('small', 'F1', 8.0, $metrics),
            TextSpan::create('large', 'F2', 14.0, $metrics),
        ];

        // Act
        $box = RichTextBox::create($spans, maxWidth: 200);

        // Assert
        self::assertEqualsWithDelta(16.8, $box->getLineHeight(), 0.001);
    }

    #[Test]
    public function createFallsBackToTwelvePointLineHeightWhenSpansIsEmpty(): void
    {
        // Arrange / Act
        $box = RichTextBox::create([], maxWidth: 200);

        // Assert
        self::assertEqualsWithDelta(12.0, $box->getLineHeight(), 0.001);
    }

    #[Test]
    public function createUsesExplicitLineHeight(): void
    {
        // Arrange
        $spans = [TextSpan::create('hi', 'F1', 10.0, $this->makeMetrics())];

        // Act
        $box = RichTextBox::create($spans, maxWidth: 200, lineHeight: 20.0);

        // Assert
        self::assertEqualsWithDelta(20.0, $box->getLineHeight(), 0.001);
    }

    #[Test]
    public function createStoresAlignment(): void
    {
        // Arrange / Act
        $box = RichTextBox::create([], maxWidth: 200, align: TextAlign::Right);

        // Assert
        self::assertSame(TextAlign::Right, $box->getAlign());
    }

    #[Test]
    public function createWrapsTextAcrossSpans(): void
    {
        // Arrange — each char = 500 units, fontSize=10 → 5 pt per char
        // maxWidth=20 → fits 4 chars per line
        // "aa" (10pt) + space (5pt) + "bb" (10pt) = 25pt > 20pt → wraps
        $metrics = $this->makeMetrics();
        $spans = [
            TextSpan::create('aa bb', 'F1', 10.0, $metrics),
        ];

        // Act
        $box = RichTextBox::create($spans, maxWidth: 20);

        // Assert
        self::assertCount(2, $box->getLines());
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
