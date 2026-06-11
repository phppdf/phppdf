<?php

declare(strict_types=1);

namespace PhpPdf\Serialization;

use PhpPdf\Object\PdfContentStream;
use PhpPdf\Output\PdfOutput;

final class PdfContentStreamSerializer
{
    public function __construct(private readonly PdfOutput $output,)
    {
    }

    public function writeStream(PdfContentStream $stream): void
    {
        foreach ($stream->getOperations() as $operation) {
            $operation->serialize($this);
        }
    }

    public function writeLine(string $line): void
    {
        $this->output->write($line . "\n");
    }

    /**
     * Escapes PDF literal-string special characters (backslash and parentheses).
     *
     * Encoding (UTF-8 → Windows-1252 for Type1, glyph IDs for embedded fonts)
     * is handled by PdfContentStreamBuilder before the operation is created,
     * so this method only needs to deal with PDF syntax.
     */
    public function escape(string $text): string
    {
        return str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            $text,
        );
    }
}
