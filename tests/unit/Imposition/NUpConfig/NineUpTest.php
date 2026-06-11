<?php

declare(strict_types=1);

namespace PhpPdf\Imposition\NUpConfig;

use PhpPdf\Imposition\NUpConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(NUpConfig::class)]
#[CoversMethod(NUpConfig::class, 'nineUp')]
final class NineUpTest extends TestCase
{
    #[Test]
    public function nineUpReturnsConfigWith3Cols3Rows(): void
    {
        // Arrange / Act
        $config = NUpConfig::nineUp(595, 842);

        // Assert
        self::assertSame(3, $config->cols);
        self::assertSame(3, $config->rows);
        self::assertSame(595, $config->sheetWidth);
        self::assertSame(842, $config->sheetHeight);
    }

    #[Test]
    public function nineUpForwardsMarginAndGutter(): void
    {
        // Arrange / Act
        $config = NUpConfig::nineUp(595, 842, 10.0, 4.0);

        // Assert
        self::assertSame(10.0, $config->margin);
        self::assertSame(4.0, $config->gutter);
    }
}
