<?php

declare(strict_types=1);

namespace PhpPdf\Document\PdfDocument;

use PhpPdf\Document\PdfDocument;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocument::class)]
#[CoversMethod(PdfDocument::class, 'getInfo')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfObjectRegistry::class)]
final class GetInfoTest extends TestCase
{
    #[Test]
    public function getInfoReturnsNullWhenNotSet(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $catalogRef = $registry->register(new PdfDictionary([]));
        $document = new PdfDocument($registry, PdfVersion::PDF_1_7, $catalogRef, null);

        // Act / Assert
        self::assertNull($document->getInfo());
    }

    #[Test]
    public function getInfoReturnsInfoReference(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $catalogRef = $registry->register(new PdfDictionary([]));
        $infoRef = $registry->register(new PdfDictionary([]));
        $document = new PdfDocument($registry, PdfVersion::PDF_1_7, $catalogRef, $infoRef);

        // Act
        $result = $document->getInfo();

        // Assert
        self::assertSame($infoRef, $result);
    }
}
