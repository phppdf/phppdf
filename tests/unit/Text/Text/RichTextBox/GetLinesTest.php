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
#[CoversMethod(RichTextBox::class, 'getLines')]
#[UsesClass(TextSpan::class)]
final class GetLinesTest extends TestCase
{
    #[Test]
    public function getLinesReturnsOneEmptyLineForEmptySpans(): void
    {
        // Arrange / Act
        $box = RichTextBox::create([], maxWidth: 200);

        // Assert — empty input yields one empty line to preserve line rhythm
        self::assertSame([[]], $box->getLines());
    }

    #[Test]
    public function getLinesReturnsSingleLineWhenTextFits(): void
    {
        // Arrange — "hi" fits well within 200 pt
        $spans = [TextSpan::create('hi', 'F1', 10.0, $this->makeMetrics())];

        // Act
        $box = RichTextBox::create($spans, maxWidth: 200);

        // Assert
        self::assertCount(1, $box->getLines());
    }

    #[Test]
    public function getLinesWrapsLongTextIntoMultipleLines(): void
    {
        // Arrange — 3 words that don't fit on one 20pt line
        $spans = [TextSpan::create('aa bb cc', 'F1', 10.0, $this->makeMetrics())];

        // Act
        $box = RichTextBox::create($spans, maxWidth: 20);

        // Assert
        self::assertCount(3, $box->getLines());
    }

    #[Test]
    public function getLinesReturnsArrayOfSpanArrays(): void
    {
        // Arrange
        $spans = [TextSpan::create('hello', 'F1', 10.0, $this->makeMetrics())];

        // Act
        $lines = RichTextBox::create($spans, maxWidth: 200)->getLines();

        // Assert — each line is a list of TextSpan
        self::assertIsArray($lines[0]);
        self::assertContainsOnlyInstancesOf(TextSpan::class, $lines[0]);
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
