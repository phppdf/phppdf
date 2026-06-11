<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfAcroForm;

use PhpPdf\Object\PdfAcroForm;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfAcroForm::class)]
#[CoversMethod(PdfAcroForm::class, '__construct')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfIndirectReference::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesInstance(): void
    {
        // Arrange
        $fields = [new PdfIndirectReference(1, 0)];

        // Act
        $obj = new PdfAcroForm($fields);

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }
}
