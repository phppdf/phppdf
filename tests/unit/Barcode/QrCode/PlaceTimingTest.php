<?php

declare(strict_types=1);

namespace PhpPdf\Barcode\QrCode;

use PhpPdf\Barcode\QrCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

#[CoversClass(QrCode::class)]
#[CoversMethod(QrCode::class, 'placeTiming')]
final class PlaceTimingTest extends TestCase
{
    #[Test]
    public function skipsVerticalTimingModuleAlreadyPlaced(): void
    {
        // Arrange
        // placeTiming is private static; access via reflection. Pre-seed the
        // vertical timing column (column 6) at row 8 so the `mat[$i][6] !== null`
        // guard is true, exercising the `continue` branch (line 422).
        $n = 21;
        $mat = array_fill(0, $n, array_fill(0, $n, null));
        $fixed = array_fill(0, $n, array_fill(0, $n, false));
        $mat[8][6] = true;
        $fixed[8][6] = true;

        $method = new ReflectionMethod(QrCode::class, 'placeTiming');

        // Act
        $method->invokeArgs(null, [&$mat, &$fixed, $n]);

        // Assert — the pre-seeded value at (8, 6) is left untouched
        self::assertTrue($mat[8][6]);
        // Assert — the horizontal timing module at (6, 8) is still placed normally
        self::assertTrue($mat[6][8]);
    }
}
