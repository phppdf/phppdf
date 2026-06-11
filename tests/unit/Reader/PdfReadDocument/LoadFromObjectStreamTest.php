<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfReadDocument;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfNull;
use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Object\PdfStream;
use PhpPdf\Object\PdfVersion;
use PhpPdf\Reader\PdfLexer;
use PhpPdf\Reader\PdfObjectParser;
use PhpPdf\Reader\PdfReadDocument;
use PhpPdf\Reader\PdfToken;
use PhpPdf\Reader\PdfXRefTable;
use PhpPdf\Serialization\PdfStreamSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfReadDocument::class)]
#[CoversMethod(PdfReadDocument::class, 'getObject')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfNull::class)]
#[UsesClass(PdfRawStreamData::class)]
#[UsesClass(PdfStream::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfObjectParser::class)]
#[UsesClass(PdfToken::class)]
#[UsesClass(PdfXRefTable::class)]
#[UsesClass(PdfStreamSerializer::class)]
final class LoadFromObjectStreamTest extends TestCase
{
    #[Test]
    public function loadsObjectFromObjectStream(): void
    {
        // Arrange — build a PDF 1.5 where objs 2 and 3 live in an ObjStm (obj 1).
        // ObjStm header: "2 0 3 20\n"  (obj2 at byte 0 from /First, obj3 at byte 20)
        // /First = 9 (length of "2 0 3 20\n")
        // Body at /First: "<< /Type /Catalog >><< /Type /Pages >>"  (20 + 18 chars)

        $objStmBody = "2 0 3 20\n<< /Type /Catalog >><< /Type /Pages >>";
        $compressed = gzcompress($objStmBody);
        self::assertNotFalse($compressed);

        $pdfContent = "%PDF-1.5\n";
        $off1 = strlen($pdfContent);
        $pdfContent .= "1 0 obj\n"
                     . "<< /Type /ObjStm /N 2 /First 9 /Length "
                     . strlen($compressed)
                     . " /Filter /FlateDecode >>\n"
                     . "stream\n"
                     . $compressed
                     . "\nendstream\nendobj\n";

        $lexer = PdfLexer::fromString($pdfContent);

        // xref: obj 1 normal (the ObjStm); obj 2 and 3 are inside the stream
        $xref = [
            1 => ['offset' => $off1, 'generation' => 0, 'type' => 'n'],
            2 => ['streamObj' => 1, 'index' => 0, 'type' => 's'],
            3 => ['streamObj' => 1, 'index' => 1, 'type' => 's'],
        ];

        $trailer = new PdfDictionary(['Root' => new PdfIndirectReference(2, 0), 'Size' => new PdfInteger(4)]);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_5);

        // Act — request obj 2 (lives in ObjStm at index 0)
        $obj2 = $document->getObject(new PdfIndirectReference(2, 0));

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj2);
    }

    #[Test]
    public function cachesParsedObjStmEntries(): void
    {
        // Arrange — same ObjStm setup
        $objStmBody = "2 0 3 20\n<< /Type /Catalog >><< /Type /Pages >>";
        $compressed = gzcompress($objStmBody);
        self::assertNotFalse($compressed);

        $pdfContent = "%PDF-1.5\n";
        $off1 = strlen($pdfContent);
        $pdfContent .= "1 0 obj\n"
                     . "<< /Type /ObjStm /N 2 /First 9 /Length "
                     . strlen($compressed)
                     . " /Filter /FlateDecode >>\n"
                     . "stream\n"
                     . $compressed
                     . "\nendstream\nendobj\n";

        $lexer = PdfLexer::fromString($pdfContent);
        $xref = [
            1 => ['offset' => $off1, 'generation' => 0, 'type' => 'n'],
            2 => ['streamObj' => 1, 'index' => 0, 'type' => 's'],
            3 => ['streamObj' => 1, 'index' => 1, 'type' => 's'],
        ];

        $trailer = new PdfDictionary(['Root' => new PdfIndirectReference(2, 0), 'Size' => new PdfInteger(4)]);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_5);

        // Act — load obj 2 twice; both calls should return the same instance (cache)
        $first = $document->getObject(new PdfIndirectReference(2, 0));
        $second = $document->getObject(new PdfIndirectReference(2, 0));

        // Also load the second object in the same ObjStm
        $third = $document->getObject(new PdfIndirectReference(3, 0));

        // Assert — cached result
        self::assertSame($first, $second);
        self::assertInstanceOf(PdfDictionary::class, $third);
    }

    #[Test]
    public function defaultsNAndFirstToZeroWhenNotIntegers(): void
    {
        // Arrange — ObjStm dictionary is missing /N and /First (defaults to 0, lines 218/221)
        $objStmBody = '';
        $compressed = gzcompress($objStmBody);
        self::assertNotFalse($compressed);

        $pdfContent = "%PDF-1.5\n";
        $off1 = strlen($pdfContent);
        $pdfContent .= "1 0 obj\n"
                     . "<< /Type /ObjStm /Length "
                     . strlen($compressed)
                     . " /Filter /FlateDecode >>\n"
                     . "stream\n"
                     . $compressed
                     . "\nendstream\nendobj\n";

        $lexer = PdfLexer::fromString($pdfContent);

        $xref = [
            1 => ['offset' => $off1, 'generation' => 0, 'type' => 'n'],
            2 => ['streamObj' => 1, 'index' => 0, 'type' => 's'],
        ];

        $trailer = new PdfDictionary(['Root' => new PdfIndirectReference(2, 0), 'Size' => new PdfInteger(3)]);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_5);

        // Act — N and First are absent, so both default to 0; loop over N=0 produces no entries
        $obj = $document->getObject(new PdfIndirectReference(2, 0));

        // Assert — no entries parsed, returns PdfNull
        self::assertInstanceOf(PdfNull::class, $obj);
    }
}
