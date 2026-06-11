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
#[CoversMethod(Color::class, 'black')]
final class BlackTest extends TestCase
{
    #[Test]
    public function blackReturnsGrayAtZero(): void
    {
        // Arrange / Act
        $color = Color::black();

        // Assert
        self::assertSame(ColorType::Gray, $color->getType());
        self::assertEqualsWithDelta([0.0], $color->getComponents(), 0.0001);
    }
}
