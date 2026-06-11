<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * A PDF file specification dictionary describing an embedded file attachment.
 *
 * Associates a display filename with an embedded file stream. Add this to the
 * document's EmbeddedFiles name tree (via the catalog's Names entry) to make
 * the attachment accessible in the PDF viewer's attachments panel.
 */
final class PdfFileSpecification extends PdfDictionary
{
    public function __construct(string $filename, PdfIndirectReference $embeddedFile)
    {
        parent::__construct([
            'EF' => new PdfDictionary([
                'F' => $embeddedFile,
            ]),
            'F' => new PdfString($filename),
            'Type' => new PdfName('Filespec'),
        ]);
    }
}
