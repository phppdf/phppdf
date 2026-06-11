<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\MeasuredBlock;

use PhpPdf\Html\Internal\ComputedStyle;
use PhpPdf\Html\Internal\LayoutBlock;
use PhpPdf\Html\Internal\LayoutBlockType;
use PhpPdf\Html\Internal\MeasuredBlock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MeasuredBlock::class)]
#[CoversMethod(MeasuredBlock::class, 'totalHeight')]
#[UsesClass(LayoutBlock::class)]
#[UsesClass(ComputedStyle::class)]
final class TotalHeightTest extends TestCase
{
    #[Test]
    public function sumsMarginsAndHeight(): void
    {
        // Arrange
        $style = new ComputedStyle('helvetica', 11.0);
        $block = new LayoutBlock(LayoutBlockType::Text, $style, 'Hello');
        $measured = new MeasuredBlock($block, 20.0, 5.0, 3.0);

        // Act
        $result = $measured->totalHeight();

        // Assert
        self::assertSame(28.0, $result);
    }

    #[Test]
    public function worksWithZeroMargins(): void
    {
        // Arrange
        $style = new ComputedStyle('helvetica', 11.0);
        $block = new LayoutBlock(LayoutBlockType::Text, $style, 'Hello');
        $measured = new MeasuredBlock($block, 15.0, 0.0, 0.0);

        // Act
        $result = $measured->totalHeight();

        // Assert
        self::assertSame(15.0, $result);
    }
}
