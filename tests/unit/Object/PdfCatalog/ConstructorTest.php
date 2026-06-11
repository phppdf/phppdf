<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfCatalog;

use PhpPdf\Object\PdfCatalog;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfCatalog::class)]
#[CoversMethod(PdfCatalog::class, '__construct')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfName::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesInstance(): void
    {
        // Arrange / Act
        $obj = new PdfCatalog(new PdfIndirectReference(1, 0));

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }
}
