<?php

declare(strict_types=1);

namespace PhpPdf\Barcode\Code128;

use PhpPdf\Barcode\Code128;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

// Code 128 structure:
//   Bar count  = 6n + 19  (n data chars; each symbol = 6 widths except Stop = 7)
//   Module sum = 11n + 35 (each non-Stop symbol = 11 modules; Stop = 13)

#[CoversClass(Code128::class)]
#[CoversMethod(Code128::class, 'getBars')]
final class GetBarsTest extends TestCase
{
    #[Test]
    public function getBarsReturnsCorrectBarCountForEmptyString(): void
    {
        // Arrange
        $barcode = Code128::encode('');

        // Act
        $bars = $barcode->getBars();

        // Assert
        self::assertCount(19, $bars);
    }

    #[Test]
    public function getBarsReturnsCorrectBarCountForOneCharacter(): void
    {
        // Arrange
        $barcode = Code128::encode('A');

        // Act
        $bars = $barcode->getBars();

        // Assert
        self::assertCount(25, $bars);
    }

    #[Test]
    public function getBarsBarCountGrowsBySixPerAdditionalCharacter(): void
    {
        // Arrange
        $barcode = Code128::encode('ABC');

        // Act
        $bars = $barcode->getBars();

        // Assert
        self::assertCount(37, $bars); // 6*3 + 19
    }

    #[Test]
    public function getBarsModuleSumIsCorrectForEmptyString(): void
    {
        // Arrange
        $barcode = Code128::encode('');

        // Act
        $sum = array_sum($barcode->getBars());

        // Assert
        self::assertSame(35, $sum); // 11*0 + 35
    }

    #[Test]
    public function getBarsModuleSumIsCorrectForOneCharacter(): void
    {
        // Arrange
        $barcode = Code128::encode('A');

        // Act
        $sum = array_sum($barcode->getBars());

        // Assert
        self::assertSame(46, $sum); // 11*1 + 35
    }
}
