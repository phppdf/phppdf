<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfDocumentBuilder;

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Document\PdfDocument;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentBuilder::class)]
#[CoversMethod(PdfDocumentBuilder::class, 'version')]
#[UsesClass(PdfDocument::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectRegistry::class)]
final class VersionTest extends TestCase
{
    #[Test]
    public function versionReturnsSelf(): void
    {
        // Arrange
        $builder = new PdfDocumentBuilder();

        // Act
        $result = $builder->version(PdfVersion::PDF_1_4);

        // Assert
        self::assertSame($builder, $result);
    }

    #[Test]
    public function versionIsReflectedInDocument(): void
    {
        // Arrange / Act
        $document = (new PdfDocumentBuilder())
            ->version(PdfVersion::PDF_1_4)
            ->build();

        // Assert
        self::assertSame(PdfVersion::PDF_1_4, $document->getVersion());
    }
}
