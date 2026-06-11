<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfImageExtractor;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfNull;
use PhpPdf\Reader\MinimalPdfFixture;
use PhpPdf\Reader\PdfImageExtractor;
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

#[CoversClass(PdfImageExtractor::class)]
#[CoversMethod(PdfImageExtractor::class, 'getImagesForPage')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfNull::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfObjectParser::class)]
#[UsesClass(PdfReadDocument::class)]
#[UsesClass(PdfReadPage::class)]
#[UsesClass(PdfToken::class)]
#[UsesClass(PdfXRefTable::class)]
final class GetImagesForPageTest extends TestCase
{
    use MinimalPdfFixture;

    #[Test]
    public function returnsEmptyArrayWhenPageHasNoImages(): void
    {
        // Arrange — minimal page has no XObject resources
        $document = self::createMinimalDocument();
        $extractor = new PdfImageExtractor($document);

        // Act
        $images = $extractor->getImagesForPage(0);

        // Assert
        self::assertSame([], $images);
    }

    #[Test]
    public function getAllImagesReturnsEmptyForDocumentWithoutImages(): void
    {
        // Arrange
        $document = self::createMinimalDocument();
        $extractor = new PdfImageExtractor($document);

        // Act
        $images = $extractor->getAllImages();

        // Assert
        self::assertSame([], $images);
    }
}
