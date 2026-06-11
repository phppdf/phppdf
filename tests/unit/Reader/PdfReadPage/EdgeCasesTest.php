<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfReadPage;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfNull;
use PhpPdf\Object\PdfVersion;
use PhpPdf\Reader\PdfLexer;
use PhpPdf\Reader\PdfObjectParser;
use PhpPdf\Reader\PdfReadDocument;
use PhpPdf\Reader\PdfReadPage;
use PhpPdf\Reader\PdfToken;
use PhpPdf\Reader\PdfXRefTable;
use PhpPdf\Serialization\PdfStreamSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfReadPage::class)]
#[CoversMethod(PdfReadPage::class, 'getResources')]
#[CoversMethod(PdfReadPage::class, 'getContentStreams')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfNull::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfObjectParser::class)]
#[UsesClass(PdfReadDocument::class)]
#[UsesClass(PdfToken::class)]
#[UsesClass(PdfXRefTable::class)]
#[UsesClass(PdfStreamSerializer::class)]
final class EdgeCasesTest extends TestCase
{
    #[Test]
    public function getResourcesReturnsEmptyDictWhenNotDict(): void
    {
        // Arrange — /Resources is a string (not a dict)
        $lexer = PdfLexer::fromString('');
        $trailer = new PdfDictionary([]);
        $document = new PdfReadDocument($lexer, [], $trailer, PdfVersion::PDF_1_4);

        $pageDict = new PdfDictionary(['Resources' => new PdfInteger(999)]);
        $page = new PdfReadPage($pageDict, $document);

        // Act
        $resources = $page->getResources();

        // Assert — falls back to empty dict
        self::assertInstanceOf(PdfDictionary::class, $resources);
        self::assertEmpty($resources->getEntries());
    }

    #[Test]
    public function getContentStreamsHandlesNonStreamReferences(): void
    {
        // Arrange — /Contents is an integer (not a stream ref)
        $lexer = PdfLexer::fromString('');
        $trailer = new PdfDictionary([]);
        $document = new PdfReadDocument($lexer, [], $trailer, PdfVersion::PDF_1_4);

        $pageDict = new PdfDictionary(['Contents' => new PdfInteger(999)]);
        $page = new PdfReadPage($pageDict, $document);

        // Act — resolving an integer returns PdfNull (not PdfStream), so no streams
        $streams = $page->getContentStreams();

        // Assert
        self::assertSame([], $streams);
    }
}
