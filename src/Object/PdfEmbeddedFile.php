<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * A PDF embedded file stream holding the binary content of an attached file.
 *
 * The stream body contains the raw file bytes. Referenced from a
 * PdfFileSpecification via its EF dictionary. Register this as an indirect
 * object and pair it with a PdfFileSpecification to create a complete
 * file attachment.
 */
final class PdfEmbeddedFile extends PdfStream
{
    public function __construct(PdfStreamData $binary)
    {
        parent::__construct(
            new PdfDictionary([
                'Type' => new PdfName('EmbeddedFile'),
            ]),
            $binary,
        );
    }
}
