<?php

declare(strict_types=1);

namespace PhpPdf\Imposition\NUpConfig;

use PhpPdf\Imposition\NUpConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(NUpConfig::class)]
#[CoversMethod(NUpConfig::class, 'cellOrigin')]
final class CellOriginTest extends TestCase
{
    /**
     * 2-up (2 cols × 1 row), no margin/gutter, sheet 200×100.
     * cellWidth = 100, cellHeight = 100.
     *
     * Position 0 → col=0, rowLTR=0, pdfRow=(1-1-0)=0 → x=0, y=0
     * Position 1 → col=1, rowLTR=0, pdfRow=0 → x=100, y=0
     */
    #[Test]
    public function cellOriginPosition0IsBottomLeft(): void
    {
        // Arrange
        $config = new NUpConfig(2, 1, 200, 100, 0.0, 0.0);

        // Act
        [$x, $y] = $config->cellOrigin(0);

        // Assert
        self::assertSame(0.0, $x);
        self::assertSame(0.0, $y);
    }

    #[Test]
    public function cellOriginPosition1IsRightCell(): void
    {
        // Arrange
        $config = new NUpConfig(2, 1, 200, 100, 0.0, 0.0);

        // Act
        [$x, $y] = $config->cellOrigin(1);

        // Assert
        self::assertSame(100.0, $x);
        self::assertSame(0.0, $y);
    }

    /**
     * 2×2 grid, no margin/gutter, sheet 200×200.
     * cellWidth = 100, cellHeight = 100.
     *
     * Reading order: pos 0 = top-left, pos 1 = top-right,
     *                 pos 2 = bottom-left, pos 3 = bottom-right.
     * PDF y goes up, so "top" in reading order = higher y in PDF.
     *
     * pos 0 → col=0, rowLTR=0, pdfRow=1 → x=0, y=100
     * pos 1 → col=1, rowLTR=0, pdfRow=1 → x=100, y=100
     * pos 2 → col=0, rowLTR=1, pdfRow=0 → x=0, y=0
     * pos 3 → col=1, rowLTR=1, pdfRow=0 → x=100, y=0
     */
    #[Test]
    public function cellOriginTopLeftInTwoByTwoGrid(): void
    {
        // Arrange
        $config = new NUpConfig(2, 2, 200, 200, 0.0, 0.0);

        // Act
        [$x, $y] = $config->cellOrigin(0);

        // Assert – position 0 is the top-left cell; PDF y grows up so y=100
        self::assertSame(0.0, $x);
        self::assertSame(100.0, $y);
    }

    #[Test]
    public function cellOriginBottomRightInTwoByTwoGrid(): void
    {
        // Arrange
        $config = new NUpConfig(2, 2, 200, 200, 0.0, 0.0);

        // Act
        [$x, $y] = $config->cellOrigin(3);

        // Assert – position 3 is the bottom-right cell
        self::assertSame(100.0, $x);
        self::assertSame(0.0, $y);
    }

    #[Test]
    public function cellOriginAccountsForMarginAndGutter(): void
    {
        // Arrange
        // 2 cols, 1 row, sheet 200×100, margin=10, gutter=5
        // cellWidth = (200 - 2*10 - 1*5) / 2 = (200 - 25) / 2 = 87.5
        // cellHeight = (100 - 2*10 - 0*5) / 1 = 80.0
        //
        // pos 0 → col=0, pdfRow=0 → x = margin = 10, y = margin = 10
        // pos 1 → col=1, pdfRow=0 → x = 10 + 1*(87.5 + 5) = 10 + 92.5 = 102.5, y = 10
        $config = new NUpConfig(2, 1, 200, 100, 10.0, 5.0);

        // Act
        [$x0, $y0] = $config->cellOrigin(0);
        [$x1, $y1] = $config->cellOrigin(1);

        // Assert
        self::assertSame(10.0, $x0);
        self::assertSame(10.0, $y0);
        self::assertSame(102.5, $x1);
        self::assertSame(10.0, $y1);
    }
}
