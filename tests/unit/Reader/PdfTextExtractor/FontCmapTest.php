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
final class FontCmapTest extends TestCase
{
    /**
     * Builds a PDF with a Type0 font that has a ToUnicode CMap stream.
     * The CMap maps code 0x0048 → U+0048 'H', 0x0069 → U+0069 'i'.
     * The content stream shows those codes using a hex string.
     */
    #[Test]
    public function extractsTextFromType0FontWithToUnicodeCmap(): void
    {
        // Arrange
        $cmapContent = "/CIDInit /ProcSet findresource begin\n"
            . "12 dict begin\n"
            . "begincmap\n"
            . "/CIDSystemInfo << /Registry (Adobe) /Ordering (UCS) /Supplement 0 >> def\n"
            . "/CMapName /Adobe-Identity-UCS def\n"
            . "/CMapType 2 def\n"
            . "1 begincodespacerange\n"
            . "<0000> <FFFF>\n"
            . "endcodespacerange\n"
            . "2 beginbfchar\n"
            . "<0048> <0048>\n" // 0x0048 → U+0048 'H'
            . "<0069> <0069>\n" // 0x0069 → U+0069 'i'
            . "endbfchar\n"
            . "endcmap\n"
            . "CMapName currentdict /CMap defineresource pop\n"
            . "end\nend\n";

        $compressedCmap = gzcompress($cmapContent) ?: '';

        // The content stream shows 'H' and 'i' as 2-byte codes in a hex string
        // <00480069> = H + i
        $contentStream = "BT\n/F1 12 Tf\n<00480069> Tj\nET\n";
        $compressedContent = gzcompress($contentStream) ?: '';

        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        // obj 7: ToUnicode CMap stream
        $off7 = strlen($header) + strlen($body);
        $body .= "7 0 obj\n<< /Length " . strlen($compressedCmap) . " /Filter /FlateDecode >>\nstream\n"
               . $compressedCmap . "\nendstream\nendobj\n";

        // obj 6: Type0 font with ToUnicode reference
        $off6 = strlen($header) + strlen($body);
        $body .= "6 0 obj\n<< /Type /Font /Subtype /Type0 /BaseFont /TestFont /ToUnicode 7 0 R >>\nendobj\n";

        // obj 5: Font resource dict
        $off5 = strlen($header) + strlen($body);
        $body .= "5 0 obj\n<< /F1 6 0 R >>\nendobj\n";

        // obj 8: Resources dict
        $off8 = strlen($header) + strlen($body);
        $body .= "8 0 obj\n<< /Font 5 0 R >>\nendobj\n";

        // obj 4: content stream
        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n<< /Length " . strlen($compressedContent) . " /Filter /FlateDecode >>\nstream\n"
               . $compressedContent . "\nendstream\nendobj\n";

        // obj 3: Page with /Resources and /Contents
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]"
               . " /Resources 8 0 R /Contents 4 0 R >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;

        $content .= "xref\n0 9\n";
        $content .= "0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= sprintf("%010d 00000 n \n", $off4);
        $content .= sprintf("%010d 00000 n \n", $off5);
        $content .= sprintf("%010d 00000 n \n", $off6);
        $content .= sprintf("%010d 00000 n \n", $off7);
        $content .= sprintf("%010d 00000 n \n", $off8);
        $content .= "trailer\n<< /Size 9 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);
        $extractor = new PdfTextExtractor($document);

        // Act
        $text = $extractor->getTextForPage(0);

        // Assert — 'H' (U+0048) and 'i' (U+0069) should appear
        self::assertStringContainsString('H', $text);
        self::assertStringContainsString('i', $text);
    }

    #[Test]
    public function extractsTextFromSimpleFontViaDictionary(): void
    {
        // Arrange — Type1 (simple) font in resources; text is WinAnsi
        $contentStream = "BT\n/F1 12 Tf\n(Hello) Tj\nET\n";
        $compressed = gzcompress($contentStream) ?: '';

        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        // obj 5: Type1 font
        $off5 = strlen($header) + strlen($body);
        $body .= "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

        // obj 6: Font resource dict
        $off6 = strlen($header) + strlen($body);
        $body .= "6 0 obj\n<< /F1 5 0 R >>\nendobj\n";

        // obj 7: Resources
        $off7 = strlen($header) + strlen($body);
        $body .= "7 0 obj\n<< /Font 6 0 R >>\nendobj\n";

        // obj 4: content stream
        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n<< /Length " . strlen($compressed) . " /Filter /FlateDecode >>\nstream\n"
               . $compressed . "\nendstream\nendobj\n";

        // obj 3: Page
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]"
               . " /Resources 7 0 R /Contents 4 0 R >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;

        $content .= "xref\n0 8\n";
        $content .= "0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= sprintf("%010d 00000 n \n", $off4);
        $content .= sprintf("%010d 00000 n \n", $off5);
        $content .= sprintf("%010d 00000 n \n", $off6);
        $content .= sprintf("%010d 00000 n \n", $off7);
        $content .= "trailer\n<< /Size 8 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);
        $extractor = new PdfTextExtractor($document);

        // Act
        $text = $extractor->getTextForPage(0);

        // Assert
        self::assertStringContainsString('Hello', $text);
    }
}
