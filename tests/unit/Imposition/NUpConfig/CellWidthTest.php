<?php

declare(strict_types=1);

namespace PhpPdf\Imposition\NUpConfig;

use PhpPdf\Imposition\NUpConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(NUpConfig::class)]
#[CoversMethod(NUpConfig::class, 'cellWidth')]
final class CellWidthTest extends TestCase
{
    #[Test]
    public function cellWidthIsCalculatedCorrectly(): void
    {
        // Arrange
        // sheetWidth=842, cols=2, margin=18, gutter=9
        // cellWidth = (842 - 2*18 - (2-1)*9) / 2 = (842 - 36 - 9) / 2 = 797 / 2 = 398.5
        $config = new NUpConfig(2, 1, 842, 595, 18.0, 9.0);

        // Act / Assert
        self::assertSame(398.5, $config->cellWidth());
    }

    #[Test]
    public function cellWidthForSingleColumnFillsEntireWidth(): void
    {
        // Arrange
        // sheetWidth=595, cols=1, margin=0, gutter=0
        // cellWidth = (595 - 0 - 0) / 1 = 595.0
        $config = new NUpConfig(1, 1, 595, 842, 0.0, 0.0);

        // Act / Assert
        self::assertSame(595.0, $config->cellWidth());
    }
}
