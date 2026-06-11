<?php

declare(strict_types=1);

namespace PhpPdf\Color\Color;

use BadMethodCallException;
use PhpPdf\Color\Color;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Color::class)]
#[CoversMethod(Color::class, 'toHex')]
final class ToHexTest extends TestCase
{
    #[Test]
    public function toHexReturnsLowercaseHexString(): void
    {
        // Arrange
        $color = Color::rgb(1.0, 0.0, 0.0);

        // Act
        $hex = $color->toHex();

        // Assert
        self::assertSame('#ff0000', $hex);
    }

    #[Test]
    public function toHexRoundTripsFromHex(): void
    {
        // Arrange
        $original = '#336699';
        $color = Color::fromHex($original);

        // Act
        $hex = $color->toHex();

        // Assert
        self::assertSame($original, $hex);
    }

    #[Test]
    public function toHexThrowsForGrayColor(): void
    {
        // Arrange
        $color = Color::gray(0.5);
        $this->expectException(BadMethodCallException::class);

        // Act
        $color->toHex();
    }

    #[Test]
    public function toHexThrowsForCmykColor(): void
    {
        // Arrange
        $color = Color::cmyk(0.0, 0.0, 0.0, 0.5);
        $this->expectException(BadMethodCallException::class);

        // Act
        $color->toHex();
    }
}
