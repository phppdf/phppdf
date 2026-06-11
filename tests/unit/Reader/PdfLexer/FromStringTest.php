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
#[CoversMethod(PdfLexer::class, 'fromString')]
#[UsesClass(PdfToken::class)]
final class FromStringTest extends TestCase
{
    #[Test]
    public function createsLexerWithCorrectSize(): void
    {
        // Arrange / Act
        $lexer = PdfLexer::fromString('hello');

        // Assert
        self::assertSame(5, $lexer->size());
    }

    #[Test]
    public function positionStartsAtZero(): void
    {
        // Arrange / Act
        $lexer = PdfLexer::fromString('hello');

        // Assert
        self::assertSame(0, $lexer->position());
    }
}
