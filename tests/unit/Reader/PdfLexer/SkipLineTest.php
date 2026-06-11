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
#[CoversMethod(PdfLexer::class, 'skipLine')]
#[UsesClass(PdfToken::class)]
final class SkipLineTest extends TestCase
{
    #[Test]
    public function skipsToNextLineOnLf(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString("first\nsecond");

        // Act
        $lexer->skipLine();
        $next = $lexer->readRawBytes(6);

        // Assert
        self::assertSame('second', $next);
    }

    #[Test]
    public function skipsToNextLineOnCrLf(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString("first\r\nsecond");

        // Act
        $lexer->skipLine();
        $next = $lexer->readRawBytes(6);

        // Assert
        self::assertSame('second', $next);
    }

    #[Test]
    public function skipsToNextLineOnCrAlone(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString("first\rsecond");

        // Act
        $lexer->skipLine();
        $next = $lexer->readRawBytes(6);

        // Assert
        self::assertSame('second', $next);
    }
}
