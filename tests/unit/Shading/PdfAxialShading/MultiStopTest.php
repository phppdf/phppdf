<?php

declare(strict_types=1);

namespace PhpPdf\Shading\PdfAxialShading;

use InvalidArgumentException;
use PhpPdf\Color\Color;
use PhpPdf\Shading\ColorStop;
use PhpPdf\Shading\PdfAxialShading;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfAxialShading::class)]
#[CoversMethod(PdfAxialShading::class, 'multiStop')]
#[UsesClass(Color::class)]
#[UsesClass(ColorStop::class)]
final class MultiStopTest extends TestCase
{
    #[Test]
    public function multiStopReturnsPdfAxialShadingInstance(): void
    {
        // Arrange / Act
        $shading = PdfAxialShading::multiStop(
            x0: 0,
            y0: 0,
            x1: 100,
            y1: 0,
            stops: [
                new ColorStop(0.0, Color::red()),
                new ColorStop(0.5, Color::yellow()),
                new ColorStop(1.0, Color::blue()),
            ],
        );

        // Assert
        self::assertInstanceOf(PdfAxialShading::class, $shading);
    }

    #[Test]
    public function multiStopThrowsWhenFewerThanTwoStopsGiven(): void
    {
        // Arrange / Act / Assert
        $this->expectException(InvalidArgumentException::class);
        PdfAxialShading::multiStop(
            x0: 0,
            y0: 0,
            x1: 100,
            y1: 0,
            stops: [new ColorStop(0.0, Color::red())],
        );
    }
}
