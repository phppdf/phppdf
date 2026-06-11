<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfLexer;

use PhpPdf\Reader\PdfLexer;
use PhpPdf\Reader\PdfToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfLexer::class)]
#[CoversMethod(PdfLexer::class, 'readRawBytes')]
#[UsesClass(PdfToken::class)]
final class ReadRawBytesTest extends TestCase
{
    #[Test]
    public function readsRequestedNumberOfBytes(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('ABCDEF');

        // Act
        $result = $lexer->readRawBytes(3);

        // Assert
        self::assertSame('ABC', $result);
    }

    #[Test]
    public function returnsEmptyStringForZeroLength(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('ABCDEF');

        // Act
        $result = $lexer->readRawBytes(0);

        // Assert
        self::assertSame('', $result);
    }

    #[Test]
    public function returnsEmptyStringForNegativeLength(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('ABCDEF');

        // Act
        $result = $lexer->readRawBytes(-1);

        // Assert
        self::assertSame('', $result);
    }

    #[Test]
    public function clearsTokenBuffer(): void
    {
        // Arrange — peek so '42' is in the buffer (file position is at byte 2)
        $lexer = PdfLexer::fromString('42 hello');
        $lexer->peekToken(1);

        // Act — raw read clears the buffer and consumes 2 bytes from position 2 (' h')
        $lexer->readRawBytes(2);
        // File is now at position 4; next token reads 'ello' (no delimiter left before EOF)
        $next = $lexer->readToken();

        // Assert — next token comes from position 4, not from the start
        self::assertNotSame('42', $next->value);
    }
}
