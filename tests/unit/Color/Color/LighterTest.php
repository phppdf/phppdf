<?php

declare(strict_types=1);

namespace PhpPdf\Color\Color;

use PhpPdf\Color\Color;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Color::class)]
#[CoversMethod(Color::class, 'lighter')]
final class LighterTest extends TestCase
{
    #[Test]
    public function lighterAtFullFactorMovesRgbToWhite(): void
    {
        // Arrange
        $color = Color::rgb(0.0, 0.0, 0.0);

        // Act
        $result = $color->lighter(1.0);

        // Assert
        self::assertEqualsWithDelta([1.0, 1.0, 1.0], $result->getComponents(), 0.0001);
    }

    #[Test]
    public function lighterAtZeroFactorLeavesRgbUnchanged(): void
    {
        // Arrange
        $color = Color::rgb(0.4, 0.6, 0.8);

        // Act
        $result = $color->lighter(0.0);

        // Assert
        self::assertEqualsWithDelta([0.4, 0.6, 0.8], $result->getComponents(), 0.0001);
    }

    #[Test]
    public function lighterAtFullFactorReducesCmykInkToZero(): void
    {
        // Arrange
        $color = Color::cmyk(1.0, 1.0, 1.0, 1.0);

        // Act
        $result = $color->lighter(1.0);

        // Assert
        self::assertEqualsWithDelta([0.0, 0.0, 0.0, 0.0], $result->getComponents(), 0.0001);
    }
}
