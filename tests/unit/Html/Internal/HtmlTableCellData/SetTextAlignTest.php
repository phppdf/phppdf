<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\HtmlTableCellData;

use PhpPdf\Html\Internal\HtmlTableCellData;
use PhpPdf\Text\TextAlign;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlTableCellData::class)]
#[CoversMethod(HtmlTableCellData::class, 'setTextAlign')]
final class SetTextAlignTest extends TestCase
{
    #[Test]
    public function storesTextAlign(): void
    {
        // Arrange
        $cell = new HtmlTableCellData();

        // Act
        $cell->setTextAlign(TextAlign::Right);

        // Assert
        self::assertSame(TextAlign::Right, $cell->getTextAlign());
    }
}
