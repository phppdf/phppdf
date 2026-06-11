<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * A PDF name tree node that maps string keys to PDF objects.
 *
 * Name trees associate arbitrary string names with objects and are used for
 * the EmbeddedFiles, Dests, JavaScript, and other named resource collections.
 * This implementation creates a single leaf node; a multi-level hierarchy
 * is needed for large trees but is not yet supported.
 */
final class PdfNameTree extends PdfDictionary
{
    /** @param array<string, \PhpPdf\Object\PdfObject> $names */
    public function __construct(array $names)
    {
        $entries = [];

        foreach ($names as $name => $object) {
            $entries[] = new PdfString($name);
            $entries[] = $object;
        }

        parent::__construct([
            'Names' => new PdfArray($entries),
        ]);
    }
}
