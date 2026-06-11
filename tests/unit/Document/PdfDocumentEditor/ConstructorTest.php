<?php

declare(strict_types=1);

namespace PhpPdf\Document\PdfDocumentEditor;

use PhpPdf\Document\PdfDocument;
use PhpPdf\Document\PdfDocumentEditor;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfReal;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(PdfDocumentEditor::class)]
#[CoversMethod(PdfDocumentEditor::class, '__construct')]
#[UsesClass(PdfDocument::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfReal::class)]
final class ConstructorTest extends TestCase
{
    use MinimalDocument;

    #[Test]
    public function constructorInitialisesWithAllPagesOfSource(): void
    {
        // Arrange
        $doc = self::buildDocument(pageCount: 3);

        // Act
        $editor = new PdfDocumentEditor($doc);

        // Assert
        self::assertSame(3, $editor->getPageCount());
    }

    #[Test]
    public function constructorHandlesSinglePage(): void
    {
        // Arrange
        $doc = self::buildDocument(pageCount: 1);

        // Act
        $editor = new PdfDocumentEditor($doc);

        // Assert
        self::assertSame(1, $editor->getPageCount());
    }

    #[Test]
    public function constructorThrowsWhenCatalogHasNoPagesKey(): void
    {
        // Arrange — catalog dict is valid but has no /Pages entry
        $registry = new PdfObjectRegistry();
        $catalogRef = $registry->register(new PdfDictionary([]));
        $doc = new PdfDocument($registry, PdfVersion::PDF_1_7, $catalogRef, null);

        // Act / Assert
        $this->expectException(RuntimeException::class);
        new PdfDocumentEditor($doc);
    }

    #[Test]
    public function constructorThrowsWhenCatalogPagesIsNotAReference(): void
    {
        // Arrange — /Pages is a PdfInteger instead of a PdfIndirectReference
        $registry = new PdfObjectRegistry();
        $catalogRef = $registry->register(new PdfDictionary([
            'Pages' => new PdfInteger(99),
        ]));
        $doc = new PdfDocument($registry, PdfVersion::PDF_1_7, $catalogRef, null);

        // Act / Assert
        $this->expectException(RuntimeException::class);
        new PdfDocumentEditor($doc);
    }

    #[Test]
    public function constructorThrowsWhenCatalogObjectIsNotADictionary(): void
    {
        // Arrange — catalog reference points to a PdfInteger, not a PdfDictionary
        $registry = new PdfObjectRegistry();
        $catalogRef = $registry->register(new PdfInteger(0));
        $doc = new PdfDocument($registry, PdfVersion::PDF_1_7, $catalogRef, null);

        // Act / Assert
        $this->expectException(RuntimeException::class);
        new PdfDocumentEditor($doc);
    }

    #[Test]
    public function constructorHandlesKidsEntryWithNonDictionaryNode(): void
    {
        // Arrange — Kids array contains a reference to a non-dict (edge case)
        $registry = new PdfObjectRegistry();
        $badKidRef = $registry->register(new PdfInteger(0)); // not a dict
        $pagesRef = $registry->register(new PdfDictionary([
            'Count' => new PdfInteger(0),
            'Kids' => new PdfArray([$badKidRef]),
            'Type' => new PdfName('Pages'),
        ]));
        $catalogRef = $registry->register(new PdfDictionary([
            'Pages' => $pagesRef,
            'Type' => new PdfName('Catalog'),
        ]));
        $doc = new PdfDocument($registry, PdfVersion::PDF_1_7, $catalogRef, null);

        // Act — should produce 0 pages (bad kid yields nothing)
        $editor = new PdfDocumentEditor($doc);

        // Assert
        self::assertSame(0, $editor->getPageCount());
    }

    #[Test]
    public function constructorSkipsKidsEntryThatIsNotAnIndirectReference(): void
    {
        // Arrange — Kids array contains a direct (non-reference) value alongside
        // a valid page reference; the non-reference entry must be skipped.
        $registry = new PdfObjectRegistry();
        $pageRef = $registry->register(new PdfDictionary([
            'Type' => new PdfName('Page'),
        ]));
        $pagesRef = $registry->register(new PdfDictionary([
            'Count' => new PdfInteger(1),
            'Kids' => new PdfArray([new PdfInteger(0), $pageRef]),
            'Type' => new PdfName('Pages'),
        ]));
        $catalogRef = $registry->register(new PdfDictionary([
            'Pages' => $pagesRef,
            'Type' => new PdfName('Catalog'),
        ]));
        $doc = new PdfDocument($registry, PdfVersion::PDF_1_7, $catalogRef, null);

        // Act — the direct PdfInteger Kids entry is skipped; only the valid page is counted
        $editor = new PdfDocumentEditor($doc);

        // Assert
        self::assertSame(1, $editor->getPageCount());
    }
}
