<?php

declare(strict_types=1);

namespace PhpPdf\Shading\ColorStop;

use InvalidArgumentException;
use PhpPdf\Color\Color;
use PhpPdf\Shading\ColorStop;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ColorStop::class)]
#[UsesClass(Color::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorStoresOffset(): void
    {
        // Arrange / Act
        $stop = new ColorStop(0.5, Color::red());

        // Assert
        self::assertSame(0.5, $stop->offset);
    }

    #[Test]
    public function constructorStoresColor(): void
    {
        // Arrange
        $color = Color::blue();

        // Act
        $stop = new ColorStop(0.0, $color);

        // Assert
        self::assertSame($color, $stop->color);
    }

    #[Test]
    public function constructorAcceptsZeroOffset(): void
    {
        // Arrange / Act
        $stop = new ColorStop(0.0, Color::black());

        // Assert
        self::assertSame(0.0, $stop->offset);
    }

    #[Test]
    public function constructorAcceptsOneOffset(): void
    {
        // Arrange / Act
        $stop = new ColorStop(1.0, Color::white());

        // Assert
        self::assertSame(1.0, $stop->offset);
    }

    #[Test]
    public function constructorThrowsWhenOffsetIsNegative(): void
    {
        // Arrange / Act / Assert
        $this->expectException(InvalidArgumentException::class);
        new ColorStop(-0.1, Color::red());
    }

    #[Test]
    public function constructorThrowsWhenOffsetExceedsOne(): void
    {
        // Arrange / Act / Assert
        $this->expectException(InvalidArgumentException::class);
        new ColorStop(1.1, Color::red());
    }
}
