<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\HtmlTableRowData;

use PhpPdf\Html\Internal\HtmlTableCellData;
use PhpPdf\Html\Internal\HtmlTableRowData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlTableRowData::class)]
#[CoversMethod(HtmlTableRowData::class, 'addCell')]
#[UsesClass(HtmlTableCellData::class)]
final class AddCellTest extends TestCase
{
    #[Test]
    public function appendsCellToList(): void
    {
        // Arrange
        $row = new HtmlTableRowData();
        $cell1 = new HtmlTableCellData();
        $cell2 = new HtmlTableCellData();

        // Act
        $row->addCell($cell1);
        $row->addCell($cell2);

        // Assert
        self::assertSame([$cell1, $cell2], $row->getCells());
    }
}
