<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfType0Font;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfType0Font;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfType0Font::class)]
#[CoversMethod(PdfType0Font::class, '__construct')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfName::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesInstance(): void
    {
        // Arrange
        $descendant = new PdfIndirectReference(4, 0);
        $toUnicode = new PdfIndirectReference(5, 0);

        // Act
        $obj = new PdfType0Font('MyFont-Identity-H', $descendant, $toUnicode);

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }
}
