<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfGraphicsStateDictionary;

use PhpPdf\Content\BlendMode;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfGraphicsStateDictionary;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfReal;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfGraphicsStateDictionary::class)]
#[CoversMethod(PdfGraphicsStateDictionary::class, '__construct')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfReal::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesWithDefaults(): void
    {
        // Arrange / Act
        $obj = new PdfGraphicsStateDictionary();

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }

    #[Test]
    public function constructorClampsAlphaValues(): void
    {
        // Arrange / Act
        $obj = new PdfGraphicsStateDictionary(fillAlpha: 2.0, strokeAlpha: -1.0);

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }

    #[Test]
    public function constructorAcceptsBlendMode(): void
    {
        // Arrange / Act
        $obj = new PdfGraphicsStateDictionary(blendMode: BlendMode::Multiply);

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }
}
