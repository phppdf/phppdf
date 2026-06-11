<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\HtmlTableCellData;

use PhpPdf\Html\Internal\HtmlTableCellData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlTableCellData::class)]
#[CoversMethod(HtmlTableCellData::class, 'setColspan')]
final class SetColspanTest extends TestCase
{
    #[Test]
    public function storesColspan(): void
    {
        // Arrange
        $cell = new HtmlTableCellData();

        // Act
        $cell->setColspan(3);

        // Assert
        self::assertSame(3, $cell->getColspan());
    }
}
