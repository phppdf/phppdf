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
#[CoversMethod(TableCell::class, 'textColor')]
#[CoversMethod(TableCell::class, 'getTextColor')]
#[UsesClass(Color::class)]
final class TextColorTest extends TestCase
{
    #[Test]
    public function textColorSetsColorAndReturnsSelf(): void
    {
        $cell = TableCell::text('X');
        $color = Color::white();

        $result = $cell->textColor($color);

        self::assertSame($cell, $result);
        self::assertSame($color, $cell->getTextColor());
    }

    #[Test]
    public function getTextColorReturnsNullByDefault(): void
    {
        self::assertNull(TableCell::text('X')->getTextColor());
    }
}
