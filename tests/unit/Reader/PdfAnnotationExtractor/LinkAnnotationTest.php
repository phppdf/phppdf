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
final class LinkAnnotationTest extends TestCase
{
    #[Test]
    public function extractsLinkAnnotationWithUri(): void
    {
        // Arrange
        $document = self::buildPdfWithLinkAnnotation();
        $extractor = new PdfAnnotationExtractor($document);

        // Act
        $annotations = $extractor->getAnnotationsForPage(0);

        // Assert
        self::assertCount(1, $annotations);
        $ann = $annotations[0];
        self::assertSame(PdfAnnotationType::Link, $ann->type);
        self::assertSame('https://example.com', $ann->uri);
        self::assertTrue($ann->isUriLink());
    }

    private static function buildPdfWithLinkAnnotation(): PdfReadDocument
    {
        $header = "%PDF-1.4\n";
        $body = '';

        // obj 1: Catalog
        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        // obj 2: Pages
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        // obj 5: URI action
        $off5 = strlen($header) + strlen($body);
        $body .= "5 0 obj\n<< /S /URI /URI (https://example.com) >>\nendobj\n";

        // obj 4: Link annotation
        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n<< /Type /Annot /Subtype /Link /Rect [0 0 100 20] /A 5 0 R >>\nendobj\n";

        // obj 3: Page with /Annots referencing obj 4
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

        return new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4);
    }
}
