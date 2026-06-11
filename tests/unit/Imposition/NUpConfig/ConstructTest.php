<?php

declare(strict_types=1);

namespace PhpPdf\Imposition\NUpConfig;

use InvalidArgumentException;
use PhpPdf\Imposition\NUpConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(NUpConfig::class)]
#[CoversMethod(NUpConfig::class, '__construct')]
final class ConstructTest extends TestCase
{
    #[Test]
    public function constructSetsProperties(): void
    {
        // Arrange / Act
        $config = new NUpConfig(3, 2, 842, 595, 10.0, 5.0);

        // Assert
        self::assertSame(3, $config->cols);
        self::assertSame(2, $config->rows);
        self::assertSame(842, $config->sheetWidth);
        self::assertSame(595, $config->sheetHeight);
        self::assertSame(10.0, $config->margin);
        self::assertSame(5.0, $config->gutter);
    }

    #[Test]
    public function constructUsesDefaultMarginAndGutter(): void
    {
        // Arrange / Act
        $config = new NUpConfig(1, 1, 595, 842);

        // Assert
        self::assertSame(18.0, $config->margin);
        self::assertSame(9.0, $config->gutter);
    }

    #[Test]
    public function constructThrowsWhenColsIsZero(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        new NUpConfig(0, 1, 595, 842);
    }

    #[Test]
    public function constructThrowsWhenRowsIsZero(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        new NUpConfig(1, 0, 595, 842);
    }

    #[Test]
    public function constructThrowsWhenSheetWidthIsZero(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        new NUpConfig(1, 1, 0, 842);
    }

    #[Test]
    public function constructThrowsWhenSheetHeightIsZero(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        new NUpConfig(1, 1, 595, 0);
    }
}
