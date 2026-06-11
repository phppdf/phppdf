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
#[CoversMethod(PdfLexer::class, 'consumeStreamNewline')]
#[UsesClass(PdfToken::class)]
final class ConsumeStreamNewlineTest extends TestCase
{
    #[Test]
    public function consumesLineFeed(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString("\ndata");

        // Act
        $lexer->consumeStreamNewline();

        // Assert — position is after the \n
        self::assertSame(1, $lexer->position());
    }

    #[Test]
    public function consumesCrLf(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString("\r\ndata");

        // Act
        $lexer->consumeStreamNewline();

        // Assert — both \r and \n consumed
        self::assertSame(2, $lexer->position());
    }

    #[Test]
    public function consumesCrWithoutFollowingLf(): void
    {
        // Arrange — \r followed by non-\n
        $lexer = PdfLexer::fromString("\rdata");

        // Act
        $lexer->consumeStreamNewline();

        // Assert — only \r consumed
        self::assertSame(1, $lexer->position());
    }
}
