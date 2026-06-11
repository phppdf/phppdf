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
#[CoversMethod(QrCode::class, 'gfInit')]
#[CoversMethod(QrCode::class, 'gfMul')]
final class GfMulTest extends TestCase
{
    #[Test]
    public function returnsZeroWhenFirstOperandIsZero(): void
    {
        // Arrange
        // gfMul is private static; access via reflection.
        // gfInit must have run first so the GF tables are populated.
        $init = new ReflectionMethod(QrCode::class, 'gfInit');
        $init->invoke(null);
        $mul = new ReflectionMethod(QrCode::class, 'gfMul');

        // Act
        $result = $mul->invoke(null, 0, 5);

        // Assert
        self::assertSame(0, $result);
    }

    #[Test]
    public function returnsZeroWhenSecondOperandIsZero(): void
    {
        // Arrange
        $init = new ReflectionMethod(QrCode::class, 'gfInit');
        $init->invoke(null);
        $mul = new ReflectionMethod(QrCode::class, 'gfMul');

        // Act
        $result = $mul->invoke(null, 5, 0);

        // Assert
        self::assertSame(0, $result);
    }

    #[Test]
    public function multipliesNonZeroElements(): void
    {
        // Arrange
        // alpha^1 * alpha^1 = alpha^2 = 4 in GF(2^8) with primitive polynomial 0x11D
        $init = new ReflectionMethod(QrCode::class, 'gfInit');
        $init->invoke(null);
        $mul = new ReflectionMethod(QrCode::class, 'gfMul');

        // Act
        $result = $mul->invoke(null, 2, 2); // 2 = alpha^1; 2*2 = alpha^2 = 4

        // Assert
        self::assertSame(4, $result);
    }
}
