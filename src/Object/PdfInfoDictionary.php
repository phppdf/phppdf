<?php

declare(strict_types=1);

namespace PhpPdf\Object;

use DateTimeImmutable;

/**
 * The PDF document information dictionary, carried in the trailer.
 *
 * Stores standard document metadata: Title, Author, Producer, and
 * CreationDate. The CreationDate is set to the current instant at
 * construction time. This dictionary is separate from the XMP metadata
 * stream and is referenced by the trailer's Info entry.
 */
final class PdfInfoDictionary extends PdfDictionary
{
    public function __construct(string $title, string $author, string $producer = 'phppdf/phppdf',)
    {
        parent::__construct([
            'Author' => new PdfString($author),
            'CreationDate' => new PdfDate(new DateTimeImmutable()),
            'Producer' => new PdfString($producer),
            'Title' => new PdfString($title),
        ]);
    }
}
