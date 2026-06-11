<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfAcroFormReader;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfNull;
use PhpPdf\Reader\MinimalPdfFixture;
use PhpPdf\Reader\PdfAcroFormReader;
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

#[CoversClass(PdfAcroFormReader::class)]
#[CoversMethod(PdfAcroFormReader::class, 'getFieldsByName')]
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
final class GetFieldsByNameTest extends TestCase
{
    use MinimalPdfFixture;

    #[Test]
    public function returnsEmptyMapWhenNoAcroForm(): void
    {
        // Arrange
        $document = self::createMinimalDocument();
        $reader = new PdfAcroFormReader($document);

        // Act
        $map = $reader->getFieldsByName();

        // Assert
        self::assertSame([], $map);
    }
}
