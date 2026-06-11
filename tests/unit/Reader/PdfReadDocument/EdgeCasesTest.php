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
use PhpPdf\Object\PdfString;
use PhpPdf\Object\PdfVersion;
use PhpPdf\Reader\Exception\PdfReadException;
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
use RuntimeException;

#[CoversClass(PdfReadDocument::class)]
#[CoversMethod(PdfReadDocument::class, 'getObject')]
#[CoversMethod(PdfReadDocument::class, 'getCatalog')]
#[CoversMethod(PdfReadDocument::class, 'getInfo')]
#[CoversMethod(PdfReadDocument::class, 'getPageCount')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfNull::class)]
#[UsesClass(PdfRawStreamData::class)]
#[UsesClass(PdfStream::class)]
#[UsesClass(PdfString::class)]
#[UsesClass(PdfReadException::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfObjectParser::class)]
#[UsesClass(PdfToken::class)]
#[UsesClass(PdfXRefTable::class)]
#[UsesClass(PdfStreamSerializer::class)]
final class EdgeCasesTest extends TestCase
{
    #[Test]
    public function getCatalogThrowsWhenRootNotIndirectRef(): void
    {
        // Arrange — trailer without /Root (or /Root is not an indirect ref)
        $lexer = PdfLexer::fromString('');
        $trailer = new PdfDictionary([]);
        $document = new PdfReadDocument($lexer, [], $trailer, PdfVersion::PDF_1_4);

        // Act / Assert
        $this->expectException(RuntimeException::class);
        $document->getCatalog();
    }

    #[Test]
    public function getInfoReturnsNullWhenInfoNotIndirectRef(): void
    {
        // Arrange — trailer /Info is an integer (not indirect ref)
        $lexer = PdfLexer::fromString('');
        $trailer = new PdfDictionary(['Info' => new PdfInteger(5)]);
        $document = new PdfReadDocument($lexer, [], $trailer, PdfVersion::PDF_1_4);

        // Act
        $info = $document->getInfo();

        // Assert
        self::assertNull($info);
    }

    #[Test]
    public function getPageCountReturnsZeroWhenNoPagesEntry(): void
    {
        // Arrange — Catalog exists but has no /Pages entry
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 2\n";
        $content .= "0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= "trailer\n<< /Size 2 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);

        // Act
        $count = $document->getPageCount();

