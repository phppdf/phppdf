<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\HtmlTableData;

use PhpPdf\Html\Internal\HtmlTableData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlTableData::class)]
#[CoversMethod(HtmlTableData::class, 'setBorderWidth')]
final class SetBorderWidthTest extends TestCase
{
    #[Test]
    public function storesBorderWidth(): void
    {
        // Arrange
        $table = new HtmlTableData();

        // Act
        $table->setBorderWidth(1.0);

        // Assert
        self::assertSame(1.0, $table->getBorderWidth());
    }
}
