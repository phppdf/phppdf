<?php

declare(strict_types=1);

namespace PhpPdf\Object;

use PhpPdf\Serialization\PdfStreamSerializer;

/**
 * A PdfStreamData implementation backed by raw, pre-encoded bytes.
 *
 * Use when the stream content has already been encoded (e.g. compressed or
 * encrypted externally) and must be written as-is. The caller is responsible
 * for setting the appropriate Filter entry in the stream dictionary.
 */
final class PdfRawStreamData implements PdfStreamData
{
    public function __construct(private readonly string $data)
    {
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function serialize(PdfStreamSerializer $serializer): string
    {
        return $serializer->serializeRawStream($this);
    }
}
