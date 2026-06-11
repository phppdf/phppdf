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
#[CoversMethod(MeasuredBlock::class, '__construct')]
#[UsesClass(LayoutBlock::class)]
#[UsesClass(ComputedStyle::class)]
final class ConstructTest extends TestCase
{
    #[Test]
    public function storesAllProperties(): void
    {
        // Arrange
        $style = new ComputedStyle('helvetica', 11.0);
        $block = new LayoutBlock(LayoutBlockType::Text, $style, 'Hello');

        // Act
        $measured = new MeasuredBlock($block, 20.0, 5.0, 3.0);

        // Assert
        self::assertSame($block, $measured->getBlock());
        self::assertSame(20.0, $measured->getHeight());
        self::assertSame(5.0, $measured->getMarginTop());
        self::assertSame(3.0, $measured->getMarginBottom());
    }
}
