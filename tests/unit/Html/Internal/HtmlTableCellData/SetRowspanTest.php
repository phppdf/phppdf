<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\HtmlTableCellData;

use PhpPdf\Html\Internal\HtmlTableCellData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlTableCellData::class)]
#[CoversMethod(HtmlTableCellData::class, 'setRowspan')]
final class SetRowspanTest extends TestCase
{
    #[Test]
    public function storesRowspan(): void
    {
        // Arrange
        $cell = new HtmlTableCellData();

        // Act
        $cell->setRowspan(2);

        // Assert
        self::assertSame(2, $cell->getRowspan());
    }
}
