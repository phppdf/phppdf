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
#[CoversMethod(PdfLexer::class, 'seekTo')]
#[UsesClass(PdfToken::class)]
final class SeekToTest extends TestCase
{
    #[Test]
    public function movesPositionToOffset(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('ABCDEF');

        // Act
        $lexer->seekTo(3);

        // Assert
        self::assertSame(3, $lexer->position());
    }

    #[Test]
    public function clearsPeekedTokenBuffer(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('42 hello');
        $lexer->peekToken(1); // loads '42' into buffer

        // Act — seekTo clears the buffer; token now comes from position 3
        $lexer->seekTo(3);
        $token = $lexer->readToken();

        // Assert
        self::assertSame('hello', $token->value);
    }
}
