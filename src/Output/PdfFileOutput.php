<?php

declare(strict_types=1);

namespace PhpPdf\Output;

use RuntimeException;

final class PdfFileOutput implements PdfOutput
{
    /** @var resource */
    private $handle;

    public function __construct(string $path)
    {
        $handle = fopen($path, 'wb');

        if ($handle === false) {
            throw new RuntimeException("Unable to open file: {$path}");
        }

        $this->handle = $handle;
    }

    public function __destruct()
    {
        fclose($this->handle);
    }

    public function write(string $data): void
    {
        fwrite($this->handle, $data);
    }

    public function position(): int
    {
        $pos = ftell($this->handle);

        return $pos === false
            ? 0
            : $pos;
    }
}
