<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfAnnotationExtractor;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfBoolean;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfNull;
use PhpPdf\Object\PdfReal;
use PhpPdf\Object\PdfString;
use PhpPdf\Object\PdfVersion;
use PhpPdf\Reader\PdfAnnotation;
use PhpPdf\Reader\PdfAnnotationExtractor;
use PhpPdf\Reader\PdfAnnotationType;
use PhpPdf\Reader\PdfLexer;
use PhpPdf\Reader\PdfObjectParser;
use PhpPdf\Reader\PdfReadDocument;
use PhpPdf\Reader\PdfReadPage;
use PhpPdf\Reader\PdfToken;
use PhpPdf\Reader\PdfXRefTable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfAnnotationExtractor::class)]
#[CoversMethod(PdfAnnotationExtractor::class, 'getAnnotationsForPage')]
#[UsesClass(PdfAnnotation::class)]
#[UsesClass(PdfAnnotationType::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfBoolean::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfNull::class)]
#[UsesClass(PdfReal::class)]
#[UsesClass(PdfString::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfObjectParser::class)]
#[UsesClass(PdfReadDocument::class)]
#[UsesClass(PdfReadPage::class)]
#[UsesClass(PdfToken::class)]
#[UsesClass(PdfXRefTable::class)]
final class EdgeCasesTest extends TestCase
{
    #[Test]
    public function linkAnnotationWithGoToActionHasNullUri(): void
    {
        // Arrange — Link with /S /GoTo action (not URI)
        $document = self::buildPdfWithAnnotation(
            "<< /Type /Annot /Subtype /Link /Rect [0 0 100 20] /A << /S /GoTo /D [1 0 R] >> >>",
        );
        $extractor = new PdfAnnotationExtractor($document);

        // Act
        $annotations = $extractor->getAnnotationsForPage(0);

        // Assert — Link with GoTo has null uri
        self::assertCount(1, $annotations);
        self::assertNull($annotations[0]->uri);
        self::assertFalse($annotations[0]->isUriLink());
    }

    #[Test]
    public function annotationWithNullRectDefaultsToZero(): void
    {
        // Arrange — annotation with no /Rect entry
        $document = self::buildPdfWithAnnotation("<< /Type /Annot /Subtype /Text >>");
        $extractor = new PdfAnnotationExtractor($document);

        // Act
        $annotations = $extractor->getAnnotationsForPage(0);

        // Assert — defaults to [0, 0, 0, 0]
        self::assertCount(1, $annotations);
        self::assertEqualsWithDelta(0.0, $annotations[0]->x, 0.001);
        self::assertEqualsWithDelta(0.0, $annotations[0]->width, 0.001);
    }

    #[Test]
    public function colorWithZeroElementArrayReturnsNull(): void
    {
        // Arrange — /C [] with zero elements matches 'default' → null
        $document = self::buildPdfWithAnnotation("<< /Type /Annot /Subtype /Text /Rect [0 0 100 20] /C [] >>");
        $extractor = new PdfAnnotationExtractor($document);

        // Act
        $annotations = $extractor->getAnnotationsForPage(0);

        // Assert
        self::assertCount(1, $annotations);
        self::assertNull($annotations[0]->color);
    }

    #[Test]
    public function annotsArrayResolvesToNonArrayReturnsEmpty(): void
    {
        // Arrange — page /Annots references an object that resolves to an integer (line 72)
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n42\nendobj\n"; // integer, not array
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Annots 4 0 R >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 5\n";
        $content .= "0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= sprintf("%010d 00000 n \n", $off4);
        $content .= "trailer\n<< /Size 5 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);
        $extractor = new PdfAnnotationExtractor($document);

        // Act
        $annotations = $extractor->getAnnotationsForPage(0);

        // Assert — non-array Annots → empty result (line 72)
        self::assertSame([], $annotations);
    }

    #[Test]
    public function annotItemResolvingToNonDictionaryIsSkipped(): void
    {
        // Arrange — /Annots array contains an item that resolves to an integer (line 79: continue)
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n99\nendobj\n"; // not a dict
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Annots [4 0 R] >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 5\n";
        $content .= "0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= sprintf("%010d 00000 n \n", $off4);
        $content .= "trailer\n<< /Size 5 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);
        $extractor = new PdfAnnotationExtractor($document);

        // Act
        $annotations = $extractor->getAnnotationsForPage(0);

        // Assert — non-dict annotation item is skipped (line 79: continue)
        self::assertSame([], $annotations);
    }

    #[Test]
    public function rectResolvesToNonArrayReturnsZeroRect(): void
    {
        // Arrange — /Rect references an object that resolves to an integer (line 134)
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $off5 = strlen($header) + strlen($body);
        $body .= "5 0 obj\n42\nendobj\n"; // integer — Rect bad ref
        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n<< /Type /Annot /Subtype /Text /Rect 5 0 R >>\nendobj\n";
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Annots [4 0 R] >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 6\n";
        $content .= "0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= sprintf("%010d 00000 n \n", $off4);
        $content .= sprintf("%010d 00000 n \n", $off5);
        $content .= "trailer\n<< /Size 6 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);
        $document2 = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);
        $extractor = new PdfAnnotationExtractor($document2);

