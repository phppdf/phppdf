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
#[UsesClass(PdfToken::class)]
final class NumberTokenizationTest extends TestCase
{
    #[Test]
    public function plusAloneIsKeyword(): void
    {
        // Arrange — '+' with no digits following is a keyword, not a number
        $lexer = PdfLexer::fromString('+ ');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::Keyword, $token->type);
        self::assertSame('+', $token->value);
    }

    #[Test]
    public function minusAloneIsKeyword(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('- ');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::Keyword, $token->type);
        self::assertSame('-', $token->value);
    }

    #[Test]
    public function tokenizesScientificNotation(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('1.5E2');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::Real, $token->type);
        self::assertSame('1.5E2', $token->value);
    }

    #[Test]
    public function tokenizesScientificNotationWithSign(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('1e+3');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::Real, $token->type);
        self::assertStringContainsString('1e+3', $token->value);
    }

    #[Test]
    public function tokenizesDotLeadingReal(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('.5');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::Real, $token->type);
    }

    #[Test]
    public function tokenizesPositiveInteger(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('+42');

        // Act
        $token = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::Integer, $token->type);
        self::assertSame('+42', $token->value);
    }
}
