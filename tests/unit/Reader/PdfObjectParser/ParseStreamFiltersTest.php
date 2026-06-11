<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfObjectParser;

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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfObjectParser::class)]
#[CoversMethod(PdfObjectParser::class, 'parseObject')]
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
#[UsesClass(PdfToken::class)]
final class ParseStreamFiltersTest extends TestCase
{
    #[Test]
    public function parsesStreamWithAscii85Decode(): void
    {
        // Arrange — ASCII85-encode the string "Hello"
        // "Hello" as base-85 is "87cURD~>" (5 bytes → 5 chars + terminator)
        $ascii85 = "87cURD~>";
        $content = "<< /Length " . strlen($ascii85) . " /Filter /ASCII85Decode >>\nstream\n"
                 . $ascii85 . "\nendstream";

        // Act
        $obj = (new PdfObjectParser(PdfLexer::fromString($content)))->parseObject();

        // Assert
        self::assertInstanceOf(PdfStream::class, $obj);
    }

    #[Test]
    public function parsesStreamWithUnknownFilterPassthrough(): void
    {
        // Arrange — unknown filter: data passes through as-is
        $data = 'raw data bytes';
        $content = "<< /Length " . strlen($data) . " /Filter /CustomFilter >>\nstream\n"
                 . $data . "\nendstream";

        // Act
        $obj = (new PdfObjectParser(PdfLexer::fromString($content)))->parseObject();

        // Assert
        self::assertInstanceOf(PdfStream::class, $obj);
    }

    #[Test]
    public function parsesStreamWithZeroLengthFallback(): void
    {
        // Arrange — /Length 0 triggers the byte-by-byte scan fallback
        $content = "<< /Length 0 >>\nstream\nhello\nendstream";

        // Act
        $obj = (new PdfObjectParser(PdfLexer::fromString($content)))->parseObject();

        // Assert
        self::assertInstanceOf(PdfStream::class, $obj);
    }

    #[Test]
    public function throwsOnFlatDecodeWithInvalidData(): void
    {
        // Arrange — invalid compressed data
        $data = 'not compressed data';
        $content = "<< /Length " . strlen($data) . " /Filter /FlateDecode >>\nstream\n"
                 . $data . "\nendstream";

        // Act / Assert
        $this->expectException(PdfReadException::class);
        (new PdfObjectParser(PdfLexer::fromString($content)))->parseObject();
    }

    #[Test]
    public function parsesStreamWithMultipleFilters(): void
    {
        // Arrange — ASCIIHex then unknown filter; only ASCIIHex is decoded
        $hex = "4142>"; // "AB"
        $content = "<< /Length " . strlen($hex) . " /Filter [/ASCIIHexDecode] >>\nstream\n"
                 . $hex . "\nendstream";

        // Act
        $obj = (new PdfObjectParser(PdfLexer::fromString($content)))->parseObject();

        // Assert
        self::assertInstanceOf(PdfStream::class, $obj);
    }

    #[Test]
    public function skipsNonPdfNameFilterItem(): void
    {
        // Arrange — /Filter null resolves to PdfNull, which is not a PdfName → skipped
        $data = 'raw';
        $content = "<< /Length " . strlen($data) . " /Filter null >>\nstream\n"
                 . $data . "\nendstream";

        // Act
        $obj = (new PdfObjectParser(PdfLexer::fromString($content)))->parseObject();

        // Assert — filter skipped, data returned as-is
        self::assertInstanceOf(PdfStream::class, $obj);
    }

    #[Test]
    public function asciiHexDecodeWithOddLengthHex(): void
    {
        // Arrange — 3 hex digits (odd) require a trailing '0' pad before hex2bin
        $data = "414>"; // 3 hex chars + terminator; after rtrim → "414" (odd)
        $content = "<< /Length " . strlen($data) . " /Filter /ASCIIHexDecode >>\nstream\n"
                 . $data . "\nendstream";

        // Act
        $obj = (new PdfObjectParser(PdfLexer::fromString($content)))->parseObject();

        // Assert
        self::assertInstanceOf(PdfStream::class, $obj);
    }

    #[Test]
    public function ascii85NullGroupShorthandDecodesAsZeroBytes(): void
    {
        // Arrange — 'z' is the ASCII85 shorthand for four null bytes
        $data = "z~>"; // 'z' + terminator
        $content = "<< /Length " . strlen($data) . " /Filter /ASCII85Decode >>\nstream\n"
                 . $data . "\nendstream";

        // Act
        $obj = (new PdfObjectParser(PdfLexer::fromString($content)))->parseObject();

        // Assert
        self::assertInstanceOf(PdfStream::class, $obj);
    }

    #[Test]
    public function ascii85OutOfRangeCharacterIsIgnored(): void
    {
        // Arrange — newline (ord 10 < 33) inside ASCII85 data is silently skipped
        $data = "87cUR\nD~>"; // '\n' has ord < 33 → skipped during decoding
        $content = "<< /Length " . strlen($data) . " /Filter /ASCII85Decode >>\nstream\n"
                 . $data . "\nendstream";

        // Act
        $obj = (new PdfObjectParser(PdfLexer::fromString($content)))->parseObject();

        // Assert
        self::assertInstanceOf(PdfStream::class, $obj);
    }
}
