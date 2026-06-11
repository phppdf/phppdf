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
use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Object\PdfReal;
use PhpPdf\Object\PdfStream;
use PhpPdf\Object\PdfString;
use PhpPdf\Reader\Exception\PdfReadException;
use PhpPdf\Reader\PdfLexer;
use PhpPdf\Reader\PdfObjectParser;
use PhpPdf\Reader\PdfToken;
use PhpPdf\Reader\PdfXRefTable;
use PhpPdf\Serialization\PdfStreamSerializer;
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
#[UsesClass(PdfRawStreamData::class)]
#[UsesClass(PdfReal::class)]
#[UsesClass(PdfReadException::class)]
#[UsesClass(PdfStream::class)]
#[UsesClass(PdfString::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfObjectParser::class)]
#[UsesClass(PdfToken::class)]
#[UsesClass(PdfStreamSerializer::class)]
final class ParseXRefStreamTest extends TestCase
{
    #[Test]
    public function parsesXRefStream(): void
    {
        // Arrange
        [$content, $xrefOffset, $off1] = self::buildXRefStreamPdf();
        $lexer = PdfLexer::fromString($content);

        // Act
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($xrefOffset);

        // Assert — obj 1 should be in the xref with type 'n'
        self::assertArrayHasKey(1, $xref);
        $entry = $xref[1];
        self::assertSame('n', $entry['type']);
        self::assertArrayHasKey('offset', $entry);
        self::assertSame($off1, $entry['offset'] ?? null);
        self::assertInstanceOf(PdfDictionary::class, $trailer);
    }

    #[Test]
    public function parsesXRefStreamWithIndexEntry(): void
    {
        // Arrange — xref stream with /Index [1 1] (only obj 1)
        $header = "%PDF-1.5\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog >>\nendobj\n";

        // Only entry for obj 1 (type=1, at $off1, gen=0)
        $entry1 = pack('C', 1) . pack('N', $off1) . pack('n', 0);
        $compressed = gzcompress($entry1);
        self::assertNotFalse($compressed);

        $xrefOffset = strlen($header) + strlen($body);
        $body .= "2 0 obj\n"
               . "<< /Type /XRef /Size 2 /W [1 4 2] /Index [1 1] /Root 1 0 R /Length "
               . strlen($compressed)
               . " /Filter /FlateDecode >>\n"
               . "stream\n" . $compressed . "\nendstream\nendobj\n";

        $content = $header . $body;
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);

        // Act
        [$xref] = (new PdfXRefTable($lexer))->parse($xrefOffset);

        // Assert — obj 1 is present
        self::assertArrayHasKey(1, $xref);
        $entry = $xref[1];
        self::assertArrayHasKey('offset', $entry);
        self::assertSame($off1, $entry['offset'] ?? null);
    }

    #[Test]
    public function parsesXRefStreamWithObjStmEntry(): void
    {
        // Arrange — xref stream with a type-2 entry for obj 2 (stored in ObjStm obj 1).
        // Use /Index [0 3] to explicitly cover 3 objects starting at 0.
        $header = "%PDF-1.5\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /ObjStm /N 1 /First 3 >>\nendobj\n";

        // W=[1,4,2]: 7 bytes per entry; 3 entries for obj 0, 1, 2
        $entry0 = pack('C', 0) . pack('N', 0) . pack('n', 65535); // obj 0: free
        $entry1 = pack('C', 1) . pack('N', $off1) . pack('n', 0); // obj 1: normal
        $entry2 = pack('C', 2) . pack('N', 1) . pack('n', 0); // obj 2: in ObjStm(1), idx 0
        $compressed = gzcompress($entry0 . $entry1 . $entry2);
        self::assertNotFalse($compressed);

        $xrefOffset = strlen($header) + strlen($body);
        $body .= "3 0 obj\n"
               . "<< /Type /XRef /Size 4 /W [1 4 2] /Root 2 0 R /Length "
               . strlen($compressed)
               . " /Filter /FlateDecode >>\n"
               . "stream\n" . $compressed . "\nendstream\nendobj\n";

        $content = $header . $body;
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);

        // Act
        [$xref] = (new PdfXRefTable($lexer))->parse($xrefOffset);

        // Assert — obj 2 is a type 's' entry (in ObjStm)
        self::assertArrayHasKey(2, $xref);
        $entry = $xref[2];
        self::assertSame('s', $entry['type']);
        self::assertArrayHasKey('streamObj', $entry);
        self::assertArrayHasKey('index', $entry);
        self::assertSame(1, $entry['streamObj'] ?? null);
        self::assertSame(0, $entry['index'] ?? null);
    }

    /**
     * Builds a minimal PDF 1.5 with an xref stream.
     * W = [1 4 2]: 1 byte type, 4 bytes offset, 2 bytes generation — 7 bytes per entry.
     * Two objects:
     *   obj 0 (free): type=0, next=0, gen=65535
     *   obj 1 (Catalog at offset $off1): type=1, offset=off1, gen=0
     *
     * @return array{string, int, int}
     */
    private static function buildXRefStreamPdf(): array
    {
        $header = "%PDF-1.5\n";
        $body = '';

        // obj 1: Catalog (simple, no Pages needed for this test)
        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog >>\nendobj\n";

        // Build xref stream entries (W=[1,4,2], 7 bytes each, 2 entries)
        $entry0 = pack('C', 0) . pack('N', 0) . pack('n', 65535); // free
        $entry1 = pack('C', 1) . pack('N', $off1) . pack('n', 0); // obj 1 normal
        $rawEntries = $entry0 . $entry1;
        $compressed = gzcompress($rawEntries);
        self::assertNotFalse($compressed);

        // xref stream object is obj 2 (not in xref itself, that's fine for the parser)
        $xrefOffset = strlen($header) + strlen($body);
        $body .= "2 0 obj\n"
               . "<< /Type /XRef /Size 3 /W [1 4 2] /Root 1 0 R /Length "
               . strlen($compressed)
               . " /Filter /FlateDecode >>\n"
               . "stream\n"
               . $compressed
               . "\nendstream\nendobj\n";

        $content = $header . $body;
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        return [$content, $xrefOffset, $off1];
    }
}
