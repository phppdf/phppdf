<?php

declare(strict_types=1);

namespace PhpPdf\Table\TableCell;

use PhpPdf\Table\TableCell;
use PhpPdf\Table\TableVerticalAlign;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TableCell::class)]
#[CoversMethod(TableCell::class, 'verticalAlign')]
#[CoversMethod(TableCell::class, 'getVerticalAlign')]
final class VerticalAlignTest extends TestCase
{
    #[Test]
    public function verticalAlignSetsAndReturnsSelf(): void
    {
        $cell = TableCell::text('X');
        $result = $cell->verticalAlign(TableVerticalAlign::Bottom);

        self::assertSame($cell, $result);
        self::assertSame(TableVerticalAlign::Bottom, $cell->getVerticalAlign());
    }

    #[Test]
    public function getVerticalAlignDefaultsToTop(): void
    {
        self::assertSame(TableVerticalAlign::Top, TableCell::text('X')->getVerticalAlign());
    }
}
