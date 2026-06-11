<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\HtmlTableRowData;

use PhpPdf\Html\Internal\HtmlTableRowData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlTableRowData::class)]
#[CoversMethod(HtmlTableRowData::class, 'setBackgroundColor')]
final class SetBackgroundColorTest extends TestCase
{
    #[Test]
    public function storesBackgroundColor(): void
    {
        // Arrange
        $row = new HtmlTableRowData();

        // Act
        $row->setBackgroundColor([0.9, 0.9, 0.9]);

        // Assert
        self::assertSame([0.9, 0.9, 0.9], $row->getBackgroundColor());
    }

    #[Test]
    public function storesNull(): void
    {
        // Arrange
        $row = new HtmlTableRowData();
        $row->setBackgroundColor([0.5, 0.5, 0.5]);

        // Act
        $row->setBackgroundColor(null);

        // Assert
        self::assertNull($row->getBackgroundColor());
    }
}
