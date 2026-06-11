<?php

declare(strict_types=1);

namespace PhpPdf\Imposition\NUpConfig;

use PhpPdf\Imposition\NUpConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(NUpConfig::class)]
#[CoversMethod(NUpConfig::class, 'pagesPerSheet')]
final class PagesPerSheetTest extends TestCase
{
    #[Test]
    public function pagesPerSheetReturnsColsTimesRows(): void
    {
        // Arrange
        $config = new NUpConfig(3, 4, 595, 842);

        // Act / Assert
        self::assertSame(12, $config->pagesPerSheet());
    }

    #[Test]
    public function pagesPerSheetIsOneForSingleCellConfig(): void
    {
        // Arrange
        $config = new NUpConfig(1, 1, 595, 842);

        // Act / Assert
        self::assertSame(1, $config->pagesPerSheet());
    }
}
