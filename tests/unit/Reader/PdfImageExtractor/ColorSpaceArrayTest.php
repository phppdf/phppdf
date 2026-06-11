<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfImageExtractor;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfBoolean;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfNull;
use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Object\PdfStream;
use PhpPdf\Object\PdfString;
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
#[CoversMethod(PdfImageExtractor::class, 'getImagesForPage')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfBoolean::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfExtractedImage::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfNull::class)]
#[UsesClass(PdfRawStreamData::class)]
#[UsesClass(PdfStream::class)]
#[UsesClass(PdfString::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfObjectParser::class)]
#[UsesClass(PdfReadDocument::class)]
#[UsesClass(PdfReadPage::class)]
#[UsesClass(PdfToken::class)]
#[UsesClass(PdfXRefTable::class)]
#[UsesClass(PdfStreamSerializer::class)]
final class ColorSpaceArrayTest extends TestCase
{
    /**
     * Builds a PDF with an image whose ColorSpace is an array (e.g., [/Indexed /DeviceRGB 1 "\xFF\x00\x00"]).
     */
    #[Test]
    public function extractsImageWithArrayColorSpace(): void
    {
        // Arrange — 1×1 Indexed image (ColorSpace is an array)
        $pixelData = "\x00"; // palette index 0

        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n"
               . "<< /Type /XObject /Subtype /Image /Width 1 /Height 1"
               . " /ColorSpace [/Indexed /DeviceRGB 0 (\\xFF\\x00\\x00)]"
               . " /BitsPerComponent 8 /Length " . strlen($pixelData) . " >>\n"
               . "stream\n" . $pixelData . "\nendstream\nendobj\n";

        $off5 = strlen($header) + strlen($body);
        $body .= "5 0 obj\n<< /Im1 4 0 R >>\nendobj\n";

        $off6 = strlen($header) + strlen($body);
        $body .= "6 0 obj\n<< /XObject 5 0 R >>\nendobj\n";

        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources 6 0 R >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;

        $content .= "xref\n0 7\n";
        $content .= "0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= sprintf("%010d 00000 n \n", $off4);
        $content .= sprintf("%010d 00000 n \n", $off5);
        $content .= sprintf("%010d 00000 n \n", $off6);
        $content .= "trailer\n<< /Size 7 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);
        $extractor = new PdfImageExtractor($document);

        // Act
        $images = $extractor->getImagesForPage(0);

        // Assert — image extracted, colorSpace comes from first array element
        self::assertCount(1, $images);
        self::assertSame('Indexed', $images[0]->colorSpace);
    }

    #[Test]
    public function skipsImageMaskXObjects(): void
    {
        // Arrange — image with /ImageMask true should be skipped
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        $maskData = "\xFF";
        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n"
               . "<< /Type /XObject /Subtype /Image /Width 1 /Height 1"
               . " /ImageMask true /BitsPerComponent 1 /Length " . strlen($maskData) . " >>\n"
               . "stream\n" . $maskData . "\nendstream\nendobj\n";

        $off5 = strlen($header) + strlen($body);
        $body .= "5 0 obj\n<< /Mask 4 0 R >>\nendobj\n";
        $off6 = strlen($header) + strlen($body);
        $body .= "6 0 obj\n<< /XObject 5 0 R >>\nendobj\n";
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources 6 0 R >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 7\n0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= sprintf("%010d 00000 n \n", $off4);
        $content .= sprintf("%010d 00000 n \n", $off5);
        $content .= sprintf("%010d 00000 n \n", $off6);
        $content .= "trailer\n<< /Size 7 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);
        $extractor = new PdfImageExtractor($document);

        // Act
        $images = $extractor->getImagesForPage(0);

        // Assert — ImageMask=true is skipped
        self::assertSame([], $images);
    }
}
