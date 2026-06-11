<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfReadPage;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfNull;
use PhpPdf\Object\PdfVersion;
use PhpPdf\Reader\MinimalPdfFixture;
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

#[CoversClass(PdfReadPage::class)]
#[CoversMethod(PdfReadPage::class, 'getMediaBox')]
#[UsesClass(PdfVersion::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfNull::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfObjectParser::class)]
#[UsesClass(PdfReadDocument::class)]
#[UsesClass(PdfToken::class)]
#[UsesClass(PdfXRefTable::class)]
final class GetMediaBoxTest extends TestCase
{
    use MinimalPdfFixture;

    #[Test]
    public function returnsMediaBoxFromPageDictionary(): void
    {
        // Arrange — minimal document page has /MediaBox [0 0 595 842]
        $document = self::createMinimalDocument();
        $page = $document->getPage(0);

        // Act
        $box = $page->getMediaBox();

        // Assert
        self::assertCount(4, $box);
        self::assertEqualsWithDelta(0.0, $box[0], 0.001);
        self::assertEqualsWithDelta(0.0, $box[1], 0.001);
        self::assertEqualsWithDelta(595.0, $box[2], 0.001);
        self::assertEqualsWithDelta(842.0, $box[3], 0.001);
    }

    #[Test]
    public function mediaBoxItemWithNonNumericValueDefaultsToZero(): void
    {
        // Arrange — /MediaBox has a PdfName item instead of numeric (line 59: default => 0.0)
        $document = self::createMinimalDocument();
        $pageDict = new PdfDictionary([
            'MediaBox' => new PdfArray([
                new PdfName('BadValue'), // non-numeric → default => 0.0
                new PdfInteger(0),
                new PdfInteger(595),
                new PdfInteger(842),
            ]),
        ]);
        $page = new PdfReadPage($pageDict, $document);

        // Act — first item is PdfName, not numeric → default => 0.0 (line 59)
        $box = $page->getMediaBox();

        // Assert — first value defaults to 0.0 (from match default branch)
        self::assertEqualsWithDelta(0.0, $box[0], 0.001);
        self::assertEqualsWithDelta(0.0, $box[1], 0.001);
        self::assertEqualsWithDelta(595.0, $box[2], 0.001);
        self::assertEqualsWithDelta(842.0, $box[3], 0.001);
    }

    #[Test]
    public function returnsDefaultWhenMediaBoxMissing(): void
    {
        // Arrange — page without /MediaBox
        $document = self::createMinimalDocument();
        $page = new PdfReadPage(new PdfDictionary([]), $document);

        // Act
        $box = $page->getMediaBox();

        // Assert
        self::assertEqualsWithDelta(595.0, $box[2], 0.001);
        self::assertEqualsWithDelta(842.0, $box[3], 0.001);
    }

    #[Test]
    public function resolvesMediaBoxWhenItIsAnIndirectReference(): void
    {
        // Arrange — /MediaBox is an indirect reference to a PdfArray (line 50)
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n[0 0 595 842]\nendobj\n"; // MediaBox array as indirect obj
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox 4 0 R >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 5\n0000000000 65535 f \n";
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
        $page = $document->getPage(0);

        // Act — /MediaBox is PdfIndirectReference → resolve at line 50
        $box = $page->getMediaBox();

        // Assert
        self::assertEqualsWithDelta(0.0, $box[0], 0.001);
        self::assertEqualsWithDelta(0.0, $box[1], 0.001);
        self::assertEqualsWithDelta(595.0, $box[2], 0.001);
        self::assertEqualsWithDelta(842.0, $box[3], 0.001);
    }

    #[Test]
    public function returnsDefaultWhenResolvedMediaBoxIsNotArray(): void
    {
        // Arrange — /MediaBox is an indirect reference to an integer (not PdfArray) (line 59)
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n42\nendobj\n"; // integer — bad MediaBox ref
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox 4 0 R >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 5\n0000000000 65535 f \n";
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
        $page = $document->getPage(0);

        // Act — /MediaBox resolves to integer → not PdfArray → fallback [0,0,595,842] (line 59)
        $box = $page->getMediaBox();

        // Assert — returns fallback A4 dimensions
        self::assertEqualsWithDelta(0.0, $box[0], 0.001);
        self::assertEqualsWithDelta(0.0, $box[1], 0.001);
        self::assertEqualsWithDelta(595.0, $box[2], 0.001);
        self::assertEqualsWithDelta(842.0, $box[3], 0.001);
    }
}
