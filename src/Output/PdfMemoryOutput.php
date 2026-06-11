<?php

declare(strict_types=1);

namespace PhpPdf\Output;

final class PdfMemoryOutput implements PdfOutput
{
    private string $buffer = '';

    public function write(string $data): void
    {
        $this->buffer .= $data;
    }

    public function position(): int
    {
        return strlen($this->buffer);
    }

    public function getContent(): string
    {
        return $this->buffer;
    }
}
