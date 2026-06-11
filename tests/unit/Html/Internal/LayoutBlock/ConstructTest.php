<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\LayoutBlock;

use PhpPdf\Html\Internal\ComputedStyle;
use PhpPdf\Html\Internal\HtmlTableData;
use PhpPdf\Html\Internal\LayoutBlock;
use PhpPdf\Html\Internal\LayoutBlockType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LayoutBlock::class)]
#[CoversMethod(LayoutBlock::class, '__construct')]
#[UsesClass(ComputedStyle::class)]
final class ConstructTest extends TestCase
{
    #[Test]
    public function storesTypeStyleAndText(): void
    {
        // Arrange
        $style = new ComputedStyle('helvetica', 11.0);

        // Act
        $block = new LayoutBlock(LayoutBlockType::Text, $style, 'Hello');

        // Assert
        self::assertSame(LayoutBlockType::Text, $block->getType());
        self::assertSame($style, $block->getStyle());
        self::assertSame('Hello', $block->getText());
        self::assertNull($block->getTableData());
    }

    #[Test]
    public function storesOptionalTableData(): void
    {
        // Arrange
        $style = new ComputedStyle('helvetica', 11.0);
        $tableData = new HtmlTableData();

        // Act
        $block = new LayoutBlock(LayoutBlockType::Table, $style, '', $tableData);

        // Assert
        self::assertSame($tableData, $block->getTableData());
    }
}
