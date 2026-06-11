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
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfObjectParser::class)]
#[UsesClass(PdfReadDocument::class)]
#[UsesClass(PdfReadPage::class)]
#[UsesClass(PdfToken::class)]
#[UsesClass(PdfXRefTable::class)]
#[UsesClass(PdfStreamSerializer::class)]
final class GetImagesWithDataTest extends TestCase
{
    #[Test]
    public function extractsImageFromPage(): void
    {
        // Arrange
        [$document] = self::buildPdfWithImage();
        $extractor = new PdfImageExtractor($document);

        // Act
        $images = $extractor->getImagesForPage(0);

        // Assert
        self::assertCount(1, $images);
        $img = $images[0];
        self::assertSame(1, $img->width);
        self::assertSame(1, $img->height);
        self::assertSame('DeviceRGB', $img->colorSpace);
    }

    #[Test]
    public function getAllImagesDeduplicatesAcrossPages(): void
    {
        // Arrange — single-page document with one image
        [$document] = self::buildPdfWithImage();
        $extractor = new PdfImageExtractor($document);

        // Act
        $images = $extractor->getAllImages();

        // Assert — one unique image across all pages
        self::assertCount(1, $images);
    }

    /**
     * Builds a minimal PDF that has a 1×1 RGB image XObject in its page resources.
     * The image stream is raw (no filter) with 3 bytes = one RGB pixel.
     *
     * @return array{\PhpPdf\Reader\PdfReadDocument, string}
     */
    private static function buildPdfWithImage(): array
    {
        // One red pixel: R=255, G=0, B=0
        $pixelData = "\xFF\x00\x00";

        $header = "%PDF-1.4\n";
        $body = '';

        // obj 1: Catalog
        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        // obj 2: Pages
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        // obj 4: image XObject (1×1 DeviceRGB)
        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n"
               . "<< /Type /XObject /Subtype /Image /Width 1 /Height 1"
               . " /ColorSpace /DeviceRGB /BitsPerComponent 8 /Length " . strlen($pixelData) . " >>\n"
               . "stream\n" . $pixelData . "\nendstream\nendobj\n";

        // obj 5: XObject dict
        $off5 = strlen($header) + strlen($body);
        $body .= "5 0 obj\n<< /Im1 4 0 R >>\nendobj\n";

        // obj 6: Resources dict
        $off6 = strlen($header) + strlen($body);
        $body .= "6 0 obj\n<< /XObject 5 0 R >>\nendobj\n";

        // obj 3: Page with /Resources
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

        return [$document, $pixelData];
    }
}
