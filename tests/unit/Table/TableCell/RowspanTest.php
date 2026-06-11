<?php

declare(strict_types=1);

namespace PhpPdf\Table\TableCell;

use PhpPdf\Table\TableCell;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TableCell::class)]
#[CoversMethod(TableCell::class, 'rowspan')]
#[CoversMethod(TableCell::class, 'getRowspan')]
final class RowspanTest extends TestCase
{
    #[Test]
    public function rowspanSetsSpanAndReturnsSelf(): void
    {
        $cell = TableCell::text('X');
        $result = $cell->rowspan(2);

        self::assertSame($cell, $result);
        self::assertSame(2, $cell->getRowspan());
    }

    #[Test]
    public function rowspanClampsToMinimumOne(): void
    {
        $cell = TableCell::text('X')->rowspan(-1);

        self::assertSame(1, $cell->getRowspan());
    }

    #[Test]
    public function getRowspanDefaultsToOne(): void
    {
        self::assertSame(1, TableCell::text('X')->getRowspan());
    }
}
