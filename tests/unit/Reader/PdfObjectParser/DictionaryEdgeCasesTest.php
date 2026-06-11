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
final class DictionaryEdgeCasesTest extends TestCase
{
    #[Test]
    public function throwsWhenDictKeyIsNotName(): void
    {
        // Arrange — dict with integer key instead of name
        $parser = new PdfObjectParser(PdfLexer::fromString('<< 42 /Value >>'));

        // Act / Assert
        $this->expectException(PdfReadException::class);
        $parser->parseObject();
    }

    #[Test]
    public function streamLengthViaIndirectReferenceTriggersZeroFallback(): void
    {
        // Arrange — /Length is an indirect reference (can't resolve here, returns 0)
        // With length=0, readStreamContent falls back to byte-by-byte scan
        $content = "<< /Length 99 0 R >>\nstream\nhello\nendstream";
        $parser = new PdfObjectParser(PdfLexer::fromString($content));

        // Act — the indirect /Length resolves to 0, so fallback scan is used
        $obj = $parser->parseObject();

        // Assert
        self::assertInstanceOf(PdfStream::class, $obj);
    }

    #[Test]
    public function parsesIntegerNotFollowedByIndirectRef(): void
    {
        // Arrange — two integers but third token is not 'R' → returns PdfInteger
        $parser = new PdfObjectParser(PdfLexer::fromString('5 0 obj'));

        // Act
        $obj = $parser->parseObject();

        // Assert — stops at integer 5, second token is 0 but third is 'obj' not 'R'
        self::assertInstanceOf(PdfInteger::class, $obj);
        self::assertSame(5, $obj->getValue());
    }

    #[Test]
    public function throwsOnEofInsideUnclosedDictionary(): void
    {
        // Arrange — dict with a complete key/value pair but no closing >>
        $parser = new PdfObjectParser(PdfLexer::fromString('<< /Key /Value'));

        // Act / Assert
        $this->expectException(PdfReadException::class);
        $parser->parseObject();
    }
}
