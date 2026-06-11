<?php

declare(strict_types=1);

namespace PhpPdf\Table\TableCell;

use PhpPdf\Color\Color;
use PhpPdf\Table\TableCell;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TableCell::class)]
#[CoversMethod(TableCell::class, 'background')]
#[CoversMethod(TableCell::class, 'getBackground')]
#[UsesClass(Color::class)]
final class BackgroundTest extends TestCase
{
    #[Test]
    public function backgroundSetsColorAndReturnsSelf(): void
    {
        // Arrange
        $cell = TableCell::text('X');
        $color = Color::rgb(1.0, 0.0, 0.0);

        // Act
        $result = $cell->background($color);

        // Assert
        self::assertSame($cell, $result);
        self::assertSame($color, $cell->getBackground());
    }

    #[Test]
    public function getBackgroundReturnsNullByDefault(): void
    {
        $cell = TableCell::text('X');

        self::assertNull($cell->getBackground());
    }
}
