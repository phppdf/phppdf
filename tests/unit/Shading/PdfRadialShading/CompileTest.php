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
#[CoversMethod(PdfRadialShading::class, 'compile')]
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
        $shading = PdfRadialShading::circle(
            cx: 297,
            cy: 421,
            radius: 150,
            colorCenter: Color::red(),
            colorEdge: Color::blue(),
        );

        // Act
        $ref = $shading->compile($registry);

        // Assert
        self::assertInstanceOf(PdfIndirectReference::class, $ref);
    }

    #[Test]
    public function compileShadingTypeIsThree(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $shading = PdfRadialShading::circle(
            cx: 297,
            cy: 421,
            radius: 150,
            colorCenter: Color::red(),
            colorEdge: Color::blue(),
        );

        // Act
        $ref = $shading->compile($registry);
        $dict = $registry->get($ref);
        self::assertInstanceOf(PdfDictionary::class, $dict);
        $shadingType = $dict->get('ShadingType');
        self::assertInstanceOf(PdfInteger::class, $shadingType);

        // Assert
        self::assertSame(3, $shadingType->getValue());
    }

    #[Test]
    public function compileColorSpaceReflectsRgbColor(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $shading = PdfRadialShading::circle(
            cx: 100,
            cy: 100,
            radius: 50,
            colorCenter: Color::red(),
            colorEdge: Color::blue(),
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
        $shading = PdfRadialShading::circle(
            cx: 100,
            cy: 100,
            radius: 50,
            colorCenter: Color::white(),
            colorEdge: Color::black(),
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
    public function compileFunctionKeyIsPresent(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $shading = PdfRadialShading::circle(
            cx: 100,
            cy: 100,
            radius: 50,
            colorCenter: Color::white(),
            colorEdge: Color::black(),
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
        $shading = PdfRadialShading::multiStop(
            cx0: 297,
            cy0: 421,
            r0: 0,
            cx1: 297,
            cy1: 421,
            r1: 150,
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
