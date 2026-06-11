<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * An XMP metadata stream embedding document metadata as XML.
 *
 * XMP (Extensible Metadata Platform) is an ISO standard for embedding
 * structured metadata in files. The stream body is raw UTF-8 XML conforming
 * to the XMP specification. Reference this from the document catalog's
 * Metadata entry to provide machine-readable metadata alongside the Info
 * dictionary. Required for PDF/A conformance. Requires PDF 1.4+.
 */
final class PdfXmpMetadataStream extends PdfStream
{
    public function __construct(string $xml)
    {
        parent::__construct(
            new PdfDictionary([
                'Subtype' => new PdfName('XML'),
                'Type' => new PdfName('Metadata'),
            ]),
            new PdfRawStreamData($xml),
        );
    }
}
