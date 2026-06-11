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
#[CoversMethod(Color::class, 'orange')]
final class OrangeTest extends TestCase
{
    #[Test]
    public function orangeReturnsRgbWithCorrectComponents(): void
    {
        // Arrange / Act
        $color = Color::orange();

        // Assert
        self::assertSame(ColorType::Rgb, $color->getType());
        self::assertEqualsWithDelta([1.0, 102 / 255, 0.0], $color->getComponents(), 0.001);
    }
}
