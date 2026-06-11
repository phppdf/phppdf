<?php

declare(strict_types=1);

namespace PhpPdf\Imposition\NUpConfig;

use PhpPdf\Imposition\NUpConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(NUpConfig::class)]
#[CoversMethod(NUpConfig::class, 'cellHeight')]
final class CellHeightTest extends TestCase
{
    #[Test]
    public function cellHeightIsCalculatedCorrectly(): void
    {
        // Arrange
        // sheetHeight=842, rows=2, margin=18, gutter=9
        // cellHeight = (842 - 2*18 - (2-1)*9) / 2 = (842 - 36 - 9) / 2 = 797 / 2 = 398.5
        $config = new NUpConfig(1, 2, 595, 842, 18.0, 9.0);

        // Act / Assert
        self::assertSame(398.5, $config->cellHeight());
    }

    #[Test]
    public function cellHeightForSingleRowFillsEntireHeight(): void
    {
        // Arrange
        // sheetHeight=842, rows=1, margin=0, gutter=0
        // cellHeight = (842 - 0 - 0) / 1 = 842.0
        $config = new NUpConfig(1, 1, 595, 842, 0.0, 0.0);

        // Act / Assert
        self::assertSame(842.0, $config->cellHeight());
    }
}
