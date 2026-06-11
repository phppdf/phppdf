<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\HtmlTableCellData;

use PhpPdf\Html\Internal\HtmlTableCellData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlTableCellData::class)]
#[CoversMethod(HtmlTableCellData::class, 'setBackgroundColor')]
final class SetBackgroundColorTest extends TestCase
{
    #[Test]
    public function storesBackgroundColor(): void
    {
        // Arrange
        $cell = new HtmlTableCellData();

        // Act
        $cell->setBackgroundColor([0.8, 0.8, 0.8]);

        // Assert
        self::assertSame([0.8, 0.8, 0.8], $cell->getBackgroundColor());
    }

    #[Test]
    public function storesNull(): void
    {
        // Arrange
        $cell = new HtmlTableCellData();
        $cell->setBackgroundColor([0.5, 0.5, 0.5]);

        // Act
        $cell->setBackgroundColor(null);

        // Assert
        self::assertNull($cell->getBackgroundColor());
    }
}
