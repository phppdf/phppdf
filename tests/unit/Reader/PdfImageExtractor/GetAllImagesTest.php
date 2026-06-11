<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfImageExtractor;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfNull;
use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Object\PdfStream;
use PhpPdf\Object\PdfVersion;
use PhpPdf\Reader\PdfExtractedImage;
use PhpPdf\Reader\PdfImageExtractor;
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

#[CoversClass(PdfImageExtractor::class)]
#[CoversMethod(PdfImageExtractor::class, 'getAllImages')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfExtractedImage::class)]
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
final class GetAllImagesTest extends TestCase
{
    #[Test]
    public function deduplicatesSameObjectAcrossPages(): void
    {
        // Arrange — two pages both reference image object 4 0 R
        $document = self::buildTwoPagePdfWithSharedImage();
        $extractor = new PdfImageExtractor($document);

        // Act
        $images = $extractor->getAllImages();

        // Assert — image appears only once despite being on two pages
        self::assertCount(1, $images);
    }

    /**
     * Builds a two-page PDF where both pages reference the same image object (4 0 R).
     * Each page has its own XObject and Resources dicts but both point to image obj 4.
     */
    private static function buildTwoPagePdfWithSharedImage(): PdfReadDocument
    {
        $pixelData = "\xFF\x00\x00"; // 1×1 red pixel

        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R 7 0 R] /Count 2 >>\nendobj\n";

        // Shared image stream
        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n"
               . "<< /Type /XObject /Subtype /Image /Width 1 /Height 1"
               . " /ColorSpace /DeviceRGB /BitsPerComponent 8 /Length " . strlen($pixelData) . " >>\n"
               . "stream\n" . $pixelData . "\nendstream\nendobj\n";

        // XObject dict and Resources for page 1
        $off5 = strlen($header) + strlen($body);
        $body .= "5 0 obj\n<< /Im1 4 0 R >>\nendobj\n";
        $off6 = strlen($header) + strlen($body);
        $body .= "6 0 obj\n<< /XObject 5 0 R >>\nendobj\n";

        // XObject dict and Resources for page 2 (different dicts, same image ref)
        $off8 = strlen($header) + strlen($body);
        $body .= "8 0 obj\n<< /Im1 4 0 R >>\nendobj\n";
        $off9 = strlen($header) + strlen($body);
        $body .= "9 0 obj\n<< /XObject 8 0 R >>\nendobj\n";

        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources 6 0 R >>\nendobj\n";

        $off7 = strlen($header) + strlen($body);
        $body .= "7 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources 9 0 R >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;

        $content .= "xref\n0 10\n0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= sprintf("%010d 00000 n \n", $off4);
        $content .= sprintf("%010d 00000 n \n", $off5);
        $content .= sprintf("%010d 00000 n \n", $off6);
        $content .= sprintf("%010d 00000 n \n", $off7);
        $content .= sprintf("%010d 00000 n \n", $off8);
        $content .= sprintf("%010d 00000 n \n", $off9);
        $content .= "trailer\n<< /Size 10 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);

        return new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);
    }
}
