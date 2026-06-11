<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\HtmlTableCellData;

use PhpPdf\Html\Internal\HtmlTableCellData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlTableCellData::class)]
#[CoversMethod(HtmlTableCellData::class, 'setItalic')]
final class SetItalicTest extends TestCase
{
    #[Test]
    public function storesItalic(): void
    {
        // Arrange
        $cell = new HtmlTableCellData();

        // Act
        $cell->setItalic(true);

        // Assert
        self::assertTrue($cell->isItalic());
    }
}
