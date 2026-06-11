<?php

declare(strict_types=1);

namespace PhpPdf\Table\Table\TableCell;

use PhpPdf\Font\FontMetrics;
use PhpPdf\Table\TableCell;
use PhpPdf\Text\TextSpan;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TableCell::class)]
#[CoversMethod(TableCell::class, 'spans')]
#[CoversMethod(TableCell::class, 'getSpans')]
#[UsesClass(TextSpan::class)]
final class SpansTest extends TestCase
{
    #[Test]
    public function spansCreatesTableCellWithGivenSpans(): void
    {
        // Arrange
        $spanList = [TextSpan::create('Hello', 'F1', 10.0, $this->makeMetrics())];

        // Act
        $cell = TableCell::spans($spanList);

        // Assert
        self::assertInstanceOf(TableCell::class, $cell);
        self::assertSame($spanList, $cell->getSpans());
    }

    #[Test]
    public function spansStoredTextIsEmpty(): void
    {
        // Arrange / Act
        $cell = TableCell::spans([TextSpan::create('hi', 'F1', 10.0, $this->makeMetrics())]);

        // Assert — spans() cell has no plain text content
        self::assertSame('', $cell->getText());
    }

    #[Test]
    public function getSpansReturnsNullForPlainTextCell(): void
    {
        // Arrange / Act
        $cell = TableCell::text('plain');

        // Assert
        self::assertNull($cell->getSpans());
    }

    #[Test]
    public function spansAcceptsEmptyArray(): void
    {
        // Arrange / Act
        $cell = TableCell::spans([]);

        // Assert
        self::assertSame([], $cell->getSpans());
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
