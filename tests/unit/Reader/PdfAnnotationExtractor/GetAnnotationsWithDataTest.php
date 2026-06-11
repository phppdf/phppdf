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
final class GetAnnotationsWithDataTest extends TestCase
{
    #[Test]
    public function extractsTextAnnotation(): void
    {
        // Arrange
        $document = self::buildPdfWithTextAnnotation();
        $extractor = new PdfAnnotationExtractor($document);

        // Act
        $annotations = $extractor->getAnnotationsForPage(0);

        // Assert
        self::assertCount(1, $annotations);
        $ann = $annotations[0];
        self::assertSame(PdfAnnotationType::Text, $ann->type);
        self::assertEqualsWithDelta(10.0, $ann->x, 0.001);
        self::assertEqualsWithDelta(20.0, $ann->y, 0.001);
        self::assertEqualsWithDelta(100.0, $ann->width, 0.001);
        self::assertEqualsWithDelta(20.0, $ann->height, 0.001);
    }

    #[Test]
    public function getAllAnnotationsReturnsAnnotationsKeyedByPage(): void
    {
        // Arrange
        $document = self::buildPdfWithTextAnnotation();
        $extractor = new PdfAnnotationExtractor($document);

        // Act
        $all = $extractor->getAllAnnotations();

        // Assert
        self::assertArrayHasKey(0, $all);
        self::assertCount(1, $all[0]);
    }

    private static function buildPdfWithTextAnnotation(): PdfReadDocument
    {
        $header = "%PDF-1.4\n";
        $body = '';

        // obj 1: Catalog
        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        // obj 2: Pages
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        // obj 4: Text annotation dict
        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n<< /Type /Annot /Subtype /Text /Rect [10 20 110 40] /Contents (Hello) >>\nendobj\n";

        // obj 3: Page with /Annots referencing obj 4
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

        return new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4);
    }
}
