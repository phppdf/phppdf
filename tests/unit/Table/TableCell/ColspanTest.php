<?php

declare(strict_types=1);

namespace PhpPdf\Table\TableCell;

use PhpPdf\Table\TableCell;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TableCell::class)]
#[CoversMethod(TableCell::class, 'colspan')]
#[CoversMethod(TableCell::class, 'getColspan')]
final class ColspanTest extends TestCase
{
    #[Test]
    public function colspanSetsSpanAndReturnsSelf(): void
    {
        $cell = TableCell::text('X');
        $result = $cell->colspan(3);

        self::assertSame($cell, $result);
        self::assertSame(3, $cell->getColspan());
    }

    #[Test]
    public function colspanClampsToMinimumOne(): void
    {
        $cell = TableCell::text('X')->colspan(0);

        self::assertSame(1, $cell->getColspan());
    }

    #[Test]
    public function getColspanDefaultsToOne(): void
    {
        self::assertSame(1, TableCell::text('X')->getColspan());
    }
}
