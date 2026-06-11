<?php

declare(strict_types=1);

namespace PhpPdf\Shading\ShadingFunctions;

use InvalidArgumentException;
use PhpPdf\Color\Color;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfReal;
use PhpPdf\Shading\ColorStop;
use PhpPdf\Shading\ShadingFunctions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ShadingFunctions::class)]
#[CoversMethod(ShadingFunctions::class, 'buildFunction')]
#[UsesClass(Color::class)]
#[UsesClass(ColorStop::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfReal::class)]
final class BuildFunctionTest extends TestCase
{
    #[Test]
    public function buildFunctionReturnsTwoStopType2Function(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $stops = [
            new ColorStop(0.0, Color::red()),
            new ColorStop(1.0, Color::blue()),
        ];

        // Act
        $ref = ShadingFunctions::buildFunction($registry, $stops);

        // Assert
        self::assertInstanceOf(PdfIndirectReference::class, $ref);
        $dict = $registry->get($ref);
        self::assertInstanceOf(PdfDictionary::class, $dict);
        $functionType = $dict->get('FunctionType');
        self::assertInstanceOf(PdfInteger::class, $functionType);
        self::assertSame(2, $functionType->getValue());
    }

    #[Test]
    public function buildFunctionTwoStopFunctionHasDomainAndColorArrays(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $stops = [
            new ColorStop(0.0, Color::black()),
            new ColorStop(1.0, Color::white()),
        ];

        // Act
        $ref = ShadingFunctions::buildFunction($registry, $stops);

        // Assert
        $dict = $registry->get($ref);
        self::assertInstanceOf(PdfDictionary::class, $dict);
        self::assertInstanceOf(PdfArray::class, $dict->get('Domain'));
        self::assertInstanceOf(PdfArray::class, $dict->get('C0'));
        self::assertInstanceOf(PdfArray::class, $dict->get('C1'));
        $n = $dict->get('N');
        self::assertInstanceOf(PdfInteger::class, $n);
        self::assertSame(1, $n->getValue());
    }

    #[Test]
    public function buildFunctionReturnsType3FunctionForMultipleStops(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $stops = [
            new ColorStop(0.0, Color::red()),
            new ColorStop(0.5, Color::yellow()),
            new ColorStop(1.0, Color::blue()),
        ];

        // Act
        $ref = ShadingFunctions::buildFunction($registry, $stops);

        // Assert
        $dict = $registry->get($ref);
        self::assertInstanceOf(PdfDictionary::class, $dict);
        $functionType = $dict->get('FunctionType');
        self::assertInstanceOf(PdfInteger::class, $functionType);
        self::assertSame(3, $functionType->getValue());
    }

    #[Test]
    public function buildFunctionType3HasFunctionsBoundsAndEncode(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $stops = [
            new ColorStop(0.0, Color::red()),
            new ColorStop(0.5, Color::yellow()),
            new ColorStop(1.0, Color::blue()),
        ];

        // Act
        $ref = ShadingFunctions::buildFunction($registry, $stops);

        // Assert
        $dict = $registry->get($ref);
        self::assertInstanceOf(PdfDictionary::class, $dict);
        self::assertInstanceOf(PdfArray::class, $dict->get('Functions'));
        self::assertInstanceOf(PdfArray::class, $dict->get('Bounds'));
        self::assertInstanceOf(PdfArray::class, $dict->get('Encode'));
    }

    #[Test]
    public function buildFunctionThrowsWhenFewerThanTwoStops(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $stops = [new ColorStop(0.0, Color::red())];

        // Act / Assert
        $this->expectException(InvalidArgumentException::class);
        ShadingFunctions::buildFunction($registry, $stops);
    }

    #[Test]
    public function buildFunctionThrowsWhenColorTypesAreMixed(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $stops = [
            new ColorStop(0.0, Color::red()),
            new ColorStop(1.0, Color::gray(0.5)),
        ];

        // Act / Assert
        $this->expectException(InvalidArgumentException::class);
        ShadingFunctions::buildFunction($registry, $stops);
    }

    #[Test]
    public function buildFunctionThrowsWhenFirstOffsetIsNotZero(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $stops = [
            new ColorStop(0.1, Color::red()),
            new ColorStop(1.0, Color::blue()),
        ];

        // Act / Assert
        $this->expectException(InvalidArgumentException::class);
        ShadingFunctions::buildFunction($registry, $stops);
    }

    #[Test]
    public function buildFunctionThrowsWhenLastOffsetIsNotOne(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $stops = [
            new ColorStop(0.0, Color::red()),
            new ColorStop(0.9, Color::blue()),
        ];

        // Act / Assert
        $this->expectException(InvalidArgumentException::class);
        ShadingFunctions::buildFunction($registry, $stops);
    }

    #[Test]
    public function buildFunctionThrowsWhenOffsetsAreNotStrictlyIncreasing(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $stops = [
            new ColorStop(0.0, Color::red()),
            new ColorStop(0.5, Color::yellow()),
            new ColorStop(0.5, Color::blue()),
            new ColorStop(1.0, Color::green()),
        ];

        // Act / Assert
        $this->expectException(InvalidArgumentException::class);
        ShadingFunctions::buildFunction($registry, $stops);
    }
}
