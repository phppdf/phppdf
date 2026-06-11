<?php

declare(strict_types=1);

namespace PhpPdf\Imposition\NUpConfig;

use PhpPdf\Imposition\NUpConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(NUpConfig::class)]
#[CoversMethod(NUpConfig::class, 'sixteenUp')]
final class SixteenUpTest extends TestCase
{
    #[Test]
    public function sixteenUpReturnsConfigWith4Cols4Rows(): void
    {
        // Arrange / Act
        $config = NUpConfig::sixteenUp(595, 842);

        // Assert
        self::assertSame(4, $config->cols);
        self::assertSame(4, $config->rows);
        self::assertSame(595, $config->sheetWidth);
        self::assertSame(842, $config->sheetHeight);
    }

    #[Test]
    public function sixteenUpForwardsMarginAndGutter(): void
    {
        // Arrange / Act
        $config = NUpConfig::sixteenUp(595, 842, 8.0, 3.0);

        // Assert
        self::assertSame(8.0, $config->margin);
        self::assertSame(3.0, $config->gutter);
    }
}
