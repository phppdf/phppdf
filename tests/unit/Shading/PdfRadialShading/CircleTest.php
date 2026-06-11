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
#[CoversMethod(PdfRadialShading::class, 'circle')]
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
final class CircleTest extends TestCase
{
    #[Test]
    public function circleReturnsPdfRadialShadingInstance(): void
    {
        // Arrange / Act
        $shading = PdfRadialShading::circle(
            cx: 297,
            cy: 421,
            radius: 150,
            colorCenter: Color::white(),
            colorEdge: Color::navy(),
        );

        // Assert
        self::assertInstanceOf(PdfRadialShading::class, $shading);
    }

    #[Test]
    public function circleProducesCoordsWith6Values(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $shading = PdfRadialShading::circle(
            cx: 297,
            cy: 421,
            radius: 150,
            colorCenter: Color::white(),
            colorEdge: Color::black(),
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
    public function circleInnerRadiusIsZero(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $shading = PdfRadialShading::circle(
            cx: 100,
            cy: 200,
            radius: 50,
            colorCenter: Color::white(),
            colorEdge: Color::black(),
        );

        // Act
        $ref = $shading->compile($registry);
        $dict = $registry->get($ref);
        self::assertInstanceOf(PdfDictionary::class, $dict);
        $coords = $dict->get('Coords');
        self::assertInstanceOf(PdfArray::class, $coords);
        $items = $coords->getItems();
        $r0 = $items[2];
        self::assertInstanceOf(PdfReal::class, $r0);

        // Assert — r0 is index 2
        self::assertEqualsWithDelta(0.0, $r0->getValue(), 0.0001);
    }

    #[Test]
    public function circleExtendDefaultsToTrue(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $shading = PdfRadialShading::circle(
            cx: 100,
            cy: 200,
            radius: 50,
            colorCenter: Color::white(),
            colorEdge: Color::black(),
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

    #[Test]
    public function circleExtendCanBeSetToFalse(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $shading = PdfRadialShading::circle(
            cx: 100,
            cy: 200,
            radius: 50,
            colorCenter: Color::white(),
            colorEdge: Color::black(),
            extend: false,
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
        self::assertFalse($items[0]->getValue());
        self::assertFalse($items[1]->getValue());
    }
}
