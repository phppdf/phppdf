<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfLexer;

use PhpPdf\Reader\Exception\PdfReadException;
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
#[UsesClass(PdfReadException::class)]
#[UsesClass(PdfToken::class)]
final class ReadTokenTest extends TestCase
{
    #[Test]
    public function returnsEofOnEmptyInput(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::Eof, $token->type);
    }

    #[Test]
    public function tokenizesInteger(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('42');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::Integer, $token->type);
        self::assertSame('42', $token->value);
    }

    #[Test]
    public function tokenizesNegativeInteger(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('-10');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::Integer, $token->type);
        self::assertSame('-10', $token->value);
    }

    #[Test]
    public function tokenizesReal(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('3.14');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::Real, $token->type);
        self::assertSame('3.14', $token->value);
    }

    #[Test]
    public function tokenizesLiteralString(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('(hello world)');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::String, $token->type);
        self::assertSame('hello world', $token->value);
    }

    #[Test]
    public function tokenizesNestedLiteralString(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('(a (b) c)');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::String, $token->type);
        self::assertSame('a (b) c', $token->value);
    }

    #[Test]
    public function tokenizesLiteralStringWithEscapes(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('(line1\\nline2)');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::String, $token->type);
        self::assertSame("line1\nline2", $token->value);
    }

    #[Test]
    public function tokenizesOctalEscapeInLiteralString(): void
    {
        // Arrange — \101 is octal for 'A'
        $lexer = PdfLexer::fromString('(\\101)');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame('A', $token->value);
    }

    #[Test]
    public function tokenizesHexString(): void
    {
        // Arrange — <48656C6C6F> = "Hello"
        $lexer = PdfLexer::fromString('<48656C6C6F>');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::String, $token->type);
        self::assertSame('Hello', $token->value);
    }

    #[Test]
    public function tokenizesHexStringWithOddLength(): void
    {
        // Arrange — <48F> is odd → pad to <48F0>
        $lexer = PdfLexer::fromString('<48F>');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::String, $token->type);
        self::assertSame(hex2bin('48F0'), $token->value);
    }

    #[Test]
    public function tokenizesDictStart(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('<<');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::DictStart, $token->type);
    }

    #[Test]
    public function tokenizesDictEnd(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('>>');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::DictEnd, $token->type);
    }

    #[Test]
    public function tokenizesArrayStart(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('[');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::ArrayStart, $token->type);
    }

    #[Test]
    public function tokenizesArrayEnd(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString(']');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::ArrayEnd, $token->type);
    }

    #[Test]
    public function tokenizesName(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('/Type');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::Name, $token->type);
        self::assertSame('Type', $token->value);
    }

    #[Test]
    public function tokenizesNameWithHexEscape(): void
    {
        // Arrange — /Hel#6Co → 'Hello'
        $lexer = PdfLexer::fromString('/Hel#6Co');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::Name, $token->type);
        self::assertSame('Hello', $token->value);
    }

    #[Test]
    public function tokenizesKeyword(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('obj');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::Keyword, $token->type);
        self::assertSame('obj', $token->value);
    }

    #[Test]
    public function skipsWhitespaceBeforeToken(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString("  \t\n\r 42");

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::Integer, $token->type);
        self::assertSame('42', $token->value);
    }

    #[Test]
    public function skipsComments(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString("% this is a comment\n42");

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::Integer, $token->type);
        self::assertSame('42', $token->value);
    }

    #[Test]
    public function tokenizesSingleQuoteOperator(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString("'");

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::Keyword, $token->type);
        self::assertSame("'", $token->value);
    }

    #[Test]
    public function tokenizesDoubleQuoteOperator(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('"');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::Keyword, $token->type);
        self::assertSame('"', $token->value);
    }

    #[Test]
    public function throwsOnUnterminatedLiteralString(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('(unterminated');

        // Act / Assert
        $this->expectException(PdfReadException::class);
        $lexer->readToken();
    }

    #[Test]
    public function throwsOnUnterminatedHexString(): void
    {
        // Arrange — hex string without closing >
        $lexer = PdfLexer::fromString('<4142');

        // Act / Assert
        $this->expectException(PdfReadException::class);
        $lexer->readToken();
    }

    #[Test]
    public function nameTerminatedByWhitespace(): void
    {
        // Arrange — name followed by a space; whitespace must end the name
        $lexer = PdfLexer::fromString('/Type ');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::Name, $token->type);
        self::assertSame('Type', $token->value);
    }

    #[Test]
    public function keywordTerminatedByNonAlphanumericCharacter(): void
    {
        // Arrange — keyword followed by '(' which is not alphanumeric, '*', or '_'
        $lexer = PdfLexer::fromString('true(');

        // Act
        $token = $lexer->readToken();

        // Assert — keyword stops before '('
        self::assertSame(PdfTokenType::Keyword, $token->type);
        self::assertSame('true', $token->value);
    }
}
