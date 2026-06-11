<?php

declare(strict_types=1);

namespace PhpPdf\Table\TableCell;

use PhpPdf\Table\TableCell;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TableCell::class)]
#[CoversMethod(TableCell::class, 'padding')]
#[CoversMethod(TableCell::class, 'getPaddingTop')]
#[CoversMethod(TableCell::class, 'getPaddingRight')]
#[CoversMethod(TableCell::class, 'getPaddingBottom')]
#[CoversMethod(TableCell::class, 'getPaddingLeft')]
final class PaddingTest extends TestCase
{
    #[Test]
    public function paddingSetsAllSidesAndReturnsSelf(): void
    {
        $cell = TableCell::text('X');
        $result = $cell->padding(10, 8, 6, 4);

        self::assertSame($cell, $result);
        self::assertSame(10.0, $cell->getPaddingTop());
        self::assertSame(8.0, $cell->getPaddingRight());
        self::assertSame(6.0, $cell->getPaddingBottom());
        self::assertSame(4.0, $cell->getPaddingLeft());
    }

    #[Test]
    public function paddingGettersReturnNullByDefault(): void
    {
        $cell = TableCell::text('X');

        self::assertNull($cell->getPaddingTop());
        self::assertNull($cell->getPaddingRight());
        self::assertNull($cell->getPaddingBottom());
        self::assertNull($cell->getPaddingLeft());
    }
}
