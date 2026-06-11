<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\HtmlTableCellData;

use PhpPdf\Html\Internal\HtmlTableCellData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlTableCellData::class)]
#[CoversMethod(HtmlTableCellData::class, 'setBold')]
final class SetBoldTest extends TestCase
{
    #[Test]
    public function storesBold(): void
    {
        // Arrange
        $cell = new HtmlTableCellData();

        // Act
        $cell->setBold(true);

        // Assert
        self::assertTrue($cell->isBold());
    }
}
