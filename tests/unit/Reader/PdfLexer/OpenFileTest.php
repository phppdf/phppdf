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
#[CoversMethod(PdfLexer::class, 'openFile')]
#[UsesClass(PdfReadException::class)]
#[UsesClass(PdfToken::class)]
final class OpenFileTest extends TestCase
{
    #[Test]
    public function throwsForMissingFile(): void
    {
        // Arrange / Act / Assert
        $this->expectException(PdfReadException::class);
        PdfLexer::openFile('/nonexistent/path/to/file.pdf');
    }

    #[Test]
    public function opensExistingFile(): void
    {
        // Arrange
        $tmp = tempnam(sys_get_temp_dir(), 'phppdf_');
        file_put_contents($tmp, '%PDF-1.4');

        // Act
        $lexer = PdfLexer::openFile($tmp);

        // Assert
        self::assertSame(8, $lexer->size());

        unlink($tmp);
    }

    #[Test]
    public function throwsWhenFileCannotBeOpened(): void
    {
        // Arrange — file exists but has no read permission
        $tmp = tempnam(sys_get_temp_dir(), 'phppdf_');
        file_put_contents($tmp, '%PDF-1.4');
        chmod($tmp, 0000);

        // Act / Assert — @ suppresses the fopen PHP warning so failOnWarning stays clean
        try {
            $this->expectException(PdfReadException::class);
            @PdfLexer::openFile($tmp);
        } finally {
            chmod($tmp, 0644);
            unlink($tmp);
        }
    }
}
