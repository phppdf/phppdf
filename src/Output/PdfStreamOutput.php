<?php

declare(strict_types=1);

namespace PhpPdf\Output;

final class PdfStreamOutput implements PdfOutput
{
    /** @var resource */
    private $stream;

    /** @param resource $stream */
    public function __construct($stream)
    {
        $this->stream = $stream;
    }

    public function write(string $data): void
    {
        fwrite($this->stream, $data);
    }

    public function position(): int
    {
        $pos = ftell($this->stream);

        return $pos === false
            ? 0
            : $pos;
    }
}
