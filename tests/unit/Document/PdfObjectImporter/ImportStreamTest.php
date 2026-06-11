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
use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Object\PdfStream;
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
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfNull::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfRawStreamData::class)]
#[UsesClass(PdfStream::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfObjectParser::class)]
#[UsesClass(PdfReadDocument::class)]
#[UsesClass(PdfToken::class)]
#[UsesClass(PdfXRefTable::class)]
final class ImportStreamTest extends TestCase
{
    use MinimalPdfFixture;

    #[Test]
    public function clonesStreamObject(): void
    {
        // Arrange
        $document = self::createMinimalDocument();
        $registry = new PdfObjectRegistry();
        $importer = new PdfObjectImporter($document, $registry);
        $stream = new PdfStream(
            new PdfDictionary([]),
            new PdfRawStreamData('data'),
        );

        // Act
        $result = $importer->importObject($stream);

        // Assert
        self::assertInstanceOf(PdfStream::class, $result);
    }
}
