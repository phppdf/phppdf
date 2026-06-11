<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfFileSpecification;

use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfFileSpecification;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfFileSpecification::class)]
#[CoversMethod(PdfFileSpecification::class, '__construct')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfString::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesInstance(): void
    {
        // Arrange / Act
        $obj = new PdfFileSpecification('document.pdf', new PdfIndirectReference(7, 0));

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }
}
