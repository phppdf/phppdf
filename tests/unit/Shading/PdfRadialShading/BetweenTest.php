<?php

declare(strict_types=1);

namespace PhpPdf\Shading\PdfRadialShading;

use PhpPdf\Color\Color;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfBoolean;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfReal;
use PhpPdf\Shading\ColorStop;
use PhpPdf\Shading\PdfRadialShading;
use PhpPdf\Shading\ShadingFunctions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfRadialShading::class)]
#[CoversMethod(PdfRadialShading::class, 'between')]
#[UsesClass(Color::class)]
#[UsesClass(ColorStop::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfBoolean::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfReal::class)]
#[UsesClass(ShadingFunctions::class)]
final class BetweenTest extends TestCase
{
    #[Test]
    public function betweenReturnsPdfRadialShadingInstance(): void
    {
        // Arrange / Act
        $shading = PdfRadialShading::between(
            cx0: 310,
            cy0: 440,
            r0: 0,
            cx1: 297,
            cy1: 421,
            r1: 150,
            colorStart: Color::yellow(),
            colorEnd: Color::navy(),
        );

        // Assert
        self::assertInstanceOf(PdfRadialShading::class, $shading);
    }

    #[Test]
    public function betweenCoordsContainsSixValues(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $shading = PdfRadialShading::between(
            cx0: 310,
            cy0: 440,
            r0: 0,
            cx1: 297,
            cy1: 421,
            r1: 150,
            colorStart: Color::yellow(),
            colorEnd: Color::navy(),
        );

        // Act
        $ref = $shading->compile($registry);
        $dict = $registry->get($ref);
        self::assertInstanceOf(PdfDictionary::class, $dict);

        // Assert
        $coords = $dict->get('Coords');
        self::assertInstanceOf(PdfArray::class, $coords);
        self::assertCount(6, $coords->getItems());
    }

    #[Test]
    public function betweenCoordsContainsCorrectValues(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $shading = PdfRadialShading::between(
            cx0: 10.0,
            cy0: 20.0,
            r0: 5.0,
            cx1: 30.0,
            cy1: 40.0,
            r1: 50.0,
            colorStart: Color::red(),
            colorEnd: Color::blue(),
        );

        // Act
        $ref = $shading->compile($registry);
        $dict = $registry->get($ref);
        self::assertInstanceOf(PdfDictionary::class, $dict);
        $coords = $dict->get('Coords');
        self::assertInstanceOf(PdfArray::class, $coords);
        $items = $coords->getItems();
        [$x0, $y0, $r0, $x1, $y1, $r1] = $items;
        self::assertInstanceOf(PdfReal::class, $x0);
        self::assertInstanceOf(PdfReal::class, $y0);
        self::assertInstanceOf(PdfReal::class, $r0);
        self::assertInstanceOf(PdfReal::class, $x1);
        self::assertInstanceOf(PdfReal::class, $y1);
        self::assertInstanceOf(PdfReal::class, $r1);

        // Assert
        self::assertEqualsWithDelta(10.0, $x0->getValue(), 0.0001);
        self::assertEqualsWithDelta(20.0, $y0->getValue(), 0.0001);
        self::assertEqualsWithDelta(5.0, $r0->getValue(), 0.0001);
        self::assertEqualsWithDelta(30.0, $x1->getValue(), 0.0001);
        self::assertEqualsWithDelta(40.0, $y1->getValue(), 0.0001);
        self::assertEqualsWithDelta(50.0, $r1->getValue(), 0.0001);
    }

    #[Test]
    public function betweenExtendDefaultsToTrue(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $shading = PdfRadialShading::between(
            cx0: 0,
            cy0: 0,
            r0: 0,
            cx1: 100,
            cy1: 100,
            r1: 50,
            colorStart: Color::red(),
            colorEnd: Color::blue(),
        );

        // Act
        $ref = $shading->compile($registry);
        $dict = $registry->get($ref);
        self::assertInstanceOf(PdfDictionary::class, $dict);
        $extend = $dict->get('Extend');
        self::assertInstanceOf(PdfArray::class, $extend);
        $items = $extend->getItems();
        self::assertInstanceOf(PdfBoolean::class, $items[0]);
        self::assertInstanceOf(PdfBoolean::class, $items[1]);

        // Assert
        self::assertTrue($items[0]->getValue());
        self::assertTrue($items[1]->getValue());
    }
}
