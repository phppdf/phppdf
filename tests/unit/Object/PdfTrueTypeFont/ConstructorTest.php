<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfTrueTypeFont;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfFontWidths;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfTrueTypeFont;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfTrueTypeFont::class)]
#[CoversMethod(PdfTrueTypeFont::class, '__construct')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfFontWidths::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesInstance(): void
    {
        // Arrange
        $fontDescRef = new PdfIndirectReference(5, 0);
        $widths = new PdfFontWidths(array_fill(0, 224, 600));

        // Act
        $obj = new PdfTrueTypeFont('MyFont', $fontDescRef, $widths);

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }
}
