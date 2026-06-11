<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfDocumentBuilder;

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Document\PdfDocument;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDate;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentBuilder::class)]
#[CoversMethod(PdfDocumentBuilder::class, 'info')]
#[UsesClass(PdfDocument::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDate::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfDocumentInfo::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfString::class)]
final class InfoTest extends TestCase
{
    #[Test]
    public function infoReturnsSelf(): void
    {
        // Arrange
        $builder = new PdfDocumentBuilder();

        // Act
        $result = $builder->info(new PdfDocumentInfo());

        // Assert
        self::assertSame($builder, $result);
    }

    #[Test]
    public function infoIsRegisteredInDocument(): void
    {
        // Arrange / Act
        $document = (new PdfDocumentBuilder())
            ->info(new PdfDocumentInfo())
            ->build();

        // Assert
        self::assertNotNull($document->getInfo());
    }

    #[Test]
    public function withoutInfoDocumentHasNoInfoRef(): void
    {
        // Arrange / Act
        $document = (new PdfDocumentBuilder())->build();

        // Assert
        self::assertNull($document->getInfo());
    }
}
