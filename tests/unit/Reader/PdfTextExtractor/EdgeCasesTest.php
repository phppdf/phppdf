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
final class EdgeCasesTest extends TestCase
{
    #[Test]
    public function fontEntryResolvesToNonDictionary(): void
    {
        // Arrange — /Font is a direct PdfName, not an indirect ref to a dict;
        //            resolveObject returns the name itself (not PdfDictionary) → return []
        $document = self::buildDocumentWithInlineResources('<< /Font /NotADict >>', "BT\n(Hello) Tj\nET\n");
        $extractor = new PdfTextExtractor($document);

        // Act
        $text = $extractor->getTextForPage(0);

        // Assert — no crash; text still extracted via fallback encoding
        self::assertStringContainsString('Hello', $text);
    }

    #[Test]
    public function fontObjectResolvesToNonDictionary(): void
    {
        // Arrange — Font dict has a direct integer entry (42) instead of a font dict ref;
        //            resolveObject(PdfInteger) returns PdfInteger → not PdfDictionary → continue
        $document = self::buildDocumentWithInlineResources(
            '<< /Font << /F1 42 >> >>',
            "BT\n/F1 12 Tf\n(Hello) Tj\nET\n",
        );
        $extractor = new PdfTextExtractor($document);

        // Act
        $text = $extractor->getTextForPage(0);

        // Assert
        self::assertStringContainsString('Hello', $text);
    }

    #[Test]
    public function toUnicodeCmapIsNullWhenKeyMissing(): void
    {
        // Arrange — Type0 font without /ToUnicode; loadToUnicodeCmap returns null
        $document = self::buildDocumentWithInlineResources(
            '<< /Font << /F1 << /Type /Font /Subtype /Type0 /BaseFont /TestFont >> >> >>',
            "BT\n/F1 12 Tf\n(Hi) Tj\nET\n",
        );
        $extractor = new PdfTextExtractor($document);

        // Act — should not crash; falls back to simple encoding
        $text = $extractor->getTextForPage(0);

        // Assert
        self::assertNotSame(false, $text);
    }

    #[Test]
    public function toUnicodeCmapIsNullWhenNotStream(): void
    {
        // Arrange — /ToUnicode is a direct PdfName (not an indirect ref to a stream);
        //            resolveObject returns PdfName → not PdfStream → return null
        $document = self::buildDocumentWithInlineResources(
            '<< /Font << /F1 << /Type /Font /Subtype /Type0 /BaseFont /TestFont /ToUnicode /NotAStream >> >> >>',
            "BT\n/F1 12 Tf\n(Hi) Tj\nET\n",
        );
        $extractor = new PdfTextExtractor($document);

        // Act
        $text = $extractor->getTextForPage(0);

        // Assert
        self::assertNotSame(false, $text);
    }

    #[Test]
    public function cmapDecodeWithOddLengthStringTriggersOddByteSafetyCheck(): void
    {
        // Arrange — Type0 font with ToUnicode CMap; content stream uses a 3-byte literal
        //            string so decodeWithCmap hits the odd-length safety branch
        $cmapContent = "begincmap\nendcmap\n"; // minimal valid CMap with no mappings
        $compressedCmap = gzcompress($cmapContent) ?: '';

        $contentStream = "BT\n/F1 12 Tf\n(ABC) Tj\nET\n"; // 'ABC' = 3 bytes (odd)
        $compressedContent = gzcompress($contentStream) ?: '';

        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        // obj 6: ToUnicode CMap stream
        $off6 = strlen($header) + strlen($body);
        $body .= "6 0 obj\n<< /Length " . strlen($compressedCmap) . " /Filter /FlateDecode >>\nstream\n"
               . $compressedCmap . "\nendstream\nendobj\n";

        // obj 5: Type0 font referencing the CMap
        $off5 = strlen($header) + strlen($body);
        $body .= "5 0 obj\n<< /Type /Font /Subtype /Type0 /BaseFont /TestFont /ToUnicode 6 0 R >>\nendobj\n";

        // obj 7: Font resource dict
        $off7 = strlen($header) + strlen($body);
        $body .= "7 0 obj\n<< /F1 5 0 R >>\nendobj\n";

        // obj 8: Resources
        $off8 = strlen($header) + strlen($body);
        $body .= "8 0 obj\n<< /Font 7 0 R >>\nendobj\n";

        // obj 4: content stream
        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n<< /Length " . strlen($compressedContent) . " /Filter /FlateDecode >>\nstream\n"
               . $compressedContent . "\nendstream\nendobj\n";

        // obj 3: Page
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]"
               . " /Resources 8 0 R /Contents 4 0 R >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;

        $content .= "xref\n0 9\n0000000000 65535 f \n";
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

        $extractor = new PdfTextExtractor(self::buildDocument($content));

        // Act
        $text = $extractor->getTextForPage(0);

        // Assert — ran without error; odd-length safety branch executed
        self::assertNotSame(false, $text);
    }

    private static function buildDocument(string $content): PdfReadDocument
    {
        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);

        return new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);
    }

    /**
     * Builds a minimal one-page PDF whose page has a FlateDecode content stream
     * and inline Resources (no separate objects for resources).
     * The resources dict body is embedded directly into the Page dict.
     */
    private static function buildDocumentWithInlineResources(
        string $inlineResourcesDict,
        string $contentStream,
    ): PdfReadDocument {
        $compressed = gzcompress($contentStream) ?: '';

        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n<< /Length " . strlen($compressed) . " /Filter /FlateDecode >>\nstream\n"
               . $compressed . "\nendstream\nendobj\n";

        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]"
               . " /Resources " . $inlineResourcesDict . " /Contents 4 0 R >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;

        $content .= "xref\n0 5\n0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= sprintf("%010d 00000 n \n", $off4);
        $content .= "trailer\n<< /Size 5 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        return self::buildDocument($content);
    }
}