        // Act
        $annotations = $extractor->getAnnotationsForPage(0);

        // Assert — Rect resolves to non-array → [0,0,0,0] (line 134)
        self::assertCount(1, $annotations);
        self::assertEqualsWithDelta(0.0, $annotations[0]->x, 0.001);
        self::assertEqualsWithDelta(0.0, $annotations[0]->width, 0.001);
    }

    #[Test]
    public function colorEntryResolvesToNonArrayReturnsNull(): void
    {
        // Arrange — /C references an object that resolves to an integer (line 157)
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $off5 = strlen($header) + strlen($body);
        $body .= "5 0 obj\n42\nendobj\n"; // integer — bad color ref
        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n<< /Type /Annot /Subtype /Text /Rect [0 0 100 20] /C 5 0 R >>\nendobj\n";
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Annots [4 0 R] >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 6\n";
        $content .= "0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= sprintf("%010d 00000 n \n", $off4);
        $content .= sprintf("%010d 00000 n \n", $off5);
        $content .= "trailer\n<< /Size 6 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);
        $extractor = new PdfAnnotationExtractor($document);

        // Act
        $annotations = $extractor->getAnnotationsForPage(0);

        // Assert — /C resolves to non-array → null (line 157)
        self::assertCount(1, $annotations);
        self::assertNull($annotations[0]->color);
    }

    #[Test]
    public function quadPointsEntryResolvesToNonArrayReturnsNull(): void
    {
        // Arrange — /QuadPoints references an object that resolves to an integer (line 195)
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $off5 = strlen($header) + strlen($body);
        $body .= "5 0 obj\n42\nendobj\n"; // integer — bad QuadPoints ref
        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n<< /Type /Annot /Subtype /Highlight /Rect [0 0 100 20] /QuadPoints 5 0 R >>\nendobj\n";
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Annots [4 0 R] >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 6\n";
        $content .= "0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= sprintf("%010d 00000 n \n", $off4);
        $content .= sprintf("%010d 00000 n \n", $off5);
        $content .= "trailer\n<< /Size 6 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);
        $extractor = new PdfAnnotationExtractor($document);

        // Act
        $annotations = $extractor->getAnnotationsForPage(0);

        // Assert — /QuadPoints resolves to non-array → null (line 195)
        self::assertCount(1, $annotations);
        self::assertNull($annotations[0]->quadPoints);
    }

    #[Test]
    public function linkWithNoAEntryReturnsNullUri(): void
    {
        // Arrange — Link annotation with no /A entry (line 211: return null)
        $document = self::buildPdfWithAnnotation("<< /Type /Annot /Subtype /Link /Rect [0 0 100 20] >>");
        $extractor = new PdfAnnotationExtractor($document);

        // Act
        $annotations = $extractor->getAnnotationsForPage(0);

        // Assert — no /A entry → uri is null (line 211)
        self::assertCount(1, $annotations);
        self::assertNull($annotations[0]->uri);
    }

    #[Test]
    public function linkWithActionResolvingToNonDictionaryReturnsNullUri(): void
    {
        // Arrange — /A references an object that resolves to an integer (line 215)
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $off5 = strlen($header) + strlen($body);
        $body .= "5 0 obj\n99\nendobj\n"; // integer, not dict
        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n<< /Type /Annot /Subtype /Link /Rect [0 0 100 20] /A 5 0 R >>\nendobj\n";
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Annots [4 0 R] >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 6\n";
        $content .= "0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= sprintf("%010d 00000 n \n", $off4);
        $content .= sprintf("%010d 00000 n \n", $off5);
        $content .= "trailer\n<< /Size 6 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);
        $extractor = new PdfAnnotationExtractor($document);

        // Act
        $annotations = $extractor->getAnnotationsForPage(0);

        // Assert — /A resolves to non-dict → uri is null (line 215)
        self::assertCount(1, $annotations);
        self::assertNull($annotations[0]->uri);
    }

    #[Test]
    public function floatItemReturnsNullForNonNumericObject(): void
    {
        // Arrange — annotation with /Rect containing a name (not Real or Integer) → floatItem null (line 276)
        // This means the rect values are treated as 0.0 via the ?? 0.0 fallbacks.
        $document = self::buildPdfWithAnnotation("<< /Type /Annot /Subtype /Text /Rect [/BadName 0 100 20] >>");
        $extractor = new PdfAnnotationExtractor($document);

        // Act
        $annotations = $extractor->getAnnotationsForPage(0);

        // Assert — /BadName item returns null from floatItem → falls back to 0.0 (line 276)
        self::assertCount(1, $annotations);
        self::assertEqualsWithDelta(0.0, $annotations[0]->x, 0.001);
    }

    private static function buildPdfWithAnnotation(string $annotBody): PdfReadDocument
    {
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n" . $annotBody . "\n";

        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Annots [4 0 R] >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;

        $content .= "xref\n0 5\n";
        $content .= "0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= sprintf("%010d 00000 n \n", $off4);
        $content .= "trailer\n<< /Size 5 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);

        return new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);
    }
}
