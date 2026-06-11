<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfReadDocument;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfNull;
use PhpPdf\Reader\Exception\PdfReadException;
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

#[CoversClass(PdfReadDocument::class)]
#[CoversMethod(PdfReadDocument::class, 'getPage')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfNull::class)]
#[UsesClass(PdfReadException::class)]
#[UsesClass(PdfReadPage::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfObjectParser::class)]
#[UsesClass(PdfToken::class)]
#[UsesClass(PdfXRefTable::class)]
final class GetPageTest extends TestCase
{
    use MinimalPdfFixture;

    #[Test]
    public function returnsPageAtValidIndex(): void
    {
        // Arrange
        $document = self::createMinimalDocument();

        // Act
        $page = $document->getPage(0);

        // Assert
        self::assertInstanceOf(PdfReadPage::class, $page);
    }

    #[Test]
    public function throwsForNegativeIndex(): void
    {
        // Arrange
        $document = self::createMinimalDocument();

        // Act / Assert
        $this->expectException(PdfReadException::class);
        $document->getPage(-1);
    }

    #[Test]
    public function throwsForOutOfBoundsIndex(): void
    {
        // Arrange
        $document = self::createMinimalDocument();

        // Act / Assert
        $this->expectException(PdfReadException::class);
        $document->getPage(99);
    }
}
