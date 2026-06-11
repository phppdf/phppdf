<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfReadPage;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfNull;
use PhpPdf\Reader\MinimalPdfFixture;
use PhpPdf\Reader\PdfLexer;
use PhpPdf\Reader\PdfObjectParser;
use PhpPdf\Reader\PdfReadDocument;
use PhpPdf\Reader\PdfReadPage;
use PhpPdf\Reader\PdfToken;
use PhpPdf\Reader\PdfXRefTable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfReadPage::class)]
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
final class GetContentStreamsTest extends TestCase
{
    use MinimalPdfFixture;

    #[Test]
    public function returnsEmptyArrayWhenNoContents(): void
    {
        // Arrange — minimal page has no /Contents
        $document = self::createMinimalDocument();
        $page = $document->getPage(0);

        // Act
        $streams = $page->getContentStreams();

        // Assert
        self::assertSame([], $streams);
    }

    #[Test]
    public function returnsEmptyArrayForPageWithEmptyDict(): void
    {
        // Arrange
        $document = self::createMinimalDocument();
        $page = new PdfReadPage(new PdfDictionary([]), $document);

        // Act
        $streams = $page->getContentStreams();

        // Assert
        self::assertSame([], $streams);
    }
}
