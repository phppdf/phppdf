<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\HtmlTableCellData;

use PhpPdf\Html\Internal\HtmlTableCellData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlTableCellData::class)]
#[CoversMethod(HtmlTableCellData::class, 'setColor')]
final class SetColorTest extends TestCase
{
    #[Test]
    public function storesColor(): void
    {
        // Arrange
        $cell = new HtmlTableCellData();

        // Act
        $cell->setColor([0.0, 0.5, 1.0]);

        // Assert
        self::assertSame([0.0, 0.5, 1.0], $cell->getColor());
    }

    #[Test]
    public function storesNull(): void
    {
        // Arrange
        $cell = new HtmlTableCellData();
        $cell->setColor([1.0, 0.0, 0.0]);

        // Act
        $cell->setColor(null);

        // Assert
        self::assertNull($cell->getColor());
    }
}
