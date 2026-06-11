<?php

declare(strict_types=1);

namespace PhpPdf\Object;

use PhpPdf\Serialization\PdfStreamSerializer;

/**
 * A PdfStreamData implementation that serializes a PdfContentStream.
 *
 * Bridges the content stream operation model to the byte body expected by
 * PdfStream. The PdfContentStreamSerializer renders each PdfContentOperation
 * to its PDF operator syntax.
 */
final class PdfContentStreamData implements PdfStreamData
{
    public function __construct(private readonly PdfContentStream $content)
    {
    }

    public function getContent(): PdfContentStream
    {
        return $this->content;
    }

    public function serialize(PdfStreamSerializer $serializer): string
    {
        return $serializer->serializeContentStream($this);
    }
}
