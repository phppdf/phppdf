<?php

declare(strict_types=1);

namespace PhpPdf\Imposition\NUpConfig;

use PhpPdf\Imposition\NUpConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(NUpConfig::class)]
#[CoversMethod(NUpConfig::class, 'twoUp')]
final class TwoUpTest extends TestCase
{
    #[Test]
    public function twoUpReturnsConfigWith2Cols1Row(): void
    {
        // Arrange / Act
        $config = NUpConfig::twoUp(842, 595);

        // Assert
        self::assertSame(2, $config->cols);
        self::assertSame(1, $config->rows);
        self::assertSame(842, $config->sheetWidth);
        self::assertSame(595, $config->sheetHeight);
    }

    #[Test]
    public function twoUpForwardsMarginAndGutter(): void
    {
        // Arrange / Act
        $config = NUpConfig::twoUp(842, 595, 12.0, 6.0);

        // Assert
        self::assertSame(12.0, $config->margin);
        self::assertSame(6.0, $config->gutter);
    }
}
