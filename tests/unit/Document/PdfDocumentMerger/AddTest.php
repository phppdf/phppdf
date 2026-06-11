<?php

declare(strict_types=1);

namespace PhpPdf\Document\PdfDocumentMerger;

use PhpPdf\Document\PdfDocument;
use PhpPdf\Document\PdfDocumentMerger;
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

#[CoversClass(PdfDocumentMerger::class)]
#[CoversMethod(PdfDocumentMerger::class, 'add')]
#[UsesClass(PdfDocument::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectRegistry::class)]
final class AddTest extends TestCase
{
    #[Test]
    public function addReturnsSelf(): void
    {
        // Arrange
        $merger = new PdfDocumentMerger();
        $document = $this->buildDocument();

        // Act
        $result = $merger->add($document);

        // Assert
        self::assertSame($merger, $result);
    }

    private function buildDocument(): PdfDocument
    {
        $registry = new PdfObjectRegistry();
        $pageRef = $registry->register(new PdfDictionary([
            'Type' => new PdfName('Page'),
        ]));
        $pagesRef = $registry->register(new PdfDictionary([
            'Count' => new PdfInteger(1),
            'Kids' => new PdfArray([$pageRef]),
            'Type' => new PdfName('Pages'),
        ]));
        $page = $registry->get($pageRef);
        self::assertInstanceOf(PdfDictionary::class, $page);
        $page->set('Parent', $pagesRef);
        $catalogRef = $registry->register(new PdfDictionary([
            'Pages' => $pagesRef,
            'Type' => new PdfName('Catalog'),
        ]));

        return new PdfDocument($registry, PdfVersion::PDF_1_7, $catalogRef, null);
    }
}
