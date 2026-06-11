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
use PhpPdf\Object\PdfReal;
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
#[CoversMethod(PdfObjectParser::class, 'parseIndirectObject')]
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
#[UsesClass(PdfToken::class)]
final class ParseIndirectObjectTest extends TestCase
{
    #[Test]
    public function parsesIndirectObject(): void
    {
        // Arrange
        $content = "1 0 obj\n42\nendobj\n";
        $parser = new PdfObjectParser(PdfLexer::fromString($content));

        // Act
        $result = $parser->parseIndirectObject();

        // Assert
        self::assertNotNull($result);
        [$objNum, $gen, $obj] = $result;
        self::assertSame(1, $objNum);
        self::assertSame(0, $gen);
        self::assertInstanceOf(PdfInteger::class, $obj);
        self::assertSame(42, $obj->getValue());
    }

    #[Test]
    public function returnsNullAtEof(): void
    {
        // Arrange
        $parser = new PdfObjectParser(PdfLexer::fromString(''));

        // Act
        $result = $parser->parseIndirectObject();

        // Assert
        self::assertNull($result);
    }

    #[Test]
    public function throwsWhenObjectNumberMissing(): void
    {
        // Arrange — starts with a name, not an integer
        $parser = new PdfObjectParser(PdfLexer::fromString('/Type obj 42 endobj'));

        // Act / Assert
        $this->expectException(PdfReadException::class);
        $parser->parseIndirectObject();
    }

    #[Test]
    public function throwsWhenGenerationNumberMissing(): void
    {
        // Arrange — two integers but third token is not 'obj'
        $parser = new PdfObjectParser(PdfLexer::fromString('1 obj 42 endobj'));

        // Act / Assert
        $this->expectException(PdfReadException::class);
        $parser->parseIndirectObject();
    }

    #[Test]
    public function parsesWithDictBody(): void
    {
        // Arrange
        $content = "3 0 obj\n<< /Type /Page >>\nendobj\n";
        $parser = new PdfObjectParser(PdfLexer::fromString($content));

        // Act
        $result = $parser->parseIndirectObject();

        // Assert
        self::assertNotNull($result);
        [, , $obj] = $result;
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }

    #[Test]
    public function throwsWhenObjKeywordMissing(): void
    {
        // Arrange — valid object-number and generation but third token is not 'obj'
        $parser = new PdfObjectParser(PdfLexer::fromString('1 0 endobj'));

        // Act / Assert
        $this->expectException(PdfReadException::class);
        $parser->parseIndirectObject();
    }
}