        // Assert
        self::assertSame(0, $count);
    }

    #[Test]
    public function xrefEntryWithNonNormalTypeReturnsNull(): void
    {
        // Arrange — xref entry for obj 2 has unknown type
        $lexer = PdfLexer::fromString('');
        $xref = [2 => ['offset' => 0, 'generation' => 0, 'type' => 'x']];
        $trailer = new PdfDictionary([]);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4);

        // Act
        $obj = $document->getObject(new PdfIndirectReference(2, 0));

        // Assert — unknown type returns PdfNull
        self::assertInstanceOf(PdfNull::class, $obj);
    }

    #[Test]
    public function getCatalogThrowsWhenRootObjectIsNotDictionary(): void
    {
        // Arrange — /Root 1 0 R where obj 1 is an integer, not a dict (line 109)
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n42\nendobj\n"; // integer, not a dict

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 2\n0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= "trailer\n<< /Size 2 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);

        // Act / Assert — obj 1 is PdfInteger, not PdfDictionary → RuntimeException (line 109)
        $this->expectException(RuntimeException::class);
        $document->getCatalog();
    }

    #[Test]
    public function getInfoReturnsNullWhenInfoObjectIsNotDictionary(): void
    {
        // Arrange — /Info 2 0 R where obj 2 is an integer (lines 123-124)
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n42\nendobj\n"; // integer, not a dict

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 3\n0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= "trailer\n<< /Size 3 /Root 1 0 R /Info 2 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);

        // Act — /Info resolves to integer → null (lines 123-124)
        $info = $document->getInfo();

        // Assert
        self::assertNull($info);
    }

    #[Test]
    public function loadObjectReturnsNullWhenParseIndirectObjectReturnsNull(): void
    {
        // Arrange — xref entry for obj 1 at offset beyond EOF (line 188)
        $lexer = PdfLexer::fromString('%PDF-1.4\n');
        $xref = [1 => ['offset' => 99999, 'generation' => 0, 'type' => 'n']];
        $trailer = new PdfDictionary([]);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4);

        // Act — seekTo(99999) then parseIndirectObject returns null (EOF)
        $obj = $document->getObject(new PdfIndirectReference(1, 0));

        // Assert — returns PdfNull (line 188)
        self::assertInstanceOf(PdfNull::class, $obj);
    }

    #[Test]
    public function loadFromObjectStreamReturnsNullWhenStreamObjectIsNotStream(): void
    {
        // Arrange — ObjStm obj 1 exists in xref but resolves to an integer (line 205)
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n42\nendobj\n"; // integer, not a stream

        $xref = [
            1 => ['offset' => $off1, 'generation' => 0, 'type' => 'n'],
            2 => ['streamObj' => 1, 'index' => 0, 'type' => 's'],
        ];

        $lexer = PdfLexer::fromString($header . $body);
        $trailer = new PdfDictionary(['Root' => new PdfIndirectReference(2, 0)]);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4);

        // Act — obj 2 is in ObjStm 1; obj 1 is not a stream → returns PdfNull (line 205)
        $obj = $document->getObject(new PdfIndirectReference(2, 0));

        // Assert
        self::assertInstanceOf(PdfNull::class, $obj);
    }

    #[Test]
    public function objStmHeaderBreaksOnNonIntegerTokens(): void
    {
        // Arrange — ObjStm whose header contains names instead of integer pairs (line 223)
        // "N=1, First=10" but header content is "/BadToken 0\n..." (non-integer)
        $objStmBody = "/BadToken 0\n<< /Type /Catalog >>";
        $compressed = gzcompress($objStmBody);
        self::assertNotFalse($compressed);
        $first = 11; // byte offset to body (length of "/BadToken 0\n")

        $pdfContent = "%PDF-1.5\n";
        $off1 = strlen($pdfContent);
        $pdfContent .= "1 0 obj\n"
                     . "<< /Type /ObjStm /N 1 /First {$first} /Length "
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

        $trailer = new PdfDictionary(['Root' => new PdfIndirectReference(2, 0)]);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_5);

        // Act — header contains non-integer tokens → break at line 223 → byteOffsets is empty
        // ObjStm cache has no entry for index 0 → returns PdfNull
        $obj = $document->getObject(new PdfIndirectReference(2, 0));

        // Assert
        self::assertInstanceOf(PdfNull::class, $obj);
    }

    #[Test]
    public function objStmParseObjectExceptionResultsInPdfNull(): void
    {
        // Arrange — ObjStm body at the byteOffset contains invalid PDF (lines 243-244)
        // Header claims obj 2 is at byte offset 0, but the body has a keyword that
        // throws when parsed as an object.
        $objStmHeader = "2 0\n"; // 4 bytes: obj 2 at byte 0 from /First
        $objStmBody = "badkeyword"; // parseObject throws for unknown keyword
        $objStmContent = $objStmHeader . $objStmBody;
        $compressed = gzcompress($objStmContent);
        self::assertNotFalse($compressed);
        $first = strlen($objStmHeader); // = 4

        $pdfContent = "%PDF-1.5\n";
        $off1 = strlen($pdfContent);
        $pdfContent .= "1 0 obj\n"
                     . "<< /Type /ObjStm /N 1 /First {$first} /Length "
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

        $trailer = new PdfDictionary(['Root' => new PdfIndirectReference(2, 0)]);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_5);

        // Act — parseObject throws for 'badkeyword' → catch block stores PdfNull (lines 243-244)
        $obj = $document->getObject(new PdfIndirectReference(2, 0));

        // Assert — catch sets cache entry to PdfNull
        self::assertInstanceOf(PdfNull::class, $obj);
    }

    #[Test]
    public function collectPagesReturnsEmptyWhenPagesResolveToNonDictionary(): void
    {
        // Arrange — /Pages ref points to an integer object (line 263)
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n42\nendobj\n"; // integer, not a Pages dict

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 3\n0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= "trailer\n<< /Size 3 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);

        // Act — /Pages resolves to integer → collectPages returns [] (line 263)
        $count = $document->getPageCount();

        // Assert
        self::assertSame(0, $count);
    }

    #[Test]
    public function traversePageTreeReturnsEarlyWhenNoKidsEntry(): void
    {
        // Arrange — Pages node has no /Kids entry (line 283)
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        // Pages node with no /Kids (line 283: getDictValue returns null → return)
        $body .= "2 0 obj\n<< /Type /Pages /Count 0 >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 3\n0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= "trailer\n<< /Size 3 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);

        // Act — /Kids missing → traversePageTree returns early (line 283) → count = 0
        $count = $document->getPageCount();

        // Assert
        self::assertSame(0, $count);
    }

    #[Test]
    public function traversePageTreeReturnsEarlyWhenKidsResolvesToNonArray(): void
    {
        // Arrange — Pages node /Kids resolves to an integer (line 288)
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n99\nendobj\n"; // integer — bad /Kids ref
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids 3 0 R /Count 0 >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 4\n0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= "trailer\n<< /Size 4 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);
        $document = new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);

        // Act — /Kids resolves to integer → not PdfArray → return (line 288) → count = 0
        $count = $document->getPageCount();

        // Assert
        self::assertSame(0, $count);
    }

    #[Test]
    public function traversePageTreeSkipsKidThatResolvesToNonDictionary(): void
    {
        // Arrange — /Kids array contains an item that resolves to a non-dictionary (line 314: continue)
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n99\nendobj\n"; // integer — not a page dict
        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R 4 0 R] /Count 1 >>\nendobj\n";

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

        // Act — first kid resolves to PdfInteger and is skipped (line 314), second is a real page
        $count = $document->getPageCount();

        // Assert
        self::assertSame(1, $count);
    }
}
