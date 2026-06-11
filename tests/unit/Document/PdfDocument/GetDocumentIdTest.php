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
#[CoversMethod(PdfDocument::class, 'getDocumentId')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfObjectRegistry::class)]
final class GetDocumentIdTest extends TestCase
{
    #[Test]
    public function getDocumentIdReturnsNullWhenNotSet(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $catalogRef = $registry->register(new PdfDictionary([]));
        $document = new PdfDocument($registry, PdfVersion::PDF_1_7, $catalogRef, null);

        // Act / Assert
        self::assertNull($document->getDocumentId());
    }

    #[Test]
    public function getDocumentIdReturnsStoredId(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $catalogRef = $registry->register(new PdfDictionary([]));
        $id = str_repeat("\xAB", 16);
        $document = new PdfDocument($registry, PdfVersion::PDF_1_7, $catalogRef, null, $id);

        // Act
        $result = $document->getDocumentId();

        // Assert
        self::assertSame($id, $result);
    }
}
