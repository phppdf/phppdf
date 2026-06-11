<?php

declare(strict_types=1);

namespace PhpPdf\Table\TableRow;

use PhpPdf\Color\Color;
use PhpPdf\Table\TableCell;
use PhpPdf\Table\TableRow;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TableRow::class)]
#[CoversMethod(TableRow::class, 'cells')]
#[CoversMethod(TableRow::class, 'getCells')]
#[CoversMethod(TableRow::class, 'background')]
#[CoversMethod(TableRow::class, 'getBackground')]
#[UsesClass(TableCell::class)]
#[UsesClass(Color::class)]
final class CellsTest extends TestCase
{
    #[Test]
    public function cellsCreatesRowWithCells(): void
    {
        // Arrange
        $cellA = TableCell::text('A');
        $cellB = TableCell::text('B');

        // Act
        $row = TableRow::cells([$cellA, $cellB]);

        // Assert
        self::assertInstanceOf(TableRow::class, $row);
        self::assertSame([$cellA, $cellB], $row->getCells());
    }

    #[Test]
    public function getBackgroundReturnsNullByDefault(): void
    {
        $row = TableRow::cells([]);

        self::assertNull($row->getBackground());
    }

    #[Test]
    public function backgroundSetsColorAndReturnsSelf(): void
    {
        $color = Color::rgb(0.9, 0.9, 0.9);
        $row = TableRow::cells([]);

        $result = $row->background($color);

        self::assertSame($row, $result);
        self::assertSame($color, $row->getBackground());
    }
}
