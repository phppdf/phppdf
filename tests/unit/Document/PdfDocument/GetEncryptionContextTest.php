<?php

declare(strict_types=1);

namespace PhpPdf\Document\PdfDocument;

use PhpPdf\Document\PdfDocument;
use PhpPdf\Encryption\PdfEncryptionContext;
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
#[CoversMethod(PdfDocument::class, 'getEncryptionContext')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfEncryptionContext::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfObjectRegistry::class)]
final class GetEncryptionContextTest extends TestCase
{
    #[Test]
    public function getEncryptionContextReturnsNullByDefault(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $catalogRef = $registry->register(new PdfDictionary([]));
        $document = new PdfDocument($registry, PdfVersion::PDF_1_7, $catalogRef, null);

        // Act / Assert
        self::assertNull($document->getEncryptionContext());
    }

    #[Test]
    public function getEncryptionContextReturnsStoredContext(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $catalogRef = $registry->register(new PdfDictionary([]));
        $context = new PdfEncryptionContext(str_repeat("\x01", 16));
        $document = new PdfDocument($registry, PdfVersion::PDF_1_7, $catalogRef, null, null, null, $context);

        // Act
        $result = $document->getEncryptionContext();

        // Assert
        self::assertSame($context, $result);
    }
}
