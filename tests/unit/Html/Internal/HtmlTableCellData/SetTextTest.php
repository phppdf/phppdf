<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\HtmlTableCellData;

use PhpPdf\Html\Internal\HtmlTableCellData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlTableCellData::class)]
#[CoversMethod(HtmlTableCellData::class, 'setText')]
final class SetTextTest extends TestCase
{
    #[Test]
    public function storesText(): void
    {
        // Arrange
        $cell = new HtmlTableCellData();

        // Act
        $cell->setText('Hello World');

        // Assert
        self::assertSame('Hello World', $cell->getText());
    }
}
