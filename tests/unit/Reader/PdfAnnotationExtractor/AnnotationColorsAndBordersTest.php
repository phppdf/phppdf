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
final class AnnotationColorsAndBordersTest extends TestCase
{
    #[Test]
    public function extractsGrayscaleColor(): void
    {
        // Arrange — 1-element /C array = grayscale
        $document = self::buildPdfWithAnnotation("<< /Type /Annot /Subtype /Text /Rect [0 0 100 20] /C [0.5] >>");
        $extractor = new PdfAnnotationExtractor($document);

        // Act
        $annotations = $extractor->getAnnotationsForPage(0);

        // Assert — gray 0.5 → [0.5, 0.5, 0.5]
        self::assertCount(1, $annotations);
        $color = $annotations[0]->color;
        self::assertNotNull($color);
        self::assertEqualsWithDelta(0.5, $color[0], 0.001);
        self::assertEqualsWithDelta(0.5, $color[1], 0.001);
        self::assertEqualsWithDelta(0.5, $color[2], 0.001);
    }

    #[Test]
    public function extractsCmykColor(): void
    {
        // Arrange — 4-element /C array = CMYK
        $document = self::buildPdfWithAnnotation("<< /Type /Annot /Subtype /Text /Rect [0 0 100 20] /C [0 1 1 0] >>");
        $extractor = new PdfAnnotationExtractor($document);

        // Act
        $annotations = $extractor->getAnnotationsForPage(0);

        // Assert — CMYK(0,1,1,0) = red in RGB approximately
        self::assertCount(1, $annotations);
        self::assertNotNull($annotations[0]->color);
    }

    #[Test]
    public function extractsHighlightAnnotationWithQuadPoints(): void
    {
        // Arrange — Highlight annotation with /QuadPoints
        $document = self::buildPdfWithAnnotation(
            "<< /Type /Annot /Subtype /Highlight /Rect [10 20 110 40]"
            . " /QuadPoints [10 40 110 40 10 20 110 20] >>",
        );
        $extractor = new PdfAnnotationExtractor($document);

        // Act
        $annotations = $extractor->getAnnotationsForPage(0);

        // Assert
        self::assertCount(1, $annotations);
        $ann = $annotations[0];
        self::assertSame(PdfAnnotationType::Highlight, $ann->type);
        self::assertNotNull($ann->quadPoints);
        self::assertCount(8, $ann->quadPoints);
    }

    #[Test]
    public function quadPointsSkipsNonNumericItemsAndKeepsValidOnes(): void
    {
        // Arrange — /QuadPoints array contains a non-numeric item alongside numeric
        // ones (line 235: continue for the non-numeric item; line 242: returns the
        // remaining non-empty list of valid points)
        $document = self::buildPdfWithAnnotation(
            "<< /Type /Annot /Subtype /Highlight /Rect [10 20 110 40]"
            . " /QuadPoints [10 40 /BadName 40 10 20 110 20] >>",
        );
        $extractor = new PdfAnnotationExtractor($document);

        // Act
        $annotations = $extractor->getAnnotationsForPage(0);

        // Assert — non-numeric item is skipped, remaining 7 valid points are returned
        self::assertCount(1, $annotations);
        self::assertNotNull($annotations[0]->quadPoints);
        self::assertCount(7, $annotations[0]->quadPoints);
    }

    #[Test]
    public function quadPointsReturnsNullWhenAllItemsAreNonNumeric(): void
    {
        // Arrange — /QuadPoints array contains only non-numeric items, so the
        // resulting list is empty (line 242: returns null for an empty list)
        $document = self::buildPdfWithAnnotation(
            "<< /Type /Annot /Subtype /Highlight /Rect [10 20 110 40]"
            . " /QuadPoints [/Bad1 /Bad2] >>",
        );
        $extractor = new PdfAnnotationExtractor($document);

        // Act
        $annotations = $extractor->getAnnotationsForPage(0);

        // Assert
        self::assertCount(1, $annotations);
        self::assertNull($annotations[0]->quadPoints);
    }

    #[Test]
    public function extractsBorderWidthFromBsDict(): void
    {
        // Arrange — /BS << /W 2 >>
        $document = self::buildPdfWithAnnotation(
            "<< /Type /Annot /Subtype /Square /Rect [0 0 100 100] /BS << /W 2 >> >>",
        );
        $extractor = new PdfAnnotationExtractor($document);

        // Act
        $annotations = $extractor->getAnnotationsForPage(0);

        // Assert
        self::assertCount(1, $annotations);
        self::assertEqualsWithDelta(2.0, $annotations[0]->borderWidth, 0.001);
    }

    #[Test]
    public function extractsBorderWidthFromLegacyBorderArray(): void
    {
        // Arrange — /Border [0 0 3] (rx ry width)
        $document = self::buildPdfWithAnnotation(
            "<< /Type /Annot /Subtype /Square /Rect [0 0 100 100] /Border [0 0 3] >>",
        );
        $extractor = new PdfAnnotationExtractor($document);

        // Act
        $annotations = $extractor->getAnnotationsForPage(0);

        // Assert
        self::assertCount(1, $annotations);
        self::assertEqualsWithDelta(3.0, $annotations[0]->borderWidth, 0.001);
    }

    #[Test]
    public function extractsOpenStateTrue(): void
    {
        // Arrange — /Open true
        $document = self::buildPdfWithAnnotation("<< /Type /Annot /Subtype /Text /Rect [0 0 100 20] /Open true >>");
        $extractor = new PdfAnnotationExtractor($document);

        // Act
        $annotations = $extractor->getAnnotationsForPage(0);

        // Assert
        self::assertCount(1, $annotations);
        self::assertTrue($annotations[0]->open);
    }

    #[Test]
    public function extractsInteriorColorFromIcEntry(): void
    {
        // Arrange — /IC [1 0 0] = red fill
        $document = self::buildPdfWithAnnotation("<< /Type /Annot /Subtype /Circle /Rect [0 0 100 100] /IC [1 0 0] >>");
        $extractor = new PdfAnnotationExtractor($document);

        // Act
        $annotations = $extractor->getAnnotationsForPage(0);

        // Assert
        self::assertCount(1, $annotations);
        $ic = $annotations[0]->interiorColor;
        self::assertNotNull($ic);
        self::assertEqualsWithDelta(1.0, $ic[0], 0.001);
        self::assertEqualsWithDelta(0.0, $ic[1], 0.001);
    }

    #[Test]
    public function extractsTitleFromTEntry(): void
    {
        // Arrange — /T (Author)
        $document = self::buildPdfWithAnnotation("<< /Type /Annot /Subtype /Text /Rect [0 0 100 20] /T (Author) >>");
        $extractor = new PdfAnnotationExtractor($document);

        // Act
        $annotations = $extractor->getAnnotationsForPage(0);

        // Assert
        self::assertCount(1, $annotations);
        self::assertSame('Author', $annotations[0]->title);
    }

    private static function buildPdfWithAnnotation(string $annotObjContent): PdfReadDocument
    {
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n" . $annotObjContent . "\n";

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
