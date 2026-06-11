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
#[CoversMethod(PdfImageExtractor::class, 'getImagesForPage')]
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
final class EdgeCasesTest extends TestCase
{
    #[Test]
    public function xobjectDictResolvesToNonDictionary(): void
    {
        // Arrange — Resources /XObject is a direct PdfName, not an indirect ref to a dict
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]"
               . " /Resources << /XObject /NotADict >> >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 4\n0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= "trailer\n<< /Size 4 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $extractor = new PdfImageExtractor(self::buildDocument($content));

        // Act
        $images = $extractor->getImagesForPage(0);

        // Assert
        self::assertSame([], $images);
    }

    #[Test]
    public function directlyEmbeddedImageXObjectIsExtracted(): void
    {
        // Arrange — XObject entry value is a direct (non-indirect) stream object,
        // so $ref is not a PdfIndirectReference and objNum defaults to 0.
        $data = "\xFF\x00\x00";
        $length = strlen($data);

        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842]"
               . " /Resources << /XObject << /Im1 << /Type /XObject /Subtype /Image"
               . " /Width 1 /Height 1 /ColorSpace /DeviceRGB /BitsPerComponent 8"
               . " /Length {$length} >>\nstream\n" . $data . "\nendstream >> >> >> >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 4\n0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= "trailer\n<< /Size 4 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $extractor = new PdfImageExtractor(self::buildDocument($content));

        // Act
        $images = $extractor->getImagesForPage(0);

        // Assert — image extracted with objectNumber 0 (not an indirect reference)
        self::assertCount(1, $images);
        self::assertSame(0, $images[0]->objectNumber);
    }

    #[Test]
    public function nonStreamXObjectIsSkipped(): void
    {
        // Arrange — XObject entry resolves to a plain dictionary, not a stream
        $document = self::buildPdfWithXObjectBody('<< /Type /XObject /Subtype /Image >>');
        $extractor = new PdfImageExtractor($document);

        // Act
        $images = $extractor->getImagesForPage(0);

        // Assert
        self::assertSame([], $images);
    }

    #[Test]
    public function formXObjectIsSkipped(): void
    {
        // Arrange — /Subtype /Form XObject must be ignored
        $formData = "q Q";
        $formBody = "<< /Type /XObject /Subtype /Form /Length " . strlen(
            $formData,
        ) . " >>\nstream\n" . $formData . "\nendstream";
        $document = self::buildPdfWithXObjectBody($formBody);
        $extractor = new PdfImageExtractor($document);

        // Act
        $images = $extractor->getImagesForPage(0);

        // Assert
        self::assertSame([], $images);
    }

    #[Test]
    public function imageWithMissingWidthIsSkipped(): void
    {
        // Arrange — image stream is missing the /Width key
        $data = "\xFF";
        $length = strlen($data);
        $imgBody = "<< /Type /XObject /Subtype /Image /Height 1 /ColorSpace /DeviceGray"
                 . " /BitsPerComponent 8 /Length {$length} >>\nstream\n" . $data . "\nendstream";
        $document = self::buildPdfWithXObjectBody($imgBody);
        $extractor = new PdfImageExtractor($document);

        // Act
        $images = $extractor->getImagesForPage(0);

        // Assert
        self::assertSame([], $images);
    }

    #[Test]
    public function imageWithZeroHeightIsSkipped(): void
    {
        // Arrange — image stream has /Height 0 (degenerate dimension)
        $data = "\xFF";
        $length = strlen($data);
        $imgBody = "<< /Type /XObject /Subtype /Image /Width 1 /Height 0 /ColorSpace /DeviceGray"
                 . " /BitsPerComponent 8 /Length {$length} >>\nstream\n" . $data . "\nendstream";
        $document = self::buildPdfWithXObjectBody($imgBody);
        $extractor = new PdfImageExtractor($document);

        // Act
        $images = $extractor->getImagesForPage(0);

        // Assert
        self::assertSame([], $images);
    }

    #[Test]
    public function imageWithSmaskIsExtracted(): void
    {
        // Arrange — 1×1 RGB image with a separate soft-mask stream
        $pixelData = "\xFF\x00\x00"; // red pixel
        $alphaData = "\xFF"; // fully opaque

        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        $off7 = strlen($header) + strlen($body);
        $body .= "7 0 obj\n"
               . "<< /Type /XObject /Subtype /Image /Width 1 /Height 1"
               . " /ColorSpace /DeviceGray /BitsPerComponent 8 /Length " . strlen($alphaData) . " >>\n"
               . "stream\n" . $alphaData . "\nendstream\nendobj\n";

        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n"
               . "<< /Type /XObject /Subtype /Image /Width 1 /Height 1"
               . " /ColorSpace /DeviceRGB /BitsPerComponent 8 /SMask 7 0 R /Length " . strlen($pixelData) . " >>\n"
               . "stream\n" . $pixelData . "\nendstream\nendobj\n";

        $off5 = strlen($header) + strlen($body);
        $body .= "5 0 obj\n<< /Im1 4 0 R >>\nendobj\n";
        $off6 = strlen($header) + strlen($body);
        $body .= "6 0 obj\n<< /XObject 5 0 R >>\nendobj\n";
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources 6 0 R >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 8\n0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= sprintf("%010d 00000 n \n", $off4);
        $content .= sprintf("%010d 00000 n \n", $off5);
        $content .= sprintf("%010d 00000 n \n", $off6);
        $content .= sprintf("%010d 00000 n \n", $off7);
        $content .= "trailer\n<< /Size 8 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $extractor = new PdfImageExtractor(self::buildDocument($content));

        // Act
        $images = $extractor->getImagesForPage(0);

        // Assert — image extracted and smask data populated
        self::assertCount(1, $images);
        self::assertNotNull($images[0]->smaskData);
        self::assertSame($alphaData, $images[0]->smaskData);
    }

    #[Test]
    public function missingColorSpaceDefaultsToDeviceRgb(): void
    {
        // Arrange — image XObject has no /ColorSpace key
        $data = "\xFF\x00\x00";
        $imgBody = "<< /Type /XObject /Subtype /Image /Width 1 /Height 1 /BitsPerComponent 8 /Length " . strlen(
            $data,
        ) . " >>\nstream\n" . $data . "\nendstream";
        $document = self::buildPdfWithXObjectBody($imgBody);
        $extractor = new PdfImageExtractor($document);

        // Act
        $images = $extractor->getImagesForPage(0);

        // Assert — absent ColorSpace defaults to DeviceRGB
        self::assertCount(1, $images);
        self::assertSame('DeviceRGB', $images[0]->colorSpace);
    }

    #[Test]
    public function unknownColorSpaceTypeDefaultsToDeviceRgb(): void
    {
        // Arrange — /ColorSpace is null (neither PdfName nor PdfArray)
        $data = "\xFF\x00\x00";
        $length = strlen($data);
        $imgBody = "<< /Type /XObject /Subtype /Image /Width 1 /Height 1 /ColorSpace null"
                 . " /BitsPerComponent 8 /Length {$length} >>\nstream\n" . $data . "\nendstream";
        $document = self::buildPdfWithXObjectBody($imgBody);
        $extractor = new PdfImageExtractor($document);

        // Act
        $images = $extractor->getImagesForPage(0);

        // Assert — unrecognised ColorSpace type falls back to DeviceRGB
        self::assertCount(1, $images);
        self::assertSame('DeviceRGB', $images[0]->colorSpace);
    }

    private static function buildDocument(string $content): PdfReadDocument
    {
        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);

        return new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);
    }

    /**
     * Builds a one-page PDF where obj 4 is the customisable XObject body.
     * XObject dict (obj 5) references it as /Im1 4 0 R.
     * Resources dict (obj 6) exposes the XObject dict via /XObject 5 0 R.
     */
    private static function buildPdfWithXObjectBody(string $xobjectBody): PdfReadDocument
    {
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n" . $xobjectBody . "\nendobj\n";

        $off5 = strlen($header) + strlen($body);
        $body .= "5 0 obj\n<< /Im1 4 0 R >>\nendobj\n";

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

        return self::buildDocument($content);
    }
}
