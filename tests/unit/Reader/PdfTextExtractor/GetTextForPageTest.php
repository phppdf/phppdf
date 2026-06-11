<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfTextExtractor;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfNull;
use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Object\PdfStream;
use PhpPdf\Object\PdfVersion;
use PhpPdf\Reader\MinimalPdfFixture;
use PhpPdf\Reader\PdfLexer;
use PhpPdf\Reader\PdfObjectParser;
use PhpPdf\Reader\PdfReadDocument;
use PhpPdf\Reader\PdfReadPage;
use PhpPdf\Reader\PdfTextExtractionState;
use PhpPdf\Reader\PdfTextExtractor;
use PhpPdf\Reader\PdfToken;
use PhpPdf\Reader\PdfXRefTable;
use PhpPdf\Serialization\PdfStreamSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfTextExtractor::class)]
#[CoversMethod(PdfTextExtractor::class, 'getTextForPage')]
#[UsesClass(PdfTextExtractionState::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfNull::class)]
#[UsesClass(PdfRawStreamData::class)]
#[UsesClass(PdfStream::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfObjectParser::class)]
#[UsesClass(PdfReadDocument::class)]
#[UsesClass(PdfReadPage::class)]
#[UsesClass(PdfToken::class)]
#[UsesClass(PdfXRefTable::class)]
#[UsesClass(PdfStreamSerializer::class)]
final class GetTextForPageTest extends TestCase
{
    use MinimalPdfFixture;

    #[Test]
    public function returnsEmptyStringForPageWithoutContent(): void
    {
        // Arrange — minimal page has no /Contents
        $document = self::createMinimalDocument();
        $extractor = new PdfTextExtractor($document);

        // Act
        $text = $extractor->getTextForPage(0);

        // Assert
        self::assertSame('', $text);
    }

    #[Test]
    public function extractsTextFromSimpleContentStream(): void
    {
        // Arrange — build a PDF with a simple text content stream
        $content = "BT\n/F1 12 Tf\n(Hello World) Tj\nET\n";
        $compressed = gzcompress($content) ?: '';

        $header = "%PDF-1.4\n";
        $body = '';

        // obj 1: Catalog
        $off1 = strlen($header);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        // obj 2: Pages
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        // obj 3: Page with /Contents 4 0 R
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R >>\nendobj\n";

        // obj 4: content stream
        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n<< /Length " . strlen($compressed) . " /Filter /FlateDecode >>\nstream\n"
               . $compressed . "\nendstream\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $pdfContent = $header . $body;

        $pdfContent .= "xref\n0 5\n";
        $pdfContent .= "0000000000 65535 f \n";
        $pdfContent .= sprintf("%010d 00000 n \n", $off1);
        $pdfContent .= sprintf("%010d 00000 n \n", $off2);
        $pdfContent .= sprintf("%010d 00000 n \n", $off3);
        $pdfContent .= sprintf("%010d 00000 n \n", $off4);
        $pdfContent .= "trailer\n<< /Size 5 /Root 1 0 R >>\n";
        $pdfContent .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($pdfContent);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4);
        $extractor = new PdfTextExtractor($document);

        // Act
        $text = $extractor->getTextForPage(0);

        // Assert
        self::assertStringContainsString('Hello World', $text);
    }
}
