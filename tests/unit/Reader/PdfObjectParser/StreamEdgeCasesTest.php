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
#[UsesClass(PdfReadException::class)]
#[UsesClass(PdfReal::class)]
#[UsesClass(PdfStream::class)]
#[UsesClass(PdfString::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfToken::class)]
final class StreamEdgeCasesTest extends TestCase
{
    #[Test]
    public function parsesStreamWithNoLengthEntry(): void
    {
        // Arrange — no /Length in dict, getStreamLength returns 0 → fallback scan
        $content = "<< >>\nstream\nhello\nendstream";
        $parser = new PdfObjectParser(PdfLexer::fromString($content));

        // Act
        $obj = $parser->parseObject();

        // Assert
        self::assertInstanceOf(PdfStream::class, $obj);
    }

    #[Test]
    public function parsesStreamWithLengthZeroInteger(): void
    {
        // Arrange — /Length 0 explicitly (also triggers fallback scan)
        $content = "<< /Length 0 >>\nstream\nhello\nendstream";
        $parser = new PdfObjectParser(PdfLexer::fromString($content));

        // Act
        $obj = $parser->parseObject();

        // Assert
        self::assertInstanceOf(PdfStream::class, $obj);
    }

    #[Test]
    public function throwsOnUnterminatedDict(): void
    {
        // Arrange — dict starts but EOF before closing >>
        $parser = new PdfObjectParser(PdfLexer::fromString('<< /Key '));

        // Act / Assert
        $this->expectException(PdfReadException::class);
        $parser->parseObject();
    }

    #[Test]
    public function fallbackScanStopsAtEofWithoutEndstream(): void
    {
        // Arrange — /Length 0 forces fallback scan; EOF arrives before 'endstream'
        $content = "<< /Length 0 >>\nstream\nhello world";
        $parser = new PdfObjectParser(PdfLexer::fromString($content));

        // Act
        $obj = $parser->parseObject();

        // Assert — EOF branch reached; stream created with whatever bytes were scanned
        self::assertInstanceOf(PdfStream::class, $obj);
    }

    #[Test]
    public function parsesAscii85WithPartialFinalGroup(): void
    {
        // Arrange — partial group at end of ASCII85 data
        // "Hello" in ASCII85 is "87cURD~>"
        // A partial group (less than 5 chars before ~>) tests the padding branch
        // Let's use just "87" (2 chars) which is a partial group for "He"
        $ascii85 = "87~>"; // partial group for 2 bytes
        $content = "<< /Length " . strlen($ascii85) . " /Filter /ASCII85Decode >>\nstream\n"
                 . $ascii85 . "\nendstream";

        // Act
        $obj = (new PdfObjectParser(PdfLexer::fromString($content)))->parseObject();

        // Assert
        self::assertInstanceOf(PdfStream::class, $obj);
    }
}
