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
#[CoversMethod(Color::class, 'white')]
final class WhiteTest extends TestCase
{
    #[Test]
    public function whiteReturnsGrayAtOne(): void
    {
        // Arrange / Act
        $color = Color::white();

        // Assert
        self::assertSame(ColorType::Gray, $color->getType());
        self::assertEqualsWithDelta([1.0], $color->getComponents(), 0.0001);
    }
}
