<?php

declare(strict_types=1);

namespace PhpPdf\Color\Color;

use PhpPdf\Color\Color;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Color::class)]
#[CoversMethod(Color::class, 'darker')]
final class DarkerTest extends TestCase
{
    #[Test]
    public function darkerAtFullFactorMovesRgbToBlack(): void
    {
        // Arrange
        $color = Color::rgb(1.0, 1.0, 1.0);

        // Act
        $result = $color->darker(1.0);

        // Assert
        self::assertEqualsWithDelta([0.0, 0.0, 0.0], $result->getComponents(), 0.0001);
    }

    #[Test]
    public function darkerAtZeroFactorLeavesRgbUnchanged(): void
    {
        // Arrange
        $color = Color::rgb(0.4, 0.6, 0.8);

        // Act
        $result = $color->darker(0.0);

        // Assert
        self::assertEqualsWithDelta([0.4, 0.6, 0.8], $result->getComponents(), 0.0001);
    }

    #[Test]
    public function darkerAtFullFactorIncreasesCmykInkToOne(): void
    {
        // Arrange
        $color = Color::cmyk(0.0, 0.0, 0.0, 0.0);

        // Act
        $result = $color->darker(1.0);

        // Assert
        self::assertEqualsWithDelta([1.0, 1.0, 1.0, 1.0], $result->getComponents(), 0.0001);
    }
}
