<?php

declare(strict_types=1);

namespace PhpPdf\Shading\PdfAxialShading;

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
use PhpPdf\Shading\PdfAxialShading;
use PhpPdf\Shading\ShadingFunctions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfAxialShading::class)]
#[CoversMethod(PdfAxialShading::class, 'between')]
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
    public function betweenReturnsPdfAxialShadingInstance(): void
    {
        // Arrange / Act
        $shading = PdfAxialShading::between(
            x0: 0,
            y0: 0,
            x1: 100,
            y1: 0,
            colorStart: Color::red(),
            colorEnd: Color::blue(),
        );

        // Assert
        self::assertInstanceOf(PdfAxialShading::class, $shading);
    }

    #[Test]
    public function betweenExtendDefaultsToTrue(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $shading = PdfAxialShading::between(
            x0: 0,
            y0: 0,
            x1: 100,
            y1: 0,
            colorStart: Color::red(),
            colorEnd: Color::blue(),
        );

        // Act
        $ref = $shading->compile($registry);
        $dict = $registry->get($ref);
        self::assertInstanceOf(PdfDictionary::class, $dict);

        // Assert
        $extend = $dict->get('Extend');
        self::assertInstanceOf(PdfArray::class, $extend);
        $items = $extend->getItems();
        self::assertInstanceOf(PdfBoolean::class, $items[0]);
        self::assertInstanceOf(PdfBoolean::class, $items[1]);
        self::assertTrue($items[0]->getValue());
        self::assertTrue($items[1]->getValue());
    }

    #[Test]
    public function betweenExtendCanBeSetToFalse(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $shading = PdfAxialShading::between(
            x0: 0,
            y0: 0,
            x1: 100,
            y1: 0,
            colorStart: Color::red(),
            colorEnd: Color::blue(),
            extend: false,
        );

        // Act
        $ref = $shading->compile($registry);
        $dict = $registry->get($ref);
        self::assertInstanceOf(PdfDictionary::class, $dict);

        // Assert
        $extend = $dict->get('Extend');
        self::assertInstanceOf(PdfArray::class, $extend);
        $items = $extend->getItems();
        self::assertInstanceOf(PdfBoolean::class, $items[0]);
        self::assertInstanceOf(PdfBoolean::class, $items[1]);
        self::assertFalse($items[0]->getValue());
        self::assertFalse($items[1]->getValue());
    }
}
