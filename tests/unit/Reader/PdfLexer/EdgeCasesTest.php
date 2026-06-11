<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfLexer;

use PhpPdf\Reader\PdfLexer;
use PhpPdf\Reader\PdfToken;
use PhpPdf\Reader\PdfTokenType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfLexer::class)]
#[CoversMethod(PdfLexer::class, 'readToken')]
#[CoversMethod(PdfLexer::class, 'consumeStreamNewline')]
#[UsesClass(PdfToken::class)]
final class EdgeCasesTest extends TestCase
{
    #[Test]
    public function singleGreaterThanIsKeyword(): void
    {
        // Arrange — single '>' (not '>>')
        $lexer = PdfLexer::fromString('> ');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::Keyword, $token->type);
        self::assertSame('>', $token->value);
    }

    #[Test]
    public function readOctalEscapeWithPartialDigits(): void
    {
        // Arrange — \17 is 2 octal digits followed by non-octal char 'z'
        $lexer = PdfLexer::fromString('(\\17z)');

        // Act
        $token = $lexer->readToken();

        // Assert — \17 = chr(15), then 'z'
        self::assertSame(chr(15) . 'z', $token->value);
    }

    #[Test]
    public function nameStopsAtDelimiterCharacters(): void
    {
        // Arrange — name followed by '(' delimiter
        $lexer = PdfLexer::fromString('/Name(rest)');

        // Act
        $token = $lexer->readToken();

        // Assert — name stops before '('
        self::assertSame(PdfTokenType::Name, $token->type);
        self::assertSame('Name', $token->value);
    }

    #[Test]
    public function consumeStreamNewlineWithNonNewlineByte(): void
    {
        // Arrange — non-newline, non-\r byte: should seek back
        $lexer = PdfLexer::fromString('Xdata');

        // Act
        $lexer->consumeStreamNewline();

        // Assert — position is still at 0 (non-newline was put back)
        self::assertSame(0, $lexer->position());
    }

    #[Test]
    public function keywordWithAsterisk(): void
    {
        // Arrange — PDF operator with trailing '*' (e.g. 'CS*')
        $lexer = PdfLexer::fromString('CS*');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::Keyword, $token->type);
        self::assertSame('CS*', $token->value);
    }

    #[Test]
    public function hexStringWithWhitespace(): void
    {
        // Arrange — whitespace inside a hex string is ignored per spec
        $lexer = PdfLexer::fromString('<48 65 6C 6C 6F>');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::String, $token->type);
        self::assertSame('Hello', $token->value);
    }

    #[Test]
    public function unrecognizedCharacterBecomesKeywordToken(): void
    {
        // Arrange — '{' is not matched by any specific branch in readNextToken
        $lexer = PdfLexer::fromString('{');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::Keyword, $token->type);
        self::assertSame('{', $token->value);
    }
}
