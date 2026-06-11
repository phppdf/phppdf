<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfFontDescriptor;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfFontDescriptor;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfFontDescriptor::class)]
#[CoversMethod(PdfFontDescriptor::class, '__construct')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesInstance(): void
    {
        // Arrange
        $bbox = new PdfArray([]);

        // Act
        $obj = new PdfFontDescriptor('MyFont', 32, 0, 800, -200, 700, $bbox);

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }
}
