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
#[CoversMethod(Color::class, 'cmyk')]
final class CmykTest extends TestCase
{
    #[Test]
    public function cmykSetsCorrectType(): void
    {
        // Arrange / Act
        $color = Color::cmyk(0.0, 0.5, 0.5, 0.0);

        // Assert
        self::assertSame(ColorType::Cmyk, $color->getType());
    }

    #[Test]
    public function cmykStoresComponents(): void
    {
        // Arrange / Act
        $color = Color::cmyk(0.1, 0.2, 0.3, 0.4);

        // Assert
        self::assertEqualsWithDelta([0.1, 0.2, 0.3, 0.4], $color->getComponents(), 0.0001);
    }

    #[Test]
    public function cmykClampsComponents(): void
    {
        // Arrange / Act
        $color = Color::cmyk(-0.5, 0.5, 1.5, 0.0);

        // Assert
        self::assertEqualsWithDelta([0.0, 0.5, 1.0, 0.0], $color->getComponents(), 0.0001);
    }
}
