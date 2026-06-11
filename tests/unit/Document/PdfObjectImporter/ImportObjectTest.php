<?php

declare(strict_types=1);

namespace PhpPdf\Document\PdfObjectImporter;

use PhpPdf\Document\PdfObjectImporter;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfNull;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Reader\MinimalPdfFixture;
use PhpPdf\Reader\PdfLexer;
use PhpPdf\Reader\PdfObjectParser;
use PhpPdf\Reader\PdfReadDocument;
use PhpPdf\Reader\PdfToken;
use PhpPdf\Reader\PdfXRefTable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfObjectImporter::class)]
#[CoversMethod(PdfObjectImporter::class, 'importObject')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfNull::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfObjectParser::class)]
#[UsesClass(PdfReadDocument::class)]
#[UsesClass(PdfToken::class)]
#[UsesClass(PdfXRefTable::class)]
final class ImportObjectTest extends TestCase
{
    use MinimalPdfFixture;

    #[Test]
    public function returnsLeafObjectAsIs(): void
    {
        // Arrange
        $document = self::createMinimalDocument();
        $registry = new PdfObjectRegistry();
        $importer = new PdfObjectImporter($document, $registry);
        $integer = new PdfInteger(42);

        // Act
        $result = $importer->importObject($integer);

        // Assert — leaf objects are shared, not cloned
        self::assertSame($integer, $result);
    }

    #[Test]
    public function clonesAndRegistersIndirectReference(): void
    {
        // Arrange — object 1 is the Catalog (a PdfDictionary)
        $document = self::createMinimalDocument();
        $registry = new PdfObjectRegistry();
        $importer = new PdfObjectImporter($document, $registry);
        $ref = new PdfIndirectReference(1, 0);

        // Act
        $result = $importer->importObject($ref);

        // Assert
        self::assertInstanceOf(PdfIndirectReference::class, $result);
    }

    #[Test]
    public function returnsSameCloneForRepeatedImport(): void
    {
        // Arrange
        $document = self::createMinimalDocument();
        $registry = new PdfObjectRegistry();
        $importer = new PdfObjectImporter($document, $registry);
        $ref = new PdfIndirectReference(1, 0);

        // Act — import twice
        $first = $importer->importObject($ref);
        $second = $importer->importObject($ref);

        // Assert — same registered reference returned both times
        self::assertSame($first, $second);
    }

    #[Test]
    public function clonesArrayEntries(): void
    {
        // Arrange
        $document = self::createMinimalDocument();
        $registry = new PdfObjectRegistry();
        $importer = new PdfObjectImporter($document, $registry);
        $array = new PdfArray([new PdfName('Foo'), new PdfInteger(1)]);

        // Act
        $result = $importer->importObject($array);

        // Assert
        self::assertInstanceOf(PdfArray::class, $result);
        self::assertCount(2, $result->getItems());
    }

    #[Test]
    public function clonesDictionaryEntries(): void
    {
        // Arrange
        $document = self::createMinimalDocument();
        $registry = new PdfObjectRegistry();
        $importer = new PdfObjectImporter($document, $registry);
        $dict = new PdfDictionary(['Key' => new PdfName('Value')]);

        // Act
        $result = $importer->importObject($dict);

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $result);
        self::assertNotNull($result->get('Key'));
    }
}
