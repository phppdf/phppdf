<?php

declare(strict_types=1);

namespace PhpPdf\Color\Color;

use InvalidArgumentException;
use PhpPdf\Color\Color;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Color::class)]
#[CoversMethod(Color::class, 'mix')]
final class MixTest extends TestCase
{
    #[Test]
    public function mixAtHalfwayProducesAverageOfComponents(): void
    {
        // Arrange
        $a = Color::rgb(0.0, 0.0, 0.0);
        $b = Color::rgb(1.0, 1.0, 1.0);

        // Act
        $result = $a->mix($b, 0.5);

        // Assert
        self::assertEqualsWithDelta([0.5, 0.5, 0.5], $result->getComponents(), 0.0001);
    }

    #[Test]
    public function mixAtZeroReturnsFirstColor(): void
    {
        // Arrange
        $a = Color::rgb(0.2, 0.4, 0.6);
        $b = Color::rgb(0.8, 0.6, 0.4);

        // Act
        $result = $a->mix($b, 0.0);

        // Assert
        self::assertEqualsWithDelta([0.2, 0.4, 0.6], $result->getComponents(), 0.0001);
    }

    #[Test]
    public function mixAtOneReturnsSecondColor(): void
    {
        // Arrange
        $a = Color::rgb(0.2, 0.4, 0.6);
        $b = Color::rgb(0.8, 0.6, 0.4);

        // Act
        $result = $a->mix($b, 1.0);

        // Assert
        self::assertEqualsWithDelta([0.8, 0.6, 0.4], $result->getComponents(), 0.0001);
    }

    #[Test]
    public function mixThrowsWhenColorModelsDiffer(): void
    {
        // Arrange
        $rgb = Color::rgb(1.0, 0.0, 0.0);
        $gray = Color::gray(0.5);
        $this->expectException(InvalidArgumentException::class);

        // Act
        $rgb->mix($gray);
    }
}
