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
#[CoversMethod(Color::class, 'blue')]
final class BlueTest extends TestCase
{
    #[Test]
    public function blueReturnsRgbWithFullBlueChannel(): void
    {
        // Arrange / Act
        $color = Color::blue();

        // Assert
        self::assertSame(ColorType::Rgb, $color->getType());
        self::assertEqualsWithDelta([0.0, 0.0, 1.0], $color->getComponents(), 0.0001);
    }
}
