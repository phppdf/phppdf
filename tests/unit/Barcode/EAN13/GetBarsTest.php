<?php

declare(strict_types=1);

namespace PhpPdf\Barcode\EAN13;

use PhpPdf\Barcode\EAN13;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

// EAN-13 is always exactly 95 modules:
//   left guard (3) + 6 left digits × 7 + centre guard (5) + 6 right digits × 7 + right guard (3)

#[CoversClass(EAN13::class)]
#[CoversMethod(EAN13::class, 'getBars')]
final class GetBarsTest extends TestCase
{
    #[Test]
    public function getBarsAlwaysSumsTo95Modules(): void
    {
        // Arrange
        $barcode = EAN13::encode('5901234123457');

        // Act
        $sum = array_sum($barcode->getBars());

        // Assert
        self::assertSame(95, $sum);
    }

    #[Test]
    public function getBarsModuleSumIsIndependentOfInput(): void
    {
        // Arrange
        $a = EAN13::encode('0000000000000');
        $b = EAN13::encode('9999999999994');

        // Act / Assert
        self::assertSame(95, array_sum($a->getBars()));
        self::assertSame(95, array_sum($b->getBars()));
    }
}
