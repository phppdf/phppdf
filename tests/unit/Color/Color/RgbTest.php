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
#[CoversMethod(Color::class, 'rgb')]
final class RgbTest extends TestCase
{
    #[Test]
    public function rgbSetsCorrectType(): void
    {
        // Arrange / Act
        $color = Color::rgb(1.0, 0.0, 0.0);

        // Assert
        self::assertSame(ColorType::Rgb, $color->getType());
    }

    #[Test]
    public function rgbStoresComponents(): void
    {
        // Arrange / Act
        $color = Color::rgb(0.2, 0.5, 0.8);

        // Assert
        self::assertEqualsWithDelta([0.2, 0.5, 0.8], $color->getComponents(), 0.0001);
    }

    #[Test]
    public function rgbClampsComponents(): void
    {
        // Arrange / Act
        $color = Color::rgb(-0.1, 0.5, 1.5);

        // Assert
        self::assertEqualsWithDelta([0.0, 0.5, 1.0], $color->getComponents(), 0.0001);
    }
}
