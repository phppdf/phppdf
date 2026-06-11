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
#[CoversMethod(PdfAxialShading::class, 'compile')]
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
final class CompileTest extends TestCase
{
    #[Test]
    public function compileReturnsIndirectReference(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $shading = PdfAxialShading::between(
            x0: 72,
            y0: 0,
            x1: 523,
            y1: 0,
            colorStart: Color::red(),
            colorEnd: Color::blue(),
        );

        // Act
        $ref = $shading->compile($registry);

        // Assert
        self::assertInstanceOf(PdfIndirectReference::class, $ref);
    }

    #[Test]
    public function compileShadingTypeIsTwo(): void
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
        $shadingType = $dict->get('ShadingType');
        self::assertInstanceOf(PdfInteger::class, $shadingType);

        // Assert
        self::assertSame(2, $shadingType->getValue());
    }

    #[Test]
    public function compileColorSpaceReflectsRgbColor(): void
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
        $colorSpace = $dict->get('ColorSpace');
        self::assertInstanceOf(PdfName::class, $colorSpace);

        // Assert
        self::assertSame('DeviceRGB', $colorSpace->getValue());
    }

    #[Test]
    public function compileColorSpaceReflectsGrayColor(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $shading = PdfAxialShading::between(
            x0: 0,
            y0: 0,
            x1: 100,
            y1: 0,
            colorStart: Color::black(),
            colorEnd: Color::white(),
        );

        // Act
        $ref = $shading->compile($registry);
        $dict = $registry->get($ref);
        self::assertInstanceOf(PdfDictionary::class, $dict);
        $colorSpace = $dict->get('ColorSpace');
        self::assertInstanceOf(PdfName::class, $colorSpace);

        // Assert
        self::assertSame('DeviceGray', $colorSpace->getValue());
    }

    #[Test]
    public function compileCoordsContainsFourValues(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $shading = PdfAxialShading::between(
            x0: 10,
            y0: 20,
            x1: 30,
            y1: 40,
            colorStart: Color::red(),
            colorEnd: Color::blue(),
        );

        // Act
        $ref = $shading->compile($registry);
        $dict = $registry->get($ref);
        self::assertInstanceOf(PdfDictionary::class, $dict);

        // Assert
        $coords = $dict->get('Coords');
        self::assertInstanceOf(PdfArray::class, $coords);
        self::assertCount(4, $coords->getItems());
    }

    #[Test]
    public function compileCoordsContainsCorrectValues(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $shading = PdfAxialShading::between(
            x0: 10.0,
            y0: 20.0,
            x1: 30.0,
            y1: 40.0,
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
        [$x0, $y0, $x1, $y1] = $items;
        self::assertInstanceOf(PdfReal::class, $x0);
        self::assertInstanceOf(PdfReal::class, $y0);
        self::assertInstanceOf(PdfReal::class, $x1);
        self::assertInstanceOf(PdfReal::class, $y1);

        // Assert
        self::assertEqualsWithDelta(10.0, $x0->getValue(), 0.0001);
        self::assertEqualsWithDelta(20.0, $y0->getValue(), 0.0001);
        self::assertEqualsWithDelta(30.0, $x1->getValue(), 0.0001);
        self::assertEqualsWithDelta(40.0, $y1->getValue(), 0.0001);
    }

    #[Test]
    public function compileFunctionKeyIsPresent(): void
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
        self::assertTrue($dict->has('Function'));
    }

    #[Test]
    public function compileMultiStopUsesStitchingFunction(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
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

        // Act
        $ref = $shading->compile($registry);
        $dict = $registry->get($ref);
        self::assertInstanceOf(PdfDictionary::class, $dict);
        $functionRef = $dict->get('Function');
        self::assertInstanceOf(PdfIndirectReference::class, $functionRef);
        $functionDict = $registry->get($functionRef);
        self::assertInstanceOf(PdfDictionary::class, $functionDict);
        $functionType = $functionDict->get('FunctionType');
        self::assertInstanceOf(PdfInteger::class, $functionType);

        // Assert
        self::assertSame(3, $functionType->getValue());
    }
}
