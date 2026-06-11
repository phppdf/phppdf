<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * A CIDSystemInfo dictionary identifying the CID character collection.
 *
 * Specifies the Registry (e.g. 'Adobe'), Ordering (the collection name,
 * e.g. 'Identity' for arbitrary Unicode), and Supplement (revision number).
 * For arbitrary Unicode fonts the default values Adobe/Identity/0 are correct.
 * Embedded inline in the CIDFont dictionary rather than as an indirect object.
 */
final class PdfCidSystemInfo extends PdfDictionary
{
    public function __construct(string $registry = 'Adobe', string $ordering = 'Identity', int $supplement = 0)
    {
        parent::__construct([
            'Ordering' => new PdfString($ordering),
            'Registry' => new PdfString($registry),
            'Supplement' => new PdfInteger($supplement),
        ]);
    }
}
