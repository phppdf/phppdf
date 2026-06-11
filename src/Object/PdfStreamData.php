<?php

declare(strict_types=1);

namespace PhpPdf\Object;

use PhpPdf\Serialization\PdfStreamSerializer;

/**
 * Strategy interface for the body content of a PDF stream object.
 *
 * Different implementations supply different kinds of stream bytes: raw
 * pre-encoded bytes, content stream operations, or other encoded data.
 * Decouples PdfStream from the source and format of its body.
 */
interface PdfStreamData
{
    /**
     * Produces the stream body bytes using the given serializer.
     *
     * Returns the raw bytes to be written between 'stream\n' and '\nendstream'.
     */
    public function serialize(PdfStreamSerializer $serializer): string;
}
