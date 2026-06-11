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
#[CoversMethod(PdfReadDocument::class, 'getStartXRefOffset')]
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
final class GetStartXRefOffsetTest extends TestCase
{
    use MinimalPdfFixture;

    #[Test]
    public function returnsNonZeroOffset(): void
    {
        // Arrange
        $document = self::createMinimalDocument();

        // Act
        $offset = $document->getStartXRefOffset();

        // Assert
        self::assertGreaterThan(0, $offset);
    }

    #[Test]
    public function returnsNullDecryptionContext(): void
    {
        // Arrange
        $document = self::createMinimalDocument();

        // Act
        $context = $document->getDecryptionContext();

        // Assert
        self::assertNull($context);
    }

    #[Test]
    public function returnsXrefArray(): void
    {
        // Arrange
        $document = self::createMinimalDocument();

        // Act
        $xref = $document->getXref();

        // Assert
        self::assertArrayHasKey(1, $xref);
    }
}
