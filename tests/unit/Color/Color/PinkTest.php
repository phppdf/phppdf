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
#[CoversMethod(Color::class, 'pink')]
final class PinkTest extends TestCase
{
    #[Test]
    public function pinkReturnsRgbWithCorrectComponents(): void
    {
        // Arrange / Act
        $color = Color::pink();

        // Assert
        self::assertSame(ColorType::Rgb, $color->getType());
        self::assertEqualsWithDelta([1.0, 105 / 255, 180 / 255], $color->getComponents(), 0.001);
    }
}
