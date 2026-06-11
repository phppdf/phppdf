<?php

declare(strict_types=1);

namespace PhpPdf\Table\TableCell;

use PhpPdf\Table\TableCell;
use PhpPdf\Text\TextAlign;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TableCell::class)]
#[CoversMethod(TableCell::class, 'align')]
#[CoversMethod(TableCell::class, 'getAlign')]
final class AlignTest extends TestCase
{
    #[Test]
    public function alignSetsAlignAndReturnsSelf(): void
    {
        $cell = TableCell::text('X');
        $result = $cell->align(TextAlign::Right);

        self::assertSame($cell, $result);
        self::assertSame(TextAlign::Right, $cell->getAlign());
    }

    #[Test]
    public function getAlignDefaultsToLeft(): void
    {
        self::assertSame(TextAlign::Left, TableCell::text('X')->getAlign());
    }
}
