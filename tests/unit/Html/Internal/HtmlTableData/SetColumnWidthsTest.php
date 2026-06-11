<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\HtmlTableData;

use PhpPdf\Html\Internal\HtmlTableData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlTableData::class)]
#[CoversMethod(HtmlTableData::class, 'setColumnWidths')]
final class SetColumnWidthsTest extends TestCase
{
    #[Test]
    public function storesColumnWidths(): void
    {
        // Arrange
        $table = new HtmlTableData();

        // Act
        $table->setColumnWidths([100.0, 150.0, 80.0]);

        // Assert
        self::assertSame([100.0, 150.0, 80.0], $table->getColumnWidths());
    }
}
