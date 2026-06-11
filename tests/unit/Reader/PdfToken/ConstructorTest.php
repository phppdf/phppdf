<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfToken;

use PhpPdf\Reader\PdfToken;
use PhpPdf\Reader\PdfTokenType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfToken::class)]
#[CoversMethod(PdfToken::class, '__construct')]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function storesTypeAndValue(): void
    {
        // Arrange / Act
        $token = new PdfToken(PdfTokenType::Integer, '42');

        // Assert
        self::assertSame(PdfTokenType::Integer, $token->type);
        self::assertSame('42', $token->value);
    }

    #[Test]
    public function storesEofTokenWithEmptyValue(): void
    {
        // Arrange / Act
        $token = new PdfToken(PdfTokenType::Eof, '');

        // Assert
        self::assertSame(PdfTokenType::Eof, $token->type);
        self::assertSame('', $token->value);
    }
}
