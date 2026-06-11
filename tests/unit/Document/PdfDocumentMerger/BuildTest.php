<?php

declare(strict_types=1);

namespace PhpPdf\Document\PdfDocumentMerger;

use InvalidArgumentException;
use PhpPdf\Document\PdfDocument;
use PhpPdf\Document\PdfDocumentMerger;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Object\PdfStream;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(PdfDocumentMerger::class)]
#[CoversMethod(PdfDocumentMerger::class, 'build')]
#[UsesClass(PdfDocument::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfRawStreamData::class)]
#[UsesClass(PdfStream::class)]
final class BuildTest extends TestCase
{
    #[Test]
    public function buildThrowsWhenNoDocumentsAdded(): void
    {
        // Arrange
        $merger = new PdfDocumentMerger();

        // Act / Assert
        $this->expectException(InvalidArgumentException::class);
        $merger->build();
    }

    #[Test]
    public function buildReturnsPdfDocument(): void
    {
        // Arrange
        $merger = (new PdfDocumentMerger())->add($this->buildDocument());

        // Act
        $result = $merger->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $result);
    }

    #[Test]
    public function buildMergesPageFromSingleDocument(): void
    {
        // Arrange
        $doc = $this->buildDocument(pageCount: 1);
        $merger = (new PdfDocumentMerger())->add($doc);

        // Act
        $result = $merger->build();

        // Assert — output registry has a Pages dict with Count = 1
        $catalog = $result->getObjects()->get($result->getCatalog());
        self::assertInstanceOf(PdfDictionary::class, $catalog);
        $pagesRef = $catalog->get('Pages');
        self::assertInstanceOf(PdfIndirectReference::class, $pagesRef);
        $pages = $result->getObjects()->get($pagesRef);
        self::assertInstanceOf(PdfDictionary::class, $pages);
        $count = $pages->get('Count');
        self::assertInstanceOf(PdfInteger::class, $count);
        self::assertSame(1, $count->getValue());
    }

    #[Test]
    public function buildCombinesPagesFromMultipleDocuments(): void
    {
        // Arrange
        $docA = $this->buildDocument(pageCount: 1);
        $docB = $this->buildDocument(pageCount: 2);
        $merger = (new PdfDocumentMerger())->add($docA)->add($docB);

        // Act
        $result = $merger->build();

        // Assert — merged output has 3 pages
        $catalog = $result->getObjects()->get($result->getCatalog());
        self::assertInstanceOf(PdfDictionary::class, $catalog);
        $pagesRef = $catalog->get('Pages');
        self::assertInstanceOf(PdfIndirectReference::class, $pagesRef);
        $pages = $result->getObjects()->get($pagesRef);
        self::assertInstanceOf(PdfDictionary::class, $pages);
        $count = $pages->get('Count');
        self::assertInstanceOf(PdfInteger::class, $count);
        self::assertSame(3, $count->getValue());
    }

    #[Test]
    public function buildUsesHighestVersionAcrossSources(): void
    {
        // Arrange
        $docA = $this->buildDocument(version: PdfVersion::PDF_1_4);
        $docB = $this->buildDocument(version: PdfVersion::PDF_2_0);
        $merger = (new PdfDocumentMerger())->add($docA)->add($docB);

        // Act
        $result = $merger->build();

        // Assert
        self::assertSame(PdfVersion::PDF_2_0, $result->getVersion());
    }

    #[Test]
    public function buildMergesDocumentWithContentStream(): void
    {
        // Arrange — add a content stream so cloneObject(PdfStream) is exercised
        $doc = $this->buildDocumentWithStream();
        $merger = (new PdfDocumentMerger())->add($doc);

        // Act
        $result = $merger->build();

        // Assert — merged successfully
        self::assertInstanceOf(PdfDocument::class, $result);
    }

    // -------------------------------------------------------------------------

    #[Test]
    public function buildThrowsWhenCatalogObjectIsNotADictionary(): void
    {
        // Arrange — catalog reference points to PdfInteger, not PdfDictionary
        $registry = new PdfObjectRegistry();
        $catalogRef = $registry->register(new PdfInteger(0));
        $doc = new PdfDocument($registry, PdfVersion::PDF_1_7, $catalogRef, null);

        // Act / Assert
        $this->expectException(RuntimeException::class);
        (new PdfDocumentMerger())->add($doc)->build();
    }

    #[Test]
    public function buildThrowsWhenCatalogHasNoPagesKey(): void
    {
        // Arrange — catalog dict has no /Pages entry
        $registry = new PdfObjectRegistry();
        $catalogRef = $registry->register(new PdfDictionary([]));
        $doc = new PdfDocument($registry, PdfVersion::PDF_1_7, $catalogRef, null);

        // Act / Assert
        $this->expectException(RuntimeException::class);
        (new PdfDocumentMerger())->add($doc)->build();
    }

    #[Test]
    public function buildThrowsWhenCatalogPagesIsNotAReference(): void
    {
        // Arrange — /Pages is a PdfInteger, not a PdfIndirectReference
        $registry = new PdfObjectRegistry();
        $catalogRef = $registry->register(new PdfDictionary([
            'Pages' => new PdfInteger(99),
        ]));
        $doc = new PdfDocument($registry, PdfVersion::PDF_1_7, $catalogRef, null);

        // Act / Assert
        $this->expectException(RuntimeException::class);
        (new PdfDocumentMerger())->add($doc)->build();
    }

    #[Test]
    public function buildHandlesKidsEntryWithNonDictionaryNode(): void
    {
        // Arrange — Kids contains a reference to a non-dict object
        $registry = new PdfObjectRegistry();
        $badKidRef = $registry->register(new PdfInteger(0));
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

        // Act — builds without crash; no pages are included from the bad kid
        $result = (new PdfDocumentMerger())->add($doc)->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $result);
    }

    #[Test]
    public function buildSkipsKidsEntryThatIsNotAnIndirectReference(): void
    {
        // Arrange — Kids array contains a direct (non-reference) value (line 231: continue)
        $registry = new PdfObjectRegistry();
        $pageRef = $registry->register(new PdfDictionary([
            'Type' => new PdfName('Page'),
        ]));
        $pagesRef = $registry->register(new PdfDictionary([
            'Count' => new PdfInteger(1),
            'Kids' => new PdfArray([new PdfInteger(0), $pageRef]),
            'Type' => new PdfName('Pages'),
        ]));
        $page = $registry->get($pageRef);
        self::assertInstanceOf(PdfDictionary::class, $page);
        $page->set('Parent', $pagesRef);
        $catalogRef = $registry->register(new PdfDictionary([
            'Pages' => $pagesRef,
            'Type' => new PdfName('Catalog'),
        ]));
        $doc = new PdfDocument($registry, PdfVersion::PDF_1_7, $catalogRef, null);

        // Act — the non-reference Kids entry is skipped; the real page is still merged
        $result = (new PdfDocumentMerger())->add($doc)->build();

        // Assert
        $catalog = $result->getObjects()->get($result->getCatalog());
        self::assertInstanceOf(PdfDictionary::class, $catalog);
        $pagesRefOut = $catalog->get('Pages');
        self::assertInstanceOf(PdfIndirectReference::class, $pagesRefOut);
        $pages = $result->getObjects()->get($pagesRefOut);
        self::assertInstanceOf(PdfDictionary::class, $pages);
        $count = $pages->get('Count');
        self::assertInstanceOf(PdfInteger::class, $count);
        self::assertSame(1, $count->getValue());
    }

    private function buildDocument(int $pageCount = 1, PdfVersion $version = PdfVersion::PDF_1_7): PdfDocument
    {
        $registry = new PdfObjectRegistry();
        $pageRefs = [];

        for ($i = 0; $i < $pageCount; $i++) {
            $pageRefs[] = $registry->register(new PdfDictionary([
                'Type' => new PdfName('Page'),
            ]));
        }

        $pagesRef = $registry->register(new PdfDictionary([
            'Count' => new PdfInteger($pageCount),
            'Kids' => new PdfArray($pageRefs),
            'Type' => new PdfName('Pages'),
        ]));

        foreach ($pageRefs as $pageRef) {
            $page = $registry->get($pageRef);
            self::assertInstanceOf(PdfDictionary::class, $page);
            $page->set('Parent', $pagesRef);
        }

        $catalogRef = $registry->register(new PdfDictionary([
            'Pages' => $pagesRef,
            'Type' => new PdfName('Catalog'),
        ]));

        return new PdfDocument($registry, $version, $catalogRef, null);
    }

    private function buildDocumentWithStream(): PdfDocument
    {
        $registry = new PdfObjectRegistry();
        $streamRef = $registry->register(new PdfStream(new PdfDictionary([]), new PdfRawStreamData('BT ET')));
        $pageRef = $registry->register(new PdfDictionary([
            'Contents' => $streamRef,
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
