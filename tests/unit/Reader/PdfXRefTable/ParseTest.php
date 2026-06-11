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
#[UsesClass(PdfReadException::class)]
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
#[UsesClass(PdfToken::class)]
final class ParseTest extends TestCase
{
    #[Test]
    public function parsesXRefTableAndTrailer(): void
    {
        // Arrange
        [$content, $xrefOffset, $offsets] = $this->buildMinimalPdf();
        $lexer = PdfLexer::fromString($content);

        // Act
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($xrefOffset);

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $trailer);
        self::assertArrayHasKey(1, $xref);
        self::assertSame($offsets[1], $xref[1]['offset'] ?? null);
        self::assertSame('n', $xref[1]['type']);
    }

    #[Test]
    public function returnsEmptyXRefForNoInUseEntries(): void
    {
        // Arrange — xref with only the free-list entry
        $content = "%PDF-1.4\nxref\n0 1\n0000000000 65535 f \n"
                 . "trailer\n<< /Size 1 /Root 1 0 R >>\nstartxref\n9\n%%EOF\n";
        $lexer = PdfLexer::fromString($content);

        // Act
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse(9);

        // Assert — no normal entries
        self::assertEmpty($xref);
        self::assertInstanceOf(PdfDictionary::class, $trailer);
    }

    #[Test]
    public function throwsForInvalidXRefKeyword(): void
    {
        // Arrange — 'badxref' instead of 'xref'
        $content = "badxref\n0 1\n0000000000 65535 f \ntrailer\n<< >>\n";
        $lexer = PdfLexer::fromString($content);

        // Act / Assert
        $this->expectException(PdfReadException::class);
        (new PdfXRefTable($lexer))->parse(0);
    }

    /** @return array{string, int, array<int,int>} */
    private function buildMinimalPdf(): array
    {
        $header = "%PDF-1.4\n";
        $body = '';

        $objs = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [] /Count 0 >>\nendobj\n",
        ];

        $offsets = [];

        foreach ($objs as $i => $obj) {
            $offsets[$i + 1] = strlen($header) + strlen($body);
            $body .= $obj;
        }

        $xrefOffset = strlen($header) + strlen($body);

        $xref = "xref\n0 3\n";
        $xref .= "0000000000 65535 f \n";

        for ($i = 1; $i <= 2; $i++) {
            $xref .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $xref .= "trailer\n<< /Size 3 /Root 1 0 R >>\n";
        $xref .= "startxref\n{$xrefOffset}\n%%EOF\n";

        return [$header . $body . $xref, $xrefOffset, $offsets];
    }
}
