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
#[CoversMethod(PdfLexer::class, 'peekToken')]
#[UsesClass(PdfToken::class)]
final class PeekTokenTest extends TestCase
{
    #[Test]
    public function doesNotConsumeToken(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('42 R');

        // Act — peek twice, then read
        $peeked1 = $lexer->peekToken(1);
        $peeked2 = $lexer->peekToken(1);
        $read = $lexer->readToken();

        // Assert — all three return the same first token
        self::assertSame(PdfTokenType::Integer, $peeked1->type);
        self::assertSame($peeked1->value, $peeked2->value);
        self::assertSame($peeked1->value, $read->value);
    }

    #[Test]
    public function peeksAhead(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('1 2 R');

        // Act
        $first = $lexer->peekToken(1);
        $second = $lexer->peekToken(2);
        $third = $lexer->peekToken(3);

        // Assert
        self::assertSame('1', $first->value);
        self::assertSame('2', $second->value);
        self::assertSame('R', $third->value);
    }
}
