<?php

declare(strict_types=1);

namespace PhpPdf\Shading\ShadingFunctions;

use PhpPdf\Color\Color;
use PhpPdf\Shading\ShadingFunctions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ShadingFunctions::class)]
#[CoversMethod(ShadingFunctions::class, 'colorSpaceName')]
#[UsesClass(Color::class)]
final class ColorSpaceNameTest extends TestCase
{
    #[Test]
    public function colorSpaceNameReturnsDeviceGrayForGrayColor(): void
    {
        // Arrange
        $color = Color::gray(0.5);

        // Act
        $name = ShadingFunctions::colorSpaceName($color);

        // Assert
        self::assertSame('DeviceGray', $name);
    }

    #[Test]
    public function colorSpaceNameReturnsDeviceRgbForRgbColor(): void
    {
        // Arrange
        $color = Color::red();

        // Act
        $name = ShadingFunctions::colorSpaceName($color);

        // Assert
        self::assertSame('DeviceRGB', $name);
    }

    #[Test]
    public function colorSpaceNameReturnsDeviceCmykForCmykColor(): void
    {
        // Arrange
        $color = Color::cmyk(0.0, 1.0, 1.0, 0.0);

        // Act
        $name = ShadingFunctions::colorSpaceName($color);

        // Assert
        self::assertSame('DeviceCMYK', $name);
    }
}
