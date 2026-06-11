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
#[CoversMethod(PdfDocument::class, 'isCompressionEnabled')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfObjectRegistry::class)]
final class IsCompressionEnabledTest extends TestCase
{
    #[Test]
    public function isCompressionEnabledDefaultsToFalse(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $catalogRef = $registry->register(new PdfDictionary([]));
        $document = new PdfDocument($registry, PdfVersion::PDF_1_7, $catalogRef, null);

        // Act / Assert
        self::assertFalse($document->isCompressionEnabled());
    }

    #[Test]
    public function isCompressionEnabledReturnsTrueWhenSet(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $catalogRef = $registry->register(new PdfDictionary([]));
        $document = new PdfDocument($registry, PdfVersion::PDF_1_7, $catalogRef, null, null, null, null, true);

        // Act / Assert
        self::assertTrue($document->isCompressionEnabled());
    }
}
