<?php

declare(strict_types=1);

namespace PhpPdf\Table\TableCell;

use PhpPdf\Font\Type1FontMetrics;
use PhpPdf\Table\TableCell;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TableCell::class)]
#[CoversMethod(TableCell::class, 'font')]
#[CoversMethod(TableCell::class, 'getFontName')]
#[CoversMethod(TableCell::class, 'getFontSize')]
#[CoversMethod(TableCell::class, 'getMetrics')]
#[UsesClass(Type1FontMetrics::class)]
final class FontTest extends TestCase
{
    #[Test]
    public function fontSetsAllValuesAndReturnsSelf(): void
    {
        // Arrange
        $cell = TableCell::text('X');
        $metrics = Type1FontMetrics::helvetica();

        // Act
        $result = $cell->font('F1', 12.0, $metrics);

        // Assert
        self::assertSame($cell, $result);
        self::assertSame('F1', $cell->getFontName());
        self::assertSame(12.0, $cell->getFontSize());
        self::assertSame($metrics, $cell->getMetrics());
    }

    #[Test]
    public function fontGettersReturnNullByDefault(): void
    {
        $cell = TableCell::text('X');

        self::assertNull($cell->getFontName());
        self::assertNull($cell->getFontSize());
        self::assertNull($cell->getMetrics());
    }
}
