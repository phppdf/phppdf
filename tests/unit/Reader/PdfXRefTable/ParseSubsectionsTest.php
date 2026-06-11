<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfXRefTable;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfBoolean;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfNull;
use PhpPdf\Object\PdfReal;
use PhpPdf\Object\PdfString;
use PhpPdf\Reader\Exception\PdfReadException;
use PhpPdf\Reader\PdfLexer;
use PhpPdf\Reader\PdfObjectParser;
use PhpPdf\Reader\PdfToken;
use PhpPdf\Reader\PdfXRefTable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfXRefTable::class)]
#[CoversMethod(PdfXRefTable::class, 'parse')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfBoolean::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfNull::class)]
#[UsesClass(PdfReal::class)]
#[UsesClass(PdfReadException::class)]
#[UsesClass(PdfString::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfObjectParser::class)]
#[UsesClass(PdfToken::class)]
final class ParseSubsectionsTest extends TestCase
{
    #[Test]
    public function parsesMultipleSubsections(): void
    {
        // Arrange — xref with two subsections
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [] /Count 0 >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);

        $xref = "xref\n";
        $xref .= "1 1\n"; // first subsection: obj 1
        $xref .= sprintf("%010d 00000 n \n", $off1);
        $xref .= "2 1\n"; // second subsection: obj 2
        $xref .= sprintf("%010d 00000 n \n", $off2);
        $xref .= "trailer\n<< /Size 3 /Root 1 0 R >>\n";
        $xref .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $content = $header . $body . $xref;
        $lexer = PdfLexer::fromString($content);

        // Act
        [$entries] = (new PdfXRefTable($lexer))->parse($xrefOffset);

        // Assert
        self::assertArrayHasKey(1, $entries);
        self::assertArrayHasKey(2, $entries);
        self::assertSame($off1, $entries[1]['offset'] ?? null);
        self::assertSame($off2, $entries[2]['offset'] ?? null);
    }

    #[Test]
    public function throwsForInvalidTrailerValue(): void
    {
        // Arrange — trailer value is not a dictionary
        $content = "%PDF-1.4\nxref\n0 1\n0000000000 65535 f \ntrailer\n42\n";
        $lexer = PdfLexer::fromString($content);

        // Act / Assert
        $this->expectException(PdfReadException::class);
        (new PdfXRefTable($lexer))->parse(9);
    }
}
