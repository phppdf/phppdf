<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfPage;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfPage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfPage::class)]
#[CoversMethod(PdfPage::class, '__construct')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesInstance(): void
    {
        // Arrange
        $parent = new PdfIndirectReference(1, 0);
        $contents = new PdfIndirectReference(2, 0);
        $mediaBox = new PdfArray([]);

        // Act
        $obj = new PdfPage($parent, $contents, $mediaBox);

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }
}
