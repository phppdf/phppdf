<?php

declare(strict_types=1);

namespace PhpPdf\Reader;

final class PdfToken
{
    public function __construct(public readonly PdfTokenType $type, public readonly string $value,)
    {
    }
}
