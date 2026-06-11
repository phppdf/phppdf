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
#[CoversMethod(PdfLexer::class, 'pushToken')]
#[UsesClass(PdfToken::class)]
final class PushTokenTest extends TestCase
{
    #[Test]
    public function pushedTokenIsReadFirst(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('99');
        $pushed = new PdfToken(PdfTokenType::Keyword, 'endstream');

        // Act
        $lexer->pushToken($pushed);
        $read = $lexer->readToken();

        // Assert
        self::assertSame('endstream', $read->value);
    }

    #[Test]
    public function afterPushedTokenOriginalStreamContinues(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('99');
        $pushed = new PdfToken(PdfTokenType::Keyword, 'endstream');

        // Act
        $lexer->pushToken($pushed);
        $lexer->readToken(); // consume pushed
        $next = $lexer->readToken();

        // Assert
        self::assertSame(PdfTokenType::Integer, $next->type);
        self::assertSame('99', $next->value);
    }
}
