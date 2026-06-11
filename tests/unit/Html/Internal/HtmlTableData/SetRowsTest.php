<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\HtmlTableData;

use PhpPdf\Html\Internal\HtmlTableData;
use PhpPdf\Html\Internal\HtmlTableRowData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlTableData::class)]
#[CoversMethod(HtmlTableData::class, 'setRows')]
#[UsesClass(HtmlTableRowData::class)]
final class SetRowsTest extends TestCase
{
    #[Test]
    public function storesRows(): void
    {
        // Arrange
        $table = new HtmlTableData();
        $row1 = new HtmlTableRowData();
        $row2 = new HtmlTableRowData();

        // Act
        $table->setRows([$row1, $row2]);

        // Assert
        self::assertSame([$row1, $row2], $table->getRows());
    }
}
