<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfReadDocument;

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
use PhpPdf\Reader\PdfToken;
use PhpPdf\Reader\PdfXRefTable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfReadDocument::class)]
#[CoversMethod(PdfReadDocument::class, 'getCatalog')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfNull::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfObjectParser::class)]
#[UsesClass(PdfToken::class)]
#[UsesClass(PdfXRefTable::class)]
final class GetCatalogTest extends TestCase
{
    use MinimalPdfFixture;

    #[Test]
    public function returnsCatalogDictionary(): void
    {
        // Arrange
        $document = self::createMinimalDocument();

        // Act
        $catalog = $document->getCatalog();

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $catalog);
    }
}
