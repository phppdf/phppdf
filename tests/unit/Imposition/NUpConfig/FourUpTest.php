<?php

declare(strict_types=1);

namespace PhpPdf\Imposition\NUpConfig;

use PhpPdf\Imposition\NUpConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(NUpConfig::class)]
#[CoversMethod(NUpConfig::class, 'fourUp')]
final class FourUpTest extends TestCase
{
    #[Test]
    public function fourUpReturnsConfigWith2Cols2Rows(): void
    {
        // Arrange / Act
        $config = NUpConfig::fourUp(595, 842);

        // Assert
        self::assertSame(2, $config->cols);
        self::assertSame(2, $config->rows);
        self::assertSame(595, $config->sheetWidth);
        self::assertSame(842, $config->sheetHeight);
    }

    #[Test]
    public function fourUpForwardsMarginAndGutter(): void
    {
        // Arrange / Act
        $config = NUpConfig::fourUp(595, 842, 15.0, 7.0);

        // Assert
        self::assertSame(15.0, $config->margin);
        self::assertSame(7.0, $config->gutter);
    }
}
