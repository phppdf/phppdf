<?php

declare(strict_types=1);

namespace PhpPdf\Object;

use PhpPdf\Serialization\PdfDocumentSerializer;

/**
 * A PDF stream object: a dictionary followed by a delimited sequence of bytes.
 *
 * Streams carry data that is too large to inline or benefits from compression:
 * page content, embedded images, font programs, XMP metadata, and color
 * profiles. The dictionary must include a Length entry (added by the
 * serializer) and may include a Filter entry describing the body encoding.
 */
class PdfStream implements PdfObject
{
    public function __construct(private readonly PdfDictionary $dictionary, private readonly PdfStreamData $data)
    {
    }

    public function getDictionary(): PdfDictionary
    {
        return $this->dictionary;
    }

    public function getData(): PdfStreamData
    {
        return $this->data;
    }

    public function serialize(PdfDocumentSerializer $serializer): void
    {
        $serializer->writeStreamObject($this);
    }
}
