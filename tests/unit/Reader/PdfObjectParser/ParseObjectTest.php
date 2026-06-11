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
#[UsesClass(PdfReadException::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfBoolean::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfNull::class)]
#[UsesClass(PdfRawStreamData::class)]
#[UsesClass(PdfReal::class)]
#[UsesClass(PdfStream::class)]
#[UsesClass(PdfString::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfToken::class)]
final class ParseObjectTest extends TestCase
{
    #[Test]
    public function parsesInteger(): void
    {
        // Arrange / Act
        $obj = $this->parserFor('42')->parseObject();

        // Assert
        self::assertInstanceOf(PdfInteger::class, $obj);
        self::assertSame(42, $obj->getValue());
    }

    #[Test]
    public function parsesReal(): void
    {
        // Arrange / Act
        $obj = $this->parserFor('3.14')->parseObject();

        // Assert
        self::assertInstanceOf(PdfReal::class, $obj);
        self::assertEqualsWithDelta(3.14, $obj->getValue(), 0.001);
    }

    #[Test]
    public function parsesLiteralString(): void
    {
        // Arrange / Act
        $obj = $this->parserFor('(hello)')->parseObject();

        // Assert
        self::assertInstanceOf(PdfString::class, $obj);
        self::assertSame('hello', $obj->getValue());
    }

    #[Test]
    public function parsesHexString(): void
    {
        // Arrange / Act — <48656C6C6F> = "Hello"
        $obj = $this->parserFor('<48656C6C6F>')->parseObject();

        // Assert
        self::assertInstanceOf(PdfString::class, $obj);
        self::assertSame('Hello', $obj->getValue());
    }

    #[Test]
    public function parsesName(): void
    {
        // Arrange / Act
        $obj = $this->parserFor('/Type')->parseObject();

        // Assert
        self::assertInstanceOf(PdfName::class, $obj);
        self::assertSame('Type', $obj->getValue());
    }

    #[Test]
    public function parsesTrue(): void
    {
        // Arrange / Act
        $obj = $this->parserFor('true')->parseObject();

        // Assert
        self::assertInstanceOf(PdfBoolean::class, $obj);
        self::assertTrue($obj->getValue());
    }

    #[Test]
    public function parsesFalse(): void
    {
        // Arrange / Act
        $obj = $this->parserFor('false')->parseObject();

        // Assert
        self::assertInstanceOf(PdfBoolean::class, $obj);
        self::assertFalse($obj->getValue());
    }

    #[Test]
    public function parsesNull(): void
    {
        // Arrange / Act
        $obj = $this->parserFor('null')->parseObject();

        // Assert
        self::assertInstanceOf(PdfNull::class, $obj);
    }

    #[Test]
    public function parsesArray(): void
    {
        // Arrange / Act
        $obj = $this->parserFor('[1 2 3]')->parseObject();

        // Assert
        self::assertInstanceOf(PdfArray::class, $obj);
        self::assertCount(3, $obj->getItems());
    }

    #[Test]
    public function parsesEmptyArray(): void
    {
        // Arrange / Act
        $obj = $this->parserFor('[]')->parseObject();

        // Assert
        self::assertInstanceOf(PdfArray::class, $obj);
        self::assertCount(0, $obj->getItems());
    }

    #[Test]
    public function parsesDictionary(): void
    {
        // Arrange / Act
        $obj = $this->parserFor('<< /Type /Catalog >>')->parseObject();

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
        $type = $obj->get('Type');
        self::assertInstanceOf(PdfName::class, $type);
        self::assertSame('Catalog', $type->getValue());
    }

    #[Test]
    public function parsesIndirectReference(): void
    {
        // Arrange / Act
        $obj = $this->parserFor('5 0 R')->parseObject();

        // Assert
        self::assertInstanceOf(PdfIndirectReference::class, $obj);
        self::assertSame(5, $obj->getObjectNumber());
        self::assertSame(0, $obj->getGenerationNumber());
    }

    #[Test]
    public function parsesStream(): void
    {
        // Arrange
        $content = "<< /Length 5 >>\nstream\nhello\nendstream";

        // Act
        $obj = $this->parserFor($content)->parseObject();

        // Assert
        self::assertInstanceOf(PdfStream::class, $obj);
    }

    #[Test]
    public function parsesStreamWithFlateDecode(): void
    {
        // Arrange — produce a tiny compressed payload
        $raw = 'test data';
        $compressed = gzcompress($raw) ?: '';
        $content = "<< /Length " . strlen($compressed) . " /Filter /FlateDecode >>\nstream\n"
                    . $compressed . "\nendstream";

        // Act
        $obj = $this->parserFor($content)->parseObject();

        // Assert
        self::assertInstanceOf(PdfStream::class, $obj);
    }

    #[Test]
    public function parsesStreamWithAsciiHexDecode(): void
    {
        // Arrange — hex-encoded "AB" plus EOD marker
        $hexData = "4142>";
        $content = "<< /Length " . strlen($hexData) . " /Filter /ASCIIHexDecode >>\nstream\n"
                  . $hexData . "\nendstream";

        // Act
        $obj = $this->parserFor($content)->parseObject();

        // Assert
        self::assertInstanceOf(PdfStream::class, $obj);
    }

    #[Test]
    public function throwsForUnexpectedToken(): void
    {
        // Arrange — 'endobj' is not a valid object keyword
        $parser = $this->parserFor('endobj');

        // Act / Assert
        $this->expectException(PdfReadException::class);
        $parser->parseObject();
    }

    #[Test]
    public function throwsForUnterminatedArray(): void
    {
        // Arrange
        $parser = $this->parserFor('[1 2 3');

        // Act / Assert
        $this->expectException(PdfReadException::class);
        $parser->parseObject();
    }

    private function parserFor(string $content): PdfObjectParser
    {
        return new PdfObjectParser(PdfLexer::fromString($content));
    }
}
