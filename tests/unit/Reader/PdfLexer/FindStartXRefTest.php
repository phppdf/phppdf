<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfLexer;

use PhpPdf\Reader\Exception\PdfReadException;
use PhpPdf\Reader\PdfLexer;
use PhpPdf\Reader\PdfToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfLexer::class)]
#[CoversMethod(PdfLexer::class, 'findStartXRef')]
#[UsesClass(PdfReadException::class)]
#[UsesClass(PdfToken::class)]
final class FindStartXRefTest extends TestCase
{
    #[Test]
    public function findsStartXRefOffset(): void
    {
        // Arrange — minimal tail with startxref marker
        $content = "%PDF-1.4\nstartxref\n999\n%%EOF\n";
        $lexer = PdfLexer::fromString($content);

        // Act
        $offset = $lexer->findStartXRef();

        // Assert
        self::assertSame(999, $offset);
    }

    #[Test]
    public function throwsWhenStartXRefMissing(): void
    {
        // Arrange
        $lexer = PdfLexer::fromString('%PDF-1.4 no xref here at all');

        // Act / Assert
        $this->expectException(PdfReadException::class);
        $lexer->findStartXRef();
    }

    #[Test]
    public function throwsWhenStartXRefHasNoOffset(): void
    {
        // Arrange — marker present but no integer follows
        $lexer = PdfLexer::fromString("startxref\n%%EOF\n");

        // Act / Assert
        $this->expectException(PdfReadException::class);
        $lexer->findStartXRef();
    }
}
