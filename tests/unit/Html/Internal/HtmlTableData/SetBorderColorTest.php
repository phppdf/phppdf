<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\HtmlTableData;

use PhpPdf\Html\Internal\HtmlTableData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlTableData::class)]
#[CoversMethod(HtmlTableData::class, 'setBorderColor')]
final class SetBorderColorTest extends TestCase
{
    #[Test]
    public function storesBorderColor(): void
    {
        // Arrange
        $table = new HtmlTableData();

        // Act
        $table->setBorderColor([0.0, 0.0, 0.0]);

        // Assert
        self::assertSame([0.0, 0.0, 0.0], $table->getBorderColor());
    }

    #[Test]
    public function storesNull(): void
    {
        // Arrange
        $table = new HtmlTableData();
        $table->setBorderColor([0.0, 0.0, 0.0]);

        // Act
        $table->setBorderColor(null);

        // Assert
        self::assertNull($table->getBorderColor());
    }
}
