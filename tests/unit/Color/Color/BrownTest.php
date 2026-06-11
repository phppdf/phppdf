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
#[CoversMethod(Color::class, 'brown')]
final class BrownTest extends TestCase
{
    #[Test]
    public function brownReturnsRgbWithCorrectComponents(): void
    {
        // Arrange / Act
        $color = Color::brown();

        // Assert
        self::assertSame(ColorType::Rgb, $color->getType());
        self::assertEqualsWithDelta([165 / 255, 42 / 255, 42 / 255], $color->getComponents(), 0.001);
    }
}
