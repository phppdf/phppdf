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
#[CoversMethod(PdfLexer::class, 'readToken')]
#[UsesClass(PdfToken::class)]
final class StringEscapesTest extends TestCase
{
    #[Test]
    public function escapeRProducesCarriageReturn(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('(\\r)');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame("\r", $token->value);
    }

    #[Test]
    public function escapeTProducesTab(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('(\\t)');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame("\t", $token->value);
    }

    #[Test]
    public function escapeBProducesBackspace(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('(\\b)');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame("\x08", $token->value);
    }

    #[Test]
    public function escapeFProducesFormFeed(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('(\\f)');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame("\x0C", $token->value);
    }

    #[Test]
    public function escapeBackslashProducesBackslash(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('(\\\\)');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame('\\', $token->value);
    }

    #[Test]
    public function escapeLineContinuationLf(): void
    {
        // Arrange — backslash-newline is line continuation (no character emitted)
        $lexer = PdfLexer::fromString("(ab\\\ncd)");

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame('abcd', $token->value);
    }

    #[Test]
    public function escapeLineContinuationCrLf(): void
    {
        // Arrange — backslash-CRLF is line continuation
        $lexer = PdfLexer::fromString("(ab\\\r\ncd)");

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame('abcd', $token->value);
    }

    #[Test]
    public function unknownEscapePassesThroughCharacter(): void
    {
        // Arrange — \x is not a recognised escape, so 'x' is emitted
        $lexer = PdfLexer::fromString('(\\x)');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame('x', $token->value);
    }

    #[Test]
    public function escapeAtEofTerminatesStringEarly(): void
    {
        // Arrange — backslash at end of input, no following character
        $lexer = PdfLexer::fromString('(\\');

        // Act
        $token = $lexer->readToken();

        // Assert — string closes with empty value when EOF follows backslash
        self::assertSame('', $token->value);
    }

    #[Test]
    public function escapeOpenParenProducesOpenParen(): void
    {
        // Arrange — \( is an escaped open parenthesis
        $lexer = PdfLexer::fromString('(\\()');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame('(', $token->value);
    }

    #[Test]
    public function escapeCloseParenProducesCloseParen(): void
    {
        // Arrange — \) is an escaped close parenthesis
        $lexer = PdfLexer::fromString('(\\))');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(')', $token->value);
    }

    #[Test]
    public function escapeCrNotFollowedByLfIsLineContinuation(): void
    {
        // Arrange — backslash-CR where CR is not followed by LF; the CR is a line
        // continuation so it is consumed, then x is read normally
        $lexer = PdfLexer::fromString("(\\\rx)");

        // Act
        $token = $lexer->readToken();

        // Assert — line continuation consumed, x preserved
        self::assertSame('x', $token->value);
    }
}
