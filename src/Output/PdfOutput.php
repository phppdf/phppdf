<?php

declare(strict_types=1);

namespace PhpPdf\Output;

interface PdfOutput
{
    public function write(string $data): void;

    public function position(): int;
}
