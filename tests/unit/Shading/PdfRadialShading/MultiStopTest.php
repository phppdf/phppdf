<?php

declare(strict_types=1);

namespace PhpPdf\Shading\PdfRadialShading;

use InvalidArgumentException;
use PhpPdf\Color\Color;
use PhpPdf\Shading\ColorStop;
use PhpPdf\Shading\PdfRadialShading;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfRadialShading::class)]
#[CoversMethod(PdfRadialShading::class, 'multiStop')]
#[UsesClass(Color::class)]
#[UsesClass(ColorStop::class)]
final class MultiStopTest extends TestCase
{
    #[Test]
    public function multiStopReturnsPdfRadialShadingInstance(): void
    {
        // Arrange / Act
        $shading = PdfRadialShading::multiStop(
            cx0: 297,
            cy0: 421,
            r0: 0,
            cx1: 297,
            cy1: 421,
            r1: 150,
            stops: [
                new ColorStop(0.0, Color::white()),
                new ColorStop(0.5, Color::blue()),
                new ColorStop(1.0, Color::navy()),
            ],
        );

        // Assert
        self::assertInstanceOf(PdfRadialShading::class, $shading);
    }

    #[Test]
    public function multiStopThrowsWhenFewerThanTwoStopsGiven(): void
    {
        // Arrange / Act / Assert
        $this->expectException(InvalidArgumentException::class);
        PdfRadialShading::multiStop(
            cx0: 0,
            cy0: 0,
            r0: 0,
            cx1: 100,
            cy1: 100,
            r1: 50,
            stops: [new ColorStop(0.0, Color::red())],
        );
    }
}
