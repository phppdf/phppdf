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
#[CoversMethod(PdfLexer::class, 'readBytesAt')]
#[UsesClass(PdfToken::class)]
final class ReadBytesAtTest extends TestCase
{
    #[Test]
    public function readsBytesAtOffset(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('ABCDEF');

        // Act
        $result = $lexer->readBytesAt(2, 3);

        // Assert
        self::assertSame('CDE', $result);
    }

    #[Test]
    public function doesNotChangeCurrentPosition(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('ABCDEF');
        $lexer->readRawBytes(2); // advance to position 2

        // Act
        $lexer->readBytesAt(0, 3);

        // Assert — position is still 2
        self::assertSame(2, $lexer->position());
    }
}
