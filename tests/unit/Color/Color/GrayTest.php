<?php

declare(strict_types=1);

namespace PhpPdf\Color\Color;

use PhpPdf\Color\Color;
use PhpPdf\Color\ColorType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Color::class)]
#[CoversMethod(Color::class, 'gray')]
final class GrayTest extends TestCase
{
    #[Test]
    public function graySetsCorrectType(): void
    {
        // Arrange
        $lightness = 0.0;

        // Act
        $color = Color::gray($lightness);

        // Assert
        self::assertSame(ColorType::Gray, $color->getType());
    }

    #[Test]
    public function grayStoresComponent(): void
    {
        // Arrange
        $lightness = 0.5;

        // Act
        $color = Color::gray($lightness);

        // Assert
        self::assertEqualsWithDelta([0.5], $color->getComponents(), 0.0001);
    }

    #[Test]
    public function grayClampsBelowZero(): void
    {
        // Arrange
        $lightness = -0.5;

        // Act
        $color = Color::gray($lightness);

        // Assert
        self::assertEqualsWithDelta([0.0], $color->getComponents(), 0.0001);
    }

    #[Test]
    public function grayClampsAboveOne(): void
    {
        // Arrange
        $lightness = 1.5;

        // Act
        $color = Color::gray($lightness);

        // Assert
        self::assertEqualsWithDelta([1.0], $color->getComponents(), 0.0001);
    }
}
