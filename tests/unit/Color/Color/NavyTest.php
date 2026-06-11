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
#[CoversMethod(Color::class, 'navy')]
final class NavyTest extends TestCase
{
    #[Test]
    public function navyReturnsRgbWithCorrectComponents(): void
    {
        // Arrange / Act
        $color = Color::navy();

        // Assert
        self::assertSame(ColorType::Rgb, $color->getType());
        self::assertEqualsWithDelta([0.0, 0.0, 128 / 255], $color->getComponents(), 0.001);
    }
}
