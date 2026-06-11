<?php

declare(strict_types=1);

namespace PhpPdf\Table\TableCell;

use PhpPdf\Table\TableCell;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TableCell::class)]
#[CoversMethod(TableCell::class, 'text')]
#[CoversMethod(TableCell::class, 'getText')]
final class TextTest extends TestCase
{
    #[Test]
    public function textCreatesTableCellWithGivenText(): void
    {
        // Arrange / Act
        $cell = TableCell::text('Hello');

        // Assert
        self::assertInstanceOf(TableCell::class, $cell);
        self::assertSame('Hello', $cell->getText());
    }
}
