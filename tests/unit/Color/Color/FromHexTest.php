<?php

declare(strict_types=1);

namespace PhpPdf\Color\Color;

use InvalidArgumentException;
use PhpPdf\Color\Color;
use PhpPdf\Color\ColorType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Color::class)]
#[CoversMethod(Color::class, 'fromHex')]
final class FromHexTest extends TestCase
{
    #[Test]
    public function fromHexParsesFullHexWithHash(): void
    {
        // Arrange / Act
        $color = Color::fromHex('#ff0080');

        // Assert
        self::assertSame(ColorType::Rgb, $color->getType());
        self::assertEqualsWithDelta([1.0, 0.0, 128 / 255], $color->getComponents(), 0.001);
    }

    #[Test]
    public function fromHexParsesFullHexWithoutHash(): void
    {
        // Arrange / Act
        $color = Color::fromHex('ff0000');

        // Assert
        self::assertEqualsWithDelta([1.0, 0.0, 0.0], $color->getComponents(), 0.0001);
    }

    #[Test]
    public function fromHexExpandsShorthandNotation(): void
    {
        // Arrange / Act
        $color = Color::fromHex('#f00'); // expands to #ff0000

        // Assert
        self::assertEqualsWithDelta([1.0, 0.0, 0.0], $color->getComponents(), 0.0001);
    }

    #[Test]
    public function fromHexThrowsOnInvalidInput(): void
    {
        // Arrange
        $this->expectException(InvalidArgumentException::class);

        // Act
        Color::fromHex('not-a-color');
    }
}
